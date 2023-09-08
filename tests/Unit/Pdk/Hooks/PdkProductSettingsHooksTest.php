<?php
/** @noinspection PhpUnhandledExceptionInspection,StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Unit\Pdk\Hooks;

use MyParcelNL\Pdk\App\Order\Contract\PdkProductRepositoryInterface;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Model\ProductSettings;
use MyParcelNL\Pdk\Shipment\Model\DeliveryOptions;
use MyParcelNL\Pdk\Tests\Bootstrap\MockPdkProductRepository;
use MyParcelNL\Pdk\Types\Service\TriStateService;
use MyParcelNL\WooCommerce\Pdk\Hooks\PdkProductSettingsHooks;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use WC_Product;
use function DI\autowire;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\wpFactory;

function defaultProductSettings(): array
{
    return [
        'id'                                      => ProductSettings::ID,
        ProductSettings::COUNTRY_OF_ORIGIN        => TriStateService::INHERIT,
        ProductSettings::CUSTOMS_CODE             => TriStateService::INHERIT,
        ProductSettings::DISABLE_DELIVERY_OPTIONS => TriStateService::INHERIT,
        ProductSettings::DROP_OFF_DELAY           => 0,
        ProductSettings::EXPORT_AGE_CHECK         => TriStateService::INHERIT,
        ProductSettings::EXPORT_INSURANCE         => TriStateService::INHERIT,
        ProductSettings::EXPORT_LARGE_FORMAT      => TriStateService::INHERIT,
        ProductSettings::EXPORT_ONLY_RECIPIENT    => TriStateService::INHERIT,
        ProductSettings::EXPORT_RETURN            => TriStateService::INHERIT,
        ProductSettings::EXPORT_SIGNATURE         => TriStateService::INHERIT,
        ProductSettings::FIT_IN_DIGITAL_STAMP     => TriStateService::INHERIT,
        ProductSettings::FIT_IN_MAILBOX           => TriStateService::INHERIT,
        ProductSettings::PACKAGE_TYPE             => DeliveryOptions::PACKAGE_TYPE_PACKAGE_NAME,
    ];
}

usesShared(
    new UsesMockWcPdkInstance([
        PdkProductRepositoryInterface::class => autowire(MockPdkProductRepository::class)->constructor([
            [
                'externalIdentifier' => '7000',
                'settings'           => defaultProductSettings(),
            ],
        ]),
    ])
);

it('saves product data correctly', function (array $postData, array $productSettings) {
    /** @var PdkProductRepositoryInterface $productRepository */
    $productRepository = Pdk::get(PdkProductRepositoryInterface::class);

    /** @var \MyParcelNL\WooCommerce\Pdk\Hooks\PdkProductSettingsHooks $hooks */
    $hooks = Pdk::get(PdkProductSettingsHooks::class);

    wpFactory(WC_Product::class)
        ->withId(7000)
        ->withSettings($productSettings)
        ->make();

    $hooks->savePdkProduct($postData, 7000);

    $savedProductSettings = $productRepository
        ->getProduct(7000)
        ->settings
        ->toArray();

    expect($savedProductSettings)->toEqual($productSettings);
})->with([
    'some settings'       => [
        'postData'        => [
            'metakeyselect'          => '#NONE#',
            'metavalue'              => '',
            'pest-countryOfOrigin'   => 'DE',
            'pest-customsCode'       => '1234',
            'pest-dropOffDelay'      => '0',
            'pest-element'           => '1',
            'pest-exportInsurance'   => '10000',
            'pest-fitInDigitalStamp' => '-1',
            'pest-fitInMailbox'      => '10',
            'pest-packageType'       => 'mailbox',
            'newproduct_cat'         => 'New category name',
            'newproduct_cat_parent'  => '-1',
            'newtag'                 => [],
            'original_post_status'   => 'publish',
            'original_post_title'    => 'WordPress Pennant',
            'original_publish'       => 'Update',
            'post_ID'                => '7000',
        ],
        'productSettings' => array_replace(defaultProductSettings(), [
            ProductSettings::COUNTRY_OF_ORIGIN        => 'DE',
            ProductSettings::CUSTOMS_CODE             => '1234',
            ProductSettings::DISABLE_DELIVERY_OPTIONS => TriStateService::INHERIT,
            ProductSettings::DROP_OFF_DELAY           => 0,
            ProductSettings::EXPORT_HIDE_SENDER       => TriStateService::INHERIT,
            ProductSettings::EXPORT_INSURANCE         => TriStateService::ENABLED,
            ProductSettings::FIT_IN_MAILBOX           => 10,
            ProductSettings::FIT_IN_DIGITAL_STAMP     => TriStateService::INHERIT,
            ProductSettings::PACKAGE_TYPE             => DeliveryOptions::PACKAGE_TYPE_MAILBOX_NAME,
        ]),
    ],
    'change all settings' => [
        'postData'        => [
            'metakeyselect'               => '#NONE#',
            'metavalue'                   => '',
            'pest-countryOfOrigin'        => 'BE',
            'pest-customsCode'            => '9422',
            'pest-disableDeliveryOptions' => '0',
            'pest-dropOffDelay'           => '3',
            'pest-exportAgeCheck'         => '1',
            'pest-exportInsurance'        => '0',
            'pest-exportLargeFormat'      => '1',
            'pest-exportOnlyRecipient'    => '0',
            'pest-exportReturn'           => '1',
            'pest-exportSignature'        => '0',
            'pest-fitInDigitalStamp'      => '-1',
            'pest-fitInMailbox'           => '12',
            'pest-packageType'            => 'digital_stamp',
            'newproduct_cat'              => 'New category name',
            'newproduct_cat_parent'       => '-1',
            'newtag'                      => [],
            'original_post_status'        => 'publish',
            'original_post_title'         => 'WordPress Pennant',
            'original_publish'            => 'Update',
            'post_ID'                     => '7000',
        ],
        'productSettings' => array_replace(defaultProductSettings(), [
            ProductSettings::COUNTRY_OF_ORIGIN        => 'BE',
            ProductSettings::CUSTOMS_CODE             => '9422',
            ProductSettings::DISABLE_DELIVERY_OPTIONS => TriStateService::DISABLED,
            ProductSettings::DROP_OFF_DELAY           => 3,
            ProductSettings::EXPORT_AGE_CHECK         => TriStateService::ENABLED,
            ProductSettings::EXPORT_HIDE_SENDER       => TriStateService::INHERIT,
            ProductSettings::EXPORT_INSURANCE         => TriStateService::DISABLED,
            ProductSettings::EXPORT_LARGE_FORMAT      => TriStateService::ENABLED,
            ProductSettings::EXPORT_ONLY_RECIPIENT    => TriStateService::DISABLED,
            ProductSettings::EXPORT_RETURN            => TriStateService::ENABLED,
            ProductSettings::EXPORT_SIGNATURE         => TriStateService::DISABLED,
            ProductSettings::FIT_IN_MAILBOX           => 12,
            ProductSettings::FIT_IN_DIGITAL_STAMP     => TriStateService::INHERIT,
            ProductSettings::PACKAGE_TYPE             => DeliveryOptions::PACKAGE_TYPE_DIGITAL_STAMP_NAME,
        ]),
    ],
]);
