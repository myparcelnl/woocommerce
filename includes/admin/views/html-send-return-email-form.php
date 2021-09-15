<?php

use WPO\WC\MyParcel\Compatibility\Order as WCX_Order;
use WPO\WC\MyParcel\Compatibility\WC_Core as WCX;

defined('ABSPATH') or die();

include('html-start.php');

/**
 * @var array $order_ids
 */

$export_return = WCMP_Export::EXPORT_RETURN;
$export        = WCMP_Export::EXPORT;

$order_ids_string = implode(';', $order_ids);

$target_url = wp_nonce_url(
    admin_url("admin-ajax.php?action=$export&request=$export_return&modal=true&order_ids=$order_ids_string"),
    WCMYPA::NONCE_ACTION
);

?>
    <form
        method="post" class="page-form wcmp__bulk-options wcmp__return-dialog" action="<?php echo $target_url; ?>">
        <table style="width: 100%">
            <tbody>
            <?php
            $c = true;
            foreach ($order_ids as $order_id) :
                $order         = WCX::get_order($order_id);
                $orderSettings = new OrderSettings($order);

                // skip non-myparcel destinations
                $shipping_country = WCX_Order::get_prop($order, 'shipping_country');
                if (! WCMP_Country_Codes::isAllowedDestination($shipping_country)) {
                    continue;
                }

                $recipient     = WCMP_Export::getRecipientFromOrder($order);
                $package_types = WCMP_Data::getPackageTypes();
                ?>
                <tr
                    class="order-row <?php echo(($c = ! $c)
                        ? 'alternate'
                        : ''); ?>">
                    <td>
                        <table style="width: 100%">
                            <tr>
                                <td colspan="2">
                                    <strong>
                                        <?php echo sprintf(
                                            "%s %s",
                                            __("Order", "woocommerce-myparcel"),
                                            $order->get_order_number()
                                        ); ?>
                                    </strong>
                                </td>
                            </tr>
                            <tr>
                                <td class="ordercell">
                                    <table class="widefat">
                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            <th><?php _e("Product name", "woocommerce-myparcel"); ?></th>
                                            <th class="wcmp__text--right"><?php _e("weight", "woocommerce-myparcel"); ?></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($order->get_items() as $item_id => $item) : ?>
                                            <tr>
                                                <td><?php echo $item['qty'] . 'x'; ?></td>
                                                <td><?php echo WCMP_Export::get_item_display_name($item, $order) ?></td>
                                                <td class="wcmp__text--right">
                                                    <?php

                                                    $weight = $item->get_product()->weight;

                                                    if ($weight) {
                                                        echo wc_format_weight($weight * $item['qty']);
                                                    } else {
                                                        echo esc_html__('N/A', 'woocommerce');
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                        <tr>
                                            <th>&nbsp;</th>
                                            <th><?php _e("Total weight", "woocommerce-myparcel"); ?></th>
                                            <th class="wcmp__text--right">
                                                <?php
                                                $weight = $orderSettings->getWeight();

                                                if ($weight) {
                                                    echo wc_format_weight($weight);
                                                } else {
                                                    echo esc_html__('N/A', 'woocommerce');
                                                }
                                                ?>
                                            </th>
                                        </tr>
                                        </tfoot>
                                    </table>
                                </td>
                                <td>
                                    <?php
                                    if (WCMP_Data::isHomeCountry($shipping_country)
                                    && (empty($recipient['street']) || empty($recipient['number']))): ?>
                                    <p>
                                        <span style="color:red">
                                            <?php __(
                                                "This order does not contain valid street and house number data and cannot be exported because of this! This order was probably placed before the MyParcel plugin was activated. The address data can still be manually entered in the order screen.",
                                                "woocommerce-myparcel"
                                            ); ?>
                                        </span>
                                    </p>
                                </td>
                            </tr> <!-- last row -->
                            <?php else: // required address data is available
                                // print address
                                echo '<p>' . $order->get_formatted_shipping_address() . '<br/>' . WCX_Order::get_prop(
                                        $order,
                                        'billing_phone'
                                    ) . '<br/>' . WCX_Order::get_prop($order, 'billing_email') . '</p>';
                                ?>
                                </td></tr>
                                <tr>
                                    <td
                                        colspan="2" class="wcmp__shipment-options">
                                        <?php
                                        $skip_save = true; // dont show save button for each order
                                        if (isset($dialog) && $dialog === 'shipment') {
                                            include('html-order-shipment-options.php');
                                        } else {
                                            include('html-order-return-shipment-options.php');
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div>
            <?php
            if (isset($dialog) && $dialog === 'shipment') {
                $button_text = __('action_export_to_myparcel', 'woocommerce-myparcel');
            } else {
                $button_text = __("Send email", "woocommerce-myparcel");
            }
            ?>
            <div class="wcmp__d--flex">
                <input type="submit" value="<?php echo $button_text; ?>" class="button wcmp__return-dialog__save">
                <?php WCMYPA_Admin::renderSpinner() ?>
            </div>
        </div>
    </form>

<?php

include('html-end.php');
