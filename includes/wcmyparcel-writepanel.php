<?php
class WC_MyParcel_Writepanel {

	public function __construct() {
		$this->settings = get_option( 'wcmyparcel_settings' );

		// Add meta box with MyParcel links/buttons
		add_action( 'add_meta_boxes_shop_order', array( $this, 'add_box' ) );

		// Add export action to drop down menu
		add_action(	'admin_footer', array( &$this, 'export_actions' ) ); 

		// Add buttons in order listing
		add_action( 'woocommerce_admin_order_actions_end', array( $this, 'add_listing_actions' ), 20 );
		
    	// Customer Emails
		if (isset($this->settings['email_tracktrace'])) {
	    	add_action( 'woocommerce_email_before_order_table', array( $this, 'track_trace_email' ), 10, 2 );
		}

		// Track & trace in my account
		if (isset($this->settings['myaccount_tracktrace'])) {
			add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'track_trace_myaccount' ), 10, 2 );
		}
		
		// Pakjegemak
		if (isset($this->settings['pakjegemak'])) {
			add_action( apply_filters( 'wcmyparcel_pakjegemak_locatie', 'woocommerce_checkout_before_customer_details' ), array( $this, 'pakjegemak' ), 10, 1 );
		}

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
		$pdf_link = wp_nonce_url( admin_url( 'edit.php?&action=wcmyparcel-label&order_ids=' . $post_id ), 'wcmyparcel-label' );
		$export_link = wp_nonce_url( admin_url( 'edit.php?&action=wcmyparcel&order_ids=' . $post_id ), 'wcmyparcel' );

		$target = ( isset($this->settings['download_display']) && $this->settings['download_display'] == 'display') ? 'target="_blank"' : '';


		if (get_post_meta($post_id,'_myparcel_consignment_id',true)) {
			$consignment_id = get_post_meta($post_id,'_myparcel_consignment_id',true);

			$tracktrace = get_post_meta($post_id,'_myparcel_tracktrace',true);
			$tracktrace_url = $this->get_tracktrace_url($post_id);


			// fetch TNT status
			$tnt_status_url = 'http://www.myparcel.nl/status/tnt/' . $consignment_id;
			$tnt_status = explode('|', @file_get_contents($tnt_status_url));
            $tnt_status = (count($tnt_status) == 3) ? $tnt_status[2] : '';
			
			?>
			<ul>
				<li>
					<a href="<?php echo $pdf_link; ?>" class="button" alt="Download label (PDF)" <?php echo $target; ?>>Download label (PDF)</a>
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

		$pdf_link = wp_nonce_url( admin_url( 'edit.php?&action=wcmyparcel-label&order_ids=' . $order->id ), 'wcmyparcel-label' );
		$export_link = wp_nonce_url( admin_url( 'edit.php?&action=wcmyparcel&order_ids=' . $order->id ), 'wcmyparcel' );

		$target = ( isset($this->settings['download_display']) && $this->settings['download_display'] == 'display') ? 'target="_blank"' : '';
		if (!empty($consignment_id)) {
			?>
			<a href="<?php echo $pdf_link; ?>" class="button tips myparcel" alt="Print MyParcel label" data-tip="Print MyParcel label" <?php echo $target; ?>>
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

    public function track_trace_email( $order, $sent_to_admin ) {

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

	public function track_trace_myaccount( $actions, $order ) {
		$tracktrace = get_post_meta($order->id,'_myparcel_tracktrace',true);
		if ( !empty($tracktrace) ) {
			$tracktrace_url = $this->get_tracktrace_url($order->id);

			$actions['myparcel_tracktrace'] = array(
				'url'  => $tracktrace_url,
				'name' => apply_filters( 'wcmyparcel_myaccount_tracktrace_button', __( 'Track&Trace', 'wpo_wcpdf' ) )
			);				
		}

		return $actions;
	}

	public function get_tracktrace_url($order_id) {
		if (empty($order_id))
			return;

		$country = get_post_meta($order_id,'_shipping_country',true);
		$tracktrace = get_post_meta($order_id,'_myparcel_tracktrace',true);
		$postcode = preg_replace('/\s+/', '',get_post_meta($order_id,'_shipping_postcode',true));

		// set url for NL or foreign orders
		if ($country == 'NL') {
			// $tracktrace_url = sprintf('https://mijnpakket.postnl.nl/Inbox/Search?lang=nl&B=%s&P=%s', $tracktrace, $postcode);
			$tracktrace_url = sprintf('https://mijnpakket.postnl.nl/Claim?Barcode=%s&Postalcode=%s', $tracktrace, $postcode);
		} else {
			$tracktrace_url = sprintf('https://www.internationalparceltracking.com/Main.aspx#/track/%s/%s/%s', $tracktrace, $country, $postcode);			
		}

		return $tracktrace_url;
	}

	/**
	 * Add pakjegemak button to checkout
	 */
	
	public function pakjegemak() {
		$username = $this->settings['api_username'];
		$api_key = $this->settings['api_key'];

		$webshop = trailingslashit( plugin_dir_url( __FILE__ ) ) . 'wcmyparcel-pakjegemak-passdata.php';
		$hash = hash_hmac('sha1', $username . 'MyParcel' . $webshop, $api_key);

		// check for secure context
		$context = is_ssl() ? 'https' : 'http';

		$popup_url = sprintf('%s://www.myparcel.nl/pakjegemak-locatie?hash=%s&webshop=%s&user=%s', $context, $hash, $webshop, $username);


		if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '<=' ) ) {
			// old versions use 'shiptobilling'
			$stda = 'shiptobilling';
			$stda_checked = 'false'; // uncheck for alternate shipping address			
		} else {
			// WC2.1
			$stda = 'ship-to-different-address';
			$stda_checked = 'true'; // check for alternate shipping address			
		}

		// Create page text/HTML
		$omschrijving	= $this->settings['pakjegemak_description'];
		$knop			= $this->settings['pakjegemak_button'];

		ob_start();
		?>
		<div class="myparcel-pakjegemak" style="overflow:auto;">
			<span class="myparcel-pakjegemak-omschrijving"><?php echo $omschrijving; ?></span>
			<a class="myparcel-pakjegemak button" onclick="return pakjegemak();" style="cursor:pointer; float:right; margin:1em 0"><?php echo $knop; ?></a>
		</div>
		<?php
		// gebruik het filter om je eigen HTML/tekst weer te geven
		echo apply_filters( 'wcmyparcel_pakjegemak_html', ob_get_clean() );
		?>
			<script type="text/javascript">
			var pg_popup;
			function pakjegemak()
			{
				jQuery( '#<?php echo $stda;?> input' ).prop('checked', <?php echo $stda_checked;?>);
				jQuery( '#<?php echo $stda;?> input' ).change();
				jQuery( '#shipping_country' ).val('NL');
				jQuery( '#shipping_country' ).trigger("chosen:updated")
				jQuery( '#shipping_country' ).change();
				if(!pg_popup || pg_popup.closed)
				{
					pg_popup = window.open(
						'<?php echo $popup_url; ?>',
						'myparcel-pakjegemak',
						'width=980,height=680,dependent,resizable,scrollbars'
					);
					if(window.focus) { pg_popup.focus(); }
				}
				else
				{
					pg_popup.focus();
				}
				return false;
			}
			</script>
		<?php
	}	
}