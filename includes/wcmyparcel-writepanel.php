<?php
class WC_MyParcel_Writepanel {

	public function __construct() {
		$this->settings = get_option( 'wcmyparcel_settings' );

		// Add meta box with MyParcel links/buttons
		add_action( 'add_meta_boxes_shop_order', array( $this, 'add_box' ) );

		// Add export action to drop down menu
		add_action(	'admin_footer-edit.php', array( &$this, 'export_actions' ) ); 

		//Add buttons in order listing
		add_action( 'woocommerce_admin_order_actions_end', array( $this, 'add_listing_actions' ), 20 );
		
    	// Customer Emails
		if (isset($this->settings['email_tracktrace']))
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
			$tracktrace_url = $this->get_tracktrace_url($post_id);

			$pdf_link = wp_nonce_url( admin_url( 'edit.php?&action=wcmyparcel-label&order_ids=' . $post_id ), 'wcmyparcel-label' );
			$export_link = wp_nonce_url( admin_url( 'edit.php?&action=wcmyparcel&order_ids=' . $post_id ), 'wcmyparcel' );

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
					<a href="<?php echo $export_link; ?>" class="button myparcel one-myparcel" alt="Exporteer naar MyParcel">Exporteer opnieuw</a>
				</li>
			</ul>
			<?php
		} else {
			?>
			<ul>
				<li>
					<a href="<?php echo $export_link; ?>" class="button myparcel one-myparcel" alt="Exporteer naar MyParcel">Exporteer naar MyParcel</a>
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
		$consignment_id = get_post_meta($order->id,'_myparcel_consignment_id',true);
		if (!empty($consignment_id)) {
			$pdf_link = wp_nonce_url( admin_url( 'edit.php?&action=wcmyparcel-label&order_ids=' . $order->id ), 'wcmyparcel-label' );
			$export_link = wp_nonce_url( admin_url( 'edit.php?&action=wcmyparcel&order_ids=' . $order->id ), 'wcmyparcel' );
			?>
			<a href="<?php echo $pdf_link; ?>" class="button tips myparcel" alt="Print MyParcel label" data-tip="Print MyParcel label">
				<img src="<?php echo dirname(plugin_dir_url(__FILE__)) . '/img/myparcel-pdf.png'; ?>" alt="Print MyParcel label">
			</a>
			<a href="<?php echo $export_link; ?>" class="button tips myparcel one-myparcel" alt="Exporteer naar MyParcel" data-tip="Exporteer naar MyParcel">
				<img src="<?php echo dirname(plugin_dir_url(__FILE__)) . '/img/myparcel-up.png'; ?>" alt="Exporteer naar MyParcel">
			</a>
			<?php
		} else {
			?>
			<a href="<?php echo $export_link; ?>" class="button tips myparcel one-myparcel" alt="Exporteer naar MyParcel" data-tip="Exporteer naar MyParcel">
				<img src="<?php echo dirname(plugin_dir_url(__FILE__)) . '/img/myparcel-up.png'; ?>" alt="Exporteer naar MyParcel">
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

		$tracktrace = get_post_meta($order->id,'_myparcel_tracktrace',true);
		if ( !empty($tracktrace) ) {
			$tracktrace_url = $this->get_tracktrace_url($order->id);

			$tracktrace_link = '<a href="'.$tracktrace_url.'">'.$tracktrace.'</a>';
			$email_text = apply_filters( 'wcmyparcel_email_text', 'U kunt uw bestelling volgen met het volgende PostNL track&trace nummer:' );
			?>
			<p><?php echo $email_text.' '.$tracktrace_link; ?></p>
	
			<?php
		}
	}

	public function get_tracktrace_url($order_id) {
		if (empty($order_id))
			return;

		$tracktrace = get_post_meta($order_id,'_myparcel_tracktrace',true);
		$postcode = preg_replace('/\s+/', '',get_post_meta($order_id,'_shipping_postcode',true));
		$tracktrace_url = sprintf('https://mijnpakket.postnl.nl/Claim?Barcode=%s&Postalcode=%s', $tracktrace, $postcode);
		
		//Check if foreign
		$country = get_post_meta($order_id,'_shipping_country',true);
		if ($country != 'NL')
			$tracktrace_url = add_query_arg( 'Foreign', 'True', $tracktrace_url );

		return $tracktrace_url;
	}
}