<?php
/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\WooCommerce\Integration\DeliveryOptionsBlocksIntegration;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use function MyParcelNL\Pdk\Tests\usesShared;

usesShared(new UsesMockWcPdkInstance());

it('integrates with Woocommerce Blocks', function () {
    $class      = new DeliveryOptionsBlocksIntegration();
    $scriptData = $class->get_script_data();

    expect($class->get_name())
        ->toBe('myparcelnl-delivery-options')
        ->and($class->get_script_handles())
        ->toBe(['myparcelnl-delivery-options-block-view-script', 'myparcelnl-delivery-options-block-editor-script'])
        ->and($class->get_editor_script_handles())
        ->toBe(['myparcelnl-delivery-options-block-view-script'])
        ->and($scriptData)
        ->toHaveKeys(['context', 'style'])
        ->and($scriptData['context'])
        ->toBeString();
});
