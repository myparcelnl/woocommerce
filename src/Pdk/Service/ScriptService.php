<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Service;

use MyParcelNL\Pdk\Facade\Pdk;

class ScriptService
{
    public const HANDLE_DELIVERY_OPTIONS = 'myparcelnl-delivery-options';
    public const HANDLE_VUE              = 'vue';
    // Scripts that are already present in WooCommerce
    public const HANDLE_WC_CHECKOUT = 'wc-checkout';
    public const HANDLE_JQUERY      = 'jquery';

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
        $version = $version ?? (Pdk::isProduction() ? Pdk::get('pluginVersion') : null);

        wp_enqueue_script($handle, $src, $deps, $version, $inFooter);
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
            ]), [],
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
}
