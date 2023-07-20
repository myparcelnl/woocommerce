<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Settings\Repository;

use MyParcelNL\Pdk\Settings\Repository\PdkSettingsRepository;

class WcPdkSettingsRepository extends PdkSettingsRepository
{
    //    public function __construct(CacheStorageInterface $cache, WpOptionsStorage $storage)
    //    {
    //        parent::__construct($cache, $storage);
    //    }

    //    /**
    //     * @param  string $namespace
    //     *
    //     * @return mixed
    //     */
    //    public function getGroup(string $namespace)
    //    {
    //        return $this->retrieve($namespace, function () use ($namespace) {
    //            return get_option($namespace, null);
    //        });
    //    }
    //
    //    /**
    //     * @param  string $key
    //     * @param  mixed  $value
    //     *
    //     * @return void
    //     */
    //    public function store(string $key, $value): void
    //    {
    //        update_option($key, $value);
    //
    //        $this->save($key, $value);
    //    }
}
