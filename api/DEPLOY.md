# Benja Kikoba — Deploy Guide

Single Laravel application: **server-rendered Blade frontend + backend + JSON API** (for the
Flutter app) in one codebase.

## Stack
- Laravel 13 · PHP 8.3+ · MySQL 8
- Blade + Alpine.js + Tailwind v4 (Vite) · Chart.js
- Auth: Laravel session guard (web) + Sanctum tokens (`/api/v1/*` for the mobile app)
- RBAC: spatie/laravel-permission
- SMS/OTP: Beem Africa (`SMS_ENABLED=true` to activate)

## First-time setup

```bash
cd api
composer install --no-dev --optimize-autoloader
npm install && npm run build
cp .env.example .env        # then edit (see below)
php artisan key:generate
php artisan migrate --seed  # RolePermissionSeeder + DemoSeeder (one sample per entity)
php artisan storage:link
php artisan config:cache route:cache view:cache
```

## .env (production)

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=benja_kikoba
DB_USERNAME=...
DB_PASSWORD=...
DB_ENGINE=InnoDB          # required — MyISAM breaks UUID/utf8mb4 indexes

SESSION_DRIVER=file        # or database/redis
SESSION_SECURE_COOKIE=true

# Beem SMS (leave SMS_ENABLED=false to skip OTP; codes are logged in dev)
BEEM_API_KEY=
BEEM_SECRET_KEY=
BEEM_SENDER_ID=INFO
SMS_ENABLED=false

# Mobile app / SPA CORS (config/cors.php)
```

## Web server

Point the docroot at `api/public`. Nginx example:

```nginx
root /var/www/kikoba/api/public;
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

Change or remove these before going live (`Users` screen), and re-run only
`php artisan db:seed --class=RolePermissionSeeder` if you need to reset permissions
without wiping data.

## Routes

- `GET /` → login or role dashboard
- `/admin/*` — staff panel (23 screens: dashboard, members, shares, savings, loans,
  products, guarantors, projects, insurance, payments, transactions, accounting,
  profit distribution, reports, notifications, users, roles, settings, audit logs)
- `/member/*` — member portal (14 screens)
- `/api/v1/*` — JSON API for the Flutter app (token auth)
- `/locale/{en|sw}` — language switch (session)

## Notes
- Organisation name / currency are editable at **Settings → Organization** and flow
  through the whole UI (sidebar, login, money formatting).
- Financial records are never hard-deleted — payments, transactions and journal
  entries use reversals; loans use cancel-before-disbursement.
