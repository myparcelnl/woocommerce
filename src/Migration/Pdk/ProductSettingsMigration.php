<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration\Pdk;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Product\Repository\AbstractProductRepository;
use MyParcelNL\WooCommerce\Migration\AbstractUpgradeMigration;

class ProductSettingsMigration extends AbstractUpgradeMigration
{
    private const OPTION_TRANSLATIONS = [
        '_myparcel_hs_code'           => ['name' => 'customsCode'],
        '_myparcel_country_of_origin' => ['name' => 'countryOfOrigin'],
        '_myparcel_age_check'         => ['name' => 'exportAgeCheck', 'values' => ['no' => false, 'yes' => true]],
    ];
    private const CHUNK_SIZE          = 10;
    private const SECONDS_APART       = 30;

    public function run(): void
    {
        $this->migrateProductSettings();
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

    private function migrateTheseWcProducts($wcProducts): void
    {
        /** @var \MyParcelNL\WooCommerce\Pdk\Product\Repository\PdkProductRepository $productRepository */
        $productRepository = Pdk::get(AbstractProductRepository::class);

        foreach ($wcProducts as $wcProduct) {
            if (! $wcProduct instanceof \WC_Product) {
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
                $productRepository->store($product);
            }

            (wc_get_logger())->debug(
                sprintf('Settings for product %s migrated %s', $wcProduct->get_id(), $changed ? '' : '(no data)') . PHP_EOL,
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
