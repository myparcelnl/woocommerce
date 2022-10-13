<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\admin\settings;

defined('ABSPATH') or die();

use MyParcelNL\Sdk\src\Model\Carrier\AbstractCarrier;
use MyParcelNL\Sdk\src\Model\Carrier\CarrierInstabox;
use MyParcelNL\Sdk\src\Model\Consignment\DropOffPoint;
use MyParcelNL\WooCommerce\includes\admin\MessagesRepository;
use MyParcelNL\WooCommerce\includes\Settings\Api\AccountSettings;
use MyParcelNL\WooCommerce\includes\Webhook\Service\WebhookSubscriptionService;
use WCMP_Data;
use WCMP_Settings_Callbacks;
use WCMYPA_Admin;

class Status
{
    public const LINK_RETAIL_OVERVIEW    = 'https://backoffice.myparcel.nl/shipments/retail-overview';
    public const LINK_SETTINGS_SHIPMENTS = 'https://backoffice.myparcel.nl/settings/shipment';

    private const TYPE_ERROR   = 'error';
    private const TYPE_SUCCESS = 'success';

    /**
     * @var array
     */
    private static $items = [];

    public static function renderDiagnostics(): void
    {
        self::addShopConnectionRow();
        self::addWebhookStatusRow();
        self::addCarrierRows();

        self::renderStatusTable();
    }

    private static function addCarrierRows(): void
    {
        $hasApiKey = AccountSettings::getInstance()->hasApiKey();

        foreach (WCMP_Data::getCarriers() as $carrierClass) {
            $carrier = new $carrierClass();

            if (! $hasApiKey) {
                self::addItem($carrier->getHuman(), '', self::TYPE_ERROR);
                continue;
            }

            $text = __('diagnostics_status_carrier_ready', 'woocommerce-myparcel');
            $type = self::TYPE_SUCCESS;

            if ($carrierClass === CarrierInstabox::class && ! self::getDropOffPoint($carrier)) {
                $text = WCMP_Settings_Callbacks::getLink(
                    __('diagnostics_status_drop_off_point_missing', 'woocommerce-myparcel'),
                    CarrierSettings::getRetailOverviewLink($carrier)
                );
                $type = self::TYPE_ERROR;
            }

            if (! AccountSettings::getInstance()
                ->isEnabledCarrier($carrier->getName())) {
                $text = WCMP_Settings_Callbacks::getLink(
                    __('diagnostics_status_carrier_disabled', 'woocommerce-myparcel'),
                    self::LINK_SETTINGS_SHIPMENTS
                );
                $type = self::TYPE_ERROR;
            }

            self::addItem($carrier->getHuman(), $text, $type);
        }
    }

    /**
     * @param  string      $title
     * @param  string      $text
     * @param  string|null $type
     *
     * @return void
     */
    private static function addItem(string $title, string $text, string $type = null): void
    {
        self::$items[] = [
            'title' => $title,
            'text'  => $text,
            'type'  => $type,
        ];
    }

    private static function addShopConnectionRow(): void
    {
        $title = __('diagnostics_status_shop_connection', 'woocommerce-myparcel');

        if (! AccountSettings::getInstance()->hasApiKey()) {
            self::addItem($title, __('diagnostics_status_api_key_missing', 'woocommerce-myparcel'), self::TYPE_ERROR);
            return;
        }

        $shop = AccountSettings::getInstance()
            ->getShop();

        self::addItem(
            $title,
            $shop
                ? sprintf(
                __('diagnostics_status_shop_connection_success', 'woocommerce-myparcel'),
                $shop->getName()
            )
                : __('diagnostics_status_shop_connection_failure', 'woocommerce-myparcel'),
            $shop ? self::TYPE_SUCCESS : self::TYPE_ERROR
        );
    }

    /**
     * @throws \Exception
     */
    private static function addWebhookStatusRow(): void
    {
        $title = __('diagnostics_status_webhooks', 'woocommerce-myparcel');

        if (! AccountSettings::getInstance()->hasApiKey()) {
            self::addItem($title, '', self::TYPE_ERROR);
            return;
        }

        $canUseWebhooks = WCMYPA_Admin::canUseWebhooks();

        if (! $canUseWebhooks) {
            self::addItem(
                $title,
                __('diagnostics_status_webhooks_unavailable', 'woocommerce-myparcel'),
                self::TYPE_ERROR
            );
            return;
        }

        $allWebhooksPresent = WebhookSubscriptionService::hasValidSubscription();

        $text = $allWebhooksPresent ? 'diagnostics_status_webhooks_set_up' : 'diagnostics_status_webhooks_error';
        $type = $allWebhooksPresent ? self::TYPE_SUCCESS : self::TYPE_ERROR;

        self::addItem($title, __($text, 'woocommerce-myparcel'), $type);
    }

    /**
     * @param  \MyParcelNL\Sdk\src\Model\Carrier\AbstractCarrier $carrier
     *
     * @return null|\MyParcelNL\Sdk\src\Model\Consignment\DropOffPoint
     */
    private static function getDropOffPoint(AbstractCarrier $carrier): ?DropOffPoint
    {
        $configuration = AccountSettings::getInstance()
            ->getCarrierConfigurationByCarrierId($carrier->getId());

        return $configuration ? $configuration->getDefaultDropOffPoint() : null;
    }

    private static function renderStatusTable(): void
    {
        echo '<div class="wcmp__d--flex">';
        echo '<div class="wcmp__box">';
        echo '<table class="wcmp__table wc_status_table widefat">';
        echo sprintf(
            "<thead><tr><td colspan='2'>%s</td></tr></thead>",
            esc_html__('diagnostics_status_title', 'woocommerce-myparcel')
        );
        echo '<tbody>';
        foreach (self::$items as $item) {
            echo '<tr>';
            printf('<th>%s</th>', esc_html($item['title']));
            echo '<td>';
            switch ($item['type'] ?? null) {
                case self::TYPE_SUCCESS:
                    printf(
                        '<mark class="yes"><span class="dashicons dashicons-yes"></span> %s</mark>',
                        wp_kses($item['text'], MessagesRepository::ALLOWED_HTML)
                    );
                    break;
                case self::TYPE_ERROR:
                    printf(
                        '<mark class="error"><span class="dashicons dashicons-warning"></span> %s</mark>',
                        wp_kses($item['text'], MessagesRepository::ALLOWED_HTML)
                    );
                    break;
                default:
                    echo wp_kses($item['text'], MessagesRepository::ALLOWED_HTML);

                    break;
            }
            echo '</td></tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
        echo '</div>';
    }
}
