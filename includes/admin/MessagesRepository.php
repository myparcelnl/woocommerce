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
        if(get_option( 'myparcel_notice_dismissed' ) !== true) {
            add_action( 'admin_notices', [$this, 'showMessages']);
            add_action('wp_ajax_dismissNotice', [$this, 'dismissNotice']);
        }
    }

    /**
     * @param  string $message
     * @param  string $level
     * @param  array  $onPages
     */
    public function addMessage(string $message, string $level, array $onPages = []): void
    {
        $this->messages[] = [
            'message' => $message,
            'level'   => $level,
            'onPages' => $onPages,
        ];
    }

    public function showMessages(): void
    {
        foreach ($this->messages as $message) {
            if ($this->shouldMessageBeShown($message)) {
                echo sprintf(
                    '<div class="notice myparcel-dismiss-notice notice-%s is-dismissible"><p>%s</p></div>',
                    $message['level'],
                    $message['message']
                );
            }
        }
    }

    private function shouldMessageBeShown(array $message): bool
    {
        $currentPage = get_current_screen();

        if (in_array($currentPage->id, $message['onPages'], true)) {
            return true;
        }

        if (empty($message['onPages'])) {
            return true;
        }

        return false;
    }

    private function dismissNotice()
    {
        update_option('myparcel_notice_dismissed', true);
    }
}
