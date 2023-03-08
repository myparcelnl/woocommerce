<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Webhook;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Plugin\Webhook\Repository\AbstractPdkWebhooksRepository;
use MyParcelNL\Pdk\Webhook\Collection\WebhookSubscriptionCollection;

class WcWebhooksRepository extends AbstractPdkWebhooksRepository
{
    private const KEY_WEBHOOKS     = 'webhooks';
    private const KEY_WEBHOOK_HASH = 'webhook_hash';

    /**
     * @return \MyParcelNL\Pdk\Webhook\Collection\WebhookSubscriptionCollection
     */
    public function getAll(): WebhookSubscriptionCollection
    {
        $key = $this->getKey(self::KEY_WEBHOOKS);

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
        return get_option($this->getKey(self::KEY_WEBHOOK_HASH), null);
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
        update_option($this->getKey(self::KEY_WEBHOOKS), $subscriptions->toArray());
    }

    /**
     * @param  string $url
     *
     * @return void
     */
    public function storeHashedUrl(string $url): void
    {
        update_option($this->getKey(self::KEY_WEBHOOK_HASH), $url);
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
            ':app'  => $appInfo->name,
            ':hook' => $hook,
        ]);
    }
}
