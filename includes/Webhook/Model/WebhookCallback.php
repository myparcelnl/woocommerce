<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\Webhook\Model;

defined('ABSPATH') or die();

use MyParcelNL\WooCommerce\includes\Model\Model;

/**
 * @property string $hash
 * @property string $full_url
 * @property string $path
 */
class WebhookCallback extends Model
{
    /**
     * @var string[]
     */
    protected $attributes = [
        'full_url',
        'hash',
        'path',
    ];

    /**
     * @return string
     */
    public function getFullUrl(): string
    {
        return $this->full_url;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }
}
