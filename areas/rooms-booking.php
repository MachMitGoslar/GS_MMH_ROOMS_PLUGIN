<?php

return function () {
    return [
        'label' => 'Raumbuchungen',
        'icon' => 'calendar',
        'menu' => true,
        'link' => 'rooms-booking',
        'views' => [
            [
                'pattern' => 'rooms-booking',
                'action' => function () {
                    return page('rooms')->panel()->view();
                },
            ],
        ],
    ];
};
