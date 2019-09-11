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
$deliveryOptions = wcmp_admin::getDeliveryOptionsFromOrder($order);

$packageTypeOptions = [];

foreach ($package_types as $packageType) {
    array_push($packageTypeOptions, $packageType);
}
echo "<pre>";
print_r($shipment_options);
echo "</pre>";

?>
<div class="wcmyparcelbe_change_order">
    <table class="wcmyparcelbe_settings_table">
        <tr>
            <td>
                <?php _e("Shipment type", "woocommerce-myparcelbe") ?>: <br/> <small class="calculated_weight">
                    <?php printf(
                        __("Calculated weight: %s", "woocommerce-myparcelbe"),
                        wc_format_weight($order->get_meta("_wcmp_order_weight"))
                    ); ?>
                </small>
            </td>
            <td>
                <?php
                $isPackageTypeDisabled = count($package_types) === 1 || $deliveryOptions->isPickup();

                woocommerce_form_field(
                    "myparcelbe_options[$order_id][package_type]",
                    [
                        "type"              => "select",
                        "class"             => ["package_type"],
                        "options"           => $packageTypeOptions,
                        "selected"          => $shipment_options["package_type"],
                        "custom_attributes" => [
                            "disabled" => $isPackageTypeDisabled ? "disabled" : null,
                        ],
                    ]
                );
                ?>
            </td>
        </tr>
        <tr>
            <td>
                <?php _e("Number of labels", "woocommerce-myparcelbe") ?>:
                <?php echo wc_help_tip(__("helpText", "woocommerce-myparcelbe")); ?>
            </td>
            <td>
                <?php
                $name         = "myparcelbe_options[$order_id][extra_options][colli_amount]";
                $colli_amount =
                    isset($myparcelbe_options_extra['colli_amount']) ? $myparcelbe_options_extra['colli_amount'] : 1;

                woocommerce_form_field(
                    "myparcelbe_options[$order_id][extra_options][colli_amount]",
                    [
                        "type"              => "number",
                        "value"             => $colli_amount,
                        "custom_attributes" => [
                            "step" => "1",
                            "min"  => "1",
                            "max"  => "10",
                        ],
                    ]
                );
                ?>
            </td>
        </tr>
        <?php

        $option_rows = [
            "[signature]" => [
                "label" => __("Signature on delivery", "woocommerce-myparcelbe"),
                "value" => isset($shipment_options["signature"]) ? $shipment_options["signature"] : 0,
            ],
            "[insured]"   => [
                "label" => __("Insured to &euro; 500", "woocommerce-myparcelbe"),
                "value" => WooCommerce_MyParcelBE()->setting_collection->getByName("insured") ? 1 : 0,
                "class" => "insured",
            ],
        ];

        if (isset($recipient['cc']) && $recipient['cc'] !== 'BE') {
            unset($option_rows['[signature]']);
        } ?>
        <!--    </table>-->
        <!--    <table class="wcmyparcelbe_settings_table parcel_options" >-->
        <tr>
            <th>Parcel options</th>
        </tr>
        <?php foreach ($option_rows as $option_name => $option_row): ?>
            <tr class="wcmyparcelbe_settings_table parcel_options">
                <td>
                    <?php
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

                    ?>
                </td>
                <td class="wcmp_option_cost">
                    <?php
                    if (! empty($option_row['cost'])) {
                        echo "+ &euro; {$option_row['cost']}";
                    }
                    ?>
                </td>
            </tr>
        <?php endforeach ?>
    </table>
    <div class="wcmp_save_shipment_settings">
        <?php
        echo get_submit_button(
            __('Save', 'woocommerce-myparcelbe'),
            null,
            null,
            null,
            [
                "class"      => "button save",
                "data-order" => $order_id,
            ]
        ); ?>
        <!--        <a class="button save" data-order="--><?php //echo $order_id; ?><!--">-->
        <?php //_e('Save', 'woocommerce-myparcelbe') ?><!--</a>-->
        <img alt="loading"
             src="<?php echo WooCommerce_MyParcelBE()->plugin_url() . '/assets/img/wpspin_light.gif'; ?>"
             class="wcmp_spinner waiting"/>
    </div>
</div>
