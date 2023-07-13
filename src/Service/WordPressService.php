<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Service;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Contract\WordPressServiceInterface;

/**
 * @see /config/pdk.php
 */
class WordPressService implements WordPressServiceInterface
{
    /**
     * @return string
     */
    public function getVersion(): string
    {
        return Pdk::get('wordPressVersion');
    }
}
