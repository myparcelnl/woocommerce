<?php

use WPO\WC\MyParcelBE\Entity\DeliveryOptions;

/**
 * @var int   $order_id
 * @var array $package_types
 * @var array $shipment_options
 * @var array $myparcelbe_options_extra
 */

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/**
 * @type DeliveryOptions
 */
$deliveryOptions    = self::getDeliveryOptionsFromOrder($order);
$packageTypeOptions = [];

foreach ($package_types as $packageType) {
    array_push($packageTypeOptions, $packageType);
}

echo '<div class="wcmyparcelbe_change_order">';

$isPackageTypeDisabled = count($package_types) === 1 || $deliveryOptions->isPickup();

woocommerce_form_field(
    "myparcelbe_options[$order_id][package_type]",
    [
        "label"             => __("Shipment type", "woocommerce-myparcelbe"),
        "description"       => sprintf(
            __("Calculated weight: %s", "woocommerce-myparcelbe"),
            wc_format_weight($order->get_meta("_wcmp_order_weight"))
        ),
        "type"              => "select",
        "class"             => ["package_type"],
        "options"           => $packageTypeOptions,
        "selected"          => $shipment_options["package_type"],
        "custom_attributes" => [
            "disabled" => $isPackageTypeDisabled ? "disabled" : null,
        ],
    ]
);

woocommerce_form_field(
    "myparcelbe_options[$order_id][extra_options][colli_amount]",
    [
        "label"             => __("Number of labels", "woocommerce-myparcelbe"),
        "type"              => "number",
        "value"             => isset($myparcelbe_options_extra['colli_amount'])
            ? $myparcelbe_options_extra['colli_amount'] : 1,
        "custom_attributes" => [
            "step" => "1",
            "min"  => "1",
            "max"  => "10",
        ],
    ]
);

$option_rows = [
    "[signature]" => [
        "label" => __("Signature on delivery", "woocommerce-myparcelbe"),
        "value" => isset($shipment_options["signature"]) ? $shipment_options["signature"] : 0,
    ],
    "[insured]"   => [
        "label" => __("Insured to &euro; 500", "woocommerce-myparcelbe"),
        "value" => WCMP()->setting_collection->getByName("insured") ? 1 : 0,
        "class" => "insured",
    ],
];

if (isset($recipient['cc']) && $recipient['cc'] !== 'BE') {
    unset($option_rows['[signature]']);
}

foreach ($option_rows as $option_name => $option_row) {
    $isChecked = isset($option_row['checked'])
        ? $option_row['checked']
        : checked(
            "1",
            $option_row['value'],
            false
        );

    woocommerce_form_field(
        "myparcelbe_options[$order_id]$option_name",
        [
            "label"             => $option_row['label'],
            "type"              => isset($option_row['hidden']) ? 'hidden' : 'checkbox',
            "class"             => [isset($option_row['class']) ? $option_row['class'] : ''],
            "options"           => $packageTypeOptions,
            "selected"          => $shipment_options["package_type"],
            "custom_attributes" => [
                "checked"  => $isChecked,
                "disabled" => isset($option_row['disabled']) ? "disabled" : null,
            ],
        ]
    );

    echo '</td>';
    echo '<td class="wcmp_option_cost">';
    if (! empty($option_row['cost'])) {
        echo "+ &euro; {$option_row['cost']}";
    }
}

echo '<div class="wcmp_save_shipment_settings">';

echo get_submit_button(
    __('Save', 'woocommerce-myparcelbe'),
    null,
    null,
    null,
    [
        "class"      => "button save",
        "data-order" => $order_id,
    ]
);

$this->showSpinner();

echo '</div></div>';
