
<table class="wcmyparcel_settings_table" style="width: auto">
	<tr>
		<td>
			<?php _e( 'Shipment type', 'woocommerce-myparcel' ) ?>:<br/>
			<small class="calculated_weight"><?php printf( __( 'Calculated weight: %s kg', 'woocommerce-myparcel' ), number_format( $consignment['weight'], 3, ',', ' ' ) ); ?></small>
		</td>
		<td>
			
			<?php
			$shipment_types = array(
				'standard'		=> __( 'Parcel' , 'woocommerce-myparcel' ),
				'letterbox'		=> __( 'Letterbox' , 'woocommerce-myparcel' ),
				'unpaid_letter'	=> __( 'Unpaid letter' , 'woocommerce-myparcel' ),
			);

			// disable letterbox outside NL
			if (isset($consignment['ToAddress']['country_code']) && $consignment['ToAddress']['country_code'] != 'NL') {
				unset($shipment_types['letterbox']);
			}

			// disable letterbox and unpaid letter for pakjegemak
			if ( WooCommerce_MyParcel()->export->is_pakjegemak( $order ) ) {
				unset($shipment_types['letterbox']);
				unset($shipment_types['unpaid_letter']);
				$shipment_types['standard'] .= ' (Pakjegemak)';
			}										

			$name = "consignments[{$order_id}][shipment_type]";
			printf( '<select name="%s" class="shipment_type">', $name );
			foreach ( $shipment_types as $key => $label ) {
				printf( '<option value="%s"%s>%s</option>', $key, selected( $consignment['shipment_type'], $key, false ), $label );
			}
			echo '</select>';
			?>										
		</td>
	</tr>
	<tr>
		<td>
			<?php _e( 'Number of labels', 'woocommerce-myparcel' ) ?>:
		</td>
		<td>
			<?php
			$name = "consignments[{$order_id}][colli_amount]";
			printf('<input type="number" step="1" min="0" name="%s" value="%s" size="2">', $name, 1);
			?>								
		</td>
	</tr>
</table>
<br>
<table class="wcmyparcel_settings_table parcel_options">
	<?php
	$option_rows = array(
		'[ProductCode][extra_size]'	=> array(
			'label'	=> __( 'Extra large size', 'woocommerce-myparcel' ),
			'value'	=> $consignment['ProductCode']['extra_size'],
			'cost'	=> '2.19',
		),
		'[ProductCode][home_address_only]'	=> array(
			'label'	=> __( 'Home address only', 'woocommerce-myparcel' ),
			'value'	=> $consignment['ProductCode']['home_address_only'],
			'cost'	=> '0.26',
		),
		'[ProductCode][signature_on_receipt]'	=> array(
			'label'	=> __( 'Signature on delivery', 'woocommerce-myparcel' ),
			'value'	=> $consignment['ProductCode']['signature_on_receipt'],
			'cost'	=> empty($order->myparcel_is_pakjegemak) ? '0.33' : '',
		),
		'[ProductCode][home_address_signature]'	=> array(
			'label'	=> __( 'Home address only + signature on delivery', 'woocommerce-myparcel' ),
			'value'	=> $consignment['ProductCode']['home_address_signature'],
			'cost'	=> '0.40',
		),
		'[ProductCode][return_if_no_answer]'	=> array(
			'label'	=> __( 'Return if no answer', 'woocommerce-myparcel' ),
			'value'	=> $consignment['ProductCode']['return_if_no_answer'],
		),
		'[ProductCode][insured]'	=> array(
			'label'	=> __( 'Insured + home address only + signature on delivery', 'woocommerce-myparcel' ),
			'value'	=> $consignment['ProductCode']['insured'],
			'class'	=> 'insured',
		),
	);

	?>
	<?php foreach ($option_rows as $name => $option_row): ?>
	<tr>
		<td>
			<?php
			$name = "consignments[{$order_id}]{$name}";
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
			$insured_amount = array(
				'49'		=> __( 'Insured up to &euro; 50' , 'woocommerce-myparcel' ).' (+ &euro; 0.50)',
				'249'		=> __( 'Insured up to  &euro; 250' , 'woocommerce-myparcel' ).' (+ &euro; 1.00)',
				'499'		=> __( 'Insured up to  &euro; 500' , 'woocommerce-myparcel' ).' (+ &euro; 1.50)',
				''			=> __( '> &euro; 500 insured' , 'woocommerce-myparcel' ).' (+ &euro; 1.50)',
			);

			$name = "consignments[{$order_id}][insured_amount]";
			printf( '<select name="%s" class="insured_amount">', $name );
			foreach ( $insured_amount as $key => $label ) {
				printf( '<option value="%s"%s>%s</option>', $key, selected( $consignment['insured_amount'], $key, false ), $label );
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
			$name = "consignments[{$order_id}][insured_amount]";
			printf('<input type="text" name="%s" value="%s" style="width:100%%" class="insured_amount">', $name, $consignment['insured_amount']);
			?>
		</td>
	</tr>
	<tr>
		<td><?php _e( 'Custom ID (top left on label)', 'woocommerce-myparcel' ) ?></td>
		<td>
			<?php
			$name = "consignments[{$order_id}][custom_id]";
			printf('<input type="text" name="%s" value="%s" style="width:100%%">', $name, $consignment['custom_id']);
			?>
		</td>
	</tr>
</table>

<div class="wcmp_save_shipment_settings">
	<a class="button"><?php _e( 'Save', 'woocommerce-myparcel' ) ?></a>
	<img src="<?php echo WooCommerce_MyParcel()->plugin_url() . '/assets/img/wpspin_light.gif';?>" class="waiting"/>
</div>
