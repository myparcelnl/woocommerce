<?php if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

?>
<table class="wcmyparcelbe_settings_table" style="width: auto">
    <tr>
        <td>
            <?php _wcmpe('Shipment type') ?>:<br/> <small class="calculated_weight">
                <?php printf(
                    _wcmp('Calculated weight: %s'),
                    wc_format_weight($order->get_meta(WCMP_Admin::META_ORDER_WEIGHT))
                ) ?>
            </small>
        </td>
        <td>
            <?php
            $name = "myparcelbe_options[{$order_id}][package_type]";
            printf('<select name="%s" class="package_type">', $name);
            foreach ($package_types as $key => $label) {
                printf(
                    '<option value="%s"%s>%s</option>',
                    $key,
                    selected($shipment_options['package_type'], $key, false),
                    $label
                );
            }
            echo '</select>';
            ?>
        </td>
    </tr>
</table><br>
<?php if (! isset($skip_save)): ?>
    <div class="wcmp_save_shipment_settings">
        <a class="button save" data-order="<?php echo $order_id; ?>"><?php _wcmpe('Save') ?></a>
        <img src="<?php echo WCMP()->plugin_url() . '/assets/img/wpspin_light.gif'; ?>"
             class="wcmp_spinner waiting"/>
    </div>
<?php endif ?>
