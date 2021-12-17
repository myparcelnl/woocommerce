<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\admin;

defined('ABSPATH') or die();

class Messages
{
    public const NOTICE_LEVEL_DEFAULT = self::NOTICE_LEVEL_LOG;
    public const NOTICE_LEVEL_ERROR   = 'error';
    public const NOTICE_LEVEL_LOG     = 'log';
    public const NOTICE_LEVEL_INFO    = 'info';
    public const NOTICE_LEVEL_SUCCESS = 'success';
    public const NOTICE_LEVEL_WARNING = 'warning';

    /**
     * Make sure the message is shown to the admin in due time.
     * Normal notices will only require $message as a parameter. When a $messageId
     * is given, once dismissed, the message will be dismissed forever.
     *
     * @param  string      $message
     * @param  string      $level
     * @param  string|null $messageId
     * @param  array       $onPages
     */
    public static function showAdminNotice(
        string  $message,
        string  $level = self::NOTICE_LEVEL_DEFAULT,
        ?string $messageId = null,
        array   $onPages = []
    ): void {
        if (! in_array($level, [
            self::NOTICE_LEVEL_LOG,
            self::NOTICE_LEVEL_INFO,
            self::NOTICE_LEVEL_ERROR,
            self::NOTICE_LEVEL_WARNING,
            self::NOTICE_LEVEL_SUCCESS,
        ])) {
            $level = self::NOTICE_LEVEL_DEFAULT;
        }

        MessagesRepository::getInstance()
            ->addMessage($message, $level, $messageId, $onPages);
    }
}
