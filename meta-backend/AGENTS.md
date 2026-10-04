# AGENTS.md

Laravel 11 backend for student activity management system.

## Tech Stack

- **Framework:** (laravel/laravel, laravel/framework), PHP 8.3+
- **Database:** MySQL
- **Testing:** Pest PHP
- **API Documentation:** (knuckleswtf/scribe)
- **Auth:** (laravel/sanctum)

*- repo name in ()*

## Code Style

- Follow Laravel conventions and PSR-12
- Use PHPDoc comments for API documentation — see `docs/SCRIBE_STYLEGUIDE.md`
- Return JSON responses with `response()->json()`

## Architecture Overview

- `app/Http/Controllers/{Domain}/` — API controllers by feature (Activity, User, Admin, etc.)
- `routes/api.php` (public endpoints) + `routes/api/{admin,mentor,student}.php` — Role-based route files

## Docker

Доступ к php через docker

```bash
docker compose exec --user www php-fpm php artisan
```

