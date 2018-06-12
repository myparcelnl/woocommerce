<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<a href="#" class="wcmyparcel_change_order">
<table class="wcmyparcelbe_settings_table" style="width: auto" onclick="return false">
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
    var_dump($shipment_options['insured']);
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
        unset( $option_rows['[signature]'] );
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

</a>
<div class="wcmp_save_shipment_settings">
	<a class="button save" data-order="<?php echo $order_id; ?>"><?php _e( 'Save', 'woocommerce-myparcelbe' ) ?></a>
	<img src="<?php echo WooCommerce_MyParcelBE()->plugin_url() . '/assets/img/wpspin_light.gif';?>" class="wcmp_spinner waiting"/>
</div>
