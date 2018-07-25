jQuery( function( $ ) {
	var postnl_update_timer = false;
	window.postnl_checkout_updating = false;
	window.postnl_force_update = false;
	window.postnl_selected_shipping_method = '';
	window.postnl_updated_shipping_method = '';

	// reference jQuery for PostNL iFrame
	window.postjQuery = $;
	
	// replace iframe placeholder with actual iframe
	$('.postnl-iframe-placeholder').replaceWith( '<iframe id="postnl-iframe" src="" frameborder="0" scrolling="auto" style="width: 100%; display: none;">Bezig met laden...</iframe>');
	// show if we have to
	if ( window.postnl_initial_hide == false ) {
		$('#postnl-iframe').show();
	}

	// set iframe object load functions
	var iframe_object = $('#postnl-iframe')
		.load( function() {
			var $PoStiFrame = $('#postnl-iframe')[0];
			window.PoStWindow = $PoStiFrame.contentWindow ? $PoStiFrame.contentWindow : $PoStiFrame.contentDocument.defaultView;
			PoStLoaded();
		});
	// load iframe content
	iframe_object.attr( 'src', wc_postnl_frontend.iframe_url );

	window.PoStSetHeight = function() {
		setTimeout(function () {
			var iframeheight = PoStWindow.document.body.scrollHeight;
			// console.log(iframeheight);
			$('#postnl-iframe').height(iframeheight);
		}, 500);

		// $('#postnl-iframe').height($('#postnl-iframe').contents().height());
	}

	window.PoStLoaded = function() {
		window.update_postnl_settings();
		PoStWindow.initSettings( window.post.settings );
		PoStSetHeight();
	}

	// set iframe height when delivery options changed
	$( document ).on('change', '#post-chosen-delivery-options input', function() {
		PoStSetHeight(); // may need a trick to prevent height from updating 10x
		window.postnl_checkout_updating = true;
		$('body').trigger('update_checkout');
	});

	// make delivery options update at least once (but don't hammer)
	// postnl_update_timer = setTimeout( update_postnl_delivery_options_action, '500' );

	// hide checkout options if not NL
	$( '#billing_country, #shipping_country' ).change(function() {
		window.postnl_force_update = true; // in case the shipping method doesn't change
		// check_country();
		update_postnl_settings();
	});

	// multi-step checkout doesn't trigger update_checkout when postcode changed
	$( document ).on('change','.wizard .content #billing_street_name, .wizard .content #billing_house_number, .wizard .content #billing_postcode',function() {
		update_postnl_settings();
	});

	// hide checkout options for non parcel shipments
	$( document ).on( 'updated_checkout', function() {
		window.postnl_checkout_updating = false; //done updating
		if ( typeof window.postnl_delivery_options_always_display !== 'undefined' && window.postnl_delivery_options_always_display == 'yes') {
			show_postnl_delivery_options();
		} else if ( window.postnl_delivery_options_shipping_methods.length > 0 ) {
			// check if shipping is user choice or fixed
			if ( $( '#order_review .shipping_method' ).length > 1 ) {
				var shipping_method = $( '#order_review .shipping_method:checked').val();
			} else {
				var shipping_method = $( '#order_review .shipping_method').val();
			}

			if ( typeof shipping_method === 'undefined' ) {
				// no shipping method selected, hide by default
				hide_postnl_delivery_options();
				return;
			}

			if (shipping_method.indexOf('table_rate:') !== -1 || shipping_method.indexOf('betrs_shipping:') !== -1){
				// WC Table Rates
				// use shipping_method = method_id:instance_id:rate_id
                if (shipping_method.indexOf('betrs_shipping:') !== -1) {
					shipping_method = shipping_method.replace(":", "_");
				}
			} else {
				// none table rates
				// strip instance_id if present
				if (shipping_method.indexOf(':') !== -1) {
					shipping_method = shipping_method.substring(0, shipping_method.indexOf(':'));
				}
				var shipping_class = $('#postnl_highest_shipping_class').val();
				// add class refinement if we have a shipping class
				if (shipping_class) {
					shipping_method_class = shipping_method+':'+shipping_class;
				}
			}
			
			if ( shipping_class && $.inArray(shipping_method_class, window.postnl_delivery_options_shipping_methods) > -1 ) {
				window.postnl_updated_shipping_method = shipping_method_class;
				show_postnl_delivery_options();
				window.postnl_selected_shipping_method = shipping_method_class;
			} else if ( $.inArray(shipping_method, window.postnl_delivery_options_shipping_methods) > -1 ) {
				// fallback to bare method if selected in settings
				window.postnl_updated_shipping_method = shipping_method;
				show_postnl_delivery_options();
				window.postnl_selected_shipping_method = shipping_method;
			} else {
				shipping_method_now = typeof shipping_method_class !== 'undefined' ? shipping_method_class : shipping_method;
				window.postnl_updated_shipping_method = shipping_method_now;
				hide_postnl_delivery_options();
				window.postnl_selected_shipping_method = shipping_method_now;
			}
		} else {
			// not sure if we should already hide by default?
			hide_postnl_delivery_options();
		}
	});

	// update postnl settings object with address when shipping or billing address changes
	window.update_postnl_settings = function() {
		var settings = get_settings();
		if (settings == false) {
			return;
		}

		var billing_postcode = $( '#billing_postcode' ).val();
		var billing_house_number = $( '#billing_house_number' ).val();
		var billing_street_name = $( '#billing_street_name' ).val();

		var shipping_postcode = $( '#shipping_postcode' ).val();
		var shipping_house_number = $( '#shipping_house_number' ).val();
		var shipping_street_name = $( '#shipping_street_name' ).val();

		var use_shipping = $( '#ship-to-different-address-checkbox' ).is(':checked');

		if (!use_shipping && billing_postcode && billing_house_number) {
			window.post.settings.postal_code = billing_postcode.replace(/\s+/g, '');
			window.post.settings.number = billing_house_number;
			window.post.settings.street = billing_street_name;
			update_postnl_delivery_options()
		} else if (shipping_postcode && shipping_house_number) {
			window.post.settings.postal_code = shipping_postcode.replace(/\s+/g, '');;
			window.post.settings.number = shipping_house_number;
			window.post.settings.street = shipping_street_name;
			update_postnl_delivery_options()
		}

	}
	
	// billing or shipping changes
	$( '#billing_postcode, #billing_house_number, #shipping_postcode, #shipping_house_number' ).change(function() {
		update_postnl_settings();
	});


	$( '#billing_postcode, #billing_house_number, #shipping_postcode, #shipping_house_number' ).change();

	// any delivery option selected/changed - update checkout for fees
	$('#post-chosen-delivery-options').on('change', 'input', function() {
		window.postnl_checkout_updating = true;
		// disable signed & recipient only when switching to pickup location
		post_postnl_data = JSON.parse( $('#post-chosen-delivery-options #post-input').val() );
		if (typeof post_postnl_data.location != 'undefined' ) {
			$('#post-signed, #post-recipient-only').prop( "checked", false );
		}
		jQuery('body').trigger('update_checkout');
	});

	// pickup location selected
	// $('#post-location-container').on('change', 'input[type=radio]', function() {
	// 	var pickup_location = $( this ).val();
	// });
	// 
	function get_settings() {
		if (typeof window.post != 'undefined' && typeof window.post.settings != 'undefined') {
			return window.post.settings;
		} else {
			return false;
		}
	}

	function check_country() {
		country = get_shipping_country();
		if (country != 'NL') {
			hide_postnl_delivery_options();
		} else {
			$( '#postnl-iframe' ).show();
			$( '#post-options-enabled' ).prop('checked', true);
		}
	}

	function get_shipping_country() {
		if ( $( '#ship-to-different-address-checkbox' ).is(':checked') ) {
			country = $( '#shipping_country' ).val();
		} else {
			country = $( '#billing_country' ).val();
		}

		return country;
	}

	function hide_postnl_delivery_options() {
		$( '#postnl-iframe' ).hide();
		$( '#post-options-enabled' ).prop('checked', false);
		// clear delivery options
		if ( is_updated_shipping_method() ) { // prevents infinite updated_checkout - update_checkout loop
			$( '#post-chosen-delivery-options #post-input' ).val('');
			$( '#post-chosen-delivery-options :checkbox' ).prop('checked', false);
			jQuery('body').trigger('update_checkout');
		}
	}

	function show_postnl_delivery_options() {
		// show only if NL
		check_country();
		if ( is_updated_shipping_method() ) { // prevents infinite updated_checkout - update_checkout loop
			update_postnl_settings();
		}
	}


	function update_postnl_delivery_options() {
		// Small timeout to prevent multiple requests when several fields update at the same time
		clearTimeout( postnl_update_timer );
		postnl_update_timer = setTimeout( update_postnl_delivery_options_action, '5' );
	}

	function update_postnl_delivery_options_action() {
		country = get_shipping_country();
		if ( window.postnl_checkout_updating !== true && country == 'NL' && typeof PoStWindow != 'undefined' && typeof PoStWindow.post != 'undefined' ) {
			PoStWindow.post.settings = window.post.settings;
			PoStWindow.updatePoSt();
		}
	}

	function is_updated_shipping_method() {
		if ( window.postnl_updated_shipping_method != window.postnl_selected_shipping_method || window.postnl_force_update === true ) {
			window.postnl_force_update = false; // only force once
			return true;
		} else {
			return false;
		}
	}

});
