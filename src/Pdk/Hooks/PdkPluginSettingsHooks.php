<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Hooks;

use MyParcelNL\Pdk\Facade\RenderService;
use MyParcelNL\Pdk\Plugin\Service\ViewServiceInterface;
use MyParcelNL\WooCommerce\Hooks\WordPressHooksInterface;

class PdkPluginSettingsHooks implements WordPressHooksInterface
{
    /**
     * @var \MyParcelNL\WooCommerce\Pdk\Service\WcViewService
     */
    private $viewService;

    /**
     * @param  \MyParcelNL\Pdk\Plugin\Service\ViewServiceInterface $viewService
     */
    public function __construct(ViewServiceInterface $viewService)
    {
        $this->viewService = $viewService;
    }

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
            __('MyParcel', 'woocommerce-myparcel'),
            __('MyParcel', 'woocommerce-myparcel'),
            'edit_pages',
            $this->viewService->getSettingsPageSlug(),
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
        $screenIds[] = sprintf('woocommerce_page_%s', $this->viewService->getSettingsPageSlug());

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
        if (isset($_GET['page']) && $_GET['page'] === $this->viewService->getSettingsPageSlug()) {
            $classes[] = 'woocommerce';
            $classes[] = 'woocommerce-page';
        }

        return $classes;
    }
}
