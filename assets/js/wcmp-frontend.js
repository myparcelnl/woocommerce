jQuery( function( $ ) {
	var myparcel_update_timer = false;
	var myparcel_checkout_updating = false;

	// reference jQuery for MyParcel iFrame
	window.mypajQuery = $;
	
	// bail if delivery options iframe is not found
	if ( $('#myparcel-iframe').length == 0 ) {
		return;
	}	

	// set reference to iFrame
	var $MyPaiFrame = $('#myparcel-iframe')[0];
	window.MyPaWindow = $MyPaiFrame.contentWindow ? $MyPaiFrame.contentWindow : $MyPaiFrame.contentDocument.defaultView;

	window.MyPaSetHeight = function() {
		setTimeout(function () {
			var iframeheight = MyPaWindow.document.body.scrollHeight;
			// console.log(iframeheight);
			$('#myparcel-iframe').height(iframeheight);
		}, 500);

		// $('#myparcel-iframe').height($('#myparcel-iframe').contents().height());
	}

	window.MyPaLoaded = function() {
		window.update_myparcel_settings();
		MyPaWindow.initSettings( window.mypa.settings );
		MyPaSetHeight();
	}

	// set iframe height when delivery options changed
	$('#mypa-chosen-delivery-options').on('change', 'input', function() {
		MyPaSetHeight(); // may need a trick to prevent height from updating 10x
		myparcel_checkout_updating = true;
		$('body').trigger('update_checkout');
		myparcel_checkout_updating = false;
	});

	// make delivery options update at least once (but don't hammer)
	// myparcel_update_timer = setTimeout( update_myparcel_delivery_options_action, '500' );

	// hide checkout options if not NL
	$( '#billing_country, #shipping_country' ).change(function() {
		check_country();
	});

	// hide checkout options for non parcel shipments
	$( document ).on( 'updated_checkout', function() {
		// check if shipping is user choice or fixed
		if ( $( '#order_review .shipping_method' ).length > 1 ) {
			var shipping_method = $( '#order_review .shipping_method:checked').val();
		} else {
			var shipping_method = $( '#order_review .shipping_method').val();
		}
		// console.log(shipping_method);
		// strip instance_id if present
		if (shipping_method.indexOf(':') !== -1) {
			shipping_method = shipping_method.substring(0, shipping_method.indexOf(':'));
		}
		// console.log(shipping_method);
		if ( typeof window.myparcel_delivery_options_always_display !== 'undefined' && window.myparcel_delivery_options_always_display == 'yes') {
			show_myparcel_delivery_options();
		} else if ( window.myparcel_delivery_options_shipping_methods.length > 0 ) {
			var shipping_class = $('#myparcel_highest_shipping_class').val();
			// add class refinement if we have a shipping class
			if (shipping_class) {
				shipping_method_class = shipping_method+':'+shipping_class;
			}
			if ( shipping_class && $.inArray(shipping_method_class, window.myparcel_delivery_options_shipping_methods) > -1 ) {
				show_myparcel_delivery_options();
			} else if ( $.inArray(shipping_method, window.myparcel_delivery_options_shipping_methods) > -1 ) {
				// fallback to bare method if selected in settings
				show_myparcel_delivery_options();
			} else {
				hide_myparcel_delivery_options();
			}
		} else {
			// not sure if we should already hide by default?
			hide_myparcel_delivery_options();
		}
	});

	// update myparcel settings object with address when shipping or billing address changes
	window.update_myparcel_settings = function() {
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
			window.mypa.settings.postal_code = billing_postcode.replace(/\s+/g, '');
			window.mypa.settings.number = billing_house_number;
			window.mypa.settings.street = billing_street_name;
			update_myparcel_delivery_options()
		} else if (shipping_postcode && shipping_house_number) {
			window.mypa.settings.postal_code = shipping_postcode.replace(/\s+/g, '');;
			window.mypa.settings.number = shipping_house_number;
			window.mypa.settings.street = shipping_street_name;
			update_myparcel_delivery_options()
		}

	}
	
	// billing or shipping changes
	$( '#billing_postcode, #billing_house_number, #shipping_postcode, #shipping_house_number' ).change(function() {
		update_myparcel_settings();
	});


	$( '#billing_postcode, #billing_house_number, #shipping_postcode, #shipping_house_number' ).change();

	// any delivery option selected/changed - update checkout for fees
	$('#mypa-chosen-delivery-options').on('change', 'input', function() {
		myparcel_checkout_updating = true;
		jQuery('body').trigger('update_checkout');
		myparcel_checkout_updating = false;
	});

	// pickup location selected
	// $('#mypa-location-container').on('change', 'input[type=radio]', function() {
	// 	var pickup_location = $( this ).val();
	// });
	// 
	function get_settings() {
		if (typeof window.mypa != 'undefined' && typeof window.mypa.settings != 'undefined') {
			return window.mypa.settings;
		} else {
			return false;
		}
	}

	function check_country() {
		country = get_shipping_country();
		if (country != 'NL') {
			$( '#myparcel-iframe' ).hide();
			$( '#mypa-options-enabled' ).prop('checked', false);
		} else {
			$( '#myparcel-iframe' ).show();
			$( '#mypa-options-enabled' ).prop('checked', true);
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

	function hide_myparcel_delivery_options() {
		$( '#myparcel-iframe' ).hide();
		$( '#mypa-options-enabled' ).prop('checked', false);
	}

	function show_myparcel_delivery_options() {
		// show only if NL
		check_country();
	}


	function update_myparcel_delivery_options() {
		// Small timeout to prevent multiple requests when several fields update at the same time
		clearTimeout( myparcel_update_timer );
		myparcel_update_timer = setTimeout( update_myparcel_delivery_options_action, '5' );
	}

	function update_myparcel_delivery_options_action() {
		country = get_shipping_country();
		if ( myparcel_checkout_updating !== true && country == 'NL') {
			MyPaWindow.mypa.settings = window.mypa.settings;
			MyPaWindow.updateMyPa();
		}
	}

});
