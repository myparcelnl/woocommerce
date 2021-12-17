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

    /**
     * @var array
     */
    private $messagesToDelete = [];

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
    public function addMessage(string $message, string $level, ?string $messageId, array $onPages = []): void
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
                echo sprintf(
                    '<div class="notice myparcel-dismiss-notice notice-%s %s"><p>%s</p></div>',
                    $message['level'],
                    $isDismissible,
                    $message['message']
                );
            }
        }
    }

    private function shouldMessageBeShown(array $message): bool
    {
        $messageAlreadyShown = in_array($message['messageId'], get_option('myparcel_notice_dismissed'), true);
        $currentPage         = get_current_screen();

        if ($messageAlreadyShown) {
            return false;
        }
        if (in_array($currentPage->id, $message['onPages'], true)) {
            return true;
        }

        if (empty($message['onPages'])) {
            return true;
        }

        return false;
    }

    public function dismissNotice(): void
    {
        $messageArray = get_option('myparcel_notice_dismissed', []);

        foreach ($this->messages as $message) {
            if (! in_array($message['messageId'], $messageArray, true)) {
                $messageArray[] = $message['messageId'];
                update_option('myparcel_notice_dismissed', $messageArray);
            }
        }
    }
}
