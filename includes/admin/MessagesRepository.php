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

    private const OPTION_NOTICES_DISMISSED = 'myparcel_notice_dismissed';
    private const OPTION_NOTICES_PERSISTED = 'myparcel_notice_persisted';

    /**
     * @var bool remembers if showMessages has been triggered already
     */
    private $triggered = false;

    /**
     * @var array
     */
    private $messages;

    public function __construct()
    {
        add_action('admin_notices', [$this, 'showMessages']);
        add_action('wp_ajax_dismissNotice', [$this, 'ajaxDismissNotice']);

        $this->messages = get_option(self::OPTION_NOTICES_PERSISTED, []);
        if ($this->messages) {
            update_option(self::OPTION_NOTICES_PERSISTED, []);
        }

        register_shutdown_function([$this, 'persistMessages']);
    }

    public function persistMessages(): void
    {
        if ($this->messages) {
            update_option(self::OPTION_NOTICES_PERSISTED, $this->messages);
        }
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
                    '<div class="notice myparcel-dismiss-notice notice-%s %s" data-messageid="%s"><p>%s</p></div>',
                    esc_attr($message['level']),
                    esc_attr($isDismissible),
                    esc_attr($message['messageId']),
                    esc_attr($message['message'])
                );
            }
        }

        $this->triggered = true;
        $this->messages  = [];
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
            (array) get_option(self::OPTION_NOTICES_DISMISSED),
            true
        );
        $currentPage                     = get_current_screen();
        $currentPageShouldDisplayMessage = in_array($currentPage->id, $message['onPages'], true);
        $allPagesShouldDisplayMessage    = empty($message['onPages']);

        return ! $messageAlreadyShown && ($currentPageShouldDisplayMessage || $allPagesShouldDisplayMessage);
    }

    public function ajaxDismissNotice(): void
    {
        $messageId    = $_POST['messageid'] ?? null;
        $messageArray = get_option(self::OPTION_NOTICES_DISMISSED, []);

        if ($messageId && ! in_array($messageId, $messageArray)) {
            $messageArray[] = $messageId;

            update_option(self::OPTION_NOTICES_DISMISSED, $messageArray);
        }
        die();
    }
}
