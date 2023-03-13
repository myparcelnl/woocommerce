<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration\Pdk;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Product\Contract\ProductRepositoryInterface;
use MyParcelNL\WooCommerce\Migration\Contract\MigrationInterface;
use WC_Product;

class ProductSettingsMigration implements MigrationInterface
{
    private const OPTION_TRANSLATIONS = [
        '_myparcel_hs_code'           => ['name' => 'customsCode'],
        '_myparcel_country_of_origin' => ['name' => 'countryOfOrigin'],
        '_myparcel_age_check'         => ['name' => 'exportAgeCheck', 'values' => ['no' => 0, 'yes' => 1]],
    ];
    private const CHUNK_SIZE          = 10;
    private const SECONDS_APART       = 30;

    public function down(): void
    {
        /**
         * No need to downgrade, original data is still there.
         */
    }

    public function getVersion(): string
    {
        return '5.0.0';
    }

    public function migrateProductSettings(): void
    {
        $wcProducts = wc_get_products([
            'limit'        => self::CHUNK_SIZE,
            'meta_key'     => 'myparcelnl_pdk_migrated',
            'meta_compare' => 'NOT EXISTS',
        ]);

        if (empty($wcProducts)) {
            return;
        }

        $this->scheduleNextRun();
        $this->migrateTheseWcProducts($wcProducts);
    }

    public function up(): void
    {
        if (! function_exists('wc_get_products')) {
            return;
        }

        $this->migrateProductSettings();
    }

    private function migrateTheseWcProducts($wcProducts): void
    {
        /** @var \MyParcelNL\Pdk\Product\Contract\ProductRepositoryInterface $productRepository */
        $productRepository = Pdk::get(ProductRepositoryInterface::class);

        foreach ($wcProducts as $wcProduct) {
            if (! $wcProduct instanceof WC_Product) {
                continue;
            }

            $metaData = $wcProduct->get_meta_data();
            $metaAsKV = array_reduce($metaData, static function ($carry, $item) {
                $item = $item->get_data();
                if (! isset($item['key'], $item['value'])) {
                    return $carry;
                }
                $carry[$item['key']] = $item['value'];

                return $carry;
            }, []);
            $product  = $productRepository->getProduct($wcProduct->get_id());
            $changed  = false;

            foreach (self::OPTION_TRANSLATIONS as $oldKey => $setting) {
                $settingName = $setting['name'];

                if (isset($metaAsKV[$oldKey])) {
                    $value                             = isset($setting['values'])
                        ? $setting['values'][$metaAsKV[$oldKey]] ?? null
                        : $metaAsKV[$oldKey];
                    $product->settings->{$settingName} = $value;
                    $changed                           = true;
                }
            }

            if ($changed) {
                $productRepository->update($product);
            }

            (wc_get_logger())->debug(
                sprintf(
                    'Settings for product %s migrated %s',
                    $wcProduct->get_id(),
                    $changed ? '' : '(no data)'
                ) . PHP_EOL,
                ['source' => 'wc-myparcel']
            );

            $wcProduct->update_meta_data('myparcelnl_pdk_migrated', true);
            $wcProduct->save();
        }
    }

    private function scheduleNextRun(): void
    {
        $time = time() + self::SECONDS_APART;
        wp_schedule_single_event($time, 'myparcelnl_migrate_product_settings_to_pdk_5_0_0', []);
    }
}
