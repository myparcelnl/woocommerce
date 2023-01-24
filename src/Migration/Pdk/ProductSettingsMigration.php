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

    public function run(): void
    {
        /** @var \MyParcelNL\WooCommerce\Pdk\Product\Repository\PdkProductRepository $productRepository */
        $productRepository = Pdk::get(AbstractProductRepository::class);
        $wcProducts        = wc_get_products(['limit' => -1]);

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
                $settingName  = $setting['name'];
                $settingValue = isset($setting['values']) ? $setting['values'][$metaAsKV[$oldKey]] ?? null : $metaAsKV[$oldKey];
                if (isset($metaAsKV[$oldKey])) {
                    $product->settings->{$settingName} = $settingValue;
                    $changed                           = true;
                }
            }

            if ($changed) {
                $productRepository->store($product);
            }
        }
    }
}
