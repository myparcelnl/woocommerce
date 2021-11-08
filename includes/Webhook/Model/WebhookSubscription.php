<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\Webhook\Model;

defined('ABSPATH') or die();

use MyParcelNL\WooCommerce\includes\Model\Model;

/**
 * @property int                                                            $id
 * @property string                                                         $hook
 * @property \MyParcelNL\WooCommerce\includes\Webhook\Model\WebhookCallback $callback
 */
class WebhookSubscription extends Model
{
    /**
     * @var string[]
     */
    protected $attributes = [
        'id',
        'hook',
        'callback',
    ];

    /**
     * @param  array $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->callback = new WebhookCallback($data['callback']);
    }

    /**
     * @return \MyParcelNL\WooCommerce\includes\Webhook\Model\WebhookCallback
     */
    public function getCallback(): WebhookCallback
    {
        return $this->callback;
    }

    /**
     * @return string
     */
    public function getHook(): string
    {
        return $this->hook;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
}
