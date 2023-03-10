<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Webhook;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Plugin\Webhook\Repository\AbstractPdkWebhooksRepository;
use MyParcelNL\Pdk\Webhook\Collection\WebhookSubscriptionCollection;

class WcWebhooksRepository extends AbstractPdkWebhooksRepository
{

    /**
     * @return \MyParcelNL\Pdk\Webhook\Collection\WebhookSubscriptionCollection
     */
    public function getAll(): WebhookSubscriptionCollection
    {
        $key = Pdk::get('settingKeyWebhooks');

        return $this->retrieve($key, function () use ($key) {
            $items = get_option($key, null);

            return new WebhookSubscriptionCollection($items);
        });
    }

    /**
     * @return null|string
     */
    public function getHashedUrl(): ?string
    {
        return get_option($this->getKey(Pdk::get('settingKeyWebhookHash')), null);
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
     * @param  \MyParcelNL\Pdk\Webhook\Collection\WebhookSubscriptionCollection $subscriptions
     *
     * @return void
     */
    public function store(WebhookSubscriptionCollection $subscriptions): void
    {
        update_option(Pdk::get('settingKeyWebhooks'), $subscriptions->toArray());
    }

    /**
     * @param  string $url
     *
     * @return void
     */
    public function storeHashedUrl(string $url): void
    {
        update_option($this->getKey(Pdk::get('settingKeyWebhookHash')), $url);
    }

    /**
     * @param  string $name
     *
     * @return string
     */
    private function getKey(string $name): string
    {
        return Pdk::get('settingKeyPrefix') . $name;
    }
}
