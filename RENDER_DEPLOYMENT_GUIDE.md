# UniCou Voucher Platform - Render Deployment Guide

This guide provides step-by-step instructions for deploying the **UniCou** Laravel 12 application to **[Render](https://render.com/)**.

---

## Architecture Overview

* **Runtime:** Docker Container (Debian Bookworm + PHP 8.2 + Apache)
* **Build Pipeline:** Multi-stage Docker build:
  1. **Frontend Stage (`node:20-alpine`):** Compiles Vite & Tailwind CSS v4 assets into `public/build/`.
  2. **Production Stage (`php:8.2-apache`):** Installs PHP extensions (`pdo_mysql`, `pdo_pgsql`, `bcmath`, `opcache`, `gd`, `intl`, `zip`), Composer dependencies, configures Apache virtual host, sets permissions, and handles dynamic `$PORT` routing.
* **Health Check Path:** `/up` (Laravel 11/12 built-in health endpoint)
* **Reverse Proxy & SSL:** Handled automatically by Render with HTTPS enforcement.

---

## Database Options on Render

Render natively supports PostgreSQL, whereas this project is currently configured for MySQL. You have three straightforward options:

### Option 1: Managed MySQL (Recommended for zero code changes)
Use a free or managed cloud MySQL provider:
* **[Aiven for MySQL](https://aiven.io/)** (Free tier available)
* **[Clever Cloud](https://www.clever-cloud.com/)** (Free MySQL addon)
* **Your existing cPanel / Bluehost MySQL server** (enable Remote MySQL access for `%` in cPanel).

### Option 2: Render Managed PostgreSQL
Render provides 1-click PostgreSQL databases on the dashboard:
1. In Render, click **New + > PostgreSQL**.
2. Note the internal DB credentials.
3. In your Web Service settings, set `DB_CONNECTION=pgsql`, `DB_PORT=5432`, and your PostgreSQL host/credentials. (Laravel Eloquent supports PostgreSQL transparently).

---

## Method 1: Deploy with Render Blueprint (Fastest)

The repository includes a ready-to-use `render.yaml` blueprint.

1. **Push the changes to GitHub:**
   ```bash
   git add .
   git commit -m "Add Render Docker deployment configuration"
   git push origin master
   ```

2. **Deploy on Render:**
   - Log in to your [Render Dashboard](https://dashboard.render.com/).
   - Click **New +** > **Blueprint**.
   - Select your GitHub repository (`priyanshu2405/unico`).
   - Render will parse `render.yaml` and create the Web Service.
   - Fill in your database credentials and `APP_KEY`.
   - Click **Apply**.

---

## Method 2: Manual Web Service Setup

If you prefer configuring the Web Service manually via Render dashboard:

1. **Create Web Service:**
   - Go to [dashboard.render.com](https://dashboard.render.com/).
   - Click **New +** > **Web Service**.
   - Connect your GitHub repository (`priyanshu2405/unico`).

2. **Configure Service Details:**
   - **Name:** `unicou-voucher`
   - **Region:** Choose the region closest to your users / database (e.g. Frankfurt, Oregon, Singapore).
   - **Branch:** `master`
   - **Environment:** `Docker`
   - **Dockerfile Path:** `./Dockerfile`
   - **Plan:** Free or Starter

3. **Configure Health Check:**
   - Expand **Advanced**.
   - Set **Health Check Path** to `/up`.

4. **Add Environment Variables:**
   Under the **Environment** tab, add the following variables:

   | Key | Value | Notes |
   |---|---|---|
   | `APP_NAME` | `UniCou` | |
   | `APP_ENV` | `production` | |
   | `APP_DEBUG` | `false` | |
   | `APP_KEY` | `base64:...` | Generate using `php artisan key:generate --show` |
   | `APP_URL` | `https://your-service-name.onrender.com` | Update with your Render domain |
   | `LOG_CHANNEL` | `stderr` | Sends logs to Render Dashboard |
   | `LOG_LEVEL` | `error` | |
   | `DB_CONNECTION` | `mysql` | (`pgsql` if using Render PostgreSQL) |
   | `DB_HOST` | `your-db-host` | |
   | `DB_PORT` | `3306` | (`5432` for pgsql) |
   | `DB_DATABASE` | `your_db_name` | |
   | `DB_USERNAME` | `your_db_user` | |
   | `DB_PASSWORD` | `your_db_password` | |
   | `SESSION_DRIVER` | `database` | |
   | `CACHE_STORE` | `database` | |
   | `QUEUE_CONNECTION` | `database` | |
   | `RUN_MIGRATIONS` | `true` | Automatically runs `php artisan migrate --force` on startup |
   | `SEED_DATABASE` | `true` | (First deploy only) Seeds pre-configured role test accounts |

5. **Deploy:**
   - Click **Create Web Service**.
   - Render will build the Docker container and start your service.

---

## Initial Database Seeding

To seed the initial test accounts (Admin, Manager, Support, Reseller, Agent, Student):
1. In your Render Service settings, set `SEED_DATABASE=true`.
2. Trigger a deploy.
3. Once deployed, set `SEED_DATABASE=false` to avoid re-running seeds on every subsequent restart.

**Pre-seeded Test Logins:**
* **Admin:** `admin@unicou.nl` / `Password@123`
* **Manager:** `manager@unicou.nl` / `Password@123`
* **Reseller Agent:** `reseller@unicou.nl` / `Password@123`
* **Agent:** `agent@unicou.nl` / `Password@123`
* **Student:** `student@unicou.nl` / `Password@123`
* **Support:** `support@unicou.nl` / `Password@123`

---

## Render Shell Access & Useful Commands

Render provides a web terminal under the **Shell** tab of your Web Service:
* **Run manual migrations:**
  ```bash
  php artisan migrate --force
  ```
* **Clear application cache:**
  ```bash
  php artisan optimize:clear
  ```
* **Inspect logs:** Check the **Logs** tab in Render dashboard (streamed in real-time via `stderr`).
