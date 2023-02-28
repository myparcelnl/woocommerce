<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Admin;

class MessagesRepository
{
    public const  SETTINGS_PAGE           = 'woocommerce_page_wcmp_settings';
    private const OPTION_NOTICE_DISMISSED = 'myparcelnl_notice_dismissed';
    private const OPTION_NOTICE_PERSISTED = 'myparcelnl_notice_persisted';

    /**
     * @var array
     */
    private $messages;

    public function __construct()
    {
        add_action('admin_notices', [$this, 'showMessages']);
        add_action('wp_ajax_dismissNotice', [$this, 'ajaxDismissNotice']);

        $this->preloadPersistedMessages();
        if (defined('DOING_AJAX')) {
            register_shutdown_function([$this, 'persistRemainingMessages']);
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
        if ($this->messageIsDuplicate($message)) {
            return;
        }

        $this->messages[] = [
            'message'   => $message,
            'messageId' => $messageId ?? null,
            'level'     => $level,
            'onPages'   => $onPages,
        ];
    }

    public function ajaxDismissNotice(): void
    {
        $messageId    = $_POST['messageid'] ?? null;
        $messageArray = get_option(self::OPTION_NOTICE_DISMISSED, []);

        if ($messageId && ! in_array($messageId, $messageArray)) {
            $messageArray[] = $messageId;

            update_option(self::OPTION_NOTICE_DISMISSED, $messageArray);
        }
        die();
    }

    /**
     * @return void
     */
    public function persistRemainingMessages(): void
    {
        if (! $this->messages) {
            return;
        }

        update_option(self::OPTION_NOTICE_PERSISTED, $this->messages);
    }

    /**
     * @return void
     */
    public function preloadPersistedMessages(): void
    {
        $this->messages = get_option(self::OPTION_NOTICE_PERSISTED, []);

        if ($this->messages) {
            update_option(self::OPTION_NOTICE_PERSISTED, []);
        }
    }

    public function showMessages(): void
    {
        foreach ($this->messages as $message) {
            if ($this->shouldMessageBeShown($message)) {
                $isDismissible = $message['messageId'] ? 'is-dismissible' : '';
                printf(
                    '<div class="wcmp__notice notice myparcel-dismiss-notice notice-%s %s" data-messageid="%s"><p>%s</p></div>',
                    $message['level'],
                    $isDismissible,
                    $message['messageId'],
                    $message['message']
                );
            }
        }

        $this->messages = [];
    }

    /**
     * @param  string $message
     *
     * @return bool whether $this->messages already contains a message with text $message.
     */
    private function messageIsDuplicate(string $message): bool
    {
        return 0 < count(
                array_filter($this->messages, static function (array $entry) use ($message) {
                    return $message === $entry['message'];
                })
            );
    }

    /**
     * @param  array $message
     *
     * @return bool
     */
    private function shouldMessageBeShown(array $message): bool
    {
        $messageAlreadyShown             =
            $message['messageId']
            && in_array(
                $message['messageId'],
                (array) get_option(self::OPTION_NOTICE_DISMISSED),
                true
            );
        $currentPageId                   = get_current_screen()->id ?? '';
        $currentPageShouldDisplayMessage = in_array($currentPageId, $message['onPages'], true);
        $allPagesShouldDisplayMessage    = ! $message['onPages'];

        return ! $messageAlreadyShown && ($currentPageShouldDisplayMessage || $allPagesShouldDisplayMessage);
    }
}