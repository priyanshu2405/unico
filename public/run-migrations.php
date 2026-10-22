<?php
/**
 * Unicou Deployment Artisan Runner Script
 * 
 * SECURITY WARNING: Please delete this file from your server immediately after use!
 */

define('SECURE_PIN', '832941'); // Secure PIN to access the dashboard
define('LARAVEL_START', microtime(true));

// Autoload & Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// Bootstrap the console kernel
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

session_start();

// Authentication Handling
$authenticated = false;
if (isset($_SESSION['runner_authed']) && $_SESSION['runner_authed'] === true) {
    $authenticated = true;
}

/**
 * Synchronize migrations with existing tables without failing on 1050/1060 errors.
 */
function runSmartSyncAndMigrate(): array {
    $output = [];
    
    // 1. Ensure migrations table exists
    if (!\Illuminate\Support\Facades\Schema::hasTable('migrations')) {
        \Illuminate\Support\Facades\DB::statement("CREATE TABLE IF NOT EXISTS `migrations` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `migration` varchar(255) NOT NULL,
            `batch` int(11) NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        $output[] = "[INIT] Created migrations tracking table.";
    }

    $existing = \Illuminate\Support\Facades\DB::table('migrations')->pluck('migration')->toArray();
    $currentBatch = (int) (\Illuminate\Support\Facades\DB::table('migrations')->max('batch') ?? 0) + 1;

    $files = glob(database_path('migrations/*.php'));
    sort($files);

    $syncedCount = 0;
    $createdCount = 0;
    $skippedCount = 0;

    foreach ($files as $filePath) {
        $migrationName = basename($filePath, '.php');

        // If already registered in migrations table, skip
        if (in_array($migrationName, $existing)) {
            $skippedCount++;
            continue;
        }

        try {
            // Attempt to execute the migration up() method
            $migration = require $filePath;
            if (is_object($migration) && method_exists($migration, 'up')) {
                $migration->up();
            }

            // Successfully executed
            \Illuminate\Support\Facades\DB::table('migrations')->insert([
                'migration' => $migrationName,
                'batch' => $currentBatch
            ]);
            $createdCount++;
            $output[] = "[MIGRATED] {$migrationName}";
        } catch (\Illuminate\Database\QueryException $e) {
            $errorCode = $e->errorInfo[1] ?? 0;
            $errorMsg = $e->getMessage();

            // MySQL 1050: Table already exists
            // MySQL 1060: Duplicate column name
            // MySQL 1061: Duplicate key name
            // MySQL 1054: Unknown column (e.g. from an 'after' clause positioning on an old column)
            // MySQL 1068: Multiple primary key defined
            // MySQL 1091: Can't drop column/key; check that it exists
            $isAlreadyExists = (
                $errorCode == 1050 ||
                $errorCode == 1060 ||
                $errorCode == 1061 ||
                $errorCode == 1068 ||
                $errorCode == 1091 ||
                ($errorCode == 1054 && stripos($errorMsg, 'after') !== false) ||
                stripos($errorMsg, 'already exists') !== false ||
                stripos($errorMsg, 'Duplicate column') !== false ||
                stripos($errorMsg, 'Duplicate key') !== false
            );

            if ($isAlreadyExists) {
                // Pre-existing table/column from prior SQL import
                \Illuminate\Support\Facades\DB::table('migrations')->insert([
                    'migration' => $migrationName,
                    'batch' => $currentBatch
                ]);
                $syncedCount++;
                $output[] = "[SYNCED]   {$migrationName} (schema already present or resolved in DB)";
            } else {
                $output[] = "[ERROR]    {$migrationName}: " . $errorMsg;
                return [
                    'success' => false,
                    'output' => implode("\n", $output)
                ];
            }
        } catch (\Throwable $t) {
            $output[] = "[ERROR]    {$migrationName}: " . $t->getMessage();
            return [
                'success' => false,
                'output' => implode("\n", $output)
            ];
        }
    }

    $output[] = "\n================ SUMMARY ================";
    $output[] = "Total Migration Files : " . count($files);
    $output[] = "Already Tracked       : " . $skippedCount;
    $output[] = "Newly Executed        : " . $createdCount;
    $output[] = "Synced From Database  : " . $syncedCount;
    $output[] = "Status                : SUCCESS! All database tables & migrations are in sync.";

    return [
        'success' => true,
        'output' => implode("\n", $output)
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['pin'])) {
        if ($_POST['pin'] === SECURE_PIN) {
            $_SESSION['runner_authed'] = true;
            $authenticated = true;
        } else {
            $error = "Invalid PIN. Please try again.";
        }
    }

    // Command Execution Handling
    if ($authenticated && isset($_POST['action'])) {
        $action = $_POST['action'];
        $output = '';
        $success = false;

        try {
            $consoleOutput = new \Symfony\Component\Console\Output\BufferedOutput();
            
            switch ($action) {
                case 'migrate':
                case 'smart_sync':
                    $res = runSmartSyncAndMigrate();
                    $output = $res['output'];
                    $success = $res['success'];
                    break;
                case 'force_migrate':
                    $exitCode = $kernel->call('migrate', ['--force' => true], $consoleOutput);
                    $output = $consoleOutput->fetch();
                    $success = ($exitCode === 0);
                    break;
                case 'migrate_status':
                    $exitCode = $kernel->call('migrate:status', [], $consoleOutput);
                    $output = $consoleOutput->fetch();
                    $success = ($exitCode === 0);
                    break;
                case 'cache_clear':
                    $kernel->call('cache:clear', [], $consoleOutput);
                    $kernel->call('config:clear', [], $consoleOutput);
                    $kernel->call('route:clear', [], $consoleOutput);
                    $kernel->call('view:clear', [], $consoleOutput);
                    $output = "Caches cleared successfully:\n" . $consoleOutput->fetch();
                    $success = true;
                    break;
                case 'optimize':
                    $exitCode = $kernel->call('optimize', [], $consoleOutput);
                    $output = $consoleOutput->fetch();
                    $success = ($exitCode === 0);
                    break;
                case 'view_logs':
                    $logFile = storage_path('logs/laravel.log');
                    if (file_exists($logFile)) {
                        $lines = file($logFile);
                        $output = "Last 60 lines of storage/logs/laravel.log:\n\n" . implode("", array_slice($lines, -60));
                    } else {
                        $output = "Log file storage/logs/laravel.log does not exist or is empty.";
                    }
                    $success = true;
                    break;
                case 'logout':
                    $_SESSION['runner_authed'] = false;
                    session_destroy();
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;
                default:
                    $output = "Unknown action.";
                    break;
            }
        } catch (\Exception $e) {
            $output = "Error executing command:\n" . $e->getMessage() . "\n" . $e->getTraceAsString();
        }

        // Return JSON response for AJAX requests
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => $success, 'output' => nl2br(htmlspecialchars($output))]);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unicou Production Deploy Helper</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --border-color: rgba(255, 255, 255, 0.08);
            --primary-glow: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, rgba(15, 23, 42, 0) 70%);
            --secondary-glow: radial-gradient(circle, rgba(236, 72, 153, 0.12) 0%, rgba(15, 23, 42, 0) 70%);
            --text-color: #f1f5f9;
            --accent-primary: #6366f1;
            --accent-secondary: #ec4899;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: 'Outfit', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            position: relative;
        }

        /* Background glow effects */
        .glow-1 {
            position: absolute;
            top: -20%;
            left: -10%;
            width: 60%;
            height: 60%;
            background: var(--primary-glow);
            z-index: 1;
            pointer-events: none;
        }

        .glow-2 {
            position: absolute;
            bottom: -20%;
            right: -10%;
            width: 60%;
            height: 60%;
            background: var(--secondary-glow);
            z-index: 1;
            pointer-events: none;
        }

        .container {
            width: 100%;
            max-width: 900px;
            padding: 24px;
            z-index: 2;
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 24px 48px rgba(99, 102, 241, 0.08);
        }

        .header {
            text-align: center;
            margin-bottom: 32px;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #a5b4fc, #f472b6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }

        .header p {
            color: #94a3b8;
            font-size: 1.05rem;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-bottom: 16px;
            background: rgba(236, 72, 153, 0.15);
            border: 1px solid rgba(236, 72, 153, 0.3);
            color: #f472b6;
        }

        .pin-form {
            max-width: 400px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .input-group label {
            font-size: 0.9rem;
            color: #94a3b8;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .input-control {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 14px 16px;
            color: white;
            font-size: 1.1rem;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            font-family: inherit;
        }

        .input-control:focus {
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
        }

        .btn {
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            color: white;
            border: none;
            border-radius: 12px;
            padding: 14px 24px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s ease, transform 0.2s ease;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn:hover {
            opacity: 0.95;
            transform: translateY(-1px);
        }

        .btn:active {
            transform: translateY(1px);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 0.95rem;
            margin-bottom: 16px;
            text-align: center;
        }

        /* Dashboard Styles */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 32px;
        }

        @media (max-width: 640px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        .action-card {
            background: rgba(15, 23, 42, 0.4);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 24px;
            cursor: pointer;
            transition: transform 0.2s ease, border-color 0.2s ease;
            display: flex;
            flex-direction: column;
            gap: 12px;
            text-align: left;
        }

        .action-card:hover {
            transform: translateY(-2px);
            border-color: rgba(99, 102, 241, 0.4);
            background: rgba(15, 23, 42, 0.6);
        }

        .action-card h3 {
            font-size: 1.15rem;
            font-weight: 600;
            color: white;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .action-card p {
            color: #94a3b8;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .meta-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 20px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 0.9rem;
            border: 1px solid var(--border-color);
        }

        .meta-item span {
            color: #94a3b8;
            margin-right: 6px;
        }

        .meta-item strong {
            color: white;
        }

        .terminal-container {
            background: #020617;
            border: 1px solid var(--border-color);
            border-radius: 16px;
            margin-top: 32px;
            overflow: hidden;
            box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.8);
        }

        .terminal-header {
            background: #0f172a;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-color);
        }

        .terminal-dots {
            display: flex;
            gap: 6px;
        }

        .terminal-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .dot-red { background-color: #ef4444; }
        .dot-yellow { background-color: #f59e0b; }
        .dot-green { background-color: #10b981; }

        .terminal-title {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8rem;
            color: #64748b;
        }

        .terminal-body {
            padding: 24px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.9rem;
            line-height: 1.6;
            color: #38bdf8;
            min-height: 200px;
            max-height: 400px;
            overflow-y: auto;
            text-align: left;
            white-space: pre-wrap;
        }

        .warning-banner {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #fde047;
            padding: 16px 20px;
            border-radius: 16px;
            margin-bottom: 28px;
            font-size: 0.9rem;
            line-height: 1.5;
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .warning-icon {
            font-size: 1.2rem;
            margin-top: 2px;
        }

        .logout-btn {
            background: transparent;
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
            padding: 8px 16px;
            font-size: 0.85rem;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.1);
        }

        /* Spinner for loading state */
        .spinner {
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            border-top: 2px solid white;
            width: 16px;
            height: 16px;
            animation: spin 1s linear infinite;
            display: inline-block;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="glow-1"></div>
    <div class="glow-2"></div>

    <div class="container">
        <div class="card">
            <?php if (!$authenticated): ?>
                <!-- Login PIN Request -->
                <div class="header">
                    <span class="badge">SECURE RUNNER</span>
                    <h1>Enter Access PIN</h1>
                    <p>Enter the 6-digit deployment PIN to access Artisan commands.</p>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <form class="pin-form" method="POST">
                    <div class="input-group">
                        <label for="pin">Authentication PIN</label>
                        <input class="input-control" type="password" id="pin" name="pin" maxlength="6" placeholder="••••••" autofocus required>
                    </div>
                    <button class="btn" type="submit">Verify & Access</button>
                </form>

            <?php else: ?>
                <!-- Dashboard -->
                <div class="header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <div style="text-align: left;">
                        <h1>Artisan Runner</h1>
                        <p>Execute production maintenance and deployment tasks.</p>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="logout-btn">Log Out</button>
                    </form>
                </div>

                <div class="warning-banner">
                    <div class="warning-icon">⚠️</div>
                    <div>
                        <strong>SECURITY HAZARD:</strong> This script exposes powerful administrative functions. Please delete the <code>public/run-migrations.php</code> file from the server immediately after your tasks are complete.
                    </div>
                </div>

                <div class="meta-info">
                    <div class="meta-item">
                        <span>Application:</span><strong><?php echo config('app.name', 'Laravel'); ?></strong>
                    </div>
                    <div class="meta-item">
                        <span>Environment:</span><strong style="color: #4ade80;"><?php echo config('app.env', 'production'); ?></strong>
                    </div>
                    <div class="meta-item">
                        <span>Database:</span><strong><?php echo config('database.default', 'mysql'); ?></strong>
                    </div>
                </div>

                <div class="dashboard-grid">
                    <div class="action-card" style="border-color: rgba(99, 102, 241, 0.6); background: rgba(99, 102, 241, 0.1);" onclick="runCommand('smart_sync')">
                        <h3 style="color: #a5b4fc;">⚡ Smart Sync & Migrate <span style="font-size: 0.75rem; background: #6366f1; color: white; padding: 2px 8px; border-radius: 999px; margin-left: 6px;">RECOMMENDED</span></h3>
                        <p>Safely handles pre-existing tables from SQL dumps and automatically migrates any missing tables (like disputes) without SQL 1050 errors.</p>
                    </div>
                    <div class="action-card" onclick="runCommand('migrate_status')">
                        <h3>📋 Migration Status</h3>
                        <p>Checks the status of database migrations to see what has been registered and run.</p>
                    </div>
                    <div class="action-card" onclick="runCommand('cache_clear')">
                        <h3>🗑️ Clear Caches</h3>
                        <p>Clears config, application cache, route cache, and view cache instantly.</p>
                    </div>
                    <div class="action-card" onclick="runCommand('optimize')">
                        <h3>⚡ Optimize App</h3>
                        <p>Caches configuration and routes for faster load times on production.</p>
                    </div>
                    <div class="action-card" onclick="runCommand('view_logs')">
                        <h3>📜 View Error Logs</h3>
                        <p>Displays recent entries from storage/logs/laravel.log to diagnose production errors.</p>
                    </div>
                </div>

                <div class="terminal-container">
                    <div class="terminal-header">
                        <div class="terminal-dots">
                            <div class="terminal-dot dot-red"></div>
                            <div class="terminal-dot dot-yellow"></div>
                            <div class="terminal-dot dot-green"></div>
                        </div>
                        <div class="terminal-title">Artisan Command Output</div>
                        <div></div>
                    </div>
                    <div class="terminal-body" id="terminal">System initialized. Click any task above to execute...</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function runCommand(action) {
            const terminal = document.getElementById('terminal');
            terminal.innerHTML = '<span style="color: #94a3b8;"><span class="spinner"></span> Running ' + action + ' command...</span>';
            
            const formData = new FormData();
            formData.append('action', action);

            fetch('', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    terminal.innerHTML = '<span style="color: #4ade80;">[SUCCESS]</span>\n\n' + data.output;
                } else {
                    terminal.innerHTML = '<span style="color: #f87171;">[FAILURE]</span>\n\n' + data.output;
                }
            })
            .catch(error => {
                terminal.innerHTML = '<span style="color: #f87171;">[ERROR] Request failed:</span>\n\n' + error;
            });
        }
    </script>
</body>
</html>
