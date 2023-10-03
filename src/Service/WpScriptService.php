<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Service;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Frontend\Service\ScriptService;

class WpScriptService extends ScriptService
{
    // Our local scripts
    public const HANDLE_PDK_ADMIN                 = 'myparcelnl-pdk-admin';
    public const HANDLE_CHECKOUT_CORE             = 'myparcelnl-checkout-core';
    public const HANDLE_SEPARATE_ADDRESS_FIELDS   = 'myparcelnl-checkout-separate-address-fields';
    public const HANDLE_TAX_FIELDS                = 'myparcelnl-checkout-tax-fields';
    public const HANDLE_CHECKOUT_DELIVERY_OPTIONS = 'myparcelnl-checkout-delivery-options';
    // External dependencies
    public const HANDLE_DELIVERY_OPTIONS = 'myparcelnl-delivery-options';
    public const HANDLE_VUE              = 'vue';
    public const HANDLE_VUE_DEMI         = 'vue-demi';
    // Scripts that are already present in WooCommerce
    public const HANDLE_WOOCOMMERCE_ADMIN = 'woocommerce_admin';
    public const HANDLE_WC_CHECKOUT       = 'wc-checkout';
    public const HANDLE_JQUERY            = 'jquery';

    /**
     * @return void
     */
    public function enqueueDeliveryOptions(): void
    {
        $this->enqueueVue(Pdk::get('deliveryOptionsVueVersion'));
        $this->enqueueLocalScript(self::HANDLE_DELIVERY_OPTIONS, 'tmp/myparcel.lib.js', [self::HANDLE_VUE]);
    }

    /**`
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
        $url     = sprintf('%s/%s.%s', $appInfo->url, $src, $this->getLocalFileExtension());

        $this->enqueueScript($handle, $url, $deps, $appInfo->version, $inFooter);
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
        $appInfo = Pdk::getAppInfo();
        $url     = sprintf('%s/%s', $appInfo->url, $src);

        wp_enqueue_style($handle, $url, $deps, $this->getVersion($version), $media);
    }

    /**
     * @param  string $version
     *
     * @return void
     */
    public function enqueueVue(string $version): void
    {
        $isVue3   = version_compare($version, '3.0.0', '>=');
        $file     = $isVue3 ? 'vue.global' : 'vue';
        $filename = Pdk::isDevelopment() ? "$file.js" : "$file.min.js";

        $this->enqueueScript(self::HANDLE_VUE, $this->createCdnUrl('vue', $version, $filename));
    }

    /**
     * @param  string $version
     *
     * @return void
     */
    public function enqueueVueDemi(string $version): void
    {
        $filename = Pdk::isDevelopment() ? 'index.iife.js' : 'index.iife.min.js';

        $this->enqueueScript(
            self::HANDLE_VUE_DEMI,
            $this->createCdnUrl('vue-demi', $version, $filename),
            [self::HANDLE_VUE]
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
