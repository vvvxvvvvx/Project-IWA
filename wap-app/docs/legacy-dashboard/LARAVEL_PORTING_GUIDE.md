# Laravel porting guide

Deze map is bewust gebouwd als **lichte PHP-webapp** zodat je met alleen PHP direct kunt draaien.
Omdat jouw projectcontext ook **Laravel** noemt, is de code al opgesplitst in lagen die vrijwel 1-op-1
naar Laravel kunnen worden verplaatst.

## Component mapping

| Deze codebase | Laravel-equivalent |
|---|---|
| `src/Controllers/*` | `app/Http/Controllers/*` |
| `src/Services/*` | `app/Services/*` |
| `src/Repositories/*` | `app/Repositories/*` |
| `src/Middleware/*` | `app/Http/Middleware/*` |
| `views/*` | `resources/views/*` |
| `public/index.php` router | `routes/web.php` en `routes/api.php` |
| `storage/app.sqlite` | `database/database.sqlite` of MySQL |

## Belangrijkste routes

- `POST /postWeatherData`
- `GET /`
- `GET /stations/{stn}`
- `GET /api/overview`
- `GET /api/stations`
- `GET /api/stations/{stn}`

## Waarom deze aanpak

- minimaal aantal externe installaties
- generator kan meteen koppelen
- structuur blijft wel netjes genoeg voor latere Laravel-migratie
