<?php

declare(strict_types=1);

use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use MyParcelNL\WooCommerce\includes\admin\OrderSettings;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/**
 * @var \WC_Order $order
 * @var int       $order_id
 */

/** @noinspection PhpUnhandledExceptionInspection */
$orderSettings = new OrderSettings($order);

?>
<table class="wcmp__settings-table" style="width: auto">
    <tr>
        <td>
            <?php _e("Shipment type", "woocommerce-myparcel") ?>:<br/> <small class="calculated_weight">
                <?php printf(
                    __("calculated_order_weight", "woocommerce-myparcel"),
                    wc_format_weight($orderSettings->getWeight())
                ) ?>
            </small>
        </td>
        <td>
            <?php
            $name = "myparcel_options[{$order_id}][package_type]";
            printf('<select name="%s" class="package_type">', $name);
            foreach (WCMP_Data::getPackageTypesHuman() as $key => $label) {
                $isReturnPackageType = in_array(
                    $key,
                    [
                        AbstractConsignment::PACKAGE_TYPE_PACKAGE_NAME,
                        AbstractConsignment::PACKAGE_TYPE_MAILBOX_NAME,
                    ]
                );

                if (! $isReturnPackageType) {
                    return;
                }

                printf(
                    '<option value="%s">%s</option>',
                    WCMP_Data::getPackageTypeId($key),
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
