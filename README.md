# Kikoba Management

Group savings & loans management system for a chama (**Benja Kikoba**).

## `api/` — Laravel application (primary)

Server-rendered **Blade + Alpine.js + Tailwind** frontend **and** backend in one Laravel 13 app,
plus a JSON API (`/api/v1/*`) consumed by the Flutter app.

- Staff panel (`/admin/*`) and member portal (`/member/*`) — ~40 screens, EN/SW
- Session auth, RBAC (spatie), OTP via Beem SMS
- Full ERD schema (30+ tables, UUID PKs, double-entry accounting)
- Editable organisation profile; financial-safe deletes (reversals)

See [`api/DEPLOY.md`](api/DEPLOY.md).

```bash
cd api && composer install && npm install && npm run build
php artisan migrate --seed
php artisan serve        # + `npm run dev` for hot reload
```

## `mobile/` — Flutter Android app

Admin + member front-end in Flutter, talks to the same Laravel API.

```bash
cd mobile && flutter pub get && flutter run
```

## Root `src/` — original React SPA (kept for reference)

The first frontend iteration (React + Vite). Superseded by the Laravel Blade frontend
but retained; it reads the same `/api/v1` endpoints.

```bash
npm install && npm run dev
```

## Docs

`SRS`, `SDD` and the ERD design document (shared in project history) describe the domain.
