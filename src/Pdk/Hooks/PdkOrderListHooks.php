<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Hooks;

use MyParcelNL;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\RenderService;
use MyParcelNL\Pdk\Plugin\Repository\AbstractPdkOrderRepository;
use MyParcelNL\WooCommerce\Hooks\WordPressHooksInterface;

class PdkOrderListHooks implements WordPressHooksInterface
{
    public function apply(): void
    {
        // Render custom column in order grid
        add_filter('manage_edit-shop_order_columns', [$this, 'registerMyParcelOrderListColumn'], 20);

        // Render pdk order list column in our custom order grid column
        add_action('manage_shop_order_posts_custom_column', [$this, 'renderPdkOrderListColumn']);
    }

    /**
     * @param  array $columns
     *
     * @return array
     */
    public function registerMyParcelOrderListColumn(array $columns): array
    {
        $newColumns = [];

        // Insert the column before the column we want to appear after
        foreach ($columns as $name => $data) {
            $newColumns[$name] = $data;

            if ('shipping_address' === $name) {
                $newColumns[MyParcelNL::CUSTOM_ORDER_COLUMN_ID] = __('MyParcel', 'my-textdomain');
            }
        }

        return $newColumns;
    }

    /**
     * @param  string|mixed $column
     *
     * @return void
     */
    public function renderPdkOrderListColumn($column): void
    {
        global $post;

        if (MyParcelNL::CUSTOM_ORDER_COLUMN_ID === $column) {
            /** @var \MyParcelNL\Pdk\Plugin\Repository\AbstractPdkOrderRepository $orderRepository */
            $orderRepository = Pdk::get(AbstractPdkOrderRepository::class);

            $pdkOrder = $orderRepository->get($post->ID);

            echo RenderService::renderOrderListColumn($pdkOrder);
        }
    }
}
