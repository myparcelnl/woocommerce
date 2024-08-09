<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests;

/**
 * Global Pest test configuration.
 *
 * @see https://pestphp.com/docs/underlying-test-case#testspestphp
 */

use MyParcelNL\WooCommerce\Tests\Mock\MockWcData;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcPdkBootstrapper;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpActions;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpMeta;

require __DIR__ . '/../vendor/myparcelnl/pdk/tests/Pest.php';
require __DIR__ . '/mock_class_map.php';
require __DIR__ . '/mock_namespaced_class_map.php';
require __DIR__ . '/mock_wp_functions.php';
require __DIR__ . '/mock_wc_functions.php';

define('WP_DEBUG', true);

uses()
    ->afterEach(function () {
        MockWcPdkBootstrapper::reset();

        MockWpActions::reset();
        MockWcData::reset();
        MockWpMeta::reset();
    })
    ->in(__DIR__);
