<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Webhook;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Plugin\Webhook\Repository\AbstractPdkWebhooksRepository;

class WcWebhooksRepository extends AbstractPdkWebhooksRepository
{
    /**
     * @param  string $hook
     *
     * @return null|int
     */
    public function getId(string $hook): ?int
    {
        $id = get_option($this->getKey($hook), null);

        if ($id === null) {
            return null;
        }

        return (int) $id;
    }

    /**
     * @param  string $hook
     *
     * @return void
     */
    public function remove(string $hook): void
    {
        delete_option($this->getKey($hook));
    }

    /**
     * @param  string $hook
     * @param  int    $id
     *
     * @return void
     */
    public function store(string $hook, int $id): void
    {
        update_option($this->getKey($hook), $id);
    }

    /**
     * @param  string $hook
     *
     * @return string
     */
    private function getKey(string $hook): string
    {
        $appInfo = Pdk::getAppInfo();

        return strtr('_:app_:hook', [
            ':app'  => $appInfo['name'],
            ':hook' => $hook,
        ]);
    }
}
