<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Service;

use InvalidArgumentException;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Contract\WordPressServiceInterface;

/**
 * @see /config/pdk.php
 */
class WordPressService implements WordPressServiceInterface
{
    /**
     * @param  \WC_Data|\WP_Post|int|string $input
     *
     * @return string
     */
    public function getPostId($input): string
    {
        if (method_exists($input, 'get_id')) {
            $id = $input->get_id();
        } elseif (is_object($input) && isset($input->ID)) {
            $id = $input->ID;
        } else {
            $id = $input;
        }

        if (! is_scalar($id)) {
            throw new InvalidArgumentException('Invalid input');
        }

        return (string) $id;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return Pdk::get('wordPressVersion');
    }
}
