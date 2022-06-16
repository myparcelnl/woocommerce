<?php

declare(strict_types=1);

use MyParcelNL\WooCommerce\includes\admin\settings\WordPressOptionStorage;

return [
    'storage' => [
        'default' => new WordPressOptionStorage(),
    ],
];
