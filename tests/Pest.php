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
use MyParcelNL\WooCommerce\Tests\Mock\MockWcSession;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpActions;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpCache;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpEnqueue;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpMeta;

require __DIR__ . '/../vendor/myparcelnl/pdk/tests/Pest.php';
require __DIR__ . '/mock_class_map.php';
require __DIR__ . '/mock_namespaced_class_map.php';
require __DIR__ . '/mock_wp_functions.php';
require __DIR__ . '/mock_wc_functions.php';

define('WP_DEBUG', true);

uses()
    ->afterEach(function () {
        /**
         * @var $resetInterfaces class-string<\MyParcelNL\WooCommerce\Tests\Mock\StaticMockInterface>[]
         */
        $resetInterfaces = [
            MockWcData::class,
            MockWcPdkBootstrapper::class,
            MockWcSession::class,
            MockWpActions::class,
            MockWpEnqueue::class,
            MockWpMeta::class,
            MockWpCache::class,
        ];

        foreach ($resetInterfaces as $class) {
            $class::reset();
        }
    })
    ->in(__DIR__);
