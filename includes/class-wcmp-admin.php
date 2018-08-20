<?php
use WPO\WC\MyParcel\Compatibility\WC_Core as WCX;
use WPO\WC\MyParcel\Compatibility\Order as WCX_Order;
use WPO\WC\MyParcel\Compatibility\Product as WCX_Product;

/**
 * Admin options, buttons & data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'WooCommerce_MyParcel_Admin' ) ) :

class WooCommerce_MyParcel_Admin {
	
	function __construct()	{
		add_action( 'woocommerce_admin_order_actions_end', array( $this, 'order_list_shipment_options' ), 9999 );
		// add_action( 'woocommerce_admin_'.$action_notation.'_actions_end', array( $this, 'order_list_return_shipment_options' ), 9999 );
		add_action(	'admin_footer', array( $this, 'bulk_actions' ) ); 
		add_action( 'admin_footer', array( $this, 'offset_dialog' ) );
		add_action( 'woocommerce_admin_order_actions_end', array( $this, 'admin_wc_actions' ), 20 );
		add_action( 'add_meta_boxes_shop_order', array( $this, 'shop_order_metabox' ) );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'single_order_shipment_options' ) );

		add_action( 'wp_ajax_wcmp_save_shipment_options', array( $this, 'save_shipment_options_ajax' ) );
		add_action( 'wp_ajax_wcmp_get_shipment_summary_status', array( $this, 'order_list_ajax_get_shipment_summary' ) );

		// HS code in product shipping options tab
		add_action( 'woocommerce_product_options_shipping', array( $this, 'product_hs_code_field' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'product_hs_code_field_save' ) );
	}

	public function order_list_shipment_options( $order, $hide = true ) {
		$shipping_country = WCX_Order::get_prop( $order, 'shipping_country' );
		if ( !WooCommerce_MyParcel()->export->is_myparcel_destination( $shipping_country ) ) {
			return;
		}
		$order_id = WCX_Order::get_id( $order );
		$shipment_options = WooCommerce_MyParcel()->export->get_options( $order );
		$myparcel_options_extra = WCX_Order::get_meta( $order, '_myparcel_shipment_options_extra' );
		$package_types = WooCommerce_MyParcel()->export->get_package_types();
		$recipient = WooCommerce_MyParcel()->export->get_recipient( $order );

		// get shipment data (exclude concepts = true)
		$consignments = $this->get_order_shipments( $order, true );

		$style = $hide ? 'style="display:none"' : '';
		// if we have shipments, then we show status & link to track&trace, settings under i
		if ( !empty( $consignments ) )  {
			// echo '<pre>';var_dump($consignments);echo '</pre>';die();
			// only use last shipment
			$last_shipment = array_pop( $consignments );
			$last_shipment_id = $last_shipment['shipment_id'];
			?>
			<div class="wcmp_shipment_summary" <?php echo $style; ?>>
				<?php $this->show_order_delivery_options( $order ); ?>
				<a href="#" class="wcmp_show_shipment_summary"><span class="encircle wcmp_show_shipment_summary">i</span></a>
				<div class="wcmp_shipment_summary_list" data-loaded="" data-shipment_id="<?php echo $last_shipment_id; ?>" data-order_id="<?php echo $order_id; ?>" style="display: none;">
					<img src="<?php echo WooCommerce_MyParcel()->plugin_url() . '/assets/img/wpspin_light.gif';?>" class="wcmp_spinner"/>
				</div>
			</div>
			<?php
		} else {
			?>
			<div class="wcmp_shipment_options" <?php echo $style; ?>>
			<?php $this->show_order_delivery_options( $order ); ?>
			</div>
			<?php
		}

		?>
		<div class="wcmp_shipment_options" <?php echo $style; ?>>
			<?php printf('<a href="#" class="wcmp_show_shipment_options"><span class="wcpm_package_type">%s</span> &#x25BE;</a>', $package_types[$shipment_options['package_type']]); ?>
			<div class="wcmp_shipment_options_form" style="display: none;">
				<?php include('views/wcmp-order-shipment-options.php'); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Get shipment status + track&trace link via AJAX 
	 */
	public function order_list_ajax_get_shipment_summary(){
		check_ajax_referer( 'wc_myparcel', 'security' );
		extract($_POST); // order_id, shipment_id
		$order = wc_get_order($order_id);
		$shipment = WooCommerce_MyParcel()->export->get_shipment_data( $shipment_id, $order );
		if (!empty($shipment['tracktrace'])) {
			$order_has_shipment = true;
			$tracktrace_url = $this->get_tracktrace_url( $order_id, $shipment['tracktrace']);
		}
		$package_types = WooCommerce_MyParcel()->export->get_package_types();

		include('views/wcmp-order-shipment-summary.php');
		die();
	}


	public function order_list_return_shipment_options( $order, $hide = true ) {
		$shipping_country = WCX_Order::get_prop( $order, 'shipping_country' );
		if ( $shipping_country != 'NL' && !WooCommerce_MyParcel()->export->is_eu_country( $shipping_country )  ) {
			return;
		}
		$order_id = WCX_Order::get_id( $order );
		$shipment_options = WooCommerce_MyParcel()->export->get_options( $order );
		$myparcel_options_extra = WCX_Order::get_meta( $order, '_myparcel_shipment_options_extra' );
		$package_types = WooCommerce_MyParcel()->export->get_package_types('return');
		$recipient = WooCommerce_MyParcel()->export->get_recipient( $order );

		$style = $hide ? 'style="display:none"' : '';
		?>
		<div class="wcmp_shipment_options_form return_shipment" <?php echo $style; ?>>
			<?php include('views/wcmp-order-return-shipment-options.php'); ?>
		</div>
		<?php
	}

	/**
	 * Add export option to bulk action drop down menu
	 *
	 * Using Javascript until WordPress core fixes: http://core.trac.wordpress.org/ticket/16031
	 *
	 * @access public
	 * @return void
	 */
	public function bulk_actions() {
		global $post_type;
		$bulk_actions = array(
			'wcmp_export'		=> __( 'MyParcel: Export', 'woocommerce-myparcel' ),
			'wcmp_print'		=> __( 'MyParcel: Print', 'woocommerce-myparcel' ),
			'wcmp_export_print'	=> __( 'MyParcel: Export & Print', 'woocommerce-myparcel' ),
		);


		if ( 'shop_order' == $post_type ) {
			?>
			<script type="text/javascript">
			jQuery(document).ready(function() {
				<?php foreach ($bulk_actions as $action => $title) { ?>
				jQuery('<option>').val('<?php echo $action; ?>').html('<?php echo esc_attr( $title ); ?>').appendTo("select[name='action'], select[name='action2']");
				<?php }	?>
			});
			</script>
			<img src="<?php echo WooCommerce_MyParcel()->plugin_url() . '/assets/img/wpspin_light.gif';?>" class="wcmp_bulk_spinner waiting" style="display:none;"/>
		<?php
		}
	}

	/**
	 * Show dialog to choose print position (offset)
	 *
	 * @access public
	 * @return void
	 */
	public function offset_dialog () {
		global $post_type;
		if ( 'shop_order' == $post_type ) {
			?>
			<div id="wcmyparcel_offset_dialog" style="display:none;">
				<?php _e( 'Labels to skip', 'woocommerce-myparcel' ); ?>:
				<input type="text" size="2" class="wc_myparcel_offset">
				<img src="<?php echo WooCommerce_MyParcel()->plugin_url() . '/assets/img/print-offset-icon.png'; ?>" id="wcmyparcel-offset-icon" style="vertical-align: middle;">
				<button class="button" style="display:none; margin-top: 4px"><?php _e( 'Print', 'woocommerce-myparcel' ); ?></button>
			</div>
			<?php
		}
	}

	/**
	 * Add print actions to the orders listing
     * Support wc > 3.3.0
     * Call the function admin_order_actions for the same settings
     *
	 * @param $order
	 */
	public function admin_wc_actions( $order ) {
		return $this->admin_order_actions( $order );
	}

	/**
	 * Add print actions to the orders listing
	 */
	public function admin_order_actions( $order ) {
		if (empty($order)) {
			return;
		}

		$shipping_country = WCX_Order::get_prop( $order, 'shipping_country' );
		if ( !WooCommerce_MyParcel()->export->is_myparcel_destination( $shipping_country ) ) {
			return;
		}

		$order_id = WCX_Order::get_id( $order );

		$listing_actions = array(
			'add_shipment'		=> array (
				'url'		=> wp_nonce_url( admin_url( 'admin-ajax.php?action=wc_myparcel&request=add_shipment&order_ids=' . $order_id ), 'wc_myparcel' ),
				'img'		=> WooCommerce_MyParcel()->plugin_url() . '/assets/img/myparcel-up.png',
				'alt'		=> esc_attr__( 'Export to MyParcel', 'woocommerce-myparcel' ),
			),
			'get_labels'	=> array (
				'url'		=> wp_nonce_url( admin_url( 'admin-ajax.php?action=wc_myparcel&request=get_labels&order_ids=' . $order_id ), 'wc_myparcel' ),
				'img'		=> WooCommerce_MyParcel()->plugin_url() . '/assets/img/myparcel-pdf.png',
				'alt'		=> esc_attr__( 'Print MyParcel label', 'woocommerce-myparcel' ),
			),
			'add_return'	=> array (
				'url'		=> wp_nonce_url( admin_url( 'admin-ajax.php?action=wc_myparcel&request=add_return&order_ids=' . $order_id ), 'wc_myparcel' ),
				'img'		=> WooCommerce_MyParcel()->plugin_url() . '/assets/img/myparcel-retour.png',
				'alt'		=> esc_attr__( 'Email return label', 'woocommerce-myparcel' ),
			),
		);

		$consignments = $this->get_order_shipments( $order );

		if (empty($consignments)) {
			unset($listing_actions['get_labels']);
		}

		$processed_shipments = $this->get_order_shipments( $order, true );
		if (empty($processed_shipments) || $shipping_country != 'NL' ) {
			unset($listing_actions['add_return']);
		}

		$target = ( isset(WooCommerce_MyParcel()->general_settings['download_display']) && WooCommerce_MyParcel()->general_settings['download_display'] == 'display') ? 'target="_blank"' : '';
		$nonce = wp_create_nonce('wc_myparcel');
		foreach ($listing_actions as $action => $data) {
			printf( '<a href="%1$s" class="button tips myparcel %2$s" alt="%3$s" data-tip="%3$s" data-order-id="%4$s" data-request="%2$s" data-nonce="%5$s" %6$s>', $data['url'], $action, $data['alt'], $order_id, $nonce, $target );
			?>
				<img src="<?php echo $data['img']; ?>" alt="<?php echo $data['alt']; ?>"  style="width:17px; margin: 5px 3px; pointer-events: none;" class="wcmp_button_img">
			</a>
			<?php
		}
		?>
		<img src="<?php echo WooCommerce_MyParcel()->plugin_url() . '/assets/img/wpspin_light.gif';?>" style="width: 17px; margin: 5px 3px;" class="wcmp_spinner waiting"/>
		<?php
	}

	public function get_order_shipments( $order, $exclude_concepts = false ) {
		if (empty($order)) {
			return;
		}

		$consignments = WCX_Order::get_meta( $order, '_myparcel_shipments' );
		// fallback to legacy consignment data (v1.X)
		if (empty($consignments)) {
			if ( $consignment_id = WCX_Order::get_meta( $order, '_myparcel_consignment_id' ) ) {
				$consignments = array(
					array(
						'shipment_id'	=> $consignment_id,
						'tracktrace'	=> WCX_Order::get_meta( $order, '_myparcel_tracktrace' ),
					),
				);
			} elseif( $legacy_consignments = WCX_Order::get_meta( $order, '_myparcel_consignments' ) ) {
				$consignments = array();
				foreach ( $legacy_consignments as $consignment ) {
					if (isset($consignment['consignment_id'])) {
						$consignments[] = array(
							'shipment_id'	=> $consignment['consignment_id'],
							'tracktrace'	=> $consignment['tracktrace'],
						);
					}
				}
			}
		}

		if (empty($consignments) || !is_array($consignments)) {
			return false;
		}

		if (!empty($consignments) && $exclude_concepts) {
			foreach ($consignments as $key => $consignment) {
				if (empty($consignment['tracktrace'])) {
					unset($consignments[$key]);
				}
			}
		}

		return $consignments;
	}

	public function save_shipment_options_ajax () {
		check_ajax_referer( 'wc_myparcel', 'security' );
		extract($_POST);
		parse_str($form_data, $form_data);
		$order = WCX::get_order($order_id);
		
		if (isset($form_data['myparcel_options'][$order_id])) {
			$shipment_options = $form_data['myparcel_options'][$order_id];

			// convert insurance option
			if (isset($shipment_options['insured'])) {
				unset($shipment_options['insured']);
				$shipment_options['insurance'] = array(
					'amount'	=> (int) $shipment_options['insured_amount'] * 100,
					'currency'	=> 'EUR',
				);
				unset($shipment_options['insured_amount']);
			}
			// separate extra options
			if (isset($shipment_options['extra_options'])) {
				WCX_Order::update_meta_data( $order, '_myparcel_shipment_options_extra', $shipment_options['extra_options'] );
				unset($shipment_options['extra_options']);
			}

			WCX_Order::update_meta_data( $order, '_myparcel_shipment_options', $shipment_options );
		}

		// Quit out
		die();
	}

	/**
	 * Add the meta box on the single order page
	 */
	public function shop_order_metabox() {
		add_meta_box(
			'myparcel', //$id
			__( 'MyParcel', 'woocommerce-myparcel' ), //$title
			array( $this, 'create_box_content' ), //$callback
			'shop_order', //$post_type
			'side', //$context
			'default' //$priority
		);
	}



	/**
	 * Callback: Create the meta box content on the single order page
	 */
	public function create_box_content() {
		global $post_id;
		// get order
		$order = WCX::get_order( $post_id );
		if ( !$order ) {
			return;
		}
		$order_id = WCX_Order::get_id( $order );
		
		$shipping_country = WCX_Order::get_prop( $order, 'shipping_country' );
		if ( !WooCommerce_MyParcel()->export->is_myparcel_destination( $shipping_country ) ) {
			return;
		}

		// show buttons and check if WooCommerce > 3.3.0 is used and select the correct function and class
		if ( version_compare( WOOCOMMERCE_VERSION, '3.3.0', '>=' ) ) {
			echo '<div class="single_wc_actions">';
			$this->admin_wc_actions( $order, false );
		} else {
			echo '<div class="single_order_actions">';
			$this->admin_order_actions( $order, false );
		}
		echo '</div>';

		$consignments = $this->get_order_shipments( $order );
		// show shipments if available
		if ( !empty( $consignments ) )  {
			// echo '<pre>';var_dump($consignments);echo '</pre>';die();
			?>
			<table class="tracktrace_status">
				<thead>
					<tr>
						<th>&nbsp;</th>
						<th><?php _e( 'Track&Trace', 'woocommerce-myparcel' );?></th>
						<th><?php _e( 'Status', 'woocommerce-myparcel' );?></th>
					</tr>
				</thead>
				<tbody>
				<?php
				$action = 'get_labels';
				$target = ( isset(WooCommerce_MyParcel()->general_settings['download_display']) && WooCommerce_MyParcel()->general_settings['download_display'] == 'display') ? 'target="_blank"' : '';
				$nonce = wp_create_nonce('wc_myparcel');
				$label_button_text = esc_attr__( 'Print MyParcel label', 'woocommerce-myparcel' );
				foreach ($consignments as $shipment_id => $shipment):
					$shipment = WooCommerce_MyParcel()->export->get_shipment_data( $shipment_id, $order );
					$label_url = wp_nonce_url( admin_url( 'admin-ajax.php?action=wc_myparcel&request=get_labels&shipment_ids=' . $shipment_id ), 'wc_myparcel' );
					if (isset($shipment['tracktrace'])) {
						$tracktrace_url = $this->get_tracktrace_url( $order_id, $shipment['tracktrace']);
						$tracktrace_link = sprintf ( '<a href="%s">%s</a>', $tracktrace_url, $shipment['tracktrace'] );
					} elseif ( isset($shipment['shipment']) && isset($shipment['shipment']['options']) ) {
						$tracktrace_link = '('.WooCommerce_MyParcel()->export->get_package_name($shipment['shipment']['options']['package_type']).')';
					} else {
						$tracktrace_link = '(Unknown)';
					}
					$status = isset($shipment['status']) ? $shipment['status'] : '-';
					?>
					<tr>
						<td class="wcmp-create-label">
							<?php printf( '<a href="%1$s" class="button tips myparcel %2$s" alt="%3$s" data-tip="%3$s" data-order-id="%4$s" data-request="%2$s" data-nonce="%5$s" %6$s>', $label_url, $action, $label_button_text, $order_id, $nonce, $target ); ?>
								<img src="<?php echo WooCommerce_MyParcel()->plugin_url(); ?>/assets/img/myparcel-pdf.png" alt="<?php $label_button_text; ?>" width="16" class="wcmp_button_img">
							</a>
						</td>
						<td class="wcmp-tracktrace"><?php echo $tracktrace_link; ?></td>
						<td class="wcmp-status"><?php echo $status; ?></td>
					</tr>
					<?php endforeach ?>
				</tbody>
			</table>
			<?php
		}
	}

	public function single_order_shipment_options( $order ) {
		$shipping_country = WCX_Order::get_prop( $order, 'shipping_country' );
		if ( !WooCommerce_MyParcel()->export->is_myparcel_destination( $shipping_country ) ) {
			return;
		}

		echo '<div style="clear:both;"><strong>' . __( 'MyParcel shipment:', 'woocommerce-myparcel' ) . '</strong><br/>';
		$this->order_list_shipment_options( $order, false );
		echo '</div>';
	}

	public function show_order_delivery_options($order) {
		$delivery_options = WCX_Order::get_meta( $order, '_myparcel_delivery_options' );

		if ( !empty($delivery_options) && is_array($delivery_options) ) {
			extract($delivery_options);
		}

		echo '<div class="delivery-options">';
		if (!empty($date) && !(isset(WooCommerce_MyParcel()->checkout_settings['deliverydays_window']) && WooCommerce_MyParcel()->checkout_settings['deliverydays_window'] == 0)) {
			$formatted_date = date_i18n( apply_filters( 'wcmyparcel_delivery_date_format', wc_date_format() ), strtotime( $date ) );
			if (!empty($time)) {
				$time = array_shift($time); // take first element in time array
				if (isset($time['price_comment'])) {
					switch ($time['price_comment']) {
						case 'morning':
							$time_title = __( 'Morning delivery', 'woocommerce-myparcel' );
							break;
						case 'standard':
							// $time_title = __( 'Standard delivery', 'woocommerce-myparcel' );
							break;
						case 'night':
						case 'avond':
							$time_title = __( 'Evening delivery', 'woocommerce-myparcel' );
							break;
					}
				}
				$time_title = !empty($time_title) ? "({$time_title})" : '';
			}

			printf('<div class="delivery-date"><strong>%s: </strong>%s %s</div>', __('Delivery date', 'woocommerce-myparcel'), $formatted_date, $time_title );
		}

		if ( $pickup = WooCommerce_MyParcel()->export->is_pickup( $order, $delivery_options ) ) {
			switch ($pickup['price_comment']) {
				case 'retail':
					$title = __( 'PostNL Pickup', 'woocommerce-myparcel' );
					break;
				case 'retailexpress':
					$title = __( 'PostNL Pickup Express', 'woocommerce-myparcel' );
					break;
			}

			echo "<div class='pickup-location'><strong>{$title}: </strong>{$pickup['location']}, {$pickup['street']} {$pickup['number']}, {$pickup['postal_code']} {$pickup['city']}</div>";
		}
		echo '</div>';
	}

	public function get_tracktrace_url( $order_id, $tracktrace ) {
		if (empty($order_id)) {
			return;
		}

		$order = WCX::get_order( $order_id );
		$country = WCX_Order::get_prop( $order, 'shipping_country' );
		$postcode = preg_replace( '/\s+/', '', WCX_Order::get_prop( $order, 'shipping_postcode' ) );

		// set url for NL or foreign orders
		if ($country == 'NL') {
			// use billing postcode for pickup/pakjegemak
			if ( WooCommerce_MyParcel()->export->is_pickup( $order ) ) {
				$postcode = preg_replace( '/\s+/', '', WCX_Order::get_prop( $order, 'billing_postcode' ) );
			}

			// $tracktrace_url = sprintf('https://mijnpakket.postnl.nl/Inbox/Search?lang=nl&B=%s&P=%s', $tracktrace, $postcode);
			$tracktrace_url = sprintf('https://mijnpakket.postnl.nl/Claim?Barcode=%s&Postalcode=%s', $tracktrace, $postcode);
		} else {
			$tracktrace_url = sprintf('https://www.internationalparceltracking.com/Main.aspx#/track/%s/%s/%s', $tracktrace, $country, $postcode);			
		}

		return $tracktrace_url;
	}

	public function get_tracktrace_links ( $order_id ) {
		if ( $consignments = $this->get_tracktrace_shipments( $order_id )) {
			foreach ($consignments as $key => $consignment) {
				$tracktrace_links[] = $consignment['tracktrace_link'];
			}
			return $tracktrace_links;
		} else {
			return false;
		}
	}

	public function get_tracktrace_shipments ( $order_id ) {
		$order = WCX::get_order( $order_id );
		$shipments = $this->get_order_shipments( $order, true );

		if (empty($shipments)) {
			return false;
		}

		foreach ($shipments as $shipment_id => $shipment) {
			// skip concepts, letters & mailbox packages
			if (empty($shipment['tracktrace'])) {
				unset($shipments[$shipment_id]);
				continue;
			}
			// add links & urls
			$shipments[$shipment_id]['tracktrace_url'] = $tracktrace_url = $this->get_tracktrace_url( $order_id, $shipment['tracktrace'] );
			$shipments[$shipment_id]['tracktrace_link'] = sprintf('<a href="%s">%s</a>', $tracktrace_url, $shipment['tracktrace']);
		}

		if (empty($shipments)) {
			return false;
		}


		return $shipments;
	}

	public function product_hs_code_field() {
		echo '<div class="options_group">';
		woocommerce_wp_text_input( 
			array( 
				'id'          => '_myparcel_hs_code',
				'label'       => __( 'HS Code', 'woocommerce-myparcel' ),
				'description' => sprintf( __( 'HS Codes are used for MyParcel world shipments, you can find the appropriate code on the %ssite of the Dutch Customs%s.', 'woocommerce-myparcel' ), '<a href="http://tarief.douane.nl/tariff/index.jsf" target="_blank">', '</a>' ),
				// 'desc_tip'    => true,
			)
		);  
		echo '</div>';
	}

	public function product_hs_code_field_save( $post_id ) {
		// check if hs code is passed and not an array (=variation hs code)
		if (isset($_POST['_myparcel_hs_code']) && !is_array($_POST['_myparcel_hs_code'])) {
			$product = wc_get_product( $post_id );
			$hs_code = $_POST['_myparcel_hs_code'];
			if( !empty( $hs_code ) ) {
				WCX_Product::update_meta_data( $product, '_myparcel_hs_code', esc_attr( $hs_code ) );
			} elseif( isset($_POST['_myparcel_hs_code']) && empty( $hs_code ) ) {
				WCX_Product::delete_meta_data( $product, '_myparcel_hs_code' );
			}
		}

	}
}

endif; // class_exists

return new WooCommerce_MyParcel_Admin();
