<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<table class="wcmyparcelbe_settings_table" style="width: auto">
	<tr>
		<td>
			<?php _e( 'Shipment type', 'woocommerce-myparcelbe' ) ?>:<br/>
			<?php $parcel_weight = WooCommerce_MyParcelBE()->export->get_parcel_weight( $order ); ?>
			<small class="calculated_weight"><?php printf( __( 'Calculated weight: %s kg', 'woocommerce-myparcelbe' ), number_format( $parcel_weight, 3, ',', ' ' ) ); ?></small>
		</td>
		<td>
			<?php
			// disable mailbox package outside NL
			if (isset($recipient['cc']) && $recipient['cc'] != 'NL') {
				unset($package_types[2]); // mailbox package
			}

			// disable mailbox package and unpaid letter for pakjegemak
			if ( WooCommerce_MyParcelBE()->export->is_pickup( $order ) ) {
				unset($package_types[2]); // mailbox package
				unset($package_types[3]); // unpaid letter
				$package_types[1] .= ' (Pakjegemak)';
			}

			$name = "myparcelbe_options[{$order_id}][package_type]";
			printf( '<select name="%s" class="package_type">', $name );
			foreach ( $package_types as $key => $label ) {
				printf( '<option value="%s"%s>%s</option>', $key, selected( $shipment_options['package_type'], $key, false ), $label );
			}
			echo '</select>';
			?>
		</td>
	</tr>
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
		'[large_format]'	=> array(
			'label'	=> __( 'Extra large size', 'woocommerce-myparcelbe' ),
			'value'	=> isset($shipment_options['large_format']) ? $shipment_options['large_format'] : 0,
			'cost'	=> '2.45',
		),
		'[signature]'	=> array(
			'label'	=> __( 'Signature on delivery', 'woocommerce-myparcelbe' ),
			'value'	=> isset($shipment_options['signature']) ? $shipment_options['signature'] : 0,
			'cost'	=> !(WooCommerce_MyParcelBE()->export->is_pickup( $order )) ? '0.36' : '',
		),
		'[return]'	=> array(
			'label'	=> __( 'Return if no answer', 'woocommerce-myparcelbe' ),
			'value'	=> isset($shipment_options['return']) ? $shipment_options['return'] : 0,
		),
		'[insured]'	=> array(
			'label'	=> __( 'Insured to &euro; 500', 'woocommerce-myparcelbe' ),
			'value'	=> $shipment_options['insured'],
			'class'	=> 'insured',
		),
	);

	if (isset($recipient['cc']) && $recipient['cc'] != 'NL') {
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

		$option_rows['[insured]'] = array(
			'label'		=> $insurance_text,
			'value'		=> $shipment_options['insured'],
			'class'		=> 'insured',
			'hidden'	=> 'yes',
		);
	}


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
