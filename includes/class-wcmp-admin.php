<?php
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
		//add_action( 'woocommerce_admin_order_actions_end', array( $this, 'order_list_return_shipment_options' ), 9999 );
		add_action(	'admin_footer', array( $this, 'bulk_actions' ) ); 
		add_action( 'woocommerce_admin_order_actions_end', array( $this, 'admin_order_actions' ), 20 );
		add_action( 'add_meta_boxes_shop_order', array( $this, 'shop_order_metabox' ) );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'single_order_shipment_options' ) );

		add_action( 'wp_ajax_wcmp_save_shipment_options', array( $this, 'save_shipment_options_ajax' ) );
	}

	public function order_list_shipment_options( $order, $hide = true ) {
		if ( !WooCommerce_MyParcel()->export->is_eu_country( $order->shipping_country ) ) {
			return;
		}
		$order_id = $order->id;
		$shipment_options = WooCommerce_MyParcel()->export->get_options( $order );
		$myparcel_options_extra = $order->myparcel_shipment_options_extra;
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

			$shipment = WooCommerce_MyParcel()->export->get_shipment_data( $last_shipment_id, $order );
			// echo '<pre>';var_dump($shipment);echo '</pre>';die();
			if (!empty($shipment['tracktrace'])) {
				$order_has_shipment = true;
				$tracktrace_url = $this->get_tracktrace_url( $order->id, $shipment['tracktrace']);
			}
			?>
			<div class="wcmp_shipment_summary" <?php echo $style; ?>>
				<?php $this->show_order_delivery_options( $order ); ?>
				<a href="#" class="wcmp_show_shipment_summary"><span class="encircle wcmp_show_shipment_summary">i</span></a>
				<div class="wcmp_shipment_summary_list" style="display: none;">
					<?php include('views/wcmp-order-shipment-summary.php'); ?>
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


	public function order_list_return_shipment_options( $order, $hide = true ) {
		if ( !WooCommerce_MyParcel()->export->is_eu_country( $order->shipping_country ) ) {
			return;
		}
		$order_id = $order->id;
		$shipment_options = WooCommerce_MyParcel()->export->get_options( $order );
		$myparcel_options_extra = $order->myparcel_shipment_options_extra;
		$package_types = WooCommerce_MyParcel()->export->get_package_types();
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
	 * Add print actions to the orders listing
	 */
	public function admin_order_actions( $order ) {
		if (empty($order)) {
			return;
		}

		if ( !WooCommerce_MyParcel()->export->is_eu_country( $order->shipping_country ) ) {
			return;
		}

		$listing_actions = array(
			'add_shipment'		=> array (
				'url'		=> wp_nonce_url( admin_url( 'admin-ajax.php?action=wc_myparcel&request=add_shipment&order_ids=' . $order->id ), 'wc_myparcel' ),
				'img'		=> WooCommerce_MyParcel()->plugin_url() . '/assets/img/myparcel-up.png',
				'alt'		=> esc_attr__( 'Export to MyParcel', 'woocommerce-myparcel' ),
			),
			'get_labels'	=> array (
				'url'		=> wp_nonce_url( admin_url( 'admin-ajax.php?action=wc_myparcel&request=get_labels&order_ids=' . $order->id ), 'wc_myparcel' ),
				'img'		=> WooCommerce_MyParcel()->plugin_url() . '/assets/img/myparcel-pdf.png',
				'alt'		=> esc_attr__( 'Print MyParcel label', 'woocommerce-myparcel' ),
			),
			'add_return'	=> array (
				'url'		=> wp_nonce_url( admin_url( 'admin-ajax.php?action=wc_myparcel&request=add_return&order_ids=' . $order->id ), 'wc_myparcel' ),
				'img'		=> WooCommerce_MyParcel()->plugin_url() . '/assets/img/myparcel-retour.png',
				'alt'		=> esc_attr__( 'Email return label', 'woocommerce-myparcel' ),
			),
		);

		$consignments = $this->get_order_shipments( $order );

		if (empty($consignments)) {
			unset($listing_actions['get_labels']);
		}

		$processed_shipments = $this->get_order_shipments( $order, true );
		if (empty($processed_shipments) || $order->shipping_country != 'NL' ) {
			unset($listing_actions['add_return']);
		}		

		$target = ( isset(WooCommerce_MyParcel()->general_settings['download_display']) && WooCommerce_MyParcel()->general_settings['download_display'] == 'display') ? 'target="_blank"' : '';
		$nonce = wp_create_nonce('wc_myparcel');
		foreach ($listing_actions as $action => $data) {
			printf( '<a href="%1$s" class="button tips myparcel %2$s" alt="%3$s" data-tip="%3$s" data-order-id="%4$s" data-request="%2$s" data-nonce="%5$s" %6$s>', $data['url'], $action, $data['alt'], $order->id, $nonce, $target );
			?>
				<img src="<?php echo $data['img']; ?>" alt="<?php echo $data['alt']; ?>" width="16" class="wcmp_button_img">
			</a>
			<?php
		}
		?>
		<img src="<?php echo WooCommerce_MyParcel()->plugin_url() . '/assets/img/wpspin_light.gif';?>" class="wcmp_spinner waiting"/>
		<?php
	}

	public function get_order_shipments( $order, $exclude_concepts = false ) {
		if (empty($order)) {
			return;
		}		
		if ( $consignment_id = get_post_meta($order->id,'_myparcel_consignment_id',true ) ) {
			$consignments = array(
				array(
					'shipment_id' => $consignment_id,
					'tracktrace'     => get_post_meta($order->id,'_myparcel_tracktrace',true ),
				),
			);
		} else {
			$consignments = get_post_meta($order->id,'_myparcel_shipments',true );
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
				update_post_meta( $order_id, '_myparcel_shipment_options_extra', $shipment_options['extra_options'] );
				unset($shipment_options['extra_options']);
			}

			update_post_meta( $order_id, '_myparcel_shipment_options', $shipment_options );
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
		if ( version_compare( WOOCOMMERCE_VERSION, '2.2', '<' ) ) {
			$order = new WC_Order( $post_id );
		} else {
			$order = wc_get_order( $post_id );
		}

		if ( !WooCommerce_MyParcel()->export->is_eu_country( $order->shipping_country ) ) {
			return;
		}

		// echo '<pre>';var_dump($order->myparcel_shipments);echo '</pre>';

		// show buttons
		echo '<div class="single_order_actions">';
		$this->admin_order_actions( $order, false );
		echo '</div>';

		$consignments = $this->get_order_shipments( $order );
		// show shipments if available
		if ( !empty( $consignments ) )  {
			// echo '<pre>';var_dump($consignments);echo '</pre>';die();
			$track_trace_shipments = array(); 
			foreach ($consignments as $shipment_id => $consignment) {
				$shipment = WooCommerce_MyParcel()->export->get_shipment_data( $shipment_id, $order );
				// skip concepts, letters & mailbox packages
				if (empty($shipment['tracktrace'])) {
					unset($consignments[$shipment_id]);
					continue;
				}
				$shipment['tracktrace_url'] = $this->get_tracktrace_url( $order->id, $shipment['tracktrace']);
				$track_trace_shipments[$shipment_id] = $shipment;
			}
			if ( empty( $track_trace_shipments ) ) {
				return;
			}
			?>
			<table class="tracktrace_status">
				<thead>
					<th><?php _e( 'Track&Trace', 'woocommerce-myparcel' );?></th>
					<th><?php _e( 'Status', 'woocommerce-myparcel' );?></th>
				</thead>
				<tbody>
					<?php
					// echo '<pre>';var_dump($consignments);echo '</pre>';die();
					foreach ($track_trace_shipments as $shipment_id => $shipment) {
						if (empty($shipment['tracktrace'])) {
							continue;
						}
						printf ('<tr><td><a href="%s">%s</a></td><td>%s</td></tr>', $shipment['tracktrace_url'], $shipment['tracktrace'], $shipment['status']);
					}
					?>
				</tbody>
			</table>
			<?php
		}
	}

	public function single_order_shipment_options( $order ) {
		if ( !WooCommerce_MyParcel()->export->is_eu_country( $order->shipping_country ) ) {
			return;
		}

		echo '<strong>' . __( 'MyParcel shipment:', 'woocommerce-myparcel' ) . '</strong><br/>';
		$this->order_list_shipment_options( $order, false );
	}

	public function show_order_delivery_options($order) {
		$delivery_options = $order->myparcel_delivery_options;

		if ( !empty($delivery_options) && is_array($delivery_options) ) {
			extract($delivery_options);
		}

		echo '<div class="delivery-options">';
		if (!empty($date) && !(isset(WooCommerce_MyParcel()->checkout_settings['deliverydays_window']) && WooCommerce_MyParcel()->checkout_settings['deliverydays_window'] == 0)) {
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

			printf('<div class="delivery-date"><strong>%s: </strong>%s %s</div>', __('Delivery date', 'woocommerce-myparcel'), $date, $time_title );
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

			echo "<div class='pickup-location'><strong>{$title}: </strong>{$pickup['location']}, {$pickup['street']} {$pickup['number']}, {$pickup['postal_code']} {$pickup['city']}";
		}
		echo '</div>';
	}

	public function get_tracktrace_url( $order_id, $tracktrace ) {
		if (empty($order_id))
			return;

		$country = get_post_meta($order_id,'_shipping_country',true);
		$postcode = preg_replace('/\s+/', '',get_post_meta($order_id,'_shipping_postcode',true));

		// set url for NL or foreign orders
		if ($country == 'NL') {
			// use billing postcode for pickup/pakjegemak
			if ( WooCommerce_MyParcel()->export->is_pickup( $order_id ) ) {
				$postcode = preg_replace('/\s+/', '',get_post_meta($order_id,'_billing_postcode',true));
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
		// backwards compatibility?
		$shipments = get_post_meta($order_id,'_myparcel_shipments',true);

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
}

endif; // class_exists

return new WooCommerce_MyParcel_Admin();