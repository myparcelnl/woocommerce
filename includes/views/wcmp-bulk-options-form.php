<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<?php
		global $wcmyparcelexport;
		if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '<=' ) ) {
			// Old versions
			wp_register_style(
				'wcmyparcel-admin-styles',
				WooCommerce_MyParcel()->plugin_url() . '/assets/css/wcmyparcel-admin-styles.css',
				array(),
				WC_MYPARCEL_VERSION,
				'all'
			);
		} else {
			// WC 2.1+, MP6 style with larger buttons
			wp_register_style(
				'wcmyparcel-admin-styles',
				WooCommerce_MyParcel()->plugin_url() . '/assets/css/wcmyparcel-admin-styles-wc21.css',
				array(),
				WC_MYPARCEL_VERSION,
				'all'
			);
		}

		wp_enqueue_style( 'wcmyparcel-admin-styles' );	
		wp_enqueue_style( 'colors' );
		wp_enqueue_style( 'media' );
		wp_enqueue_script( 'jquery' );
		do_action('admin_print_styles');
		do_action('admin_print_scripts');
	?>
	<script type="text/javascript">
	jQuery(function($) {
		// select > 500 if insured amount input is >499
		$( 'input.insured_amount' ).each( function( index ) {
			if ( $( this ).val() > 499 ) {
				insured_select = $( this ).closest('table').parent().find('select.insured_amount');
				$( insured_select ).val('');
			};
		});

		// hide insurance options if unsured not checked
		$('.insured').change(function () {
			insured_select = $( this ).closest('table').parent().find('select.insured_amount');
			insured_input  = $( this ).closest('table').parent().find('input.insured_amount');
			if (this.checked) {
				$( insured_select ).prop('disabled', false);
				$( insured_select ).closest('tr').show();
				$('select.insured_amount').change();
			} else {
				$( insured_select ).prop('disabled', true);
				$( insured_select ).closest('tr').hide();
				$( insured_input ).closest('tr').hide();
			}
		}).change(); //ensure visible state matches initially

		// hide & disable insured amount input if not needed
		$('select.insured_amount').change(function () {
			insured_check  = $( this ).closest('table').parent().find('.insured');
			insured_select = $( this ).closest('table').parent().find('select.insured_amount');
			insured_input  = $( this ).closest('table').find('input.insured_amount');
			if ( $( insured_select ).val() ) {
				$( insured_input ).val('');
				$( insured_input ).prop('disabled', true);
				$( insured_input ).closest('tr').hide();
			} else {
				$( insured_input ).prop('disabled', false);
				$( insured_input ).closest('tr').show();
			}
		}).change(); //ensure visible state matches initially

		// hide all options if not a parcel
		$('select.shipment_type').change(function () {
			parcel_options  = $( this ).closest('table').parent().find('.parcel_options');
			if ( $( this ).val() == 'standard') {
				// parcel
				$( parcel_options ).find('input, textarea, button, select').prop('disabled', false);
				$( parcel_options ).show();
				$('.insured').change();
			} else {
				// not a parcel
				$( parcel_options ).find('input, textarea, button, select').prop('disabled', true);
				$( parcel_options ).hide();
				$('.insured').prop('checked', false);
				$('.insured').change();
			}
		}).change(); //ensure visible state matches initially
	});
	</script>
</head>
<body>
<form  method="post" class="page-form">
	<table class="widefat">
	<thead>
		<tr>
			<th>Export opties</td>
		</tr>
	</thead>
	<tbody>
		<?php $c = true; foreach ( $form_data as $order_id => $order_data ) : extract( $order_data );?>
		<tr class="order-row <?php echo (($c = !$c)?'alternate':'');?>">
			<td>
				<table style="width: 100%">
					<tr>
						<td colspan="2"><strong>Bestelling <?php echo $order->get_order_number(); ?></strong></td>
					</tr>
					<tr>
						<td class="ordercell">
							<table class="widefat">
								<thead>
									<tr>
										<th>#</th>
										<th>Productnaam</th>
										<th align="right">Gewicht (kg)</th>
									</tr>
								</thead>
								<tbody>
								<?php
								$items = $order->get_items();
								foreach ($items as $item_id => $item) {
									?>
									<tr>
										<td><?php echo $item['qty'].'x'; ?></td>
										<td><?php echo $this->get_item_display_name ( $item, $order ) ?></td>
										<td align="right"><?php echo number_format( $this->get_item_weight_kg( $item, $order ), 3, ',', ' '); ?></td>
									</tr>
								<?php } ?>
									<tr>
										<td>&nbsp;</td>
										<td>Standaard verpakking</td>
										<td align="right"><?php echo number_format( ( (isset($this->settings['verpakkingsgewicht'])) ? preg_replace("/\D/","",$this->settings['verpakkingsgewicht'])/1000 : 0 ), 3, ',', ' '); ?></td>
									</tr>
								</tbody>
								<tfoot>
									<tr>
										<td></td>
										<td>Totaal:</td>
										<td align="right"><?php echo number_format( $consignment['weight'], 3, ',', ' ' );?></td>
									</tr>
								</tfoot>
							</table>
						</td>
						<td><?php
							if ( $order->shipping_country == 'NL' && ( empty($consignment['ToAddress']['street']) || empty($consignment['ToAddress']['house_number']) ) ) { ?>
							<p><span style="color:red">Deze order bevat geen geldige straatnaam- en huisnummergegevens, en kan daarom niet worden ge-exporteerd! Waarschijnlijk is deze order geplaatst voordat de MyParcel plugin werd geactiveerd. De gegevens kunnen wel handmatig worden ingevoerd in het order scherm.</span></p>
						</td>
					</tr> <!-- last row -->
							<?php
							} else { // required address data is available
								// print address
								echo '<p>'.$order->get_formatted_shipping_address().'<br/>'.$order->billing_phone.'<br/>'.$order->billing_email.'</p>';
							?>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<?php
							if ($dialog == 'shipment') {
								include('views/wcmp-order-shipment-options.php');
							} elseif ($dialog == 'return') {
								include('views/wcmp-order-return-shipment-options.php');
							}
							?>
						</td>
					</tr>
					<?php } // end else ?>
				</table>
			</td>
		</tr>
		<?php endforeach; ?>
	</tbody>
	</table>
<input type="hidden" name="action" value="wcmyparcel-export">
<div class="submit-wcmyparcel">
	<input type="submit" value="Exporteer naar MyParcel" class="button-wcmyparcel">
	<img src="<?php echo WooCommerce_MyParcel()->plugin_url() . '/assets/img/wpspin_light.gif';?>" class="waiting"/>
</div>
</form>
<script type="text/javascript">
	jQuery(document).ready(function($){
		$('.button-wcmyparcel').click(function(){
			$('.waiting').show();
		});
	});
</script>

</body>
</html>
