<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Hooks;

use MyParcelNL\Pdk\Facade\Pdk;
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

        // Add our settings page to woocommerce screens
        add_filter('woocommerce_screen_ids', [$this, 'registerSettingsScreenInWooCommerce']);
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
            Pdk::get('settingsMenuSlug'),
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
        echo RenderService::renderPluginSettings();
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
