<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Hooks;

use MyParcelNL\Pdk\App\Order\Contract\PdkProductRepositoryInterface;
use MyParcelNL\Pdk\Base\Support\Arr;
use MyParcelNL\Pdk\Base\Support\Collection;
use MyParcelNL\Pdk\Facade\Frontend;
use MyParcelNL\Pdk\Facade\Logger;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Sdk\src\Support\Str;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;
use Throwable;
use WC_Product_Variation;
use WP_Post;

final class PdkProductSettingsHooks implements WordPressHooksInterface
{
    public function apply(): void
    {
        // Render custom tab in product settings box
        add_filter('woocommerce_product_data_tabs', [$this, 'registerProductSettingsTab'], 99);

        // Render pdk product settings in above custom tab
        add_action('woocommerce_product_data_panels', [$this, 'renderPdkProductSettings']);

        // Render child product settings in above custom tab
        add_action(
            'woocommerce_product_after_variable_attributes',
            [$this, 'renderPdkProductSettingsForVariant'],
            99,
            3
        );

        // Save pdk product settings
        add_action('woocommerce_process_product_meta', [$this, 'handleSaveProduct']);

        add_action('woocommerce_save_product_variation', [$this, 'handleSaveProduct']);
    }

    /**
     * @param  int $productId
     *
     * @return void
     */
    public function handleSaveProduct(int $productId): void
    {
        $postData = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

        try {
            $this->savePdkProduct($postData, $productId);
        } catch (Throwable $e) {
            Logger::error('Failed to save product settings.', [
                'id'        => $productId,
                'exception' => $e,
                'postData'  => $postData,
            ]);
        }
    }

    /**
     * @param  array $tabs
     *
     * @return array
     */
    public function registerProductSettingsTab(array $tabs): array
    {
        $appInfo    = Pdk::getAppInfo();
        $pluginName = $appInfo->name;
        $productId  = get_the_ID();

        $tabs[$pluginName] = [
            'title'  => $pluginName,
            'label'  => $appInfo->title,
            'target' => "{$pluginName}_product_data_$productId",
            'class'  => ['show_if_simple', 'show_if_variable', 'show_if_grouped', 'show_if_external'],
        ];

        return $tabs;
    }

    /**
     * @return void
     */
    public function renderPdkProductSettings(): void
    {
        /** @var PdkProductRepositoryInterface $productRepository */
        $productRepository = Pdk::get(PdkProductRepositoryInterface::class);
        $product           = $productRepository->getProduct(get_the_ID());

        echo Frontend::renderProductSettings($product);
    }

    /**
     * @param  mixed    $_
     * @param  mixed    $__
     * @param  \WP_Post $post
     *
     * @return void
     * @noinspection PhpUnusedParameterInspection
     */
    public function renderPdkProductSettingsForVariant($_, $__, WP_Post $post): void
    {
        /** @var PdkProductRepositoryInterface $productRepository */
        $productRepository = Pdk::get(PdkProductRepositoryInterface::class);
        $variation         = new WC_Product_Variation($post->ID);
        $product           = $productRepository->getProduct($variation);

        echo Frontend::renderChildProductSettings($product);
    }

    /**
     * @param      $post
     * @param  int $productId
     *
     * @return void
     * @note public for testing purposes. We can't replace $_POST in the test.
     * @throws \MyParcelNL\Pdk\Base\Exception\InvalidCastException
     */
    public function savePdkProduct($post, int $productId): void
    {
        $appInfo = Pdk::getAppInfo();

        $productSettingKeys = Arr::where($post, static function ($_, string $key) use ($appInfo, $productId) {
            return Str::startsWith($key, "$appInfo->name-childProductSettings--$productId-");
        });

        if (empty($productSettingKeys)) {
            $productSettingKeys = Arr::where($post, static function ($_, string $key) use ($appInfo) {
                return Str::startsWith($key, "$appInfo->name-");
            });
        }

        if (empty($productSettingKeys)) {
            return;
        }

        $values = (new Collection($productSettingKeys))
            ->mapWithKeys(static function ($value, string $key) {
                $keyParts = explode('-', $key);
                return [
                    end($keyParts) => $value,
                ];
            })
            ->toArray();

        /** @var \MyParcelNL\WooCommerce\Pdk\Product\Repository\WcPdkProductRepository $productRepository */
        $productRepository = Pdk::get(PdkProductRepositoryInterface::class);
        $product           = $productRepository->getProduct($productId);

        $product->settings->fill($values);

        $productRepository->update($product);
    }
}
