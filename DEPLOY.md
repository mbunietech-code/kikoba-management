# Benja Kikoba — Deploy Guide

One **pure Laravel** application at the repository root: server-rendered Blade
frontend **and** backend, plus a JSON API (`/api/v1/*`) for the Flutter app.

## Stack
- Laravel 13 · PHP 8.3+ · MySQL 8
- Blade + Alpine.js + Tailwind v4 · Chart.js
- Auth: Laravel session guard (web) + Sanctum tokens (`/api/v1/*` for the mobile app)
- RBAC: spatie/laravel-permission
- SMS/OTP: Beem Africa (`SMS_ENABLED=true` to activate)

## No Node on the server

The compiled front-end assets are **committed** to the repo under
`public/build/`. Deployment needs **PHP + Composer only** — no `npm`, no build
step. Rebuild locally only when you change files under `resources/css/` or
`resources/js/`:

```bash
npm install && npm run build    # local machine only
git add public/build && git commit
```

## First-time setup (shared hosting: Hostinger etc.)

```bash
# in the app root
composer install --no-dev --optimize-autoloader
cp .env.example .env            # then edit — see below
php artisan key:generate
php artisan migrate:fresh --seed --force   # RolePermissionSeeder + DemoSeeder (one sample per entity)
php artisan storage:link       # optional; skip if symlink()/exec() are disabled
php artisan config:cache && php artisan route:cache && php artisan view:cache
chmod -R 775 storage bootstrap/cache
```

> `DemoSeeder` no-ops if member data already exists, so re-running `db:seed`
> is safe. `RolePermissionSeeder` is idempotent (`findOrCreate`).

## .env (production)

```
APP_NAME="Benja Kikoba"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://kikoba.example.co.tz
ASSET_URL=https://kikoba.example.co.tz     # so /build asset URLs are https

APP_LOCALE=en
APP_FALLBACK_LOCALE=en

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
DB_ENGINE=InnoDB          # MyISAM breaks UUID/utf8mb4 indexes

SESSION_DRIVER=file
SESSION_SECURE_COOKIE=true
CACHE_STORE=file
QUEUE_CONNECTION=sync
MAIL_MAILER=log

# Beem SMS — leave SMS_ENABLED=false to skip OTP (codes are logged in dev)
SMS_ENABLED=false
BEEM_API_KEY=
BEEM_SECRET_KEY=
BEEM_SENDER_ID=INFO
```

After any `.env` change: `php artisan config:clear` then re-cache.

## Web server — document root

Laravel serves from **`public/`**. Point the domain/subdomain document root at
`.../kikoba/public`.

**Hostinger:** Domains → Subdomains → set the custom folder to
`public_html/kikoba/public` (recreate the subdomain if it can't be edited).

**If the root can't be changed**, drop this `.htaccess` in the app root
(`.../kikoba/.htaccess`) — it forwards everything into `public/`:

```apache
RewriteEngine On
RewriteCond %{REQUEST_URI} !^/public/
RewriteRule ^(.*)$ public/$1 [L]
```

**Nginx:**

```nginx
root /var/www/kikoba/public;
index index.php;
location / { try_files $uri $uri/ /index.php?$query_string; }
location ~ \.php$ { fastcgi_pass unix:/run/php/php8.3-fpm.sock; include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name; }
```

## Demo accounts (password `demo1234`)

| Role | Email |
|---|---|
| Super admin | `super.admin@kikoba.co.tz` |
| Administrator | `admin@kikoba.co.tz` |
| Treasurer | `treasurer@kikoba.co.tz` |
| Accountant | `accountant@kikoba.co.tz` |
| Loan officer | `officer@kikoba.co.tz` |
| Member | `member@mfano.co.tz` |

Change or remove these before going live (**Users** screen). To reset only
permissions without wiping data: `php artisan db:seed --class=RolePermissionSeeder`.

## Routes

- `GET /` → login or role dashboard
- `/admin/*` — staff panel (dashboard, members, shares, savings, loans, products,
  guarantors, projects, insurance, payments, transactions, accounting, profit
  distribution, reports, notifications, users, roles, settings, audit logs)
- `/member/*` — member portal
- `/api/v1/*` — JSON API for the Flutter app (token auth)
- `/locale/{en|sw}` — language switch (session)

## Notes
- Organisation name / currency are editable at **Settings → Organization** and
  flow through the whole UI (sidebar, login, money formatting).
- Financial records are never hard-deleted — payments, transactions and journal
  entries use reversals; loans use cancel-before-disbursement.
- Fonts (Manrope + Inter) are self-hosted and inlined via `Vite::fonts()` — no
  external font requests.
