# FW Mining OS API — cPanel Deployment Guide

## Prerequisites

- cPanel hosting with PHP 8.2+ and MySQL 8.0+
- SSH access or cPanel Terminal
- Composer available (or upload vendor/ separately)
- The frontend React app already deployed (note its URL for CORS config)

---

## Step 1 — Upload Files

**Option A: File Manager**
1. Compress the project (excluding `vendor/`, `.env`, `storage/app/`) into a `.zip`
2. Upload via cPanel → File Manager to a directory outside `public_html` (e.g. `~/mining-os-api/`)
3. Extract the archive

**Option B: FTP/SFTP**
1. Use FileZilla or similar client
2. Upload all files except `vendor/` and `.env` to `~/mining-os-api/`

---

## Step 2 — Create MySQL Database

1. In cPanel → **MySQL Databases**
2. Create a new database, e.g. `youruser_mining_os`
3. Create a database user with a strong password
4. Add the user to the database with **All Privileges**
5. Note down: database name, username, password

---

## Step 3 — Set Document Root to `public/`

1. In cPanel → **Domains** (or **Addon Domains** / **Subdomains**)
2. Create or configure the domain/subdomain for your API (e.g. `api.yourcompany.com`)
3. Set the **Document Root** to `~/mining-os-api/public`
4. If using an existing domain with a subfolder, create an `.htaccess` redirect or use a subdomain

---

## Step 4 — Configure `.env`

Via cPanel Terminal or SSH:

```bash
cd ~/mining-os-api
cp .env.example .env
nano .env   # or use the File Manager editor
```

Fill in all required values:

```env
APP_NAME="FW Mining OS API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.yourcompany.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=youruser_mining_os
DB_USERNAME=youruser_dbuser
DB_PASSWORD=your_strong_password

FILESYSTEM_DISK=public

MAIL_MAILER=smtp
MAIL_HOST=smtp.yourprovider.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yourcompany.com
MAIL_PASSWORD=your_mail_password
MAIL_FROM_ADDRESS=noreply@yourcompany.com
MAIL_FROM_NAME="FW Mining OS"
```

Generate the application key:

```bash
php artisan key:generate
```

---

## Step 5 — Install Composer Dependencies

Via cPanel Terminal or SSH:

```bash
cd ~/mining-os-api
composer install --no-dev --optimize-autoloader
```

If Composer is not in PATH:
```bash
php /path/to/composer.phar install --no-dev --optimize-autoloader
```

---

## Step 6 — Run Migrations

```bash
cd ~/mining-os-api
php artisan migrate --force
```

If you need to start fresh (careful — drops all data):
```bash
php artisan migrate:fresh --force
```

---

## Step 7 — Create Storage Symlink

```bash
cd ~/mining-os-api
php artisan storage:link
```

This creates `public/storage` → `storage/app/public` so uploaded files are web-accessible.

---

## Step 8 — Set Folder Permissions

```bash
chmod -R 755 ~/mining-os-api/storage
chmod -R 755 ~/mining-os-api/bootstrap/cache
```

---

## Step 9 — Optimize for Production

```bash
cd ~/mining-os-api
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Step 10 — Configure Frontend

1. In the React frontend, go to **System Settings** (or your `.env` / config file)
2. Set the API base URL to: `https://api.yourcompany.com/api/v1`
3. The frontend's REST client will automatically prepend this to all requests

---

## Step 11 — (Production) Restrict CORS

Edit `config/cors.php`:

```php
'allowed_origins' => ['https://app.yourcompany.com'],
```

Then clear config cache:

```bash
php artisan config:cache
```

---

## Troubleshooting

**500 errors after deploy:**
- Check `storage/logs/laravel.log`
- Ensure `APP_KEY` is set
- Ensure `storage/` and `bootstrap/cache/` are writable

**"Class not found" errors:**
- Run `composer dump-autoload`

**File uploads not working:**
- Verify `php artisan storage:link` was run
- Check PHP `upload_max_filesize` and `post_max_size` in cPanel → PHP Settings

**Database connection refused:**
- cPanel MySQL often requires `127.0.0.1` instead of `localhost`
- Try: `DB_HOST=127.0.0.1`

**Migrations fail with "specified key too long":**
- In `AppServiceProvider::boot()`, add:
  ```php
  Schema::defaultStringLength(191);
  ```

---

## Updating the Application

```bash
cd ~/mining-os-api
# Upload new files via FTP/File Manager
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
