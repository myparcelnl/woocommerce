<?php

namespace MyParcelNL\WooCommerce\Pdk\Service;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Plugin\Service\AbstractViewService;

class WcViewService extends AbstractViewService
{
    private const SETTINGS_MENU_SLUG = ':name_settings';

    /**
     * @return string
     */
    public function getSettingsPageSlug(): string
    {
        $appInfo = Pdk::getAppInfo();

        return strtr(self::SETTINGS_MENU_SLUG, [':name' => $appInfo['name']]);
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
    public function isProductPage(): bool
    {
        return 'product' === $this->getScreen();
    }

    /**
     * @return bool
     */
    public function isPluginSettingsPage(): bool
    {
        return sprintf('woocommerce_page_%s', $this->getSettingsPageSlug()) === $this->getScreen();
    }

    /**
     * @return string
     */
    private function getScreen(): string
    {
        return get_current_screen()->id;
    }
}