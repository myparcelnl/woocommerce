<?php
/**
 * Main plugin functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'WooCommerce_MyParcel_Admin' ) ) :

class WooCommerce_MyParcel_Admin {
	
	function __construct()	{
		add_action( 'woocommerce_admin_order_actions_end', array( $this, 'order_list_shipment_options' ), 9999 );
		add_action(	'admin_footer', array( $this, 'bulk_actions' ) ); 
		add_action( 'woocommerce_admin_order_actions_end', array( $this, 'admin_order_actions' ), 20 );

		add_action( 'wp_ajax_wcmp_save_shipment_options', array( $this, 'save_shipment_options_ajax' ) );
	}

	public function order_list_shipment_options( $order ) {
		$order_id = $order->id;
		$shipment_options = WooCommerce_MyParcel()->export->get_options( $order );
		$myparcel_options_extra = $order->myparcel_shipment_options_extra;
		$package_types = WooCommerce_MyParcel()->export->get_package_types();


		?>
		<div class="wcmp_shipment_options" style="display:none">
			<a href="#" class="wcmp_show_shipment_options"><span class="wcpm_package_type"><?php echo $package_types[$shipment_options['package_type']]; ?></span> &#x25BE;</a>
			<div class="wcmp_shipment_options_form" style="display: none;">
				<?php include('views/wcmp-order-shipment-options.php'); ?>
			</div>
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
		<?php
		}
	}

	/**
	 * Add print actions to the orders listing
	 */
	public function admin_order_actions( $order ) {
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
			// 'add_return'	=> array (
			// 	'url'		=> wp_nonce_url( admin_url( 'admin-ajax.php?action=wc_myparcel&request=add_return&order_ids=' . $order->id ), 'wc_myparcel' ),
			// 	'img'		=> WooCommerce_MyParcel()->plugin_url() . '/assets/img/myparcel-retour.png',
			// 	'alt'		=> esc_attr__( 'Create & Print return label', 'woocommerce-myparcel' ),
			// ),
		);

		if ( $consignment_id = get_post_meta($order->id,'_myparcel_consignment_id',true ) ) {
			$consignments = array(
				array(
					'consignment_id' => $consignment_id,
					'tracktrace'     => get_post_meta($order->id,'_myparcel_tracktrace',true ),
				),
			);
		} else {
			$consignments = get_post_meta($order->id,'_myparcel_shipments',true );
		}

		if (empty($consignments)) {
			unset($listing_actions['get_labels']);
			unset($listing_actions['add_return']);
		}

		$target = ( isset(WooCommerce_MyParcel()->general_settings['download_display']) && WooCommerce_MyParcel()->general_settings['download_display'] == 'display') ? 'target="_blank"' : '';
		$nonce = wp_create_nonce('wc_myparcel');
		foreach ($listing_actions as $action => $data) {
			printf( '<a href="%1$s" class="button tips myparcel %2$s" alt="%3$s" data-tip="%3$s" data-order-id="%4$s" data-request="%2$s" data-nonce="%5$s" %6$s>', $data['url'], $action, $data['alt'], $order->id, $nonce, $target );
			?>
				<img src="<?php echo $data['img']; ?>" alt="<?php echo $data['alt']; ?>" width="16">
			</a>
			<?php
		}
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
					'insured_amount'	=> $shipment_options['insured_amount'],
					'currency'			=> 'EUR',
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

}

endif; // class_exists

return new WooCommerce_MyParcel_Admin();