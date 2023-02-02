<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Service;

use MyParcelNL\Pdk\Facade\DefaultLogger;
use MyParcelNL\Pdk\Facade\LanguageService;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Frontend\Form\Components;
use MyParcelNL\Pdk\Frontend\Settings\View\ProductSettingsView;
use MyParcelNL\Pdk\Plugin\Context;
use MyParcelNL\Pdk\Plugin\Model\PdkCart;
use MyParcelNL\Pdk\Plugin\Model\PdkProduct;
use MyParcelNL\Pdk\Plugin\Service\RenderService;
use MyParcelNL\Pdk\Settings\Model\CheckoutSettings;
use MyParcelNL\Sdk\src\Support\Str;
use Throwable;

class WcRenderService extends RenderService
{
    /**
     * @param  \MyParcelNL\Pdk\Plugin\Model\PdkCart $cart
     *
     * @return string
     * @throws \MyParcelNL\Pdk\Base\Exception\InvalidCastException
     */
    public function renderDeliveryOptions(PdkCart $cart): string
    {
        ob_start();

        do_action('woocommerce_myparcel_before_delivery_options');

        $customCss = Settings::get(CheckoutSettings::DELIVERY_OPTIONS_CUSTOM_CSS, CheckoutSettings::ID);
        $context   = $this->contextService->createContexts([Context::ID_DELIVERY_OPTIONS], ['cart' => $cart]);

        printf(
            '<div id="mypa-delivery-options-wrapper" class="woocommerce-myparcel__delivery-options" data-delivery-options-context="%s">%s<div id="myparcel-delivery-options-wrapper"></div></div>',
            $this->encodeContext($context),
            $customCss ? sprintf('<style>%s</style>', $customCss) : ''
        );

        do_action('woocommerce_myparcel_after_delivery_options');

        return ob_get_clean();
    }

    /**
     * @param  \MyParcelNL\Pdk\Plugin\Model\PdkProduct $product
     *
     * @return string
     * @throws \MyParcelNL\Pdk\Base\Exception\InvalidCastException
     */
    public function renderProductSettings(PdkProduct $product): string
    {
        try {
            $appInfo = Pdk::getAppInfo();
            $view    = Pdk::get(ProductSettingsView::class);

            ob_start();

            $pluginName = $appInfo['name'];

            printf('<div id="%s" class="panel woocommerce_options_panel">', "{$pluginName}_product_data");

            foreach ($view->toArray()['elements'] as $field) {
                $key    = Str::snake(sprintf('%s_product_%s', $appInfo['name'], $field['name']));
                $method = Str::snake('woocommerce_wp' . $field['$component']);

                $options = [
                    'id'    => $key,
                    'value' => get_post_meta(get_the_ID(), $key, true),
                    'label' => LanguageService::translate($field['label']),
                ];

                switch ($field['$component']) {
                    case Components::INPUT_TRISTATE:
                        $method             = 'woocommerce_wp_select';
                        $options['options'] = [
                            -1 => LanguageService::translate('Default'),
                            0  => LanguageService::translate('No'),
                            1  => LanguageService::translate('Yes'),
                        ];
                        break;
                    case Components::INPUT_TOGGLE:
                        $method             = 'woocommerce_wp_checkbox';
                        $options['cbvalue'] = 1;
                        break;

                    case Components::INPUT_SELECT:
                        $method             = 'woocommerce_wp_select';
                        $options['options'] = $this->transformSelectOptions($field['options'] ?? []);
                        break;
                    case Components::INPUT_NUMBER:
                        $method          = 'woocommerce_wp_text_input';
                        $options['type'] = 'number';
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

    /**
     * @param  array $options
     *
     * @return array
     */
    private function transformSelectOptions(array $options): array
    {
        $newOptions = [];

        foreach ($options as $option) {
            $newOptions[$option['value']] = LanguageService::translate($option['label']);
        }

        return $newOptions;
    }
}
