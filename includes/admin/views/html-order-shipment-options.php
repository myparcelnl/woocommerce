<?php

/**
 * @var int   $order_id
 * @var array $package_types
 * @var array $shipment_options
 * @var array $myparcelbe_options_extra
 */

use WPO\WC\MyParcelBE\Entity\SettingsFieldArguments;

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

$option_rows = [
    [
        "name"              => "[carrier]",
        "label"             => _wcmp("Carrier"),
        "type"              => "select",
        "custom_attributes" => [
            "disabled" => true,
        ],
        "value"             => $deliveryOptions->getCarrier(),
    ],
    [
        "name"              => "[$order_id][package_type]",
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
        "value"             => $shipment_options["package_type"],
    ],
    [
        "name"              => "[$order_id][extra_options][colli_amount]",
        "label"             => _wcmp("Number of labels"),
        "type"              => "number",
        "custom_attributes" => [
            "step" => "1",
            "min"  => "1",
            "max"  => "10",
        ],
        "value"             => isset($myparcelbe_options_extra['colli_amount'])
            ? $myparcelbe_options_extra['colli_amount'] : 1,
    ],
    [
        "name"              => "[signature]",
        "type"              => "toggle",
        "label"             => _wcmp("Signature on delivery"),
        "value"             => isset($shipment_options["signature"]) ? $shipment_options["signature"] : 0,
        "custom_attributes" => [
            "disabled" => isset($option_row['disabled']) ? "disabled" : null,
        ],
    ],
    [
        "name"              => "[insured]",
        "type"              => "toggle",
        "label"             => _wcmp("Insured to &euro; 500"),
        "value"             => WCMP()->setting_collection->getByName("insured") ? 1 : 0,
        "custom_attributes" => [
            "disabled" => isset($option_row['disabled']) ? "disabled" : null,
        ],
    ],
];

if (isset($recipient) && isset($recipient['cc']) && $recipient['cc'] !== 'BE') {
    unset($option_rows['[signature]']);
}

foreach ($option_rows as $option_row) {
    $class = new SettingsFieldArguments($option_row);

    woocommerce_form_field(
        "myparcelbe_options" . $class->name,
        $class->getArguments(false),
        $class->value
    );

    echo '</td>';

    echo '<td class="wcmp_option_cost">';
    if (! empty($option_row['cost'])) {
        echo "+ &euro; {$option_row['cost']}";
    }
    echo '</td>';
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
