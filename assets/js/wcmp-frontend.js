jQuery( function( $ ) {
	new MyParcel();
	var myparcel_update_timer = false;
	var myparcel_checkout_updating = false;

	// make delivery options update at least once (but don't hammer)
	// myparcel_update_timer = setTimeout( update_myparcel_delivery_options_action, '500' );

	// hide checkout options if not NL
	$( '#billing_country, #shipping_country' ).change(function() {
		country = get_shipping_country();
		if (country != 'NL') {
			$( 'myparcel' ).hide();
		} else {
			$( 'myparcel' ).show();
		}
	});

	// hide checkout options for non parcel shipments
	$( document ).on( 'updated_checkout', function() {
		var shipping_method = $( '#order_review .shipping_method:checked').val();
		// strip zone_id if present
		if (shipping_method.indexOf(':') !== -1) {
			shipping_method = shipping_method.substring(0, shipping_method.indexOf(':'));
		}
		if ( window.myparcel_delivery_options_shipping_methods.length > 0 ) {
			if ( $.inArray(shipping_method, window.myparcel_delivery_options_shipping_methods) > -1 ) {
				console.log(window.myparcel_delivery_options_shipping_methods.length);
				$( 'myparcel' ).show();
			} else {
				$( 'myparcel' ).hide();
			}
		}
	});

	// update myparcel settings object with address when shipping or billing address changes
	
	// billing changes
	$( '#billing_postcode, #billing_house_number' ).change(function() {
		// only use billing if shipping empty
		var billing_postcode = $( '#billing_postcode' ).val();
		var billing_house_number = $( '#billing_house_number' ).val();
		var billing_street_name = $( '#billing_street_name' ).val();

		var shipping_postcode = $( '#shipping_postcode' ).val();
		var shipping_house_number = $( '#shipping_house_number' ).val();

		var use_shipping = $( '#ship-to-different-address-checkbox' ).is(':checked');

		if ( !use_shipping && billing_postcode && billing_house_number) {
			window.mypa.settings.postal_code = billing_postcode.replace(/\s+/g, '');
			window.mypa.settings.number = billing_house_number;
			window.mypa.settings.street = billing_street_name;
			update_myparcel_delivery_options()
		}
	});

	// shipping changes
	$( '#shipping_postcode, #shipping_house_number' ).change(function() {
		var shipping_postcode = $( '#shipping_postcode' ).val();
		var shipping_house_number = $( '#shipping_house_number' ).val();
		var shipping_street_name = $( '#shipping_street_name' ).val();

		if (shipping_postcode && shipping_house_number) {
			window.mypa.settings.postal_code = shipping_postcode.replace(/\s+/g, '');;
			window.mypa.settings.number = shipping_house_number;
			window.mypa.settings.street = shipping_street_name;
			update_myparcel_delivery_options()
		}
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

	function get_shipping_country() {
		if ( $( '#ship-to-different-address-checkbox' ).is(':checked') ) {
			country = $( '#shipping_country' ).val();
		} else {
			country = $( '#billing_country' ).val();
		}

		return country;
	}

	function update_myparcel_delivery_options() {
		// Small timeout to prevent multiple requests when several fields update at the same time
		clearTimeout( myparcel_update_timer );
		myparcel_update_timer = setTimeout( update_myparcel_delivery_options_action, '5' );
	}

	function update_myparcel_delivery_options_action() {
		country = get_shipping_country();
		if ( myparcel_checkout_updating !== true && country == 'NL') {
			mypa.fn.updatePage();
		}
	}

});
