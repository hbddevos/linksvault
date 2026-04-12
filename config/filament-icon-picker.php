<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Icon Sets
    |--------------------------------------------------------------------------
    |
    | Define which icon sets should be available by default in the icon picker.
    | You can override this per-field using the sets() method.
    |
    | Available options: 'heroicons', 'lucide'
    |
    */
    'sets' => [
        'heroicons',
    ],

    /*
    |--------------------------------------------------------------------------
    | Icon Sets Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the icon sets that can be used in the icon picker.
    | Each set needs a prefix that matches your Blade Icons configuration.
    |
    | The prefix should match the one you've configured in your
    | blade-icons.php configuration file.
    |
    */
    'icon_sets' => [
        'heroicons' => [
            'label' => 'Heroicons',
            'prefix' => 'heroicon-o',
            'enabled' => true,
        ],

        'heroicons-solid' => [
            'label' => 'Heroicons Solid',
            'prefix' => 'heroicon-s',
            'enabled' => false,
        ],

        'heroicons-mini' => [
            'label' => 'Heroicons Mini',
            'prefix' => 'heroicon-m',
            'enabled' => false,
        ],

        'lucide' => [
            'label' => 'Lucide',
            'prefix' => 'lucide',
            'enabled' => false,
        ],

        'font-awesome' => [
            'label' => 'Font Awesome',
            'prefix' => 'fas',
            'enabled' => false,
        ],

        'fontawesome-regular' => [
            'label' => 'Font Awesome (Regular)',
            'prefix' => 'far',
            'enabled' => false,
        ],

        'fontawesome-brands' => [
            'label' => 'Font Awesome (Brands)',
            'prefix' => 'fab',
            'enabled' => false,
        ],

        'bootstrap' => [
            'label' => 'Bootstrap Icons',
            'prefix' => 'bi',
            'enabled' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Grid Columns
    |--------------------------------------------------------------------------
    |
    | Set the default number of columns for the icon grid.
    | This can be overridden per-field using the columns() method.
    |
    */
    'columns' => 6,

    /*
    |--------------------------------------------------------------------------
    | Searchable
    |--------------------------------------------------------------------------
    |
    | Enable or disable the search functionality by default.
    | This can be overridden per-field using the searchable() method.
    |
    */
    'searchable' => true,

    /*
    |--------------------------------------------------------------------------
    | Placeholder
    |--------------------------------------------------------------------------
    |
    | Set the default placeholder text for the icon picker field.
    | This can be overridden per-field using the placeholder() method.
    |
    */
    'placeholder' => 'Select an icon',

    /*
    |--------------------------------------------------------------------------
    | Cache TTL
    |--------------------------------------------------------------------------
    |
    | Icon discovery can be expensive (filesystem scanning). The package caches
    | discovered icons per set for this many seconds.
    |
    */
    'cache_ttl_seconds' => (int) env('FILAMENT_ICON_PICKER_CACHE_TTL_SECONDS', 86400),
];
