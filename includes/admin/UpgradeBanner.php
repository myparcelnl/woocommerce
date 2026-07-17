<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\admin;

defined('ABSPATH') or die();

/**
 * Admin banner on the plugin settings and orders pages telling merchants still
 * on 4.x that MyParcel for WooCommerce 6.0 is available. It renders through the
 * plugin's own Messages system, so it only shows while 4.x is active and is gone
 * once the merchant moves to 6.x. Dismissal is remembered per shop.
 */
class UpgradeBanner
{
    private const MESSAGE_ID    = 'notice_v6_upgrade';
    private const MIGRATION_URL = 'https://developer.myparcel.nl/nl/documentatie/10.woocommerce-v6.0.html';

    public static function show(): void
    {
        $link = sprintf(
            '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
            esc_url(self::MIGRATION_URL),
            esc_html__('notice_v6_upgrade_link', 'woocommerce-myparcel')
        );

        Messages::showAdminNotice(
            sprintf(
                '<h3>%s</h3><p>%s</p>',
                esc_html__('notice_v6_upgrade_title', 'woocommerce-myparcel'),
                // The body translation holds a %s placeholder for the link.
                sprintf(esc_html__('notice_v6_upgrade_body', 'woocommerce-myparcel'), $link)
            ),
            Messages::NOTICE_LEVEL_INFO,
            self::MESSAGE_ID,
            [
                MessagesRepository::ORDERS_PAGE,
                MessagesRepository::ORDERS_PAGE_HPOS,
                MessagesRepository::SETTINGS_PAGE,
                MessagesRepository::HOME_PAGE,
                MessagesRepository::PLUGINS_PAGE,
            ]
        );
    }
}
