<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<?php
		require_once(ABSPATH . 'wp-admin/admin.php');
		wp_register_style( 'wcmyparcel-admin-styles', dirname(plugin_dir_url(__FILE__)) .  '/css/wcmyparcel-admin-styles.css', array(), '', 'all' );
		wp_enqueue_style( 'wcmyparcel-admin-styles' );		
		wp_enqueue_style( 'colors' );
		wp_enqueue_style( 'media' );
		wp_enqueue_script( 'jquery' );
		do_action('admin_print_styles');
		do_action('admin_print_scripts');
	?>
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
		<tr <?php echo (($c = !$c)?' class="alternate"':'');?>>
			<td>
				<table>
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
							<table class="wcmyparcel_settings_table">
								<tr>
									<td>Soort zending</td>
									<td>
										<?php
										$zendingen = array(
											'standard'		=> __( 'Pakket' , 'wcmyparcel' ),
											'letterbox'		=> __( 'Brievenbuspakje' , 'wcmyparcel' ),
											'unpaid_letter'	=> __( 'Ongefrankeerd label' , 'wcmyparcel' ),
										);

										$name = "consignments[{$order_id}][shipment_type]";
										printf( '<select name="%s">', $name );
										foreach ( $zendingen as $key => $label ) {
											printf( '<option value="%s"%s>%s</option>', $key, selected( $consignment['shipment_type'], $key, false ), $label );
										}
										echo '</select>';
										?>
									</td>
								</tr>								

								<?php
								$option_rows = array(
									'[ToAddress][email]'	=> array(
										'label'	=> 'Email adres koppelen',
										'value'	=> $order->billing_email,
										'checked'	=> checked( $order->billing_email, $consignment['ToAddress']['email'], false ),
									),
									'[ToAddress][phone_number]'	=> array(
										'label'	=> 'Telefoonnummer koppelen',
										'value'	=> $order->billing_phone,
										'checked'	=> checked( $order->billing_phone, $consignment['ToAddress']['phone_number'], false ),
									),
									'[extra_size]'	=> array(
										'label'	=> 'Extra groot formaat (+ &euro; 2.19)',
										'value'	=> $consignment['extra_size'],
									),
									'[ProductCode][home_address_only]'	=> array(
										'label'	=> 'Niet bij buren bezorgen (+ &euro; 0.26)',
										'value'	=> $consignment['ProductCode']['home_address_only'],
									),
									'[ProductCode][signature_on_receipt]'	=> array(
										'label'	=> 'Handtekening voor ontvangst (+ &euro; 0.33)',
										'value'	=> $consignment['ProductCode']['signature_on_receipt'],
									),
									'[ProductCode][home_address_signature]'	=> array(
										'label'	=> 'Niet bij buren bezorgen + Handtekening voor ontvangst (+ &euro; 0.40)',
										'value'	=> $consignment['ProductCode']['home_address_signature'],
									),
									'[ProductCode][mypa_insured]'	=> array(
										'label'	=> 'Niet bij buren bezorgen + Handtekening voor ontvangst + verzekerd tot &euro; 50 (+ &euro; 0.50)',
										'value'	=> $consignment['ProductCode']['mypa_insured'],
									),
									'[ProductCode][return_if_no_answer]'	=> array(
										'label'	=> 'Retour bij geen gehoor',
										'value'	=> $consignment['ProductCode']['return_if_no_answer'],
									),
									'[ProductCode][insured]'	=> array(
										'label'	=> 'Verhoogd aansprakelijk (+ &euro; 1.58 per &euro; 500 verzekerd)',
										'value'	=> $consignment['ProductCode']['insured'],
									),
								);
								?>
								<?php foreach ($option_rows as $name => $option_row): ?>
								<tr>
									<td><?php echo $option_row['label']; ?></td>
									<td>
										<?php
										$name = "consignments[{$order_id}]{$name}";
										$checked = isset($option_row['checked'])? $option_row['checked'] : checked( "1", $option_row['value'], false );
										printf('<input type="checkbox" name="%s" value="%s" %s>', $name, $option_row['value'], $checked );
										?>
									</td>
								</tr>									
								<?php endforeach ?>

								<tr>
									<td>Verzekerd bedrag (afgerond in hele in &euro;)</td>
									<td>
										<?php
										$name = "consignments[{$order_id}][insured_amount]";
										printf('<input type="text" name="%s" value="%s" size="5">', $name, $consignment['insured_amount']);
										?>
									</td>
								</tr>
								<tr>
									<td>Eigen kenmerk (linksboven op label)</td>
									<td>
										<?php
										$name = "consignments[{$order_id}][custom_id]";
										printf('<input type="text" name="%s" value="%s">', $name, $consignment['custom_id']);
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
