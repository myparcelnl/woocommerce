<?php

/**
 * @var int   $order_id
 * @var array $package_types
 * @var array $shipment_options
 * @var array $myparcelbe_options_extra
 */

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

try {
    $deliveryOptions = WCMP_Admin::getDeliveryOptionsFromOrder($order);
} catch (Exception $e) {
    exit();
}

echo '<div class="wcmyparcelbe_change_order">';

$isPackageTypeDisabled = count($package_types) === 1 || $deliveryOptions->isPickup();

woocommerce_form_field(
    "myparcelbe_options[$order_id][package_type]",
    [
        "label"             => _wcmp("Shipment type"),
        "description"       => sprintf(
            _wcmp("Calculated weight: %s"),
            wc_format_weight($order->get_meta("_wcmp_order_weight"))
        ),
        "type"              => "select",
        "class"             => ["package_type"],
        "options"           => $package_types,
        "custom_attributes" => [
            "disabled" => $isPackageTypeDisabled ? "disabled" : null,
        ],
    ],
    $shipment_options["package_type"]
);

woocommerce_form_field(
    "myparcelbe_options[$order_id][extra_options][colli_amount]",
    [
        "label"             => _wcmp("Number of labels"),
        "type"              => "number",
        "custom_attributes" => [
            "step" => "1",
            "min"  => "1",
            "max"  => "10",
        ],
    ],
    isset($myparcelbe_options_extra['colli_amount']) ? $myparcelbe_options_extra['colli_amount'] : 1
);

$option_rows = [
    "[signature]" => [
        "label" => _wcmp("Signature on delivery"),
        "value" => isset($shipment_options["signature"]) ? $shipment_options["signature"] : 0,
    ],
    "[insured]"   => [
        "label" => _wcmp("Insured to &euro; 500"),
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
            "options"           => $package_types,
            "selected"          => $shipment_options["package_type"],
            "custom_attributes" => [
                "checked"  => $isChecked,
                "disabled" => isset($option_row['disabled']) ? "disabled" : null,
            ],
        ],
        $option_row["value"]
    );

    echo '</td>';
    echo '<td class="wcmp_option_cost">';
    if (! empty($option_row['cost'])) {
        echo "+ &euro; {$option_row['cost']}";
    }
}

echo '<div class="wcmp_save_shipment_settings">';

submit_button(
    null,
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
