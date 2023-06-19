<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Service;

use MyParcelNL\Pdk\App\Cart\Model\PdkCart;
use MyParcelNL\Pdk\App\Order\Model\PdkProduct;
use MyParcelNL\Pdk\Context\Context;
use MyParcelNL\Pdk\Facade\Language;
use MyParcelNL\Pdk\Facade\Logger;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Frontend\Form\Components;
use MyParcelNL\Pdk\Frontend\Service\FrontendRenderService;
use MyParcelNL\Pdk\Frontend\View\AbstractSettingsView;
use MyParcelNL\Pdk\Frontend\View\ProductSettingsView;
use MyParcelNL\Pdk\Settings\Model\AbstractSettingsModel;
use MyParcelNL\Pdk\Settings\Model\CheckoutSettings;
use MyParcelNL\Sdk\src\Support\Str;
use Throwable;

class WcFrontendRenderService extends FrontendRenderService
{
    /**
     * @param  \MyParcelNL\Pdk\App\Cart\Model\PdkCart $cart
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
     * @param  \MyParcelNL\Pdk\App\Order\Model\PdkProduct $product
     *
     * @return string
     */
    public function renderProductSettings(PdkProduct $product): string
    {
        try {
            $appInfo = Pdk::getAppInfo();

            /** @var ProductSettingsView $productSettingsView */
            $productSettingsView = Pdk::get(ProductSettingsView::class);

            ob_start();

            printf('<div id="%s" class="panel woocommerce_options_panel">', "{$appInfo->name}_product_data");

            $elements = $productSettingsView->getElements();

            foreach ($elements ? $elements->toArray() : [] as $field) {
                $this->renderProductSettingsField($field);
            }

            echo '</div>';

            return ob_get_clean();
        } catch (Throwable $e) {
            Logger::error('Failed to render component', [
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
            AbstractSettingsModel::TRISTATE_VALUE_DEFAULT  => Language::translate('toggle_default'),
            AbstractSettingsModel::TRISTATE_VALUE_DISABLED => Language::translate('toggle_no'),
            AbstractSettingsModel::TRISTATE_VALUE_ENABLED  => Language::translate('toggle_yes'),
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
        $key    = Str::snake(sprintf('_%s_product_%s', $appInfo->name, $field['name'] ?? ''));

        $params = [
            'id'                => $key,
            'value'             => get_post_meta(get_the_ID(), $key, true),
            'label'             => isset($field['label']) ? Language::translate($field['label']) : null,
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
                    Language::translate($field['heading']),
                    Language::translate($field['content'])
                );
                break;

            default:
                $method = Str::snake(sprintf('woocommerce_wp%s', $field['$component']));
                break;
        }

        if ($method) {
            $descriptionKey = "{$field['label']}_description";
            $subtextKey     = "{$field['label']}_subtext";

            if (Language::hasTranslation($descriptionKey)) {
                $params['desc_tip'] = Language::translate($descriptionKey);
            }

            if (Language::hasTranslation($subtextKey)) {
                $params['description'] = Language::translate($subtextKey);
            }

            if (isset($params['custom_attributes']['$attributes'])) {
                $params['custom_attributes'] = array_merge($params['custom_attributes'], $params['custom_attributes']['$attributes']);
                unset($params['custom_attributes']['$attributes']);
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
        $translatedArray = Language::translateArray($flattenedArray);

        asort($translatedArray, SORT_NATURAL);

        $noneOption = $translatedArray[AbstractSettingsView::OPTIONS_VALUE_NONE] ?? null;

        if ($noneOption) {
            $translatedArray = [AbstractSettingsView::OPTIONS_VALUE_NONE => $noneOption] + $translatedArray;
        }

        return $translatedArray;
    }
}
