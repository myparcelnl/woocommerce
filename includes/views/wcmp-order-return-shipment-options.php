<table class="wcmyparcel_settings_table" style="width: auto">
	<tr>
		<td>
			<?php _e( 'Shipment type', 'woocommerce-myparcel' ) ?>:<br/>
			<?php $parcel_weight = WooCommerce_MyParcel()->export->get_parcel_weight( $order ); ?>
			<small class="calculated_weight"><?php printf( __( 'Calculated weight: %s kg', 'woocommerce-myparcel' ), number_format( $parcel_weight, 3, ',', ' ' ) ); ?></small>
		</td>
		<td>
			<?php
			$name = "myparcel_options[{$order_id}][package_type]";
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
<table class="wcmyparcel_settings_table parcel_options">
	<?php
	$shipment_options['insured'] = isset($shipment_options['insurance']['amount']) ? 1 : 0;
	if (!isset($shipment_options['insurance'])) {
		$shipment_options['insurance']['amount'] = '';
	}

	$option_rows = array(
		'[large_format]'	=> array(
			'label'	=> __( 'Extra large size', 'woocommerce-myparcel' ),
			'value'	=> $shipment_options['large_format'],
			'cost'	=> '2.19',
		),
		'[only_recipient]'	=> array(
			'label'	=> __( 'Home address only', 'woocommerce-myparcel' ),
			'value'	=> $shipment_options['only_recipient'],
			'cost'	=> '0.26',
		),
		'[signature]'	=> array(
			'label'	=> __( 'Signature on delivery', 'woocommerce-myparcel' ),
			'value'	=> $shipment_options['signature'],
			'cost'	=> !(WooCommerce_MyParcel()->export->is_pickup( $order )) ? '0.33' : '',
		),
		'[return]'	=> array(
			'label'	=> __( 'Return if no answer', 'woocommerce-myparcel' ),
			'value'	=> $shipment_options['return'],
		),
		'[insured]'	=> array(
			'label'	=> __( 'Insured + home address only + signature on delivery', 'woocommerce-myparcel' ),
			'value'	=> $shipment_options['insured'],
			'class'	=> 'insured',
		),
	);

	?>
	<?php foreach ($option_rows as $name => $option_row): ?>
	<tr>
		<td>
			<?php
			$name = "myparcel_options[{$order_id}]{$name}";
			$class = isset($option_row['class'])?$option_row['class']:'';
			$checked = isset($option_row['checked'])? $option_row['checked'] : checked( "1", $option_row['value'], false );
			printf('<input type="checkbox" name="%s" value="1" class="%s" %s>', $name, $class, $checked );
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
<table class="wcmyparcel_settings_table">
	<tr>
		<td><?php _e( 'Insurance', 'woocommerce-myparcel' ) ?></td>
		<td>
			<?php
			$insured_amounts = array(
				'49'		=> __( 'Insured up to &euro; 50' , 'woocommerce-myparcel' ).' (+ &euro; 0.50)',
				'249'		=> __( 'Insured up to  &euro; 250' , 'woocommerce-myparcel' ).' (+ &euro; 1.00)',
				'499'		=> __( 'Insured up to  &euro; 500' , 'woocommerce-myparcel' ).' (+ &euro; 1.50)',
				''			=> __( '> &euro; 500 insured' , 'woocommerce-myparcel' ).' (+ &euro; 1.50)',
			);
			$insured_amount = isset($shipment_options['insurance']['amount']) ? $shipment_options['insurance']['amount'] : '';

			$name = "myparcel_options[{$order_id}][insured_amount]";
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
</table>
<?php if (!isset($skip_save)): ?>
<div class="wcmp_save_shipment_settings">
	<a class="button save" data-order="<?php echo $order_id; ?>"><?php _e( 'Save', 'woocommerce-myparcel' ) ?></a>
	<img src="<?php echo WooCommerce_MyParcel()->plugin_url() . '/assets/img/wpspin_light.gif';?>" class="wcmp_spinner waiting"/>
</div>
<?php endif ?>