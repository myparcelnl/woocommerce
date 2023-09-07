<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration\Pdk;

use Exception;
use MyParcelNL\Pdk\App\Order\Contract\PdkProductRepositoryInterface;
use MyParcelNL\Pdk\Base\Contract\CronServiceInterface;
use MyParcelNL\Pdk\Base\Support\Arr;
use MyParcelNL\Pdk\Base\Support\Collection;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Model\CustomsSettings;
use MyParcelNL\Pdk\Settings\Model\ProductSettings;
use MyParcelNL\Pdk\Shipment\Model\DeliveryOptions;
use MyParcelNL\Sdk\src\Support\Str;
use WC_Data;
use WC_Meta_Data;
use WC_Product;

final class ProductSettingsMigration extends AbstractPdkMigration
{
    public const  PACKAGE_TYPES                          = [
        DeliveryOptions::PACKAGE_TYPE_DIGITAL_STAMP_NAME,
        DeliveryOptions::PACKAGE_TYPE_MAILBOX_NAME,
        DeliveryOptions::PACKAGE_TYPE_LETTER_NAME,
    ];
    public const  LEGACY_OPTION_EXPORT_DEFAULTS_SETTINGS = 'woocommerce_myparcel_export_defaults_settings';
    private const CHUNK_SIZE                             = 100;
    private const FIT_IN_PACKAGE_AMOUNT_LIMIT            = 1000;
    private const LEGACY_META_KEY_HS_CODE                = '_myparcel_hs_code';
    private const LEGACY_META_KEY_COUNTRY                = '_myparcel_country_of_origin';
    private const LEGACY_META_KEY_AGE_CHECK              = '_myparcel_age_check';
    private const LEGACY_META_KEY_HS_VARIATION           = '_myparcel_hs_code_variation';
    private const LEGACY_META_KEY_COUNTRY_VARIATION      = '_myparcel_country_of_origin_variation';
    private const PRODUCT_SETTINGS_MAP                   = [
        self::LEGACY_META_KEY_HS_CODE           => CustomsSettings::CUSTOMS_CODE,
        self::LEGACY_META_KEY_COUNTRY           => CustomsSettings::COUNTRY_OF_ORIGIN,
        self::LEGACY_META_KEY_AGE_CHECK         => ProductSettings::EXPORT_AGE_CHECK,
        self::LEGACY_META_KEY_HS_VARIATION      => CustomsSettings::CUSTOMS_CODE,
        self::LEGACY_META_KEY_COUNTRY_VARIATION => CustomsSettings::COUNTRY_OF_ORIGIN,
    ];
    private const SECONDS_APART                          = 5;

    /**
     * @var \MyParcelNL\Pdk\Base\Contract\CronServiceInterface
     */
    private $cronService;

    /**
     * @var \MyParcelNL\Pdk\App\Order\Contract\PdkProductRepositoryInterface
     */
    private $pdkProductRepository;

    /**
     * @param  \MyParcelNL\Pdk\Base\Contract\CronServiceInterface               $cronService
     * @param  \MyParcelNL\Pdk\App\Order\Contract\PdkProductRepositoryInterface $pdkProductRepository
     */
    public function __construct(PdkProductRepositoryInterface $pdkProductRepository, CronServiceInterface $cronService)
    {
        $this->pdkProductRepository = $pdkProductRepository;
        $this->cronService          = $cronService;
    }

    public function down(): void
    {
        /**
         * No need to downgrade, original data is still there.
         */
    }

    /**
     * @param  array $data
     *
     * @return void
     * @throws \Throwable
     */
    public function migrateProductSettings(array $data): void
    {
        $productIds = $data['productIds'] ?? [];

        $this->debug(
            sprintf(
                'Start migration for products %d..%d (chunk %d/%d)',
                Arr::first($productIds),
                Arr::last($productIds),
                $data['chunk'] ?? null,
                $data['lastChunk'] ?? null
            )
        );

        $allProducts = array_reduce($productIds, static function (array $carry, int $productId) {
            $product = new WC_Product($productId);

            $carry[] = $product;

            foreach ($product->get_children() as $childId) {
                $carry[] = wc_get_product($childId);
            }

            return $carry;
        }, []);

        foreach ($allProducts as $product) {
            $this->migrateProduct($product);
        }
    }

    public function up(): void
    {
        try {
            $productIds = $this->getAllProductIds();
        } catch (Exception $e) {
            $this->debug(sprintf('Failed to fetch products. Aborting migration. Error: %s', $e->getMessage()));

            return;
        }

        $chunks    = array_chunk($productIds, self::CHUNK_SIZE);
        $lastChunk = count($chunks);

        foreach ($chunks as $index => $chunk) {
            $time = time() + $index * self::SECONDS_APART;

            $chunkContext = [
                'productIds' => $chunk,
                'chunk'      => $index + 1,
                'lastChunk'  => $lastChunk,
            ];

            $this->cronService->schedule(Pdk::get('migrateAction_5_0_0_ProductSettings'), $time, $chunkContext);

            $this->debug('Scheduled migration for products', [
                'time'  => $time,
                'chunk' => $chunkContext,
            ]);
        }
    }

    /**
     * @param  array       $cartShippingMethods
     * @param  \WC_Product $wcProduct
     *
     * @return array
     */
    private function addFlatRateShippingClass(array $cartShippingMethods, WC_Product $wcProduct): array
    {
        $shippingClassId = $wcProduct->get_shipping_class_id();

        if ($shippingClassId) {
            foreach ($cartShippingMethods as $cartShippingMethod) {
                if (! Str::startsWith($cartShippingMethod, 'flat_rate:')) {
                    continue;
                }

                $cartShippingMethods[] = "flat_rate:$shippingClassId";

                break;
            }
        }

        return $cartShippingMethods;
    }

    /**
     * @param  string      $packageType
     * @param  \WC_Product $wcProduct
     * @param  int         $amountInPackageType
     * @param  int         $increment
     * @param  int         $previousIncrement
     *
     * @return int
     * @throws \Exception
     */
    private function calculateAmountFitInPackageType(
        string     $packageType,
        WC_Product $wcProduct,
        int        $amountInPackageType = 0,
        int        $increment = 1,
        int        $previousIncrement = 0
    ): int {
        if ($amountInPackageType > self::FIT_IN_PACKAGE_AMOUNT_LIMIT) {
            return self::FIT_IN_PACKAGE_AMOUNT_LIMIT;
        }

        WC()->cart->add_to_cart($wcProduct->get_id(), $increment);

        $fitsInPackageType = array_intersect(
            $this->getShippingMethodsForPackageType($packageType),
            $this->getCartShippingMethods($wcProduct)
        );

        if (! $fitsInPackageType && $previousIncrement > 1) {
            WC()->cart->empty_cart();
            WC()->cart->add_to_cart($wcProduct->get_id(), $amountInPackageType - $previousIncrement);

            return $this->calculateAmountFitInPackageType(
                $packageType,
                $wcProduct,
                $amountInPackageType,
                1,
                $increment
            );
        }

        if ($fitsInPackageType) {
            $amountInPackageType += $increment;

            return $this->calculateAmountFitInPackageType(
                $packageType,
                $wcProduct,
                $amountInPackageType,
                $increment * 2,
                $increment
            );
        }

        WC()->cart->empty_cart();

        return $amountInPackageType;
    }

    /**
     * @return array
     */
    private function getAllProductIds(): array
    {
        $allProducts = wc_get_products([
            'meta_key'     => Pdk::get('metaKeyMigrated'),
            'meta_value'   => sprintf('"%s"', $this->getVersion()),
            'meta_compare' => 'NOT LIKE',
        ]);

        return array_map(static function (WC_Product $product) {
            return $product->get_id();
        }, $allProducts);
    }

    /**
     * @param  \WC_Product $product
     *
     * @return array
     */
    private function getCartShippingMethods(WC_Product $product): array
    {
        $cartShippingPackages = WC()->cart->get_shipping_packages();

        return $this->addFlatRateShippingClass($this->getMethodsFromPackages($cartShippingPackages), $product);
    }

    /**
     * @param  array $cartShippingMethods
     *
     * @return string
     */
    private function getMatchingPackageType(array $cartShippingMethods): string
    {
        foreach (self::PACKAGE_TYPES as $packageType) {
            if (! array_intersect($this->getShippingMethodsForPackageType($packageType), $cartShippingMethods)) {
                continue;
            }

            return $packageType;
        }

        return 'package';
    }

    /**
     * @param  \WC_Data $data
     *
     * @return \MyParcelNL\Pdk\Base\Support\Collection
     */
    private function getMetaData(WC_Data $data): Collection
    {
        return new Collection(
            array_map(static function (WC_Meta_Data $entry) {
                return $entry->get_data();
            }, $data->get_meta_data())
        );
    }

    /**
     * @param  array $cartShippingPackages
     *
     * @return array
     */
    private function getMethodsFromPackages(array $cartShippingPackages): array
    {
        $cartShippingMethods = [];

        foreach (array_keys($cartShippingPackages) as $key) {
            $shippingForPackage = WC()->session->get("shipping_for_package_$key");

            $cartShippingMethods += array_keys($shippingForPackage['rates'] ?? []);
        }

        return $cartShippingMethods;
    }

    /**
     * @throws \Exception
     */
    private function getSettingsForPackageType(WC_Product $wcProduct): array
    {
        if (null === WC()->cart) {
            wc_load_cart();
        }

        WC()->cart->empty_cart();
        WC()->cart->add_to_cart($wcProduct->get_id());

        return [
            ProductSettings::FIT_IN_MAILBOX       => $this->calculateAmountFitInPackageType(
                DeliveryOptions::PACKAGE_TYPE_MAILBOX_NAME,
                $wcProduct
            ),
            ProductSettings::FIT_IN_DIGITAL_STAMP => $this->calculateAmountFitInPackageType(
                DeliveryOptions::PACKAGE_TYPE_DIGITAL_STAMP_NAME,
                $wcProduct
            ),
            ProductSettings::PACKAGE_TYPE         => $this->getMatchingPackageType(
                $this->getCartShippingMethods($wcProduct)
            ),
        ];
    }

    /**
     * @param  string $packageType
     *
     * @return null|array
     */
    private function getShippingMethodsForPackageType(string $packageType): ?array
    {
        $legacySettings = get_option(self::LEGACY_OPTION_EXPORT_DEFAULTS_SETTINGS) ?: [];

        $shippingMethods = $legacySettings['shipping_methods_package_types'] ?? [];

        return $shippingMethods[$packageType] ?? [];
    }

    /**
     * @param  \WC_Product $wcProduct
     *
     * @return void
     * @throws \Exception
     */
    private function migrateProduct(WC_Product $wcProduct): void
    {
        $meta       = $this->getMetaData($wcProduct);
        $pdkProduct = $this->pdkProductRepository->getProduct($wcProduct->get_id());

        foreach (self::PRODUCT_SETTINGS_MAP as $oldKey => $newKey) {
            $metaData = $meta->firstWhere('key', $oldKey);

            if (! $metaData) {
                continue;
            }

            $pdkProduct->settings->setAttribute($newKey, $this->normalizeValue($metaData['value']));
        }

        $pdkProduct->settings->fill($this->getSettingsForPackageType($wcProduct));

        $this->pdkProductRepository->update($pdkProduct);

        $this->debug(sprintf('Settings for product %s migrated', $wcProduct->get_id()));
        $this->markMigrated($wcProduct);
    }

    /**
     * @param $value
     *
     * @return mixed
     */
    private function normalizeValue($value)
    {
        switch ($value) {
            case 'yes':
                return true;
            case 'no':
                return false;
            default:
                return $value;
        }
    }
}
