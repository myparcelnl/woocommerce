<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\admin;

defined('ABSPATH') or die();

use MyParcelNL\WooCommerce\includes\Concerns\HasInstance;
use WCMYPA_Settings;

class MessagesRepository
{
    use HasInstance;

    public const ORDERS_PAGE   = 'edit-shop_order';
    public const SETTINGS_PAGE = 'woocommerce_page_wcmp_settings';
    public const PLUGINS_PAGE  = 'plugins';

    /**
     * @var array
     */
    private $messages = [];

    public function __construct()
    {
        add_action('admin_notices', [$this, 'showMessages']);
        add_action('wp_ajax_dismissNotice', [$this, 'dismissNotice']);
    }

    /**
     * @param  string      $message
     * @param  string      $level
     * @param  string|null $messageId
     * @param  array       $onPages
     */
    public function addMessage(string $message, string $level, ?string $messageId = null, array $onPages = []): void
    {
        $this->messages[] = [
            'message'   => $message,
            'messageId' => $messageId ?? null,
            'level'     => $level,
            'onPages'   => $onPages,
        ];
    }

    public function showMessages(): void
    {
        foreach ($this->messages as $message) {
            if ($this->shouldMessageBeShown($message)) {
                $isDismissible = $message['messageId'] ? 'is-dismissible' : '';
                printf(
                    '<div class="notice myparcel-dismiss-notice notice-%s %s"><p>%s</p></div>',
                    esc_attr($message['level']),
                    esc_attr($isDismissible),
                    esc_attr($message['message'])
                );
            }
        }
    }

    /**
     * @param  array $message
     *
     * @return bool
     */
    private function shouldMessageBeShown(array $message): bool
    {
        $messageAlreadyShown             = in_array(
            $message['messageId'],
            (array) get_option('myparcel_notice_dismissed'),
            true
        );
        $currentPage                     = get_current_screen();
        $currentPageShouldDisplayMessage = in_array($currentPage->id, $message['onPages'], true);
        $allPagesShouldDisplayMessage    = empty($message['onPages']);

        return ! $messageAlreadyShown && ($currentPageShouldDisplayMessage || $allPagesShouldDisplayMessage);
    }

    public function dismissNotice(): void
    {
        $messageArray = get_option('myparcel_notice_dismissed', []);

        foreach ($this->messages as $message) {
            if (in_array($message['messageId'], $messageArray, true)) {
                continue;
            }
            $messageArray[] = $message['messageId'];
            update_option('myparcel_notice_dismissed', $messageArray);
        }
    }
}
