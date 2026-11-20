<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Hooks;

use MyParcelNL\Pdk\Facade\Frontend;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Frontend\Contract\ViewServiceInterface;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;
use MyParcelNL\WooCommerce\Service\WpScriptService;

final class PdkCoreHooks implements WordPressHooksInterface
{
    /**
     * @var \MyParcelNL\WooCommerce\Service\WpScriptService
     */
    private $service;

    /**
     * @param  \MyParcelNL\WooCommerce\Service\WpScriptService $service
     */
    public function __construct(WpScriptService $service)
    {
        $this->service = $service;
    }

    public function apply(): void
    {
        // Load the js necessary to run the pdk admin component
        add_action('admin_enqueue_scripts', [$this, 'registerPdkScripts']);

        // Render pdk init scripts in the footer
        add_action('admin_footer', [$this, 'renderPdkInitScripts']);

        // Render main notification container in admin notices area
        add_action('all_admin_notices', [$this, 'renderPdkNotifications']);

        // change script tags to script type=module for esm scripts
        add_filter('script_loader_tag', [$this, 'changeScriptTag'], 10, 2);
    }

    /**
     * @param  string $tag
     * @param  string $handle
     *
     * @return string
     */
    public function changeScriptTag(string $tag, string $handle): string
    {
        if (Pdk::isDevelopment() && in_array($handle, $this->service->getEsmHandles(), true)) {
            $tag = str_replace(' src', ' type="module" src', $tag);
        }

        return $tag;
    }

    /**
     * @return void
     */
    public function registerPdkScripts(): void
    {
        /** @var ViewServiceInterface $viewService */
        $viewService = Pdk::get(ViewServiceInterface::class);

        if (! $viewService->isAnyPdkPage()) {
            return;
        }

        $this->service->enqueueVue(Pdk::get('vueVersion'));

        $select = version_compare(WC()->version, '3.2.0', '>=') ? 'selectWoo' : 'select2';

        $dependencies = [WpScriptService::HANDLE_JQUERY, WpScriptService::HANDLE_VUE, $select];

        // WooCommerce only provides the data for its admin script on its own screens. The plugins overview only shows
        // the deactivation modal, which does not need it.
        if (! $viewService->isPluginsOverviewPage()) {
            $dependencies[] = WpScriptService::HANDLE_WOOCOMMERCE_ADMIN;
        }

        $this->service->enqueueLocalScript(
            WpScriptService::HANDLE_PDK_ADMIN,
            'views/backend/admin/dist/admin',
            $dependencies
        );

        $this->service->enqueueLocalStyle(WpScriptService::HANDLE_PDK_ADMIN, 'views/backend/admin/dist/style.css');
    }

    /**
     * @return void
     */
    public function renderPdkInitScripts(): void
    {
        echo Frontend::renderInitScript();
        echo Frontend::renderModals();
        echo Frontend::renderDeactivationFeedback();
    }

    /**
     * @return void
     */
    public function renderPdkNotifications(): void
    {
        echo Frontend::renderNotifications();
    }
}
