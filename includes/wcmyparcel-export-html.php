<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<?php
		require_once(ABSPATH . 'wp-admin/admin.php');

		global $wcmyparcelexport;
		if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '<=' ) ) {
			// Old versions
			wp_register_style(
				'wcmyparcel-admin-styles',
				$wcmyparcelexport->plugin_url() . '/css/wcmyparcel-admin-styles.css',
				array(),
				WC_MYPARCEL_VERSION,
				'all'
			);
		} else {
			// WC 2.1+, MP6 style with larger buttons
			wp_register_style(
				'wcmyparcel-admin-styles',
				$wcmyparcelexport->plugin_url() . '/css/wcmyparcel-admin-styles-wc21.css',
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

								foreach ($consignment['ToAddress'] as $key => $value) {
									$name = "consignments[{$order_id}][ToAddress][{$key}]";
									printf('<input type="hidden" name="%s" value="%s">', $name, $value);
								}

								$name = "consignments[{$order_id}][weight]";
								printf('<input type="hidden" name="%s" value="%s">', $name, $consignment['weight']);
							?>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<table class="wcmyparcel_settings_table" style="width: auto">
								<tr>
									<td>
										Soort zending: 
									</td>
									<td>
										
										<?php
										$zendingen = array(
											'standard'		=> __( 'Pakket' , 'wcmyparcel' ),
											'letterbox'		=> __( 'Brievenbuspakje' , 'wcmyparcel' ),
											'unpaid_letter'	=> __( 'Ongefrankeerd label' , 'wcmyparcel' ),
										);

										// disable letterbox outside NL
										if (isset($consignment['ToAddress']['country_code']) && $consignment['ToAddress']['country_code'] != 'NL') {
											unset($zendingen['letterbox']);
										}

										// disable letterbox and unpaid letter for pakjegemak
										if ( $this->is_pakjegemak( $order ) ) {
											unset($zendingen['letterbox']);
											unset($zendingen['unpaid_letter']);
											$zendingen['standard'] .= ' (Pakjegemak)';
										}										

										$name = "consignments[{$order_id}][shipment_type]";
										printf( '<select name="%s" class="shipment_type">', $name );
										foreach ( $zendingen as $key => $label ) {
											printf( '<option value="%s"%s>%s</option>', $key, selected( $consignment['shipment_type'], $key, false ), $label );
										}
										echo '</select>';
										?>										
									</td>
								</tr>
								<tr>
									<td>
										Aantal labels: 
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
										'label'	=> 'Extra groot formaat',
										'value'	=> $consignment['ProductCode']['extra_size'],
										'cost'	=> '2.19',
									),
									'[ProductCode][home_address_only]'	=> array(
										'label'	=> 'Alléén huisadres',
										'value'	=> $consignment['ProductCode']['home_address_only'],
										'cost'	=> '0.26',
									),
									'[ProductCode][signature_on_receipt]'	=> array(
										'label'	=> 'Handtekening voor ontvangst',
										'value'	=> $consignment['ProductCode']['signature_on_receipt'],
										'cost'	=> empty($order->myparcel_is_pakjegemak) ? '0.33' : '',
									),
									'[ProductCode][home_address_signature]'	=> array(
										'label'	=> 'Alléén huisadres + Handtekening voor ontvangst',
										'value'	=> $consignment['ProductCode']['home_address_signature'],
										'cost'	=> '0.40',
									),
									'[ProductCode][return_if_no_answer]'	=> array(
										'label'	=> 'Retour bij geen gehoor',
										'value'	=> $consignment['ProductCode']['return_if_no_answer'],
									),
									'[ProductCode][insured]'	=> array(
										'label'	=> 'Verzekerd + Alléén huisadres + Handtekening voor ontvangst',
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
									<td style="text-align: right; font-weight: bold;">
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
									<td>Verzekering</td>
									<td>
										<?php
										$insured_amount = array(
											'49'		=> __( 'Tot &euro; 50 verzekerd verzenden (+ &euro; 0.50)' , 'wcmyparcel' ),
											'249'		=> __( 'Tot &euro; 250 verzekerd verzenden (+ &euro; 1.00)' , 'wcmyparcel' ),
											'499'		=> __( 'Tot &euro; 500 verzekerd verzenden (+ &euro; 1.50)' , 'wcmyparcel' ),
											''			=> __( '> &euro; 500 verzekerd verzenden (+ &euro; 1.50)' , 'wcmyparcel' ),
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
										Verzekerd bedrag
									</td>
									<td>
										<?php
										$name = "consignments[{$order_id}][insured_amount]";
										printf('<input type="text" name="%s" value="%s" style="width:100%%" class="insured_amount">', $name, $consignment['insured_amount']);
										?>
									</td>
								</tr>
								<tr>
									<td>Eigen kenmerk (linksboven op label)</td>
									<td>
										<?php
										$name = "consignments[{$order_id}][custom_id]";
										printf('<input type="text" name="%s" value="%s" style="width:100%%">', $name, $consignment['custom_id']);
										?>
									</td>
								</tr>
							</table>
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
	<img src="<?php echo dirname(plugin_dir_url(__FILE__)).'/img/wpspin_light.gif';?>" class="waiting"/>
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
