<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Hooks;

use MyParcelNL\Pdk\Facade\DefaultLogger;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\RenderService;
use MyParcelNL\WooCommerce\Hooks\WordPressHooksInterface;
use MyParcelNL\WooCommerce\Service\ScriptService;

class PdkCoreHooks implements WordPressHooksInterface
{
    private const SCRIPT_PDK_FRONTEND = 'myparcelnl-pdk-frontend';

    /**
     * @var \MyParcelNL\WooCommerce\Service\ScriptService
     */
    private $service;

    /**
     * @param  \MyParcelNL\WooCommerce\Service\ScriptService $service
     */
    public function __construct(ScriptService $service)
    {
        $this->service = $service;
    }

    public function apply(): void
    {
        // Load the js necessary to run the pdk frontend in the entire admin
        add_action('admin_enqueue_scripts', [$this, 'registerPdkScripts']);

        // Render pdk init scripts in the footer
        add_action('admin_footer', [$this, 'renderPdkInitScripts']);

        // Render main notification container in admin notices area
        add_action('admin_notices', [$this, 'renderPdkNotifications']);
    }

    /**
     * @param  mixed $page
     *
     * @return void
     */
    public function registerPdkScripts($page): void
    {
        DefaultLogger::debug('registerPdkScripts', compact('page'));

        $appInfo = Pdk::getAppInfo();

        wp_enqueue_style('woocommerce_admin_styles');

        $this->service->enqueueVue('3.2.45');
        $this->service->enqueueVueDemi('0.13.11');

        $select = version_compare(WC()->version, '3.2.0', '>=') ? 'selectWoo' : 'select2';
        $this->service->enqueueLocalScript(
            self::SCRIPT_PDK_FRONTEND,
            ('views/backend/admin/lib/index.iife.js'),
            [
                ScriptService::HANDLE_JQUERY,
                ScriptService::HANDLE_WOOCOMMERCE_ADMIN,
                ScriptService::HANDLE_VUE,
                'vue-demi',
                $select,
            ]
        );

        $this->service->enqueueStyle(
            self::SCRIPT_PDK_FRONTEND,
            sprintf('%s/views/backend/admin/lib/style.css', $appInfo['url']),
            [],
            $appInfo['version']
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
}
