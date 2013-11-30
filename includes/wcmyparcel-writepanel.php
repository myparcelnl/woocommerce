<?php
class WC_MyParcel_Writepanel {

	public function __construct() {
		// Add meta box with MyParcel links/buttons
		add_action( 'add_meta_boxes_shop_order', array( $this, 'add_box' ) );

		// Add export action to drop down menu
		add_action(	'admin_footer-edit.php', array( &$this, 'export_actions' ) ); 

		//Add buttons in order listing
		add_action( 'woocommerce_admin_order_actions_end', array( $this, 'add_listing_actions' ), 20 );
		
    	// Customer Emails
    	add_action( 'woocommerce_email_before_order_table', array( $this, 'track_trace_email' ), 10, 2 );
		
	}

	/**
	 * Add the meta box on the single order page
	 */
	public function add_box() {
		add_meta_box(
			'myparcel', //$id
			__( 'MyParcel', 'wcmyparcel' ), //$title
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
		if (get_post_meta($post_id,'_myparcel_consignment_id',true)) {
			$consignment_id = get_post_meta($post_id,'_myparcel_consignment_id',true);
			$tracktrace = get_post_meta($post_id,'_myparcel_tracktrace',true);
			$postcode = preg_replace('/\s+/', '',get_post_meta($post_id,'_shipping_postcode',true));
			$tracktrace_url = sprintf('https://www.postnlpakketten.nl/klantenservice/tracktrace/basicsearch.aspx?lang=nl&B=%s&P=%s', $tracktrace, $postcode);
			$pdf_link = wp_nonce_url( admin_url( 'edit.php?&action=wcmyparcel-label&consignment=' . $consignment_id ), 'wcmyparcel-label' );

			// fetch TNT status
			$tnt_status_url = 'http://www.myparcel.nl/status/tnt/' . $consignment_id;
			$tnt_status = explode('|', @file_get_contents($tnt_status_url));
            $tnt_status = (count($tnt_status) == 3) ? $tnt_status[2] : '';
			
			?>
			<ul>
				<li>
					<a href="<?php echo $pdf_link; ?>" class="button" alt="Download label (PDF)">Download label (PDF)</a>
				</li>
				<li>Status: <?php echo $tnt_status ?></li>
				<li>Track&Trace code: <a href="<?php echo $tracktrace_url; ?>"><?php echo $tracktrace; ?></a></li>
				<li>
					<a href="<?php echo wp_nonce_url( admin_url( 'edit.php?&action=wcmyparcel&order_ids=' . $post_id ), 'wcmyparcel' ); ?>" class="button one-myparcel" alt="Exporteer naar MyParcel">Exporteer opnieuw</a>
				</li>
			</ul>
			<?php
		} else {
			?>
			<ul>
				<li>
					<a href="<?php echo wp_nonce_url( admin_url( 'edit.php?&action=wcmyparcel&order_ids=' . $post_id ), 'wcmyparcel' ); ?>" class="button one-myparcel" alt="Exporteer naar MyParcel">Exporteer naar MyParcel</a>
				</li>
			</ul>
			<?php			
		}
	}

	/**
	 * Add export option to bulk action drop down menu
	 *
	 * Using Javascript until WordPress core fixes: http://core.trac.wordpress.org/ticket/16031
	 *
	 * @access public
	 * @return void
	 */
	public function export_actions() {
		global $post_type;

		if ( 'shop_order' == $post_type ) {
			?>
			<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery('<option>').val('wcmyparcel').text('<?php _e( 'Exporteer naar MyParcel', 'wcmyparcel' )?>').appendTo("select[name='action']");
				jQuery('<option>').val('wcmyparcel').text('<?php _e( 'Exporteer naar MyParcel', 'wcmyparcel' )?>').appendTo("select[name='action2']");

				jQuery('<option>').val('wcmyparcel-label').text('<?php _e( 'Print MyParcel labels', 'wcmyparcel' )?>').appendTo("select[name='action']");
				jQuery('<option>').val('wcmyparcel-label').text('<?php _e( 'Print MyParcel labels', 'wcmyparcel' )?>').appendTo("select[name='action2']");
			});
			</script>
			<?php
		}
	}		

	/**
	 * Add print actions to the orders listing
	 */
	public function add_listing_actions( $order ) {
		if (isset($order->order_custom_fields['_myparcel_consignment_id'][0])) {
			$consignment_id = $order->order_custom_fields['_myparcel_consignment_id'][0];
			$pdf_link = wp_nonce_url( admin_url( 'edit.php?&action=wcmyparcel-label&consignment=' . $consignment_id ), 'wcmyparcel-label' );
			?>
			<a href="<?php echo $pdf_link; ?>" class="button tips" alt="Print MyParcel label" data-tip="Print MyParcel label" style="float:left;padding:1px 2px;">
				<img src="<?php echo dirname(plugin_dir_url(__FILE__)) . '/img/myparcel-pdf.png'; ?>" alt="Print MyParcel label" width="14px" style="width:16px;height:auto;">
			</a>
			<a href="<?php echo wp_nonce_url( admin_url( 'edit.php?&action=wcmyparcel&order_ids=' . $order->id ), 'wcmyparcel' ); ?>" class="button tips one-myparcel" alt="Exporteer naar MyParcel" data-tip="Exporteer naar MyParcel" style="float:left;padding:1px 2px;">
				<img src="<?php echo dirname(plugin_dir_url(__FILE__)) . '/img/myparcel-up.png'; ?>" alt="Exporteer naar MyParcel" width="14px" style="width:16px;height:auto;">
			</a>
			<?php
		} else {
			?>
			<a href="<?php echo wp_nonce_url( admin_url( 'edit.php?&action=wcmyparcel&order_ids=' . $order->id ), 'wcmyparcel' ); ?>" class="button tips one-myparcel" alt="Exporteer naar MyParcel" data-tip="Exporteer naar MyParcel" style="float:left;padding:1px 2px;">
				<img src="<?php echo dirname(plugin_dir_url(__FILE__)) . '/img/myparcel-up.png'; ?>" alt="Exporteer naar MyParcel" width="14px" style="width:16px;height:auto;">
			</a>
			<?php
			
		}
	}

    /**
    * Add track&trace to user email
    **/
    function track_trace_email( $order, $sent_to_admin ) {

    	if ( $sent_to_admin ) return;

    	if ( $order->status != 'completed') return;
		
		if ( isset($order->order_custom_fields['_myparcel_tracktrace'][0]) ) {
			$tracktrace = $order->order_custom_fields['_myparcel_tracktrace'][0];
			$postcode = preg_replace('/\s+/', '',$order->order_custom_fields['_shipping_postcode'][0]);
			$tracktrace_url = sprintf('https://www.postnlpakketten.nl/klantenservice/tracktrace/basicsearch.aspx?lang=nl&B=%s&P=%s', $tracktrace, $postcode);
			
			$tracktrace_link = '<a href="'.$tracktrace_url.'">'.$tracktrace.'</a>';
			?>
			<p><?php printf( __( "You can follow your order with the following track&trace number: %s", 'wcmyparcel' ), $tracktrace_link ); ?></p>
	
			<?php
		}
	}
}