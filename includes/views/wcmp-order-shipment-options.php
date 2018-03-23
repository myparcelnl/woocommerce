<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<a href="#" class="wcmyparcel_change_order">
    <table class="wcmyparcel_settings_table" style="width: auto" onclick="return false;">
        <tr>
            <td>
                <?php _e( 'Shipment type', 'woocommerce-myparcel' ) ?>:<br/>
                <?php $parcel_weight = WooCommerce_MyParcel()->export->get_parcel_weight( $order ); ?>
                <small class="calculated_weight"><?php printf( __( 'Calculated weight: %s kg', 'woocommerce-myparcel' ), number_format( $parcel_weight, 3, ',', ' ' ) ); ?></small>
            </td>
            <td>
            <?php
                // disable mailbox package outside NL
                if (isset($recipient['cc']) && $recipient['cc'] != 'NL') {
                    unset($package_types[2]); // mailbox package
                }


<table class="wcmyparcelbe_settings_table" style="width: auto">
    <tr>
		<td>
			<?php _e( 'Number of labels', 'woocommerce-myparcelbe' ) ?>:
		</td>
		<td>
			<?php
			$name = "myparcelbe_options[{$order_id}][extra_options][colli_amount]";
			$colli_amount = isset( $myparcelbe_options_extra['colli_amount'] ) ? $myparcelbe_options_extra['colli_amount'] : 1;
			printf('<input type="number" step="1" min="0" name="%s" value="%s" size="2">', $name, $colli_amount);
			?>
		</td>
	</tr>
</table>
<br>
<table class="wcmyparcelbe_settings_table parcel_options">
	<?php
	$shipment_options['insured'] = isset($shipment_options['insurance']['amount']) ? 1 : 0;
	if (!isset($shipment_options['insurance'])) {
		$shipment_options['insurance']['amount'] = '';
	}

	$option_rows = array(
		'[signature]'	=> array(
			'label'	=> __( 'Signature on delivery', 'woocommerce-myparcelbe' ),
			'value'	=> isset($shipment_options['signature']) ? $shipment_options['signature'] : 0,
		),
		'[insured]'	=> array(
			'label'	=> __( 'Insured to &euro; 500', 'woocommerce-myparcelbe' ),
			'value'	=> $shipment_options['insured'],
			'class'	=> 'insured',
		),
	);

	if (isset($recipient['cc']) && $recipient['cc'] != 'BE') {
		if ( WooCommerce_MyParcelBE()->export->is_world_shipment_country( $recipient['cc'] ) ) {
			unset($option_rows['[large_format]']);
		}
		unset($option_rows['[signature]']);
		unset($option_rows['[return]']);

		$shipment_options['insured'] = 1;
		if ( WooCommerce_MyParcelBE()->export->is_world_shipment_country( $recipient['cc'] ) ) {
			$shipment_options['insurance']['amount'] = 19900;
			$insurance_text = __( 'Standard insurance up to €200 + signature on delivery', 'woocommerce-myparcelbe' );
		} else {
			$shipment_options['insurance']['amount'] = 49900;
			$insurance_text = __( 'Standard insurance up to €500 + signature on delivery', 'woocommerce-myparcelbe' );
    }

        ?>
        <?php foreach ($option_rows as $name => $option_row): ?>
        <tr>
            <td>
                <?php
                $name = "myparcel_options[{$order_id}]{$name}";
                $class = isset($option_row['class'])?$option_row['class']:'';
                $checked = isset($option_row['checked'])? $option_row['checked'] : checked( "1", $option_row['value'], false );
                $type = isset($option_row['hidden']) ? 'hidden' : 'checkbox';
                printf('<input type="%s" name="%s" value="1" class="%s" %s>', $type, $name, $class, $checked );
                echo $option_row['label'];
                ?>
            </td>
            <td class="wcmp_option_cost">
                <?php
                if (!empty($option_row['cost'])) {
                    echo "+ &euro; {$option_row['cost']}";
                }
                ?>
            </td>
        </tr>
        <?php endforeach ?>
    </table>
    <table class="wcmyparcel_settings_table" onclick="return false;">
        <?php
        $insured_amount = isset($shipment_options['insurance']['amount']) ? (int) $shipment_options['insurance']['amount'] : 0;
        $insured_amount = $insured_amount / 100; // frontend is in euros
        $name = "myparcel_options[{$order_id}][insured_amount]";
        if (isset($recipient['cc']) && $recipient['cc'] == 'NL') {
            ?>
            <tr>
                <td><?php _e( 'Insurance', 'woocommerce-myparcel' ) ?></td>
                <td>
                    <?php
                    $insured_amounts = array(
                        '49'		=> __( 'Insured up to &euro; 50' , 'woocommerce-myparcel' ).' (+ &euro; 0.50)',
                        '249'		=> __( 'Insured up to  &euro; 250' , 'woocommerce-myparcel' ).' (+ &euro; 1.00)',
                        '499'		=> __( 'Insured up to  &euro; 500' , 'woocommerce-myparcel' ).' (+ &euro; 1.65)',
                        ''			=> __( '> &euro; 500 insured' , 'woocommerce-myparcel' ).' (+ &euro; 1.65 / &euro; 500)',
                    );
                    printf( '<select name="%s" class="insured_amount">', $name );
                    foreach ( $insured_amounts as $key => $label ) {
                        printf( '<option value="%s"%s>%s</option>', $key, selected( $insured_amount, $key, false ), $label );
                    }
                    echo '</select>';
                    ?>
                </td>
            </tr>
            <tr>
                <td>
                    <?php _e( 'Insured amount', 'woocommerce-myparcel' ) ?>
                </td>
                <td>
                    <?php
                    $name = "myparcel_options[{$order_id}][insured_amount]";
                    printf('<input type="text" name="%s" value="%s" style="width:100%%" class="insured_amount">', $name, $insured_amount);
                    ?>
                </td>
            </tr>
            <?php
        } else {
            printf('<tr><td colspan="2" style="display:none;"><input type="hidden" name="%s" value="%s"></td></tr>', $name, $insured_amount );
        }
        ?>
        <tr>
            <td><?php _e( 'Custom ID (top left on label)', 'woocommerce-myparcel' ) ?></td>
            <td>
                <?php
                $name = "myparcel_options[{$order_id}][label_description]";
                printf('<input type="text" name="%s" value="%s" style="width:100%%">', $name, $shipment_options['label_description']);
                ?>
            </td>
        </tr>
    </table>


	?>
	<?php foreach ($option_rows as $name => $option_row): ?>
	<tr>
		<td>
			<?php
			$name = "myparcelbe_options[{$order_id}]{$name}";
			$class = isset($option_row['class'])?$option_row['class']:'';
			$checked = isset($option_row['checked'])? $option_row['checked'] : checked( "1", $option_row['value'], false );
			$type = isset($option_row['hidden']) ? 'hidden' : 'checkbox';
			printf('<input type="%s" name="%s" value="1" class="%s" %s>', $type, $name, $class, $checked );
			echo $option_row['label'];
			?>
		</td>
		<td class="wcmp_option_cost">
			<?php
			if (!empty($option_row['cost'])) {
				echo "+ &euro; {$option_row['cost']}";
			}
			?>
		</td>
	</tr>
	<?php endforeach ?>
</table>


<div class="wcmp_save_shipment_settings">
	<a class="button save" data-order="<?php echo $order_id; ?>"><?php _e( 'Save', 'woocommerce-myparcelbe' ) ?></a>
	<img src="<?php echo WooCommerce_MyParcelBE()->plugin_url() . '/assets/img/wpspin_light.gif';?>" class="wcmp_spinner waiting"/>
</div>

