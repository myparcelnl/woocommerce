<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Facade\Language;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;

/**
 * Adds links to the plugin actions and details in the plugins overview.
 */
class PluginInfoHooks implements WordPressHooksInterface
{
    public function apply(): void
    {
        $pluginBaseName = sprintf(
            '%s/woocommerce-myparcel.php',
            Pdk::get('pluginBaseName')
        );

        // Add links to the plugin actions.
        add_filter("plugin_action_links_$pluginBaseName", [$this, 'setPluginActionLinks']);

        // Add links to the plugin details.
        add_filter('plugin_row_meta', [$this, 'setPluginMeta'], 10, 2);
    }

    /**
     * @param  array $links
     *
     * @return array
     * @noinspection HtmlUnknownTarget
     */
    public function setPluginActionLinks(array $links): array
    {
        $appInfo  = Pdk::getAppInfo();
        $adminUrl = admin_url(sprintf('admin.php?page=%s', Pdk::get('settingsMenuSlugShort')));

        return array_merge([
            'settings' => sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                $adminUrl,
                $appInfo->title,
                __('Settings')
            ),
        ], $links);
    }

    /**
     * @param  array $links
     * @param        $file
     *
     * @return array
     * @noinspection HtmlUnknownTarget
     */
    public function setPluginMeta(array $links, $file): array
    {
        if (strpos($file, 'woocommerce-myparcelnl') !== false) {
            $link = '<a href="%s" target="_blank">%s</a>';

            $links[] = sprintf($link, Pdk::get('urlDocumentation'), Language::translate('link_documentation'));
            $links[] = sprintf($link, Pdk::get('urlReleaseNotes'), Language::translate('link_release_notes'));
        }

        return $links;
    }
}
