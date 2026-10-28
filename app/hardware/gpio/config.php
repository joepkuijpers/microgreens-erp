<?php

return [
    'mode' => 'simulation',

    'relays' => [
        'heater' => [
            'label' => 'Verwarming',
            'gpio_pin' => 17,
            'active_low' => true
        ],
        'cooler' => [
            'label' => 'Koeling',
            'gpio_pin' => 27,
            'active_low' => true
        ],
        'fan' => [
            'label' => 'Ventilator',
            'gpio_pin' => 22,
            'active_low' => true
        ],
        'humidifier' => [
            'label' => 'Bevochtiger',
            'gpio_pin' => 23,
            'active_low' => true
        ],
        'grow_light' => [
            'label' => 'Grow light',
            'gpio_pin' => 24,
            'active_low' => true
        ],
        'water_pump' => [
            'label' => 'Waterpomp',
            'gpio_pin' => 25,
            'active_low' => true
        ]
    ]
];
