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
	}

	public function order_list_shipment_options( $order ) {
		$order_id = $order->id;
		$consignment = WooCommerce_MyParcel()->export->get_consignment_data_from_order( $order );
		$shipment_types = array(
			'standard'		=> __( 'Parcel' , 'woocommerce-myparcel' ),
			'letterbox'		=> __( 'Letterbox' , 'woocommerce-myparcel' ),
			'unpaid_letter'	=> __( 'Unpaid letter' , 'woocommerce-myparcel' ),
		);

		?>
		<div class="wcmp_shipment_options" style="display:none">
			<a href="#" class="wcmp_show_shipment_options"><?php echo $shipment_types[$consignment['shipment_type']]; ?> &#x25BE;</a>
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
			'export'		=> array (
				'url'		=> wp_nonce_url( admin_url( 'edit.php?&action=wcmp_export&order_ids=' . $order->id ), 'wcmp_export' ),
				'img'		=> WooCommerce_MyParcel()->plugin_url() . '/assets/img/myparcel-pdf.png',
				'alt'		=> esc_attr__( 'Export to MyParcel', 'woocommerce-myparcel' ),
			),
			'print'	=> array (
				'url'		=> wp_nonce_url( admin_url( 'edit.php?&action=wcmp_print&order_ids=' . $order->id ), 'wcmp_print' ),
				'img'		=> WooCommerce_MyParcel()->plugin_url() . '/assets/img/myparcel-up.png',
				'alt'		=> esc_attr__( 'Print MyParcel label', 'woocommerce-myparcel' ),
			),
			'return'	=> array (
				'url'		=> wp_nonce_url( admin_url( 'edit.php?&action=wcmp_return&order_ids=' . $order->id ), 'wcmp_return' ),
				'img'		=> WooCommerce_MyParcel()->plugin_url() . '/assets/img/myparcel-retour.png',
				'alt'		=> esc_attr__( 'Create & Print return label', 'woocommerce-myparcel' ),
			),
		);

		if ( $consignment_id = get_post_meta($order->id,'_myparcel_consignment_id',true ) ) {
			$consignments = array(
				array(
					'consignment_id' => $consignment_id,
					'tracktrace'     => get_post_meta($order->id,'_myparcel_tracktrace',true ),
				),
			);
		} else {
			$consignments = get_post_meta($order->id,'_myparcel_consignments',true );
		}

		if (empty($consignments)) {
			unset($listing_actions['print']);
			unset($listing_actions['return']);
		}

		$target = ( isset(WooCommerce_MyParcel()->general_settings['download_display']) && WooCommerce_MyParcel()->general_settings['download_display'] == 'display') ? 'target="_blank"' : '';
		foreach ($listing_actions as $action => $data) {
			?>
			<a href="<?php echo $data['url']; ?>" class="button tips wpo_wcpdf <?php echo $action; ?>" target="_blank" alt="<?php echo $data['alt']; ?>" data-tip="<?php echo $data['alt']; ?>">
				<img src="<?php echo $data['img']; ?>" alt="<?php echo $data['alt']; ?>" width="16">
			</a>
			<?php
		}
	}

}

endif; // class_exists

return new WooCommerce_MyParcel_Admin();