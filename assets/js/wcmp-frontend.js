jQuery( function( $ ) {
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
			window.mypa.fn.updatePage();
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
			window.mypa.fn.updatePage();
		}
	});

	$( '#billing_postcode, #billing_house_number, #shipping_postcode, #shipping_house_number' ).change();

	// pickup location selected
	$('#mypa-location-container').on('change', 'input[type=radio]', function() {
		var pickup_location = $( this ).val();
	});
});
