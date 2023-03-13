<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Hooks;

use MyParcelNL\Pdk\Facade\LanguageService;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\RenderService;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Plugin\Contract\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\Settings\Model\GeneralSettings;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;

class PdkOrderListHooks implements WordPressHooksInterface
{
    public function apply(): void
    {
        // Render custom column in order grid
        add_filter('manage_edit-shop_order_columns', [$this, 'registerMyParcelOrderListItem'], 20);

        // Render pdk order list column in our custom order grid column
        add_action('manage_shop_order_posts_custom_column', [$this, 'renderPdkOrderListItem']);

        // add bulk actions to order list
        add_filter('bulk_actions-edit-shop_order', [$this, 'registerBulkActions']);
    }

    /**
     * @param  array $actions
     *
     * @return mixed
     */
    public function registerBulkActions(array $actions): array
    {
        $pluginName = Pdk::getAppInfo()->name;

        foreach ($this->getBulkActions() as $action) {
            $actions["$pluginName.$action"] = LanguageService::translate($action);
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

        // Insert the column before the column we want to appear after
        foreach ($columns as $name => $data) {
            $newColumns[$name] = $data;

            if (Pdk::get('orderGridColumnBefore') !== $name) {
                continue;
            }

            $newColumns[Pdk::get('orderGridColumnName')] = Pdk::getAppInfo()->title;
        }

        return $newColumns;
    }

    /**
     * @param  string|mixed $column
     *
     * @return void
     */
    public function renderPdkOrderListItem($column): void
    {
        global $post;

        if (Pdk::getAppInfo()->name === $column) {
            /** @var \MyParcelNL\Pdk\Plugin\Contract\PdkOrderRepositoryInterface $orderRepository */
            $orderRepository = Pdk::get(PdkOrderRepositoryInterface::class);

            $pdkOrder = $orderRepository->get($post->ID);

            echo RenderService::renderOrderListItem($pdkOrder);
        }
    }

    /**
     * @return string[]
     */
    private function getBulkActions(): array
    {
        $orderMode = Settings::get(GeneralSettings::ORDER_MODE, GeneralSettings::ID);

        return $orderMode ? Pdk::get('bulkActionsOrderMode') : Pdk::get('bulkActions');
    }
}
