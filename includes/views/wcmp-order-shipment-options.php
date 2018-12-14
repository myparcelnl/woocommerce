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
                // disable mailbox package outside NL
                if (isset($recipient['cc']) && $recipient['cc'] != 'BE') {
                    unset($package_types[WooCommerce_MyParcelBE_Export::MAILBOX_PACKAGE]); // mailbox package
                }

                // disable mailbox package and unpaid letter for pakjegemak
                if (WooCommerce_MyParcelBE()->export->is_pickup($order)) {
                    unset($package_types[WooCommerce_MyParcelBE_Export::MAILBOX_PACKAGE]);
                    unset($package_types[WooCommerce_MyParcelBE_Export::LETTER]);
                    unset($package_types[WooCommerce_MyParcelBE_Export::DIGITAL_STAMP]);
                    $package_types[WooCommerce_MyParcelBE_Export::PACKAGE] .= ' (Pakjegemak)';
                }

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
                $colli_amount = isset($myparcel_options_extra['colli_amount']) ? $myparcelbe_options_extra['colli_amount']
                    : 1;
                printf('<input type="number" step="1" min="0" name="%s" value="%s" size="2">', $name, $colli_amount);
                ?>
            </td>
        </tr>
        <?php

        $shipment_options['insured'] = isset($shipment_options['insurance']['amount']) ? 1 : 0;
        if ( ! isset($shipment_options['insurance'])) {
            $shipment_options['insurance']['amount'] = '';
        }

        $option_rows = array(
            '[large_format]'   => array(
                'label' => __('Extra large size', 'woocommerce-myparcelbe'),
                'value' => isset($shipment_options['large_format']) ? $shipment_options['large_format'] : 0,
                'cost'  => '2.45',
            ),
            '[only_recipient]' => array(
                'label' => __('Home address only', 'woocommerce-myparcelbe'),
                'value' => isset($shipment_options['only_recipient']) ? $shipment_options['only_recipient'] : 0,
                'cost'  => '0.29',
            ),
            '[signature]'      => array(
                'label' => __('Signature on delivery', 'woocommerce-myparcelbe'),
                'value' => isset($shipment_options['signature']) ? $shipment_options['signature'] : 0,
                'cost'  => ! (WooCommerce_MyParcelBE()->export->is_pickup($order)) ? '0.36' : '',
            ),
            '[return]'         => array(
                'label' => __('Return if no answer', 'woocommerce-myparcelbe'),
                'value' => isset($shipment_options['return']) ? $shipment_options['return'] : 0,
            ),
            '[insured]'        => array(
                'label' => __('Insured + home address only + signature on delivery', 'woocommerce-myparcelbe'),
                'value' => $shipment_options['insured'],
                'class' => 'insured',
            ),
        );

        if (isset($recipient['cc']) && $recipient['cc'] != 'BE') {
            if (WooCommerce_MyParcelBE()->export->is_world_shipment_country($recipient['cc'])) {
                unset($option_rows['[large_format]']);
            }
            unset($option_rows['[only_recipient]']);
            unset($option_rows['[signature]']);
            unset($option_rows['[return]']);

            $shipment_options['insured'] = 1;
            if (WooCommerce_MyParcelBE()->export->is_world_shipment_country($recipient['cc'])) {
                $shipment_options['insurance']['amount'] = 19900;
                $insurance_text = __('Standard insurance up to €200 + signature on delivery', 'woocommerce-myparcelbe');
            } else {
                $shipment_options['insurance']['amount'] = 49900;
                $insurance_text = __('Standard insurance up to €500 + signature on delivery', 'woocommerce-myparcelbe');
            }

            $option_rows['[insured]'] = array(
                'label'  => $insurance_text,
                'value'  => $shipment_options['insured'],
                'class'  => 'insured',
                'hidden' => 'yes',
            );
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
    <table class="wcmyparcelbe_settings_table digital_stamp_options">
        <tr>
            <td>
                <label for="myparcelbe_options_weight"><?php _e('Weight:', 'woocommerce-myparcelbe') ?></label>
            </td>
            <td>
                <?php
                $name = "myparcelbe_options[{$order_id}][weight]";
                // use grams
                $current_tier_range = WooCommerce_MyParcelBE_Export::find_tier_range($parcel_weight_gram);

                printf('<select name="%s">', $name);
                foreach (WooCommerce_MyParcelBE_Export::get_tier_ranges(true) as $tier_range => $weight) {
                    printf(
                        '<option id="myparcelbe_options_weight" value="%s"%s>%s – %s %s</option>',
                        $weight['average'],
                        selected($current_tier_range == $tier_range),
                        $weight['min'],
                        $weight['max'],
                        __('gram', 'woocommerce-myparcelbe')
                    );
                }
                printf('</select>');
                ?>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <small><?php _e(
                        '<strong>Note:</strong> Digital stamps are only available if weight is under 2000g and dimensions are within 14 x 9 cm and 38 x 26,5 x 3,2 cm.',
                        'woocommerce-myparcelbe'
                    ) ?></small>
            </td>
        </tr>
    </table>
    <table>
        <?php
        $insured_amount = isset($shipment_options['insurance']['amount'])
            ? (int) $shipment_options['insurance']['amount'] : 0;
        $insured_amount = $insured_amount / 100; // frontend is in euros
        $name = "myparcelbe_options[{$order_id}][insured_amount]";
        if (isset($recipient['cc']) && $recipient['cc'] == 'NL') {
            ?>
            <tr>
                <td><?php _e('Insurance', 'woocommerce-myparcelbe') ?></td>
                <td>
                    <?php
                    $insured_amounts = array(
                        '49' => __('Insured up to &euro; 50', 'woocommerce-myparcelbe') . ' (+ &euro; 0.50)',
                        '249' => __('Insured up to &euro; 250', 'woocommerce-myparcelbe') . ' (+ &euro; 1.00)',
                        '499' => __('Insured up to &euro; 500', 'woocommerce-myparcelbe') . ' (+ &euro; 1.65)',
                        '' => __(
                                  '> &euro; 500 insured',
                                  'woocommerce-myparcelbe'
                              ) . ' (+ &euro; 1.65 / &euro; 500)',
                    );
                    printf('<select name="%s" class="insured_amount">', $name);
                    foreach ($insured_amounts as $key => $label) {
                        printf(
                            '<option value="%s"%s>%s</option>',
                            $key,
                            selected($insured_amount, $key, false),
                            $label
                        );
                    }
                    echo '</select>';
                    ?>
                </td>
            </tr>
            <tr>
                <td>
                    <?php _e('Insured amount', 'woocommerce-myparcelbe') ?>
                </td>
                <td>
                    <?php
                    $name = "myparcelbe_options[{$order_id}][insured_amount]";
                    printf(
                        '<input type="text" name="%s" value="%s" class="insured_amount">',
                        $name,
                        $insured_amount
                    );
                    ?>
                </td>
            </tr>
            <?php
        } else {
            printf(
                '<tr><td colspan="2" class="hidden"><input type="hidden" name="%s" value="%s"></td></tr>',
                $name,
                $insured_amount
            );
        }
        ?>
        <tr>
            <td><?php _e('Custom ID (top left on label)', 'woocommerce-myparcelbe') ?></td>
            <td>
                <?php
                $name = "myparcelbe_options[{$order_id}][label_description]";
                printf('<input type="text" name="%s" value="%s">', $name, $shipment_options['label_description']);
                ?>
            </td>
        </tr>
    </table>

    <div class="wcmp_save_shipment_settings">
        <a class="button save" data-order="<?php echo $order_id; ?>"><?php _e('Save', 'woocommerce-myparcelbe') ?></a>
        <img src="<?php echo WooCommerce_MyParcelBE()->plugin_url() . '/assets/img/wpspin_light.gif'; ?>" class="wcmp_spinner waiting" />
    </div>
</a>