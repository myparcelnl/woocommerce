<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Hooks;

use MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\Facade\Frontend;
use MyParcelNL\Pdk\Facade\Language;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Facade\WooCommerce;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;

class PdkOrderListHooks implements WordPressHooksInterface
{
    /**
     * @var \MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface
     */
    private $pdkOrderRepository;

    /**
     * @param  \MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface $pdkOrderRepository
     */
    public function __construct(PdkOrderRepositoryInterface $pdkOrderRepository)
    {
        $this->pdkOrderRepository = $pdkOrderRepository;
    }

    public function apply(): void
    {
        $pageId = Pdk::get('orderListPageId');

        // Add bulk actions to order list
        add_filter("bulk_actions-$pageId", [$this, 'registerBulkActions']);

        // Render custom column in order grid
        add_filter("manage_{$pageId}_columns", [$this, 'registerMyParcelOrderListItem'], 20);

        // Render pdk order list column in our custom order grid column
        add_action($this->getOrderListColumnHook(), [$this, 'renderPdkOrderListItem'], 10, 2);
    }

    /**
     * @param  array $actions
     *
     * @return array
     */
    public function registerBulkActions(array $actions): array
    {
        $appInfo    = Pdk::getAppInfo();
        $pluginName = $appInfo->name;

        foreach (Pdk::get('bulkActions') as $action) {
            $actions["$pluginName.$action"] = sprintf('%s: %s', $appInfo->title, Language::translate($action));
        }

        return $actions;
    }

    /**
     * @param  array $columns
     *
     * @return array
     */
    public function registerMyParcelOrderListItem(array $columns): array
    {
        $newColumns = [];

        foreach ($columns as $name => $data) {
            $newColumns[$name] = $data;

            if (Pdk::get('orderListPreviousColumn') !== $name) {
                continue;
            }

            $newColumns[Pdk::get('orderListColumnName')] = Pdk::get('orderListColumnTitle');
        }

        return $newColumns;
    }

    /**
     * @param  string|mixed                                      $column
     * @param  int|\Automattic\WooCommerce\Admin\Overrides\Order $orderOrId – Order ID if legacy, Order object if HPOS.
     *
     * @return void
     */
    public function renderPdkOrderListItem($column, $orderOrId): void
    {
        if (Pdk::get('orderListColumnName') !== $column) {
            return;
        }

        $pdkOrder = $this->pdkOrderRepository->get($orderOrId);

        echo Frontend::renderOrderListItem($pdkOrder);
    }

    /**
     * @return string
     */
    private function getOrderListColumnHook(): string
    {
        return WooCommerce::isUsingHpos()
            ? sprintf('manage_%s_custom_column', Pdk::get('orderListPageId'))
            : 'manage_shop_order_posts_custom_column';
    }
}
