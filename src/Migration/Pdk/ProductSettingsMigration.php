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
use WC_Data;
use WC_Meta_Data;
use WC_Product;

final class ProductSettingsMigration extends AbstractPdkMigration
{
    public const  PACKAGE_TYPES                     = [
        DeliveryOptions::PACKAGE_TYPE_DIGITAL_STAMP_NAME,
        DeliveryOptions::PACKAGE_TYPE_MAILBOX_NAME,
        DeliveryOptions::PACKAGE_TYPE_LETTER_NAME,
    ];
    private const CHUNK_SIZE                        = 100;
    private const LEGACY_META_KEY_HS_CODE           = '_myparcel_hs_code';
    private const LEGACY_META_KEY_COUNTRY           = '_myparcel_country_of_origin';
    private const LEGACY_META_KEY_AGE_CHECK         = '_myparcel_age_check';
    private const LEGACY_META_KEY_HS_VARIATION      = '_myparcel_hs_code_variation';
    private const LEGACY_META_KEY_COUNTRY_VARIATION = '_myparcel_country_of_origin_variation';
    private const PRODUCT_SETTINGS_MAP              = [
        self::LEGACY_META_KEY_HS_CODE           => CustomsSettings::CUSTOMS_CODE,
        self::LEGACY_META_KEY_COUNTRY           => CustomsSettings::COUNTRY_OF_ORIGIN,
        self::LEGACY_META_KEY_AGE_CHECK         => ProductSettings::EXPORT_AGE_CHECK,
        self::LEGACY_META_KEY_HS_VARIATION      => CustomsSettings::CUSTOMS_CODE,
        self::LEGACY_META_KEY_COUNTRY_VARIATION => CustomsSettings::COUNTRY_OF_ORIGIN,
    ];
    private const SECONDS_APART                     = 5;

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

        $this->pdkProductRepository->update($pdkProduct);

        $migrationMeta = $this->getMigrationMeta($wcProduct);

        if ($migrationMeta) {
            update_post_meta($wcProduct->get_id(), Pdk::get('metaKeyMigrated'), $migrationMeta);
        }

        $this->debug(sprintf('Settings for product %s migrated', $wcProduct->get_id()));
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
