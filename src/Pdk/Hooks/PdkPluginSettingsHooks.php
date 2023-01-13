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
}
