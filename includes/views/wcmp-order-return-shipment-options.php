<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<table class="wcpostnl_settings_table" style="width: auto">
	<tr>
		<td>
			<?php _e( 'Shipment type', 'woocommerce-postnl' ) ?>:<br/>
			<?php $parcel_weight = WooCommerce_PostNL()->export->get_parcel_weight( $order ); ?>
			<small class="calculated_weight"><?php printf( __( 'Calculated weight: %s kg', 'woocommerce-postnl' ), number_format( $parcel_weight, 3, ',', ' ' ) ); ?></small>
		</td>
		<td>
			<?php
			$name = "postnl_options[{$order_id}][package_type]";
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
<table class="wcpostnl_settings_table parcel_options">
	<?php
	$shipment_options['insured'] = isset($shipment_options['insurance']['amount']) ? 1 : 0;
	if (!isset($shipment_options['insurance'])) {
		$shipment_options['insurance']['amount'] = '';
	}

	$option_rows = array(
		'[only_recipient]'	=> array(
			'label'	=> __( 'Home address only', 'woocommerce-postnl' ),
			'value'	=> isset($shipment_options['only_recipient']) ? $shipment_options['only_recipient'] : 0,
		),
		'[signature]'	=> array(
			'label'	=> __( 'Signature on delivery', 'woocommerce-postnl' ),
			'value'	=> isset($shipment_options['signature']) ? $shipment_options['signature'] : 0,
		),
		'[return]'	=> array(
			'label'	=> __( 'Return if no answer', 'woocommerce-postnl' ),
			'value'	=> isset($shipment_options['return']) ? $shipment_options['return'] : 0,
		),
		'[insured]'	=> array(
			'label'	=> __( 'Insured + home address only + signature on delivery', 'woocommerce-postnl' ),
			'value'	=> $shipment_options['insured'],
			'class'	=> 'insured',
		),
	);

	if (isset($recipient['cc']) && $recipient['cc'] != 'NL') {
		unset($option_rows['[only_recipient]']);
		unset($option_rows['[signature]']);
		unset($option_rows['[return]']);
		$shipment_options['insured'] = 1;
		$option_rows['[insured]'] = array(
			'label'		=> __( 'Standard insurance up to €500 + signature on delivery', 'woocommerce-postnl' ),
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
			$name = "postnl_options[{$order_id}]{$name}";
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
<table class="wcpostnl_settings_table">
	<?php
	$insured_amount = isset($shipment_options['insurance']['amount']) ? $shipment_options['insurance']['amount'] : '';
	$insured_amount = $insured_amount / 100; // frontend is in euros
	$name = "postnl_options[{$order_id}][insured_amount]";
	if (isset($recipient['cc']) && $recipient['cc'] == 'NL') {
		?>
		<tr>
			<td><?php _e( 'Insurance', 'woocommerce-postnl' ) ?></td>
			<td>
				<?php
				$insured_amounts = array(
					'49'		=> __( 'Insured up to &euro; 50' , 'woocommerce-postnl' ).' (+ &euro; 0.50)',
					'249'		=> __( 'Insured up to  &euro; 250' , 'woocommerce-postnl' ).' (+ &euro; 1.00)',
					'499'		=> __( 'Insured up to  &euro; 500' , 'woocommerce-postnl' ).' (+ &euro; 1.65)',
					''			=> __( '> &euro; 500 insured' , 'woocommerce-postnl' ).' (+ &euro; 1.65 / &euro; 500)',
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
				<?php _e( 'Insured amount', 'woocommerce-postnl' ) ?>
			</td>
			<td>
				<?php
				$name = "postnl_options[{$order_id}][insured_amount]";
				printf('<input type="text" name="%s" value="%s" style="width:100%%" class="insured_amount">', $name, $insured_amount);
				?>
			</td>
		</tr>
		<?php
	} else {
		printf('<tr><td colspan="2" style="display:none;"><input type="hidden" name="%s" value="%s"></td></tr>', $name, $insured_amount );
	}
	?>
</table>
<?php if (!isset($skip_save)): ?>
<div class="wcmp_save_shipment_settings">
	<a class="button save" data-order="<?php echo $order_id; ?>"><?php _e( 'Save', 'woocommerce-postnl' ) ?></a>
	<img src="<?php echo WooCommerce_PostNL()->plugin_url() . '/assets/img/wpspin_light.gif';?>" class="wcmp_spinner waiting"/>
</div>
<?php endif ?>