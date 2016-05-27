<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<?php
		wp_enqueue_script(
			'wcmyparcel-export',
			WooCommerce_MyParcel()->plugin_url() . '/assets/js/wcmyparcel-script.js',
			array( 'jquery', 'thickbox' ),
			WC_MYPARCEL_VERSION
		);
		wp_localize_script(
			'wcmyparcel-export',
			'wc_myparcel',
			array(  
				'ajax_url'			=> admin_url( 'admin-ajax.php' ),
				'nonce'				=> wp_create_nonce('wc_myparcel'),
				'download_display'	=> isset(WooCommerce_MyParcel()->general_settings['download_display'])?WooCommerce_MyParcel()->general_settings['download_display']:'',
			)
		);

		wp_enqueue_style(
			'wcmp-admin-styles',
			WooCommerce_MyParcel()->plugin_url() . '/assets/css/wcmp-admin-styles.css',
			array(),
			WC_MYPARCEL_VERSION,
			'all'
		);

		// Legacy styles (WC 2.1+ introduced MP6 style with larger buttons)
		if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '<=' ) ) {
			wp_enqueue_style(
				'wcmp-admin-styles-legacy',
				WooCommerce_MyParcel()->plugin_url() . '/assets/css/wcmp-admin-styles-legacy.css',
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
</head>
<body>
<form  method="post" class="page-form">
	<table class="widefat">
	<thead>
		<tr>
			<th><?php _e( 'Export options', 'woocommerce-myparcel' ); ?></td>
		</tr>
	</thead>
	<tbody>
		<?php
		$c = true;
		foreach ( $order_ids as $order_id) :
			$order = WooCommerce_MyParcel()->export->get_order( $order_id );
			$shipment_options = WooCommerce_MyParcel()->export->get_options( $order );
			$recipient = WooCommerce_MyParcel()->export->get_recipient( $order );
			$myparcel_options_extra = $order->myparcel_shipment_options_extra;
			$package_types = WooCommerce_MyParcel()->export->get_package_types();
			$parcel_weight = WooCommerce_MyParcel()->export->get_parcel_weight( $order );
		?>
		<tr class="order-row <?php echo (($c = !$c)?'alternate':'');?>">
			<td>
				<table style="width: 100%">
					<tr>
						<td colspan="2"><strong><?php _e( 'Order', 'woocommerce-myparcel' ); ?> <?php echo $order->get_order_number(); ?></strong></td>
					</tr>
					<tr>
						<td class="ordercell">
							<table class="widefat">
								<thead>
									<tr>
										<th>#</th>
										<th><?php _e( 'Productnaam', 'woocommerce-myparcel' ); ?></th>
										<th align="right"><?php _e( 'Weight (kg)', 'woocommerce-myparcel' ); ?></th>
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
										<td><?php _e( 'Empty parcel weight', 'woocommerce-myparcel' ); ?></td>
										<td align="right"><?php echo number_format( ( (isset(WooCommerce_MyParcel()->general_settings['empty_parcel_weight'])) ? preg_replace("/\D/","",$this->settings['verpakkingsgewicht'])/1000 : 0 ), 3, ',', ' '); ?></td>
									</tr>
								</tbody>
								<tfoot>
									<tr>
										<td>&nbsp;</td>
										<td><?php _e( 'Total weight', 'woocommerce-myparcel' ); ?></td>
										<td align="right"><?php echo number_format( $parcel_weight, 3, ',', ' ' );?></td>
									</tr>
								</tfoot>
							</table>
						</td>
						<td><?php
							if ( $order->shipping_country == 'NL' && ( empty($recipient['street']) || empty($recipient['number']) ) ) { ?>
							<p><span style="color:red"><?php _e( 'Deze order bevat geen geldige straatnaam- en huisnummergegevens, en kan daarom niet worden ge-exporteerd! Waarschijnlijk is deze order geplaatst voordat de MyParcel plugin werd geactiveerd. De gegevens kunnen wel handmatig worden ingevoerd in het order scherm.', 'woocommerce-myparcel' ); ?></span></p>
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
						<td colspan="2" class="wcmp_shipment_options">
							<?php
							if ($dialog == 'shipment') {
								include('wcmp-order-shipment-options.php');
							} elseif ($dialog == 'return') {
								include('wcmp-order-return-shipment-options.php');
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
