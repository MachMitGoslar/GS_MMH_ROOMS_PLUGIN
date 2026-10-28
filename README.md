# gs-mmh-rooms-plugin

Kirby plugin for the room booking of the [MachMit!Haus Goslar](https://mmh.goslar.de) website. **The feature is unfinished**; it was moved here as it was, unchanged in behaviour.

## What it provides

| Part | Where |
|---|---|
| Room pages (`rooms`, `room`) | `templates/`, `blueprints/pages/` |
| Booking requests (`booking-requests`, `booking-request`) | `blueprints/pages/` |
| Snippets (booking form, room card, equipment icon, e-mails, Google Calendar) | `snippets/`, registered as `content-types/rooms/…` |
| Panel area "Raumbuchungen" | `areas/rooms-booking.php` |
| API: `POST/GET /api/booking/submit`, `GET /api/rooms/availability.json`, `GET /api/rooms/<slug>/availability.json` | `routes/api.php` |
| Hook `page.changeStatus:after` (mail and calendar on status change of a request) | `hooks/booking-status.php` |
| Stylesheet | `assets/rooms.css`, linked by the templates |

The content (`rooms/`, `booking-requests/`) lives in the content repository, not here.

## Needs from the website

The templates use the website's layout snippets (`layout/head`, `layout/header`, `utilities/image`, `utilities/icon`, …) and design tokens. The plugin does not work without the site.

## Known gaps (moved as they were)

- `/rooms` and `/rooms/<slug>` currently answer HTTP 500 (`snippets/bookingForm.php` and `utilities/image` with an unresolved cover).
- `snippets/nextcloudCalendarIntegration.php`, required by the availability routes, does not exist.
- The Google Calendar integration needs credentials (`google.calendar.credentials`).

## Development

```bash
composer install
composer test          # PHPUnit
composer lint          # PHPCS (PSR-12 with Kirby exceptions)
composer format:check  # PHP CS Fixer
```

Same tooling and rules as the website repository (docs/CLEANUP.md, phase 2 and 4).

## Deployment

Clone into `site/plugins/gs-mmh-rooms-plugin` of the website, like the other plugins. Deploy this plugin **before** the website version that no longer contains the room files.
