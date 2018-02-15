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
			$name = "myparcelbe_options[{$order_id}][package_type]";
			printf( '<select name="%s" class="package_type">', $name );
			foreach ( $package_types as $key => $label ) {
				printf( '<option value="%s"%s>%s</option>', $key, selected( $shipment_options['package_type'], $key, false ), $label );
			}
			echo '</select>';
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
			'label'	=> __( 'Verzekerd to &euro; 500', 'woocommerce-myparcelbe' ),
			'value'	=> $shipment_options['insured'],
			'class'	=> 'insured',
		),
	);

	if (isset($recipient['cc']) && $recipient['cc'] != 'NL') {
		unset($option_rows['[signature]']);
		unset($option_rows['[return]']);
		$shipment_options['insured'] = 1;
		$option_rows['[insured]'] = array(
			'label'		=> __( 'Standard insurance up to €500 + signature on delivery', 'woocommerce-myparcelbe' ),
			'value'		=> $shipment_options['insured'],
			'class'		=> 'insured',
			'hidden'	=> 'yes',
		);

		$shipment_options['insurance']['amount'] = 499;
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
<?php if (!isset($skip_save)): ?>
<div class="wcmp_save_shipment_settings">
	<a class="button save" data-order="<?php echo $order_id; ?>"><?php _e( 'Save', 'woocommerce-myparcelbe' ) ?></a>
	<img src="<?php echo WooCommerce_MyParcelBE()->plugin_url() . '/assets/img/wpspin_light.gif';?>" class="wcmp_spinner waiting"/>
</div>
<?php endif ?>