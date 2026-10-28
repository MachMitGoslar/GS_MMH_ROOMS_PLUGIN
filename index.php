<?php

/**
 * GS MachMit!Haus Rooms Plugin
 *
 * Room booking: room pages, booking requests, availability, the panel area
 * and the status hook. Moved here from the website repository and the
 * gs-mmh-web-plugin (docs/CLEANUP.md, phase 4). The feature is unfinished.
 */

use Kirby\Cms\App as Kirby;

Kirby::plugin('gs-mmh/gs-mmh-rooms-plugin', [
    'blueprints' => [
        'pages/room' => __DIR__ . '/blueprints/pages/room.yml',
        'pages/rooms' => __DIR__ . '/blueprints/pages/rooms.yml',
        'pages/booking-request' => __DIR__ . '/blueprints/pages/booking-request.yml',
        'pages/booking-requests' => __DIR__ . '/blueprints/pages/booking-requests.yml',
    ],
    'templates' => [
        'room' => __DIR__ . '/templates/room.php',
        'rooms' => __DIR__ . '/templates/rooms.php',
    ],
    // Same names as before the move, so templates and mails need no change
    'snippets' => [
        'content-types/rooms/bookingForm' => __DIR__ . '/snippets/bookingForm.php',
        'content-types/rooms/bookingRequestHandler' => __DIR__ . '/snippets/bookingRequestHandler.php',
        'content-types/rooms/equipmentIcon' => __DIR__ . '/snippets/equipmentIcon.php',
        'content-types/rooms/googleCalendarIntegration' => __DIR__ . '/snippets/googleCalendarIntegration.php',
        'content-types/rooms/roomCard' => __DIR__ . '/snippets/roomCard.php',
        'content-types/rooms/emails/admin-notification' => __DIR__ . '/snippets/emails/admin-notification.php',
        'content-types/rooms/emails/booking-approved' => __DIR__ . '/snippets/emails/booking-approved.php',
        'content-types/rooms/emails/booking-denied' => __DIR__ . '/snippets/emails/booking-denied.php',
        'content-types/rooms/emails/booking-received' => __DIR__ . '/snippets/emails/booking-received.php',
    ],
    'areas' => [
        'rooms-booking' => require __DIR__ . '/areas/rooms-booking.php',
    ],
    'api' => [
        'routes' => require __DIR__ . '/routes/api.php',
    ],
    'hooks' => [
        'page.changeStatus:after' => require __DIR__ . '/hooks/booking-status.php',
    ],
    // Stylesheet of the room pages, linked by the templates (media/plugins/…/rooms.css)
    'assets' => [
        'rooms.css' => __DIR__ . '/assets/rooms.css',
    ],
]);
