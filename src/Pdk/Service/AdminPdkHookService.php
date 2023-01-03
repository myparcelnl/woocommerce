<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Service;

use MyParcelNL;
use MyParcelNL\Pdk\Facade\DefaultLogger;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\RenderService;
use MyParcelNL\Pdk\Plugin\Repository\AbstractPdkOrderRepository;

/**
 * Responsible for hooking PDK components into the WordPress admin.
 */
class AdminPdkHookService implements WordPressHookServiceInterface
{
    private const SCRIPT_PDK_FRONTEND = 'myparcelnl-pdk-frontend';

    /**
     * @var \MyParcelNL\WooCommerce\Pdk\Service\ScriptService
     */
    private $service;

    /**
     * @param  \MyParcelNL\WooCommerce\Pdk\Service\ScriptService $service
     */
    public function __construct(ScriptService $service)
    {
        $this->service = $service;
    }

    public function initialize(): void
    {
        // Add MyParcel menu item
        add_action('admin_menu', [$this, 'registerMenuItem']);

        // Render custom column in order grid
        add_filter('manage_edit-shop_order_columns', [$this, 'registerMyParcelOrderGridColumn'], 20);

        // Load the js necessary to run the pdk frontend in the entire admin
        add_action('admin_enqueue_scripts', [$this, 'registerPdkScripts']);

        // Render pdk init scripts in the footer
        add_action('admin_footer', [$this, 'renderPdkInitScripts']);

        // Render main notification container in admin notices area
        add_action('admin_notices', [$this, 'renderPdkNotifications']);

        // Render pdk order list column in our custom order grid column
        add_action('manage_shop_order_posts_custom_column', [$this, 'renderPdkOrderListColumn']);

        // Render product settings in product edit page
        add_action('woocommerce_product_options_shipping', [$this, 'renderPdkProductSettings']);

        // Render order card in order edit page
        add_action('woocommerce_admin_order_data_after_shipping_address', [$this, 'renderPdkOrderCard']);
    }

    /**
     * @return void
     */
    public function registerMenuItem(): void
    {
        add_submenu_page(
            'woocommerce',
            __('MyParcel', 'woocommerce-myparcel'),
            __('MyParcel', 'woocommerce-myparcel'),
            'edit_pages',
            MyParcelNL::SETTINGS_MENU_SLUG,
            [$this, 'renderPdkPluginSettings']
        );
    }

    /**
     * @param  array $columns
     *
     * @return array
     */
    public function registerMyParcelOrderGridColumn(array $columns): array
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
     * @param  mixed $page
     *
     * @return void
     */
    public function registerPdkScripts($page): void
    {
        DefaultLogger::debug('registerPdkScripts', compact('page'));

        $this->service->enqueueVue('3.2.45');
        $this->service->enqueueVueDemi('0.13.11');
        $this->service->enqueueScript(
            self::SCRIPT_PDK_FRONTEND,
            sprintf('%s/views/backend/admin/lib/index.iife.js', Pdk::get('pluginUrl')),
            [ScriptService::HANDLE_JQUERY, ScriptService::HANDLE_VUE, 'vue-demi']
        );

        wp_enqueue_style(
            self::SCRIPT_PDK_FRONTEND,
            sprintf('%s/views/backend/admin/lib/style.css', Pdk::get('pluginUrl'))
        );
    }

    /**
     * @return void
     */
    public function renderPdkInitScripts(): void
    {
        echo RenderService::renderInitScript();
        echo RenderService::renderModals();
    }

    /**
     * @return void
     */
    public function renderPdkNotifications(): void
    {
        echo RenderService::renderNotifications();
    }

    /**
     * @return void
     */
    public function renderPdkOrderCard(): void
    {
        global $post;

        $orderRepository = Pdk::get(AbstractPdkOrderRepository::class);
        $order           = $orderRepository->get($post->ID);

        echo RenderService::renderOrderCard($order);
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

    /**
     * @return void
     */
    public function renderPdkPluginSettings(): void
    {
        echo RenderService::renderPluginSettings();
    }

    /**
     * @return void
     */
    public function renderPdkProductSettings(): void
    {
        global $post;

        /** @var \MyParcelNL\Pdk\Product\Repository\AbstractProductRepository $productRepository */
        $productRepository = Pdk::get(MyParcelNL\Pdk\Product\Repository\AbstractProductRepository::class);
        $product           = $productRepository->getProduct($post->ID);

        echo RenderService::renderProductSettings($product);
    }
}
