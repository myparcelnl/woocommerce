<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Service;

use MyParcelNL\Pdk\Facade\Pdk;

class ScriptService
{
    // Our local scripts
    public const HANDLE_PDK_FRONTEND              = 'myparcelnl-pdk-frontend';
    public const HANDLE_SPLIT_ADDRESS_FIELDS      = 'myparcelnl-checkout-split-address-fields';
    public const HANDLE_CHECKOUT_DELIVERY_OPTIONS = 'myparcelnl-checkout-delivery-options';
    // External dependencies
    public const HANDLE_DELIVERY_OPTIONS = 'myparcelnl-delivery-options';
    public const HANDLE_VUE              = 'vue';
    // Scripts that are already present in WooCommerce
    public const HANDLE_WOOCOMMERCE_ADMIN = 'woocommerce_admin';
    public const HANDLE_WC_CHECKOUT       = 'wc-checkout';
    public const HANDLE_JQUERY            = 'jquery';

    /**
     * @return void
     */
    public function enqueueDeliveryOptions(): void
    {
        $version = Pdk::get('deliveryOptionsVersion');

        $this->enqueueVue('2.6.0');
        $this->enqueueScript(
            self::HANDLE_DELIVERY_OPTIONS,
            sprintf('https://unpkg.com/@myparcel/delivery-options@%s/dist/myparcel.lib.js', $version),
            [self::HANDLE_VUE],
            $version
        );
    }

    /**
     * @param  string $handle
     * @param  string $src
     * @param  array  $deps
     * @param  bool   $inFooter
     *
     * @return void
     */
    public function enqueueLocalScript(string $handle, string $src, array $deps = [], bool $inFooter = true): void
    {
        $appInfo = Pdk::getAppInfo();
        $url     = sprintf('%s/%s.%s', $appInfo['url'], $src, $this->getLocalFileExtension());

        $this->enqueueScript($handle, $url, $deps, $appInfo['version'], $inFooter);
    }

    /**
     * Enqueue a script.
     *
     * @param  string      $handle
     * @param  string      $src
     * @param  array       $deps
     * @param  string|null $version
     * @param  bool        $inFooter
     *
     * @return void
     */
    public function enqueueScript(
        string $handle,
        string $src,
        array  $deps = [],
        string $version = null,
        bool   $inFooter = true
    ): void {
        wp_enqueue_script($handle, $src, $deps, $this->getVersion($version), $inFooter);
    }

    /**
     * Enqueue a style.
     *
     * @param  string      $handle
     * @param  string      $src
     * @param  array       $deps
     * @param  string|null $version
     * @param  string      $media
     *
     * @return void
     */
    public function enqueueStyle(
        string $handle,
        string $src,
        array  $deps = [],
        string $version = null,
        string $media = 'all'
    ): void {
        wp_enqueue_style($handle, $src, $deps, $this->getVersion($version), $media);
    }

    /**
     * @param  string $version
     *
     * @return void
     */
    public function enqueueVue(string $version): void
    {
        $isVue3 = version_compare($version, '3.0.0', '>=');

        if (Pdk::isDevelopment()) {
            $url = 'https://cdn.jsdelivr.net/npm/vue@:version/dist/:filename.js';
        } else {
            $url = 'https://cdn.jsdelivr.net/npm/vue@:version/dist/:filename.min.js';
        }

        $this->enqueueScript(
            self::HANDLE_VUE,
            strtr($url, [
                ':version'  => $version,
                ':filename' => $isVue3 ? 'vue.global' : 'vue',
            ]),
            [],
            $version
        );
    }

    /**
     * @param  string $version
     *
     * @return void
     */
    public function enqueueVueDemi(string $version): void
    {
        if (Pdk::isDevelopment()) {
            $url = 'https://cdnjs.cloudflare.com/ajax/libs/vue-demi/:version/index.iife.js';
        } else {
            $url = 'https://cdnjs.cloudflare.com/ajax/libs/vue-demi/:version/index.iife.min.js';
        }

        $this->enqueueScript(
            'vue-demi',
            strtr($url, [':version' => $version]),
            [self::HANDLE_VUE],
            $version
        );
    }

    /**
     * @return array
     */
    public function getEsmHandles(): array
    {
        // TODO: support esm in development
        return [];
    }

    /**
     * @return string
     */
    private function getLocalFileExtension(): string
    {
        // TODO: support esm in development
        return 'iife.js';
    }

    /**
     * @param  null|string $version
     *
     * @return null|string
     */
    private function getVersion(?string $version): ?string
    {
        return $version ?? (Pdk::isProduction() ? Pdk::getAppInfo()['version'] : null);
    }
}
