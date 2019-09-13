<?php

use WPO\WC\MyParcelBE\Compatibility\WC_Core as WCX;
use WPO\WC\MyParcelBE\Compatibility\Order as WCX_Order;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <?php
    wp_enqueue_script(
        'wcmyparcelbe-export',
        WCMP()->plugin_url() . '/assets/js/wcmp-admin.js',
        ['jquery', 'thickbox', 'wp-color-picker'],
        WC_MYPARCEL_BE_VERSION
    );
    wp_localize_script(
        'wcmyparcelbe-export',
        'wc_myparcelbe',
        [
            'ajax_url'         => admin_url('admin-ajax.php'),
            'nonce'            => wp_create_nonce('wc_myparcelbe'),
            'download_display' => WCMP()->setting_collection->getByName('download_display') ? WCMP(
            )->setting_collection->getByName('download_display') : '',
        ]
    );

    wp_enqueue_style(
        'wcmp-admin-styles',
        WCMP()->plugin_url() . '/assets/css/wcmp-admin-styles.css',
        [],
        WC_MYPARCEL_BE_VERSION,
        'all'
    );

    // Legacy styles (WC 2.1+ introduced MP6 style with larger buttons)
    if (version_compare(WOOCOMMERCE_VERSION, '2.1', '<=')) {
        wp_enqueue_style(
            'wcmp-admin-styles-legacy',
            WCMP()->plugin_url() . '/assets/css/wcmp-admin-styles-legacy.css',
            [],
            WC_MYPARCEL_BE_VERSION,
            'all'
        );
    }

    wp_enqueue_style('wp-color-picker');
    wp_enqueue_style('wcmyparcelbe-admin-styles');
    wp_enqueue_style('colors');
    wp_enqueue_style('media');
    wp_enqueue_script('jquery');
    do_action('admin_print_styles');
    do_action('admin_print_scripts');
    ?>
</head>
<body>
<?php
$target_url =
    wp_nonce_url(admin_url('admin-ajax.php?action=wc_myparcelbe&request=add_return&modal=true'), 'wc_myparcelbe');
?>
<form method="post" class="page-form wcmp_bulk_options_form" action="<?php echo $target_url; ?>">
    <table class="widefat">
        <thead>
        <tr>
            <th><?php _wcmpe('Export options'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        $c = true;
        foreach ($order_ids as $order_id) :
            $order = WCX::get_order($order_id);
            // skip non-myparcelbe destinations
            $shipping_country = WCX_Order::get_prop($order, 'shipping_country');
            if (! WCMP()->export->is_myparcelbe_destination($shipping_country)) {
                continue;
            }
            $shipment_options         = WCMP()->export->get_options($order);
            $recipient                = WCMP()->export->get_recipient($order);
            $myparcelbe_options_extra = WCX_Order::get_meta($order, '_myparcelbe_shipment_options_extra');
            $package_types            = WCMP()->export->get_package_types($dialog);
            ?>
            <tr class="order-row <?php echo(($c = ! $c) ? 'alternate' : ''); ?>">
                <td>
                    <table style="width: 100%">
                        <tr>
                            <td colspan="2">
                                <strong>
                                    <?php echo _wcmp('Order') . $order->get_order_number(); ?>
                                </strong>
                            </td>
                        </tr>
                        <tr>
                            <td class="ordercell">
                                <table class="widefat">
                                    <thead>
                                    <tr>
                                        <th>#</th>
                                        <th><?php _wcmpe('Product name'); ?></th>
                                        <th align="right"><?php _wcmpe('Weight (kg)'); ?></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($order->get_items() as $item_id => $item) : ?>
                                        <tr>
                                            <td><?php echo $item['qty'] . 'x'; ?></td>
                                            <td><?php echo $this->get_item_display_name($item, $order) ?></td>
                                            <td align="right">
                                                <?php echo wc_format_weight(
                                                    $this->get_item_weight_kg($item, $order)
                                                ); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td><?php _wcmpe('Total weight'); ?></td>
                                        <td align="right"><?php echo wc_format_weight(
                                                $order->get_meta('_wcmp_order_weight')
                                            ); ?></td>
                                    </tr>
                                    </tfoot>
                                </table>
                            </td>
                            <td>
                                <?php
                                if ($shipping_country == 'BE'
                                    && (empty($recipient['street'])
                                        || empty($recipient['number']))) { ?>
                                <p><span style="color:red"><?php _wcmp(
                                            'This order does not contain valid street and house number data and cannot be exported because of this! This order was probably placed before the MyParcel BE plugin was activated. The address data can still be manually entered in the order screen.'
                                        ); ?></span></p>
                            </td>
                        </tr> <!-- last row -->
                        <?php
                        } else { // required address data is available
                            // print address
                            echo '<p>' . $order->get_formatted_shipping_address() . '<br/>' . WCX_Order::get_prop(
                                    $order,
                                    'billing_phone'
                                ) . '<br/>' . WCX_Order::get_prop($order, 'billing_email') . '</p>';
                            ?>
                            </td></tr>
                            <tr>
                                <td colspan="2" class="wcmp_shipment_options">
                                    <?php
                                    $skip_save = true; // dont show save button for each order
                                    if ($dialog === 'shipment') {
                                        include('html-order-shipment-options.php');
                                    } else if ($dialog === 'return') {
                                        include('html-order-return-shipment-options.php');
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php } // end else
                        ?>
                    </table>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <input type="hidden" name="action" value="wc_myparcelbe">
    <div class="wcmp_save_shipment_settings">
        <?php
        if ($dialog == 'shipment') {
            $button_text = _wcmp('Export to MyParcel BE');
        } else if ($dialog == 'return') {
            $button_text = _wcmp('Send email');
        }
        ?>
        <input type="submit" value="<?php echo $button_text; ?>" class="button save wcmp_export">
        <img src="<?php echo WCMP()->plugin_url() . '/assets/img/wpspin_light.gif'; ?>" class="wcmp_spinner"/>
    </div>
</form>
<script type="text/javascript">
jQuery(document).ready(function($) {
  $('.button-wcmyparcelbe').click(function() {
    $('.waiting').show();
  });
});
</script>
</body>
</html>
