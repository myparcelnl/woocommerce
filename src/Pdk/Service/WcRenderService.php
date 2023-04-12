<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Service;

use MyParcelNL\Pdk\Facade\DefaultLogger;
use MyParcelNL\Pdk\Facade\LanguageService;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Frontend\Form\Components;
use MyParcelNL\Pdk\Frontend\Settings\View\AbstractSettingsView;
use MyParcelNL\Pdk\Frontend\Settings\View\ProductSettingsView;
use MyParcelNL\Pdk\Plugin\Context;
use MyParcelNL\Pdk\Plugin\Model\PdkCart;
use MyParcelNL\Pdk\Plugin\Model\PdkProduct;
use MyParcelNL\Pdk\Plugin\Service\RenderService;
use MyParcelNL\Pdk\Settings\Model\AbstractSettingsModel;
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
        $context   = $this->contextService->createContexts([Context::ID_CHECKOUT], ['cart' => $cart]);

        printf(
            '<div id="mypa-delivery-options-wrapper" class="woocommerce-myparcel__delivery-options" data-context="%s">%s<div id="myparcel-delivery-options"></div></div>',
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
     */
    public function renderProductSettings(PdkProduct $product): string
    {
        try {
            $appInfo = Pdk::getAppInfo();

            /** @var \MyParcelNL\Pdk\Frontend\Settings\View\ProductSettingsView $productSettingsView */
            $productSettingsView = Pdk::get(ProductSettingsView::class);

            ob_start();

            printf('<div id="%s" class="panel woocommerce_options_panel">', "{$appInfo->name}_product_data");

            foreach ($productSettingsView->getElements() ?? [] as $field) {
                $this->renderProductSettingsField($field);
            }

            echo '</div>';

            return ob_get_clean();
        } catch (Throwable $e) {
            DefaultLogger::error('Failed to render component', [
                'component' => self::COMPONENT_PRODUCT_SETTINGS,
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            return '';
        }
    }

    /**
     * @return array
     */
    private function getTristateOptions(): array
    {
        return [
            AbstractSettingsModel::TRISTATE_VALUE_DEFAULT  => LanguageService::translate('toggle_default'),
            AbstractSettingsModel::TRISTATE_VALUE_DISABLED => LanguageService::translate('toggle_no'),
            AbstractSettingsModel::TRISTATE_VALUE_ENABLED  => LanguageService::translate('toggle_yes'),
        ];
    }

    /**
     * @param  array $field
     *
     * @return void
     */
    private function renderProductSettingsField(array $field): void
    {
        $appInfo = Pdk::getAppInfo();

        $method = null;
        $key    = Str::snake(sprintf('%s_product_%s', $appInfo->name, $field['name'] ?? ''));

        $params = [
            'id'                => $key,
            'value'             => get_post_meta(get_the_ID(), $key, true),
            'label'             => isset($field['label']) ? LanguageService::translate($field['label']) : null,
            'custom_attributes' => $field,
        ];

        switch ($field['$component']) {
            case Components::INPUT_TRISTATE:
                $method            = 'woocommerce_wp_select';
                $params['options'] = $this->getTristateOptions();
                break;

            case Components::INPUT_TOGGLE:
                $method            = 'woocommerce_wp_checkbox';
                $params['cbvalue'] = 1;
                break;

            case Components::INPUT_SELECT:
                $method            = 'woocommerce_wp_select';
                $params['options'] = $this->transformSelectOptions($field['options']);
                break;

            case Components::INPUT_NUMBER:
                $method         = 'woocommerce_wp_text_input';
                $params['type'] = 'number';
                break;

            case Components::SETTINGS_DIVIDER:
                echo sprintf(
                    '<h2>%s</h2><p class="description">%s</p><hr />',
                    LanguageService::translate($field['heading']),
                    LanguageService::translate($field['content'])
                );
                break;

            default:
                $method = Str::snake(sprintf('woocommerce_wp%s', $field['$component']));
                break;
        }

        if ($method) {
            $descriptionKey = "{$field['label']}_description";

            if (LanguageService::hasTranslation($descriptionKey)) {
                $params['desc_tip'] = LanguageService::translate($descriptionKey);
            }

            $method($params);
        }
    }

    /**
     * @param  array $options
     *
     * @return array
     */
    private function transformSelectOptions(array $options): array
    {
        $flattenedArray  = array_column($options, 'label', 'value');
        $translatedArray = LanguageService::translateArray($flattenedArray);

        asort($translatedArray, SORT_NATURAL);

        $noneOption = $translatedArray[AbstractSettingsView::OPTIONS_VALUE_NONE] ?? null;

        if ($noneOption) {
            $translatedArray = [AbstractSettingsView::OPTIONS_VALUE_NONE => $noneOption] + $translatedArray;
        }

        return $translatedArray;
    }
}
