# Benja Kikoba

Group savings & loans management system for a chama (**Benja Kikoba**).

## Pure Laravel app (repository root)

Server-rendered **Blade + Alpine.js + Tailwind** frontend **and** backend in one
Laravel 13 application, plus a JSON API (`/api/v1/*`) consumed by the Flutter app.

- Staff panel (`/admin/*`) and member portal (`/member/*`) — EN / SW
- Session auth, RBAC (spatie/laravel-permission), OTP via Beem SMS
- Full ERD schema (30+ tables, UUID PKs, double-entry accounting)
- Editable organisation profile; financial-safe deletes (reversals)
- Compiled assets are committed (`public/build/`) — **deploy needs PHP + Composer only, no Node**

### Run locally

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

Only if you change `resources/css` or `resources/js`:

```bash
npm install && npm run build      # then commit public/build
# or `npm run dev` for hot reload during development
```

See [`DEPLOY.md`](DEPLOY.md) for production / shared hosting.

## `mobile/` — Flutter Android app

Admin + member front-end in Flutter, talks to the same Laravel API.

```bash
cd mobile && flutter pub get && flutter run
```

## Docs

`SRS`, `SDD` and the ERD design document (shared in project history) describe the domain.
