<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Service;

use MyParcelNL\Pdk\Facade\DefaultLogger;
use MyParcelNL\Pdk\Facade\LanguageService;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Frontend\Form\Components;
use MyParcelNL\Pdk\Frontend\Settings\View\ProductSettingsView;
use MyParcelNL\Pdk\Plugin\Model\PdkProduct;
use MyParcelNL\Pdk\Plugin\Service\RenderService;
use MyParcelNL\Sdk\src\Support\Str;
use Throwable;

class WcRenderService extends RenderService
{
    /**
     * @param  \MyParcelNL\Pdk\Plugin\Model\PdkProduct $product
     *
     * @return string
     * @throws \MyParcelNL\Pdk\Base\Exception\InvalidCastException
     */
    public function renderProductSettings(PdkProduct $product): string
    {
        try {
            $view = Pdk::get(ProductSettingsView::class);

            ob_start();

            $pluginName = Pdk::get('pluginName');

            printf('<div id="%s" class="panel woocommerce_options_panel">', "{$pluginName}_product_data");

            foreach ($view->toArray()['fields'] as $field) {
                $key    = Str::snake(sprintf('%s_product_%s', Pdk::get('pluginName'), $field['name']));
                $method = Str::snake('woocommerce_wp' . $field['$component']);

                $options = [
                    'id'    => $key,
                    'value' => get_post_meta(get_the_ID(), $key, true),
                    'label' => LanguageService::translate($field['label']),
                ];

                switch ($field['$component']) {
                    case Components::INPUT_TOGGLE:
                        $method             = 'woocommerce_wp_checkbox';
                        $options['cbvalue'] = 1;
                        break;

                    case Components::INPUT_SELECT:
                        $method             = 'woocommerce_wp_select';
                        $options['options'] = $field['options'] ?? [];
                        break;
                }

                if (isset($field['description']) && LanguageService::hasTranslation($field['description'])) {
                    $options['description'] = LanguageService::translate($field['description']);
                }

                $method($options);
            }

            echo '</div>';

            return ob_get_clean();
        } catch (Throwable $e) {
            DefaultLogger::error('Failed to render component', [
                'component' => self::COMPONENT_PRODUCT_SETTINGS,
                'exception' => $e,
            ]);

            return '';
        }
    }
}
