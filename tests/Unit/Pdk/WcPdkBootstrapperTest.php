<?php
/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Model\CheckoutSettings;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpCache;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpUser;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use function MyParcelNL\Pdk\Tests\usesShared;

usesShared(new UsesMockWcPdkInstance());

it('returns proper permission callback', function (array $roles, bool $expected) {
    foreach ($roles as $role) {
        MockWpUser::addRole($role);
    }

    $actual = (Pdk::get('routeBackendPermissionCallback'))();

    expect($actual)->toBe($expected);
})->with([
    'no roles'                    => [
        'roles'    => [],
        'expected' => false,
    ],
    'no administrator'            => [
        'roles'    => ['subscriber'],
        'expected' => false,
    ],
    'nobody, subscriber'          => [
        'roles'    => ['nobody', 'subscriber'],
        'expected' => false,
    ],
    'administrator'               => [
        'roles'    => ['administrator'],
        'expected' => true,
    ],
    'shop manager'                => [
        'roles'    => ['shop_manager'],
        'expected' => true,
    ],
    'subscriber, shop manager'    => [
        'roles'    => ['subscriber', 'shop_manager'],
        'expected' => true,
    ],
    'subscriber, administrator'   => [
        'roles'    => ['subscriber', 'administrator'],
        'expected' => true,
    ],
    'shop manager, administrator' => [
        'roles'    => ['shop_manager', 'administrator'],
        'expected' => true,
    ],
    'shop manager, subscriber'    => [
        'roles'    => ['shop_manager', 'subscriber'],
        'expected' => true,
    ],
    'many, shop_manager'          => [
        'roles'    => ['nobody', 'subscriber', 'anybody', 'shop_manager'],
        'expected' => true,
    ],
    'many, shop_manager, any'     => [
        'roles'    => ['nobody', 'subscriber', 'anybody', 'shop_manager', 'somebody'],
        'expected' => true,
    ],
]);

it('enables or disables delivery options position setting', function ($pageId, $group, $data, $result) {
    MockWpCache::add($pageId, $data, $group);
    $disabledSettings = Pdk::get('disabledSettings');

    expect($disabledSettings)->toEqual($result);
})->with([
    'Blocks checkout enabled'  => [
        'pageId' => '7',
        'group'  => 'pages',
        'data'   => ['pageName' => 'checkout', 'hasBlocks' => true],
        'result' => [CheckoutSettings::ID => [CheckoutSettings::DELIVERY_OPTIONS_POSITION]],
    ],
    'Classic checkout enabled' => [
        'pageId' => '7',
        'group'  => 'pages',
        'data'   => ['pageName' => 'checkout', 'hasBlocks' => false],
        'result' => [],
    ],
]);
