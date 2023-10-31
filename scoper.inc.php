<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

// For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md
return [
    'prefix' => '_MyParcelNL',

    'finders' => [
        Finder::create()
            ->append([
                'woocommerce-myparcel.php',
                'composer.json',
            ]),
        Finder::create()
            ->files()
            ->in(['src', 'config']),
    ],

    'exclude-namespaces' => [
        // Exclude global namespace
        '/^$/',
        'Automattic',
        'Composer',
        'MyParcelNL',
        'WP',
    ],
];
