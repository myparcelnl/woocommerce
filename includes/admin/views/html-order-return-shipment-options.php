<?php use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/** @noinspection PhpUnhandledExceptionInspection */
$deliveryOptions = WCMYPA_Admin::getDeliveryOptionsFromOrder($order);

?>
<table class="wcmp__settings-table" style="width: auto">
    <tr>
        <td>
            <?php _e("Shipment type", "woocommerce-myparcel") ?>:<br/> <small class="calculated_weight">
                <?php printf(
                    __("Calculated weight: %s", "woocommerce-myparcel"),
                    wc_format_weight($order->get_meta(WCMYPA_Admin::META_ORDER_WEIGHT))
                ) ?>
            </small>
        </td>
        <td>
            <?php
            $name = "myparcel_options[{$order_id}][package_type]";
            printf('<select name="%s" class="package_type">', $name);
            foreach ($package_types as $key => $label) {
                printf(
                    '<option value="%s">%s</option>',
                    AbstractConsignment::PACKAGE_TYPE_PACKAGE,
                    $label
                );
            }
            echo '</select>';
            ?>
        </td>
    </tr>
</table><br>
<?php if (! isset($skip_save)): ?>
    <div class="wcmp__d--flex">
        <a class="button save" data-order="<?php echo $order_id; ?>"><?php _e("Save", "woocommerce-myparcel") ?>
            <?php WCMYPA_Admin::renderSpinner() ?>
        </a>
    </div>
<?php endif ?>
