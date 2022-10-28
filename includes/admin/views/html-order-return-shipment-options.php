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
            <?php esc_html_e('Shipment type', 'woocommerce-myparcel') ?>:<br/> <small class="calculated_weight">
                <?php echo esc_html(sprintf(
                    __('calculated_order_weight', 'woocommerce-myparcel'),
                    wc_format_weight($orderSettings->getWeight())
                )) ?>
            </small>
        </td>
        <td>
            <?php
            echo "<select name=\"myparcel_options[$order_id][package_type]\" class=\"package_type\">";
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
                    esc_html($label)
                );
            }
            echo '</select>';
            ?>
        </td>
    </tr>
</table><br>
<?php if (! isset($skip_save)): ?>
    <div class="wcmp__d--flex">
        <a class="button save" data-order="<?php echo $order_id; ?>"><?php esc_html_e('Save', 'woocommerce-myparcel') ?>
            <?php WCMYPA_Admin::renderSpinner() ?>
        </a>
    </div>
<?php endif ?>
