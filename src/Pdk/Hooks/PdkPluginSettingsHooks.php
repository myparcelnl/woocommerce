<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Hooks;

use MyParcelNL;
use MyParcelNL\Pdk\Facade\RenderService;
use MyParcelNL\WooCommerce\Hooks\WordPressHooksInterface;

class PdkPluginSettingsHooks implements WordPressHooksInterface
{
    public function apply(): void
    {
        // Add MyParcel menu item
        add_action('admin_menu', [$this, 'registerMenuItem']);

        // Add WooCommerce body classes to plugin settings page
        add_filter('body_class', [$this, 'setWooCommerceBodyClasses']);
    }

    /**
     * @return void
     */
    public function registerMenuItem(): void
    {
        add_submenu_page(
            'woocommerce',
            __('MyParcel', 'woocommerce-myparcel'),
            __('MyParcel', 'woocommerce-myparcel'),
            'edit_pages',
            MyParcelNL::SETTINGS_MENU_SLUG,
            [$this, 'renderPdkPluginSettings']
        );
    }

    /**
     * @return void
     */
    public function renderPdkPluginSettings(): void
    {
        echo RenderService::renderPluginSettings();
    }

    /**
     * @param  array $classes
     *
     * @return array
     */
    public function setWooCommerceBodyClasses(array $classes): array
    {
        if (isset($_GET['page']) && $_GET['page'] === MyParcelNL::SETTINGS_MENU_SLUG) {
            $classes[] = 'woocommerce';
            $classes[] = 'woocommerce-page';
        }

        return $classes;
    }
}
