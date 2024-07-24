<?php
/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Hooks;

use MyParcelNL\Pdk\App\Webhook\Contract\PdkWebhooksRepositoryInterface;
use MyParcelNL\Pdk\Base\Contract\CronServiceInterface;
use MyParcelNL\Pdk\Base\Support\Arr;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Logger\Contract\PdkLoggerInterface;
use MyParcelNL\Pdk\Tests\Bootstrap\MockCronService;
use MyParcelNL\Pdk\Webhook\Model\WebhookSubscription;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpActions;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use WP_REST_Request;
use function DI\get;
use function MyParcelNL\Pdk\Tests\usesShared;

usesShared(new UsesMockWcPdkInstance([
    CronServiceInterface::class => get(MockCronService::class),
]));

function sendWebhook(string $route): void
{
    /** @var \MyParcelNL\WooCommerce\Pdk\Hooks\PdkWebhookHooks $hookClass */
    $hookClass = Pdk::get(PdkWebhookHooks::class);
    /** @var \MyParcelNL\Pdk\Tests\Bootstrap\MockPdkWebhooksRepository $webhooksRepository */
    $webhooksRepository = Pdk::get(PdkWebhooksRepositoryInterface::class);

    /**
     * The stored url contains the /wp-json/ prefix.
     *
     * @see \MyParcelNL\WooCommerce\Pdk\Hooks\PdkWebhookHooks::normalizeRequest
     */
    $webhooksRepository->storeHashedUrl('/wp-json/myparcelnl/backend/v1/webhook/a-valid-hash');

    $request = new WP_REST_Request('POST', $route);
    $request->set_headers(['x-myparcel-hook' => WebhookSubscription::SHOP_UPDATED]);
    $request->set_body(json_encode(['data' => ['hooks' => [['event' => WebhookSubscription::SHOP_UPDATED]]]]));

    $hookClass->processWebhookRequest($request);
}

it('registers webhook handler in wp rest api', function () {
    /** @var \MyParcelNL\WooCommerce\Pdk\Hooks\PdkWebhookHooks $hookClass */
    $hookClass = Pdk::get(PdkWebhookHooks::class);
    $hookClass->apply();

    expect(MockWpActions::get('rest_api_init'))->toBeArray();

    MockWpActions::execute('rest_api_init');

    $routes = rest_get_server()->get_routes();

    expect($routes)->toEqual([
        'myparcelnl/backend/v1/webhook/(?P<hash>.+)' => [
            'override' => false,
            'args'     => [
                'callback'            => [$hookClass, 'processWebhookRequest'],
                'methods'             => 'creatable',
                'permission_callback' => '__return_true',
            ],
        ],
    ]);
});

it('returns 202 on any incoming webhook', function () {
    /** @var \MyParcelNL\WooCommerce\Pdk\Hooks\PdkWebhookHooks $hookClass */
    $hookClass = Pdk::get(PdkWebhookHooks::class);

    $request = new WP_REST_Request('POST', '/myparcelnl/backend/v1/webhook/some-random-hash');

    $result = $hookClass->processWebhookRequest($request);

    expect($result->get_status())->toBe(202);
});

it('schedules a cron job for an incoming webhook', function () {
    /** @var \MyParcelNL\Pdk\Tests\Bootstrap\MockCronService $cronService */
    $cronService = Pdk::get(CronServiceInterface::class);

    expect($cronService->getScheduledTasks())->toHaveLength(0);
    sendWebhook('/myparcelnl/backend/v1/webhook/some-hash');
    expect($cronService->getScheduledTasks())->toHaveLength(1);
});

it('executes the cron job for a valid incoming webhook', function () {
    /** @var \MyParcelNL\Pdk\Tests\Bootstrap\MockCronService $cronService */
    $cronService = Pdk::get(CronServiceInterface::class);
    /** @var \MyParcelNL\Pdk\Tests\Bootstrap\MockLogger $logger */
    $logger = Pdk::get(PdkLoggerInterface::class);

    sendWebhook('/myparcelnl/backend/v1/webhook/a-valid-hash');

    $cronService->executeAllTasks();

    expect($cronService->getScheduledTasks())
        ->toHaveLength(0)
        ->and(Arr::pluck($logger->getLogs(), 'message'))
        ->toEqual([
            '[PDK]: Incoming webhook',
            '[PDK]: Webhook received',
            '[PDK]: Webhook processed',
        ]);
});

it('executes the cron job for an invalid incoming webhook', function () {
    /** @var \MyParcelNL\Pdk\Tests\Bootstrap\MockCronService $cronService */
    $cronService = Pdk::get(CronServiceInterface::class);
    /** @var \MyParcelNL\Pdk\Tests\Bootstrap\MockLogger $logger */
    $logger = Pdk::get(PdkLoggerInterface::class);

    sendWebhook('/myparcelnl/backend/v1/webhook/invalid-hash');

    $cronService->executeAllTasks();

    expect($cronService->getScheduledTasks())
        ->toHaveLength(0)
        ->and(Arr::pluck($logger->getLogs(), 'message'))
        ->toEqual([
            '[PDK]: Incoming webhook',
            '[PDK]: Webhook received with invalid url',
        ]);
});
