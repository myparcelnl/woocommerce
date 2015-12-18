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

		// Save pakjegemak choice
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_pakjegemak_choice' ), 10, 2 );

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

		if ( get_post_meta($post_id,'_myparcel_consignments',true ) || get_post_meta($post_id,'_myparcel_consignment_id',true ) ) {
			if ( $tracktrace_consignments = $this->get_tracktrace_consignments( $post_id ) ) {
				?>
				<table class="tracktrace_status">
					<thead>
						<th>Track&Trace</th>
						<th>Status</th>
					</thead>
					<tbody>
					<?php
					// echo '<pre>';var_dump($consignments);echo '</pre>';die();
					foreach ($tracktrace_consignments as $consignment) {
						// fetch TNT status
						$tnt_status_url = 'http://www.myparcel.nl/status/tnt/' . $consignment['consignment_id'];
						$tnt_status = explode('|', @file_get_contents($tnt_status_url));
						$tnt_status = (count($tnt_status) == 3) ? $tnt_status[2] : '-';

						echo "<tr><td>{$consignment['tracktrace_link']}</td><td>{$tnt_status}</td></tr>";
					}
					?>
					</tbody>
				</table>
				<?php
			}
			?>
			<ul>
				<li><a href="<?php echo $pdf_link; ?>" class="button" alt="Download label (PDF)" <?php echo $target; ?>>Download label (PDF)</a></li>
				<li><a href="<?php echo $export_link; ?>" class="button myparcel one-myparcel" alt="Exporteer naar MyParcel">Exporteer opnieuw</a></li>
			</ul>
			<?php
		} else {
			?>
			<ul>
				<li><a href="<?php echo $export_link; ?>" class="button myparcel one-myparcel" alt="Exporteer naar MyParcel">Exporteer naar MyParcel</a></li>
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

		$pdf_link = wp_nonce_url( admin_url( 'edit.php?&action=wcmyparcel-label&order_ids=' . $order->id ), 'wcmyparcel-label' );
		$export_link = wp_nonce_url( admin_url( 'edit.php?&action=wcmyparcel&order_ids=' . $order->id ), 'wcmyparcel' );

		$target = ( isset($this->settings['download_display']) && $this->settings['download_display'] == 'display') ? 'target="_blank"' : '';
		if (!empty($consignments)) {
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

		$tracktrace_links = $this->get_tracktrace_links ( $order->id );
		if ( !empty($tracktrace_links) ) {;
			$email_text = apply_filters( 'wcmyparcel_email_text', 'U kunt uw bestelling volgen met het volgende PostNL track&trace nummer:' );
			?>
			<p><?php echo $email_text.' '.implode(', ', $tracktrace_links); ?></p>
	
			<?php
		}
	}

	public function track_trace_myaccount( $actions, $order ) {
		if ( $consignments = $this->get_tracktrace_consignments( $order->id ) ) {
			foreach ($consignments as $key => $consignment) {
				$actions['myparcel_tracktrace_'.$consignment['tracktrace']] = array(
					'url'  => $consignment['tracktrace_url'],
					'name' => apply_filters( 'wcmyparcel_myaccount_tracktrace_button', __( 'Track&Trace', 'wpo_wcpdf' ) )
				);
			}
		}

		return $actions;
	}

	public function get_tracktrace_links ( $order_id ) {
		if ( $consignments = $this->get_tracktrace_consignments( $order_id )) {
			foreach ($consignments as $key => $consignment) {
				$tracktrace_links[] = $consignment['tracktrace_link'];
			}
			return $tracktrace_links;
		} else {
			return false;
		}
	}

	public function get_tracktrace_consignments ( $order_id ) {
		if ( $consignment_id = get_post_meta($order_id,'_myparcel_consignment_id',true ) ) {
			$consignments = array(
				array(
					'consignment_id' => $consignment_id,
					'tracktrace'     => get_post_meta($order_id,'_myparcel_tracktrace',true ),
				),
			);
		} else {
			$consignments = get_post_meta($order_id,'_myparcel_consignments',true );
		}

		if (empty($consignments)) {
			return false;
		}

		// remove non-track & trace consignments
		foreach ($consignments as $key => $consignment) {
			if (empty($consignment['tracktrace'])) {
				unset($consignments[$key]);
			}
		}

		if (empty($consignments)) {
			return false;
		}

		// add links & urls
		foreach ($consignments as $key => $consignment) {
			$consignments[$key]['tracktrace_url'] = $tracktrace_url = $this->get_tracktrace_url( $order_id, $consignment['tracktrace'] );
			$consignments[$key]['tracktrace_link'] = sprintf('<a href="%s">%s</a>', $tracktrace_url, $consignment['tracktrace']);
		}

		return $consignments;
	}

	public function get_tracktrace_url( $order_id, $tracktrace ) {
		if (empty($order_id))
			return;

		$country = get_post_meta($order_id,'_shipping_country',true);
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

		// allow overriding passdata file via theme / child theme
		$passdata_file = 'wcmyparcel-pakjegemak-passdata.php';
		// get paths for file_exists check
		$child_theme_template_path = get_stylesheet_directory() . '/woocommerce/';
		$theme_template_path = get_template_directory() . '/woocommerce/';

		// First check child theme, then theme, else use bundled file
		if( file_exists( $child_theme_template_path . $passdata_file ) ) {
			$passdata_url = get_stylesheet_directory_uri() . '/woocommerce/' . $passdata_file;
		} elseif( file_exists( $theme_template_path . $passdata_file ) ) {
			$passdata_url = get_template_directory_uri() . '/woocommerce/' . $passdata_file;
		} else {
			$passdata_url = trailingslashit( plugin_dir_url( __FILE__ ) ) . $passdata_file;
		}
		
		$webshop = trailingslashit( plugin_dir_url( __FILE__ ) ) . 'wcmyparcel-pakjegemak-passdata.php';
		$hash = hash_hmac('sha1', $username . 'MyParcel' . $passdata_url, $api_key);

		// check for secure context
		$context = is_ssl() ? 'https' : 'http';

		$popup_url = sprintf('%s://www.myparcel.nl/pakjegemak-locatie?hash=%s&webshop=%s&user=%s', $context, $hash, $passdata_url, $username);


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
			<input type="hidden" name="myparcel_is_pakjegemak" value="">
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

	/**
	 * Save whether pakjegemak was used or not.
	 *
	 * @param  int   $order_id
	 * @param  array $posted
	 *
	 * @return void
	 */
	public function save_pakjegemak_choice( $order_id, $posted ) {
		if (!empty($_POST['myparcel_is_pakjegemak'])) {
			update_post_meta( $order_id, '_myparcel_is_pakjegemak', 'yes' );
		}
	}
}