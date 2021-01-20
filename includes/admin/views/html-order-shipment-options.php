<?php

use MyParcelNL\WooCommerce\Includes\Admin\OrderSettingsRows;
use WPO\WC\MyParcel\Entity\SettingsFieldArguments;

/**
 * @var WC_Order $order
 */

defined('ABSPATH') or die();

try {
    $deliveryOptions = WCMYPA_Admin::getDeliveryOptionsFromOrder($order);
} catch (Exception $e) {
    return;
}

?>
<div class="wcmp wcmp__shipment-options">
    <table>
    <?php

    WCMYPA_Admin::renderPickupLocation($deliveryOptions);

    $optionRows = OrderSettingsRows::getOptionsRows($deliveryOptions, $order);
    $optionRows = OrderSettingsRows::filterRowsByCountry($order->get_shipping_country(), $optionRows);

    $namePrefix = WCMYPA_Admin::SHIPMENT_OPTIONS_FORM_NAME . "[{$order->get_id()}]";

    foreach ($optionRows as $optionRow) :
        $class = new SettingsFieldArguments($optionRow, $namePrefix);

        // Cast boolean values to the correct enabled/disabled values.
        if (is_bool($optionRow["value"])) {
            $optionRow["value"] = $optionRow["value"] ? WCMP_Settings_Data::ENABLED : WCMP_Settings_Data::DISABLED;
        }

        $class->setValue($optionRow["value"]);
        ?>

        <tr>
            <td>
                <label for="<?php echo $class->getName() ?>">
                    <?php echo $class->getArgument('label'); ?>
                </label>
            </td>
            <td>
                <?php
                if (isset($optionRow['help_text'])) {
                    printf("<span class='ml-auto'>%s</span>", wc_help_tip($optionRow['help_text'], true));
                }
                ?>
            </td>
            <td>
                <?php WCMP_Settings_Callbacks::renderField($class); ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <tr>
        <td colspan="2">
            <div class="button wcmp__shipment-options__save">
                <?php
                _e("Save", "woocommerce-myparcel");
                WCMYPA_Admin::renderSpinner();
                ?>
            </div>
        </td>
    </tr>
    </table>
</div>
