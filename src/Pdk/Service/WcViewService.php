<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Service;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Frontend\Service\AbstractViewService;

class WcViewService extends AbstractViewService
{
    /**
     * The order received page has the same page id as the checkout so `is_checkout()` returns true on both.
     *
     * @return bool
     */
    public function isCheckoutPage(): bool
    {
        return is_checkout() && ! is_order_received_page();
    }

    /**
     * @return bool
     */
    public function isChildProductPage(): bool
    {
        return isset($_POST['action']) && 'woocommerce_load_variations' === $_POST['action'];
    }

    /**
     * @return bool
     */
    public function isOrderListPage(): bool
    {
        return Pdk::get('orderListPageId') === $this->getScreenId();
    }

    /**
     * @return bool
     */
    public function isOrderPage(): bool
    {
        return Pdk::get('orderPageId') === $this->getScreenId();
    }

    /**
     * @return bool
     */
    public function isPluginSettingsPage(): bool
    {
        return Pdk::get('settingsMenuSlug') === $this->getScreenId();
    }

    /**
     * The plugins overview, where the deactivation modal asks why the plugin is deactivated. Only for users that can
     * deactivate plugins.
     *
     * @return bool
     */
    public function isPluginsOverviewPage(): bool
    {
        return in_array($this->getScreenId(), ['plugins', 'plugins-network'], true)
            && current_user_can('activate_plugins');
    }

    /**
     * @return bool
     */
    public function isProductPage(): bool
    {
        return 'product' === $this->getScreenId();
    }

    /**
     * @return string
     */
    private function getScreenId(): string
    {
        return get_current_screen()->id ?? '';
    }
}
