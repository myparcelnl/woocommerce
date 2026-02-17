<?php

/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Hooks;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpActions;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpUser;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use WP_REST_Request;
use function DI\get;
use function MyParcelNL\Pdk\Tests\usesShared;

usesShared(new UsesMockWcPdkInstance());

it('registers delivery options handler in wp rest api', function () {
    /** @var \MyParcelNL\WooCommerce\Pdk\Hooks\PdkEndpointHooks $hookClass */
    $hookClass = Pdk::get(PdkEndpointHooks::class);
    $hookClass->apply();

    expect(MockWpActions::get('rest_api_init'))->toBeArray();

    MockWpActions::execute('rest_api_init');

    $routes = rest_get_server()->get_routes();

    expect($routes)->toEqual([
        'myparcelcom/delivery-options' => [
            'override' => false,
            'args'     => [
                'methods'  => 'GET',
                'callback'            => [$hookClass, 'processDeliveryOptionsRequest'],
                'permission_callback' => [$hookClass, 'checkDeliveryOptionsPermission'],
            ],
        ],
    ]);
});

it('returns the negotated and supported versions in accept and content-type headers', function () {
    /** @var \MyParcelNL\WooCommerce\Pdk\Hooks\PdkEndpointHooks $hookClass */
    $hookClass = Pdk::get(PdkEndpointHooks::class);

    $request = new WP_REST_Request('GET', '/myparcelcom/delivery-options?orderId=123');

    $result = $hookClass->processDeliveryOptionsRequest($request);

    expect($result->get_status())->toBe(404); // order does not exist
    expect($result->get_headers())->toHaveKey('Content-Type');
    expect($result->get_headers())->toHaveKey('Accept');
    expect($result->get_headers()['Content-Type'])->toBe('application/json; version=1');
    expect($result->get_headers()['Accept'])->toBe('application/json; version=1');
});

it('requires read order permissions to access delivery options endpoint', function () {
    /** @var \MyParcelNL\WooCommerce\Pdk\Hooks\PdkEndpointHooks $hookClass */
    $hookClass = Pdk::get(PdkEndpointHooks::class);

    expect($hookClass->checkDeliveryOptionsPermission())->toBeInstanceOf('WP_Error');

    // Simulate a user with read order permissions
    MockWpUser::addRole('shop_manager');
    expect($hookClass->checkDeliveryOptionsPermission())->toBeTrue();
});
