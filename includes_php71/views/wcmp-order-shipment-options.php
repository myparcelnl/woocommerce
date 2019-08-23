<?php if ( ! defined('ABSPATH')) exit; // Exit if accessed directly

// get parcel weight in grams
$parcel_weight = WooCommerce_MyParcelBE()->export->get_parcel_weight($order);
$parcel_weight_gram = WooCommerce_MyParcelBE()->export->get_parcel_weight($order, 'g');

?>
<a href="#" class="wcmyparcelbe_change_order">
    <table class="wcmyparcelbe_settings_table" onclick="return false;">
        <tr>
            <td>
                <?php _e('Shipment type', 'woocommerce-myparcelbe') ?>:<br />
                <?php $parcel_weight = WooCommerce_MyParcelBE()->export->get_parcel_weight($order); ?>
                <small class="calculated_weight"><?php printf(__('Calculated weight: %s kg', 'woocommerce-myparcelbe'), number_format($parcel_weight, 3, ',', ' ')); ?></small>
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
        <tr>
            <td>
                <?php _e('Number of labels', 'woocommerce-myparcelbe') ?>:
            </td>
            <td>
                <?php
                $name = "myparcelbe_options[{$order_id}][extra_options][colli_amount]";
                $colli_amount = isset($myparcelbe_options_extra['colli_amount']) ? $myparcelbe_options_extra['colli_amount']
                    : 1;
                printf('<input type="number" step="1" min="0" name="%s" value="%s" size="2">', $name, $colli_amount);
                ?>
            </td>
        </tr>
        <?php

        $option_rows = array(
            '[signature]'      => array(
                'label' => __('Signature on delivery', 'woocommerce-myparcelbe'),
                'value' => isset($shipment_options['signature']) ? $shipment_options['signature'] : 0,
            ),
            '[insured]'        => array(
                'label' => __('Insured to &euro; 500', 'woocommerce-myparcelbe'),
                'value' => WooCommerce_MyParcelBE()->setting_collection->getByName('insured') ? 1 : 0,
                'class' => 'insured',
            ),
        );

        if (isset($recipient['cc']) && $recipient['cc'] != 'BE') {
            unset($option_rows['[signature]']);
        } ?>
    </table>
    <table class="wcmyparcelbe_settings_table parcel_options">
        <?php foreach ($option_rows as $name => $option_row): ?>
            <tr>
                <td>
                    <?php
                    $name = "myparcelbe_options[{$order_id}]{$name}";
                    $class = isset($option_row['class']) ? $option_row['class'] : '';
                    $checked = isset($option_row['checked'])
                        ? $option_row['checked']
                        : checked(
                            "1",
                            $option_row['value'],
                            false
                        );
                    $disabled = isset($option_row['disabled']);
                    $type = isset($option_row['hidden']) ? 'hidden' : 'checkbox';
                    printf('<input type="%s" name="%s" value="1" class="%s" %s>', $type, $name, $class, $checked);
                    echo $option_row['label'];
                    ?>
                </td>
                <td class="wcmp_option_cost">
                    <?php
                    if ( ! empty($option_row['cost'])) {
                        echo "+ &euro; {$option_row['cost']}";
                    }
                    ?>
                </td>
            </tr>
        <?php endforeach ?>
    </table>

    <div class="wcmp_save_shipment_settings">
        <a class="button save" data-order="<?php echo $order_id; ?>"><?php _e('Save', 'woocommerce-myparcelbe') ?></a>
        <img src="<?php echo WooCommerce_MyParcelBE()->plugin_url() . '/assets/img/wpspin_light.gif'; ?>" class="wcmp_spinner waiting" />
    </div>
</a>