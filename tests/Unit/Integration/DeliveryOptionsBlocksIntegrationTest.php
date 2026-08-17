<?php
/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Context\Contract\ContextServiceInterface;
use MyParcelNL\WooCommerce\Integration\DeliveryOptionsBlocksIntegration;
use MyParcelNL\WooCommerce\Tests\Mock\MockThrowingContextService;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use function MyParcelNL\Pdk\Tests\mockPdkProperties;
use function MyParcelNL\Pdk\Tests\usesShared;

usesShared(new UsesMockWcPdkInstance());

it('integrates with WooCommerce Blocks', function () {
    $class      = new DeliveryOptionsBlocksIntegration('myparcelcom-delivery-options');
    $scriptData = $class->get_script_data();

    expect($class->get_name())
        ->toBe('myparcelcom-delivery-options')
        ->and($class->get_script_handles())
        ->toBe(['myparcelcom-delivery-options-block-view-script', 'myparcelcom-delivery-options-block-editor-script'])
        ->and($class->get_editor_script_handles())
        ->toBe(['myparcelcom-delivery-options-block-view-script'])
        ->and($scriptData)
        ->toHaveKeys(['context', 'style'])
        ->and($scriptData['context'])
        ->toBeString();
});

it('passes an empty context to the block when building it fails', function () {
    mockPdkProperties([ContextServiceInterface::class => new MockThrowingContextService()]);

    $class = new DeliveryOptionsBlocksIntegration('myparcelcom-delivery-options');

    // The blocks checkout must render without delivery options instead of returning a 500.
    expect($class->get_script_data()['context'])->toBe('');
});
