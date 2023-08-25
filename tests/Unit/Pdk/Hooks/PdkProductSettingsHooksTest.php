<?php
/** @noinspection PhpUnhandledExceptionInspection,StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Unit\Pdk\Hooks;

use MyParcelNL\Pdk\App\Order\Contract\PdkProductRepositoryInterface;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Model\AbstractSettingsModel;
use MyParcelNL\Pdk\Settings\Model\ProductSettings;
use MyParcelNL\Pdk\Shipment\Model\DeliveryOptions;
use MyParcelNL\Pdk\Tests\Bootstrap\MockPdkProductRepository;
use MyParcelNL\WooCommerce\Pdk\Hooks\PdkProductSettingsHooks;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use function DI\autowire;
use function MyParcelNL\Pdk\Tests\usesShared;

function defaultProductSettings(): array
{
    return [
        'id'                                      => ProductSettings::ID,
        ProductSettings::COUNTRY_OF_ORIGIN        => 'NL',
        ProductSettings::CUSTOMS_CODE             => '0000',
        ProductSettings::DISABLE_DELIVERY_OPTIONS => false,
        ProductSettings::DROP_OFF_DELAY           => 0,
        ProductSettings::EXPORT_AGE_CHECK         => AbstractSettingsModel::TRISTATE_VALUE_DEFAULT,
        ProductSettings::EXPORT_INSURANCE         => AbstractSettingsModel::TRISTATE_VALUE_DEFAULT,
        ProductSettings::EXPORT_LARGE_FORMAT      => AbstractSettingsModel::TRISTATE_VALUE_DEFAULT,
        ProductSettings::EXPORT_ONLY_RECIPIENT    => AbstractSettingsModel::TRISTATE_VALUE_DEFAULT,
        ProductSettings::EXPORT_RETURN            => AbstractSettingsModel::TRISTATE_VALUE_DEFAULT,
        ProductSettings::EXPORT_SIGNATURE         => AbstractSettingsModel::TRISTATE_VALUE_DEFAULT,
        ProductSettings::FIT_IN_MAILBOX           => 0,
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

    $hooks->savePdkProduct($postData, 7000);

    $savedProductSettings = $productRepository
        ->getProduct(7000)
        ->settings
        ->toArray();

    expect($savedProductSettings)->toEqual($productSettings);
})->with([
    'some settings'       => [
        'postData'        => [
            'metakeyselect'         => '#NONE#',
            'metavalue'             => '',
            'pest-countryOfOrigin'  => 'DE',
            'pest-customsCode'      => '1234',
            'pest-dropOffDelay'     => '0',
            'pest-element'          => '1',
            'pest-exportInsurance'  => 'true',
            'pest-fitInMailbox'     => '10',
            'pest-packageType'      => 'mailbox',
            'newproduct_cat'        => 'New category name',
            'newproduct_cat_parent' => '-1',
            'newtag'                => [],
            'original_post_status'  => 'publish',
            'original_post_title'   => 'WordPress Pennant',
            'original_publish'      => 'Update',
            'post_ID'               => '7000',
        ],
        'productSettings' => array_replace(defaultProductSettings(), [
            ProductSettings::COUNTRY_OF_ORIGIN  => 'DE',
            ProductSettings::CUSTOMS_CODE       => '1234',
            ProductSettings::DROP_OFF_DELAY     => 0,
            ProductSettings::EXPORT_HIDE_SENDER => AbstractSettingsModel::TRISTATE_VALUE_DEFAULT,
            ProductSettings::EXPORT_INSURANCE   => AbstractSettingsModel::TRISTATE_VALUE_ENABLED,
            ProductSettings::FIT_IN_MAILBOX     => 10,
            ProductSettings::PACKAGE_TYPE       => DeliveryOptions::PACKAGE_TYPE_MAILBOX_NAME,
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
            ProductSettings::DISABLE_DELIVERY_OPTIONS => AbstractSettingsModel::TRISTATE_VALUE_DISABLED,
            ProductSettings::DROP_OFF_DELAY           => 3,
            ProductSettings::EXPORT_AGE_CHECK         => AbstractSettingsModel::TRISTATE_VALUE_ENABLED,
            ProductSettings::EXPORT_HIDE_SENDER       => AbstractSettingsModel::TRISTATE_VALUE_DEFAULT,
            ProductSettings::EXPORT_INSURANCE         => AbstractSettingsModel::TRISTATE_VALUE_DISABLED,
            ProductSettings::EXPORT_LARGE_FORMAT      => AbstractSettingsModel::TRISTATE_VALUE_ENABLED,
            ProductSettings::EXPORT_ONLY_RECIPIENT    => AbstractSettingsModel::TRISTATE_VALUE_DISABLED,
            ProductSettings::EXPORT_RETURN            => AbstractSettingsModel::TRISTATE_VALUE_ENABLED,
            ProductSettings::EXPORT_SIGNATURE         => AbstractSettingsModel::TRISTATE_VALUE_DISABLED,
            ProductSettings::FIT_IN_MAILBOX           => 12,
            ProductSettings::PACKAGE_TYPE             => DeliveryOptions::PACKAGE_TYPE_DIGITAL_STAMP_NAME,
        ]),
    ],
]);
