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
    private const CHUNK_SIZE          = 10;
    private const SECONDS_APART       = 30;

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

            if ($changed) {
                $productRepository->update($product);
            }

            $this->debug(
                sprintf('Settings for product %s migrated %s', $wcProduct->get_id(), $changed ? '' : '(no data)')
            );

            $this->markObjectMigrated($wcProduct);
        }
    }

    private function scheduleNextRun(): void
    {
        $time = time() + self::SECONDS_APART;
        wp_schedule_single_event($time, Pdk::get('migrateAction_5_0_0_ProductSettings'));
    }
}
