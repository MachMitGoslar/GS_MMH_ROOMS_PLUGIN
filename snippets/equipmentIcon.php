<?php

/**
 * Equipment Icon Snippet
 *
 * Thin wrapper around `utilities/icon` so the room views keep their existing
 * call signature while the actual icon set lives in one place.
 *
 * @var string $icon The icon key
 * @var bool $small Whether to render a smaller version
 */

$aliases = [
    'wheelchair' => 'accessibility',
    'other' => 'info',
];

snippet('utilities/icon', [
    'name' => $aliases[$icon] ?? $icon,
    'size' => ($small ?? false) ? 18 : 24,
]);
