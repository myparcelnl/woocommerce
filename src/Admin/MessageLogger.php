<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Admin;

use MyParcelNL\Pdk\Logger\AbstractLogger;

defined('ABSPATH') or die();

class MessageLogger extends AbstractLogger
{
    /**
     * @var \MyParcelNL\WooCommerce\Admin\MessagesRepository
     */
    private $repository;

    /**
     * @param  \MyParcelNL\WooCommerce\Admin\MessagesRepository $repository
     */
    public function __construct(MessagesRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Make sure the message is shown to the admin in due time.
     * Normal notices will only require $message as a parameter. When a $messageId
     * is given, once dismissed, the message will be dismissed forever.
     *
     * @param  string $message
     * @param  string $level
     * @param  array  $context
     */
    public function log($level, $message, array $context = []): void
    {
        $this->repository->addMessage(
            $message,
            $level,
            $context['messageId'] ?? null,
            $context['onPages'] ?? null
        );
    }
}
