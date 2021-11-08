<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\admin;

defined('ABSPATH') or die();

use MyParcelNL\WooCommerce\includes\Concerns\HasInstance;
use WCMYPA_Settings;

class MessagesRepository
{
    use HasInstance;

    /**
     * @var array
     */
    private $messages = [];

    public function __construct()
    {
        add_action('admin_notices', [$this, 'showMessages']);
    }

    /**
     * @param  string $message
     * @param  string $level
     * @param  bool   $onAllPages
     */
    public function addMessage(string $message, string $level, bool $onAllPages = false): void
    {
        $this->messages[] = [
            'message'    => $message,
            'level'      => $level,
            'onAllPages' => $onAllPages,
        ];
    }

    public function showMessages(): void
    {
        foreach ($this->messages as $message) {
            if (! $message['onAllPages'] && ! WCMYPA_Settings::isViewingOwnSettingsPage()) {
                continue;
            }

            echo sprintf('<div class="notice notice-%s"><p>%s</p></div>', $message['level'], $message['message']);
        }
    }
}
