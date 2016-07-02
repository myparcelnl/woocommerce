jQuery( function( $ ) {
	var myparcel_update_timer = false;
	var myparcel_checkout_updating = false;

	// make delivery options update at least once (but don't hammer)
	// myparcel_update_timer = setTimeout( update_myparcel_delivery_options_action, '500' );

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
			window.mypa.settings.postal_code = billing_postcode;
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
			window.mypa.settings.postal_code = shipping_postcode;
			window.mypa.settings.number = shipping_house_number;
			window.mypa.settings.street = shipping_street_name;
			update_myparcel_delivery_options()
		}
	});

	$( '#billing_postcode, #billing_house_number, #shipping_postcode, #shipping_house_number' ).change();

	// any delivery option selected/changed - update checkout for fees
	$('#mypa-delivery-options-container').on('change', 'input[type=radio], input[type=checkbox]', function() {
		myparcel_checkout_updating = true;
		jQuery('body').trigger('update_checkout');
		myparcel_checkout_updating = false;
	});

	// pickup location selected
	// $('#mypa-location-container').on('change', 'input[type=radio]', function() {
	// 	var pickup_location = $( this ).val();
	// });

	function update_myparcel_delivery_options() {
		// Small timeout to prevent multiple requests when several fields update at the same time
		clearTimeout( myparcel_update_timer );
		myparcel_update_timer = setTimeout( update_myparcel_delivery_options_action, '5' );
	}

	function update_myparcel_delivery_options_action() {
		if ( myparcel_checkout_updating !== true ) {
			mypa.fn.updatePage();
		}
	}

});
