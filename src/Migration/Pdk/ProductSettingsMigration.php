<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration\Pdk;

use MyParcelNL\Pdk\App\Order\Contract\PdkProductRepositoryInterface;
use MyParcelNL\Pdk\Facade\Pdk;
use WC_Product;

final class ProductSettingsMigration extends AbstractPdkMigration
{
    private const OPTION_TRANSLATIONS = [
        '_myparcel_hs_code'           => [
            'name' => 'customsCode',
        ],
        '_myparcel_country_of_origin' => [
            'name' => 'countryOfOrigin',
        ],
        '_myparcel_age_check'         => [
            'name'   => 'exportAgeCheck',
            'values' => [
                'yes' => true,
                'no'  => false,
            ],
        ],
    ];
    private const CHUNK_SIZE          = 100;
    private const SECONDS_APART       = 60;

    public function down(): void
    {
        /**
         * No need to downgrade, original data is still there.
         */
    }

    public function migrateProductSettings(): void
    {
        $nonMigratedProducts = wc_get_products([
            'limit'        => self::CHUNK_SIZE,
            'meta_key'     => Pdk::get('metaKeyMigrated'),
            'meta_compare' => "NOT LIKE %\"{$this->getVersion()}\"%",
        ]);

        if (empty($nonMigratedProducts)) {
            return;
        }

        $this->scheduleNextRun();
        $this->migrateTheseWcProducts($nonMigratedProducts);
    }

    public function up(): void
    {
        if (! function_exists('wc_get_products')) {
            $this->warning('Could not find function wc_get_products.');
            return;
        }

        $this->migrateProductSettings();
    }

    /**
     * @param  WC_Product[] $wcProducts
     *
     * @return void
     */
    private function migrateTheseWcProducts(array $wcProducts): void
    {
        /** @var PdkProductRepositoryInterface $productRepository */
        $productRepository = Pdk::get(PdkProductRepositoryInterface::class);
        $phpMemoryLimit    = ini_get('memory_limit');

        foreach ($wcProducts as $wcProduct) {
            if (! $wcProduct instanceof WC_Product) {
                continue;
            }

            $metaData = $wcProduct->get_meta_data();

            $metaKeysAndValues = array_reduce($metaData, static function ($carry, $item) {
                $item = $item->get_data();

                if (! isset($item['key'], $item['value'])) {
                    return $carry;
                }

                $carry[$item['key']] = $item['value'];

                return $carry;
            }, []);

            $product = $productRepository->getProduct($wcProduct->get_id());
            $changed = false;

            foreach (self::OPTION_TRANSLATIONS as $oldKey => $setting) {
                $settingName = $setting['name'];

                if (isset($metaKeysAndValues[$oldKey])) {
                    $value                             = isset($setting['values'])
                        ? $setting['values'][$metaKeysAndValues[$oldKey]] ?? null
                        : $metaKeysAndValues[$oldKey];
                    $product->settings->{$settingName} = $value;
                    $changed                           = true;
                }
            }

            [$product->settings->fitInMailbox, $product->settings->packageType] = $this->getPackageTypeForProduct($wcProduct);

            $productRepository->update($product);

            $this->debug(
                sprintf('Settings for product %s migrated %s', $wcProduct->get_id(), $changed ? '' : '(no data)')
            );

            $this->markObjectMigrated($wcProduct);
            $wcProduct->save();

            if (memory_get_usage() > .8 * $phpMemoryLimit) {
                return;
            }
        }
    }

    private function getPackageTypeForProduct(WC_Product $wcProduct): array
    {
        // TODO support variants!!

        /* empty the woocommerce cart */
        if (null === WC()->cart) {
            wc_load_cart();
        }
        WC()->cart->empty_cart();

        /* add product to cart */
        WC()->cart->add_to_cart($wcProduct->get_id());

        /* get available shipping methods from cart */
        $cartShippingPackages = WC()->cart->get_shipping_packages();
        // todo log nice error if this did not return an array
        $cartShippingMethods = $this->getMethodsFromPackages($cartShippingPackages);
        // todo abstraheren en in een aparte functie zetten en let op als er false uit get_option komt
        $methodToPackageType = get_option('woocommerce_myparcel_export_defaults_settings');
        $methodToPackageType = $methodToPackageType['shipping_methods_package_types'];
        $migratePackageType = 'package';
        $productFitInMailbox = 0;
        $increment = 1;


        /* loop through shipping methods to see which one(s) are connected to a myparcel packagetype */
        foreach (['digital_stamp', 'mailbox'] as $packageType) {
            if (isset($methodToPackageType[$packageType])) {
                $available = array_intersect($methodToPackageType[$packageType], $cartShippingMethods);
                if ($available) {
                    $productFitInMailbox = 1;
                    $migratePackageType = $packageType;
                    break;
                }
            }
        }
        $doingTheDo = true;
        while ($doingTheDo) {
            $doingTheDo = false;
            WC()->cart->add_to_cart($wcProduct->get_id(), 1);
            $cartShippingPackages = WC()->cart->get_shipping_packages();
            // todo log nice error if this did not return an array
            $cartShippingMethods = $this->getMethodsFromPackages($cartShippingPackages);
            foreach (['digital_stamp', 'mailbox'] as $packageType) { // todo slightly duplicate code
                if (isset($methodToPackageType[$packageType])) {
                    $available = array_intersect($methodToPackageType[$packageType], $cartShippingMethods);
                    if ($available) {
                        $doingTheDo = true;
                        $productFitInMailbox+=$increment;
                    }
                    switch (strlen((string)$productFitInMailbox)) {
                        case 3:
                            $increment = 10;
                            break;
                        case 4:
                            $increment = 100;
                            break;
                        case 5:
                            $doingTheDo = false; // we’ve had enough
                    }
                }
            }
        }
//        if (17 === $wcProduct->get_id()) {
//            echo '<textarea style="width: 100%; height: 500px;">';
//            var_dump(['product_id' => $wcProduct->get_id()]);
//            var_dump($cartShippingMethods);
//            var_dump($methodToPackageType);
//            var_dump([$productFitInMailbox, $migratePackageType,]);
//            echo '</textarea>';
//        }

        return [$productFitInMailbox,$migratePackageType,];
    }

    private function getMethodsFromPackages(array $cartShippingPackages): array {
        $cartShippingMethods = [];
        foreach(array_keys($cartShippingPackages) as $key) {
            if(($shipping_for_package = WC()->session->get('shipping_for_package_'.$key))) {
                if(isset($shipping_for_package['rates'])) {
                    // Loop through customer available shipping methods
                    foreach ( $shipping_for_package['rates'] as $rate_key => $rate ) {
                        $rate_id = $rate->id; // the shipping method rate ID (or $rate_key)
                        $method_id = $rate->method_id; // the shipping method label
                        $instance_id = $rate->instance_id; // The instance ID
                        $cost = $rate->label; // The cost
                        $label = $rate->label; // The label name
                        $taxes = $rate->taxes; // The taxes (array)
                        $cartShippingMethods[] = $rate_key;
                    }
                }
            }
        }
        return $cartShippingMethods;
    }

    private function scheduleNextRun(): void
    {
        $time = time() + self::SECONDS_APART;
        wp_schedule_single_event($time, Pdk::get('migrateAction_5_0_0_ProductSettings'));
    }
}
