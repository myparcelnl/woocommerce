<?php
/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\WooCommerce\Integration\DeliveryOptionsBlocksIntegration;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use function MyParcelNL\Pdk\Tests\usesShared;

usesShared(new UsesMockWcPdkInstance());

it('integrates with WooCommerce Blocks', function () {
    $class      = new DeliveryOptionsBlocksIntegration('delivery-options');
    $scriptData = $class->get_script_data();

    expect($class->get_name())
        ->toBe('pest-delivery-options')
        ->and($class->get_script_handles())
        ->toBe(['pest-delivery-options-block-view-script', 'pest-delivery-options-block-editor-script'])
        ->and($class->get_editor_script_handles())
        ->toBe(['pest-delivery-options-block-view-script'])
        ->and($scriptData)
        ->toHaveKeys(['context', 'style'])
        ->and($scriptData['context'])
        ->toBeString();
});
