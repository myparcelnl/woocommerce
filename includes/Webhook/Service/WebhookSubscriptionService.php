<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\Webhook\Service;

defined('ABSPATH') or die();

use Exception;
use MyParcelNL\Sdk\src\Services\Web\Webhook\AbstractWebhookWebService;
use MyParcelNL\Sdk\src\Services\Web\Webhook\OrderStatusChangeWebhookWebService;
use MyParcelNL\Sdk\src\Services\Web\Webhook\ShipmentStatusChangeWebhookWebService;
use MyParcelNL\Sdk\src\Support\Collection;
use MyParcelNL\WooCommerce\includes\Concerns\HasApiKey;
use MyParcelNL\WooCommerce\includes\Utils;
use MyParcelNL\WooCommerce\includes\Validators\WebhookCallbackUrlValidator;
use MyParcelNL\WooCommerce\includes\Webhook\Model\WebhookCallback;
use MyParcelNL\WooCommerce\includes\Webhook\Model\WebhookSubscription;
use MyParcelNL\WooCommerce\includes\Webhooks\Hooks\AccountSettingsWebhook;
use WCMP_Export_Consignments;
use WCMP_Log;
use WCMP_Settings_Data;
use WCMYPA;
use WCMYPA_Settings;

class WebhookSubscriptionService
{
    use HasApiKey;

    private const WEBHOOK_SETTINGS_PATH = 'woocommerce_myparcel_webhook_settings';
    private const VERSION               = 'v1';

    /**
     * @var \MyParcelNL\Sdk\src\Support\Collection|\MyParcelNL\WooCommerce\includes\Webhook\Model\WebhookSubscription[]
     */
    private $subscriptions;

    public function __construct()
    {
        $this->subscriptions = $this->loadSubscriptions();
    }

    /**
     * @param  \MyParcelNL\Sdk\src\Services\Web\Webhook\AbstractWebhookWebService $service
     * @param  callable                                                           $callback
     * @param  string                                                             $version
     *
     * @return $this
     * @throws \Exception
     */
    public function register(AbstractWebhookWebService $service, callable $callback, string $version = 'v1'): self
    {
        $existingWebhook = $this->getExistingWebhook($service, $version);

        $webhookCallback = $existingWebhook ? $existingWebhook->getCallback()
            : $this->createCallbackUrl($service, $version);

        $this->registerRestRoute($webhookCallback, $callback, $version);

        return $this;
    }

    /**
     * @param  \MyParcelNL\Sdk\src\Services\Web\Webhook\AbstractWebhookWebService $service
     *
     * @return \MyParcelNL\WooCommerce\includes\Webhook\Service\WebhookSubscriptionService
     */
    public function delete(AbstractWebhookWebService $service): self
    {
        $subscription = $this->findByHook($service->getHook());

        if (! $subscription) {
            return $this;
        }

        try {
            $service->unsubscribe($subscription->getId());
        } catch (Exception $e) {
            WCMP_Log::add(sprintf("Error deleting webhook %s:", $subscription->getId()), $e->getMessage());
        }

        return $this;
    }

    /**
     * Delete all webhooks from the database.
     *
     * @return void
     */
    public function deleteAll(): void
    {
        update_option(self::WEBHOOK_SETTINGS_PATH, null);
    }

    /**
     * @param  string $hook
     *
     * @return null|\MyParcelNL\WooCommerce\includes\Webhook\Model\WebhookSubscription
     */
    public function findByHook(string $hook): ?WebhookSubscription
    {
        return $this->subscriptions->firstWhere('hook', $hook);
    }

    /**
     * @return \MyParcelNL\Sdk\src\Support\Collection|\MyParcelNL\WooCommerce\includes\Webhook\Model\WebhookSubscription[]
     */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    /**
     * @param  \MyParcelNL\Sdk\src\Services\Web\Webhook\AbstractWebhookWebService $service
     * @param  string                                                             $version
     *
     * @return \MyParcelNL\WooCommerce\includes\Webhook\Model\WebhookCallback
     * @throws \Exception
     */
    public function createCallbackUrl(AbstractWebhookWebService $service, string $version): WebhookCallback
    {
        $hash = $this->generateHash();
        $path = implode('/', [$service->getHook(), $hash]);

        $basePath = $this->getBaseCallbackPath($service, $version);
        $fullUrl  = get_rest_url(null, $basePath . '/' . $hash);

        $validator = new WebhookCallbackUrlValidator();

        if (! $validator->validateAll($fullUrl)) {
            $validator->report();
        }

        return new WebhookCallback([
            'full_url' => $fullUrl,
            'path'     => $path,
            'hash'     => $hash,
        ]);
    }

    /**
     * @param  \MyParcelNL\Sdk\src\Services\Web\Webhook\AbstractWebhookWebService $service
     * @param  \MyParcelNL\WooCommerce\includes\Webhook\Model\WebhookCallback     $webhookCallback
     *
     * @return null|int
     */
    public function createWebhook(AbstractWebhookWebService $service, WebhookCallback $webhookCallback): ?int
    {
        try {
            $subscriptionId = $service->subscribe($webhookCallback->getFullUrl());
        } catch (Exception $e) {
            WCMP_Log::add(
                sprintf(
                    'Could not subscribe to webhook %s. Error: %s',
                    $service->getHook(),
                    $e->getMessage()
                )
            );
            return null;
        }

        return $subscriptionId;
    }

    /**
     * @return string
     */
    private function generateHash(): string
    {
        return md5(uniqid((string) mt_rand(), true));
    }

    /**
     * @param  \MyParcelNL\Sdk\src\Services\Web\Webhook\AbstractWebhookWebService $service
     * @param  string                                                             $version
     *
     * @return string
     */
    private function getBaseCallbackPath(AbstractWebhookWebService $service, string $version): string
    {
        return implode('/', [
            WCMYPA::NAME,
            $version,
            $service->getHook(),
        ]);
    }

    /**
     * Checks if the webhook should be created.
     * Return true if:
     * - Webhook does not exist yet
     * - Webhook exists but base url differs. Can happen when the site URL or the webhook version changes. The old
     *   webhook gets deleted before a new one is created.
     *
     * @param  \MyParcelNL\Sdk\src\Services\Web\Webhook\AbstractWebhookWebService $service
     * @param  string                                                             $version
     *
     * @return \MyParcelNL\WooCommerce\includes\Webhook\Model\WebhookSubscription
     */
    private function getExistingWebhook(AbstractWebhookWebService $service, string $version): ?WebhookSubscription
    {
        $existingWebhook = $this->findByHook($service->getHook());

        if (! $existingWebhook) {
            return null;
        }

        return $existingWebhook;

        // TODO MY-31304 validation could fail erroneously (10 jan 2022), investigate and fix
        /*
        $callback       = $existingWebhook->getCallback();
        $webhookSiteUrl = str_replace("/{$callback->getHash()}", '', $callback->getFullUrl());

        // Remove the hash and check if the rest of the url matches
        if ($webhookSiteUrl === $this->getFullRestUrl($service, $version)) {
            return $existingWebhook;
        }

        $this->delete($service);

        return null;
        */
    }

    /**
     * @param  \MyParcelNL\Sdk\src\Services\Web\Webhook\AbstractWebhookWebService $service
     * @param  string                                                             $version
     *
     * @return string
     */
    private function getFullRestUrl(AbstractWebhookWebService $service, string $version): string
    {
        return get_rest_url(null, $this->getBaseCallbackPath($service, $version));
    }

    /**
     * Get the webhook subscriptions from the database.
     *
     * @return \MyParcelNL\Sdk\src\Support\Collection|\MyParcelNL\WooCommerce\includes\Webhook\Model\WebhookSubscription[]
     */
    private function loadSubscriptions(): Collection
    {
        $webhooksOption = get_option(self::WEBHOOK_SETTINGS_PATH);

        if (! $webhooksOption) {
            return new Collection();
        }

        return (new Collection(json_decode($webhooksOption, true)))->mapInto(WebhookSubscription::class);
    }

    /**
     * Make this route available in the WordPress REST API.
     *
     * @param  \MyParcelNL\WooCommerce\includes\Webhook\Model\WebhookCallback $webhookCallback
     * @param  callable                                                       $callback
     * @param  string                                                         $version
     */
    private function registerRestRoute(WebhookCallback $webhookCallback, callable $callback, string $version): void
    {
        add_action('rest_api_init', function () use ($webhookCallback, $callback, $version) {
            register_rest_route(
                WCMYPA::NAME . '/' . $version,
                $webhookCallback->getPath(),
                [
                    'methods'             => 'GET,POST',
                    'callback'            => $callback,
                    'permission_callback' => '__return_true',
                ]
            );
        });
    }

    /**
     * @param  \MyParcelNL\Sdk\src\Services\Web\Webhook\AbstractWebhookWebService $service
     * @param  \MyParcelNL\WooCommerce\includes\Webhook\Model\WebhookCallback     $callback
     * @param  int                                                                $subscriptionId
     *
     * @return void
     */
    public function saveSubscription(
        AbstractWebhookWebService $service,
        WebhookCallback           $callback,
        int                       $subscriptionId
    ): void {
        $newSubscriptions = $this->loadSubscriptions()
            ->where('hook', '!=', $service->getHook());
        $subscription     = new WebhookSubscription([
            'hook'     => $service->getHook(),
            'id'       => $subscriptionId,
            'callback' => $callback->toArray(),
        ]);

        $newSubscriptions->push($subscription);
        $array = Utils::toArray($newSubscriptions->all());

        update_option(self::WEBHOOK_SETTINGS_PATH, json_encode($array));
    }

    /**
     * @param  string $apiKey
     *
     * @return void
     * @throws \Exception
     */
    public function subscribeToWebhooks(string $apiKey): void
    {
        $hooks                      = AccountSettingsWebhook::ACCOUNT_SETTINGS_WEBHOOKS;
        array_push($hooks, OrderStatusChangeWebhookWebService::class, ShipmentStatusChangeWebhookWebService::class);
        $webhookSubscriptionService = new WebhookSubscriptionService();

        foreach ($hooks as $webhookClass) {
            $service         = (new $webhookClass())->setApiKey($apiKey);
            $webhookCallback = $webhookSubscriptionService->createCallbackUrl($service, self::VERSION);
            $subscriptionId  = $webhookSubscriptionService->createWebhook($service, $webhookCallback);

            if (! $subscriptionId) {
                return;
            }

            $webhookSubscriptionService->saveSubscription($service, $webhookCallback, $subscriptionId);
        }
    }

    /**
     * @return bool
     */
    public static function hasValidSubscription(): bool
    {
        $webhookSubscriptionService = new WebhookSubscriptionService();

        foreach (AccountSettingsWebhook::ACCOUNT_SETTINGS_WEBHOOKS as $webhook) {
            /**
             * @var class-string<\MyParcelNL\Sdk\src\Services\Web\Webhook\AbstractWebhookWebService>[] $webhook
             */
            $subscription = $webhookSubscriptionService->findByHook((new $webhook())->getHook());

            if (! $subscription) {
                return false;
            }
        }

        return true;
    }
}
