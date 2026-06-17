<?php

/**
 * Production Packaging Script for Laravel
 * Creates a clean zip file excluding development and environment-specific files/folders,
 * while preserving required empty directory structures.
 */

$rootPath = realpath(__DIR__ . '/..');
$zipFileName = 'unicou_production.zip';
$zipPath = $rootPath . '/' . $zipFileName;

echo "Root Path: $rootPath\n";
echo "Creating Zip: $zipPath\n\n";

if (!extension_loaded('zip')) {
    die("Error: PHP zip extension is not enabled.\n");
}

$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die("Error: Cannot open/create zip file: $zipPath\n");
}

// Files/Directories to exclude completely
$excludePatterns = [
    '/^\.git$/i',
    '/^\.git\//i',
    '/^node_modules$/i',
    '/^node_modules\//i',
    '/^scratch$/i',
    '/^scratch\//i',
    '/^\.env$/i',
    '/^\.env\.backup$/i',
    '/^\.env\.production$/i',
    '/.*\.zip$/i', // exclude any zip files
    '/^\.idea$/i',
    '/^\.idea\//i',
    '/^\.vscode$/i',
    '/^\.vscode\//i',
    '/^\.zencoder$/i',
    '/^\.zencoder\//i',
    '/^public\/storage$/i',
    '/^public\/storage\//i',
    '/^public\/hot$/i',
];

// Folders where files should be excluded but the directory structure must be preserved
$clearFolderContents = [
    'storage/logs',
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/views',
    'bootstrap/cache'
];

$fileCount = 0;
$dirCount = 0;
$skippedCount = 0;

// Set up recursive directory iterator
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($rootPath, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($files as $name => $file) {
    $pathname = $file->getPathname();
    
    // Relative path based on the traversed path (preserving symlink names like public/storage)
    $relativePath = substr($pathname, strlen($rootPath) + 1);
    $relativePath = str_replace('\\', '/', $relativePath);

    // Check if path matches any exclusion patterns
    $exclude = false;
    foreach ($excludePatterns as $pattern) {
        if (preg_match($pattern, $relativePath)) {
            $exclude = true;
            break;
        }
    }

    if ($exclude) {
        continue;
    }

    // Skip symlinks
    if (is_link($pathname) || $file->isLink()) {
        echo "Skipping symlink: $relativePath\n";
        $skippedCount++;
        continue;
    }

    // Check if this file is inside one of the folders to clear
    $isClearedFolderFile = false;
    foreach ($clearFolderContents as $folder) {
        // Match files strictly under the folder (but not the folder itself or its .gitignore)
        if (strpos($relativePath, $folder . '/') === 0) {
            $filename = basename($relativePath);
            if ($filename !== '.gitignore') {
                $isClearedFolderFile = true;
                break;
            }
        }
    }

    if ($isClearedFolderFile) {
        continue;
    }

    if (is_dir($pathname)) {
        // Add empty directory
        if ($zip->addEmptyDir($relativePath)) {
            $dirCount++;
        } else {
            echo "Failed to add directory: $relativePath\n";
        }
    } elseif (is_file($pathname)) {
        $realFilePath = realpath($pathname);
        if (!$realFilePath || !is_readable($realFilePath)) {
            echo "Warning: File not readable: $relativePath\n";
            $skippedCount++;
            continue;
        }
        
        // Add file
        if ($zip->addFile($realFilePath, $relativePath)) {
            $fileCount++;
        } else {
            echo "Failed to add file: $relativePath\n";
        }
    } else {
        echo "Warning: Unknown file type for $relativePath\n";
        $skippedCount++;
    }
}

// Make sure standard empty directories exist in the zip
foreach ($clearFolderContents as $folder) {
    $zip->addEmptyDir($folder);
}

echo "\nClosing zip archive. This may take a moment...\n";
if ($zip->close()) {
    echo "\nZip creation completed successfully!\n";
    echo "Total files added: $fileCount\n";
    echo "Total directories added: $dirCount\n";
    echo "Total files/dirs skipped: $skippedCount\n";
    echo "Zip Location: " . realpath($zipPath) . "\n";
    echo "Zip Size: " . round(filesize($zipPath) / (1024 * 1024), 2) . " MB\n";
} else {
    echo "\nError: ZipArchive failed to close and save. Check permissions or locked files.\n";
}
