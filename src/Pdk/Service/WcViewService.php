<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Service;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Plugin\Service\AbstractViewService;

class WcViewService extends AbstractViewService
{
    /**
     * The order received page has the same page id as the checkout so `is_checkout()` returns true on both.
     *
     * @return bool
     */
    public function isCheckoutPage(): bool
    {
        return ! is_checkout() || is_order_received_page();
    }

    /**
     * @return bool
     */
    public function isOrderListPage(): bool
    {
        return 'edit-shop_order' === $this->getScreen();
    }

    /**
     * @return bool
     */
    public function isOrderPage(): bool
    {
        return 'shop_order' === $this->getScreen();
    }

    /**
     * @return bool
     */
    public function isPluginSettingsPage(): bool
    {
        return Pdk::get('settingsMenuSlug') === $this->getScreen();
    }

    /**
     * @return bool
     */
    public function isProductPage(): bool
    {
        return 'product' === $this->getScreen();
    }

    /**
     * @return string
     */
    private function getScreen(): string
    {
        return get_current_screen()->id;
    }
}
