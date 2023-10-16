<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Hooks;

use MyParcelNL\Pdk\Facade\Frontend;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;

class PdkPluginSettingsHooks implements WordPressHooksInterface
{
    public function apply(): void
    {
        // Add MyParcel menu item
        add_action('admin_menu', [$this, 'registerMenuItem']);

        // Add WooCommerce body classes to plugin settings page
        add_filter('body_class', [$this, 'setWooCommerceBodyClasses']);

        // Add our settings page to woocommerce screens
        add_filter('woocommerce_screen_ids', [$this, 'registerSettingsScreenInWooCommerce']);

        // Mark our page as connected to WooCommerce to render the Woo admin header and styles
        add_filter('woocommerce_navigation_is_connected_page', [$this, 'connectPageToWooCommerce'], 99, 1);
    }

    /**
     * @param  bool $isConnected
     *
     * @return bool
     */
    public function connectPageToWooCommerce(bool $isConnected): bool
    {
        $page = $_GET['page'] ?? '';

        return Pdk::get('settingsMenuSlugShort') === $page ? true : $isConnected;
    }

    /**
     * @return void
     */
    public function registerMenuItem(): void
    {
        add_submenu_page(
            'woocommerce',
            Pdk::get('settingsPageTitle'),
            Pdk::get('settingsMenuTitle'),
            'edit_pages',
            Pdk::get('settingsMenuSlugShort'),
            [$this, 'renderPdkPluginSettings']
        );
    }

    /**
     * @param  array $screenIds
     *
     * @return array
     */
    public function registerSettingsScreenInWooCommerce(array $screenIds): array
    {
        $screenIds[] = Pdk::get('settingsMenuSlug');

        return $screenIds;
    }

    /**
     * @return void
     */
    public function renderPdkPluginSettings(): void
    {
        echo Frontend::renderPluginSettings();
    }

    /**
     * @param  array $classes
     *
     * @return array
     */
    public function setWooCommerceBodyClasses(array $classes): array
    {
        if (isset($_GET['page']) && $_GET['page'] === Pdk::get('settingsMenuSlug')) {
            $classes[] = 'woocommerce';
            $classes[] = 'woocommerce-page';
        }

        return $classes;
    }
}
