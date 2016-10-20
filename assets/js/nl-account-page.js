jQuery(document).ready(function($) {

	function localize_address_fields (address_type) {
		// Hide custom NL fields by default when country not NL
		var country = $('#'+address_type+'_country').val();
		if (typeof country != 'undefined') {		
			if (country != 'NL') {
				$('#'+address_type+'_street_name_field').hide();
				$('#'+address_type+'_house_number_field').hide();
				$('#'+address_type+'_house_number_suffix_field').hide();
				$('#'+address_type+'_address_1_field').show();
				$('#'+address_type+'_address_2_field').show();				
			} else {
				$('#'+address_type+'_street_name_field').show();
				$('#'+address_type+'_house_number_field').show();
				$('#'+address_type+'_house_number_suffix_field').show();
				$('#'+address_type+'_address_1_field').hide();
				$('#'+address_type+'_address_2_field').hide();				
			}
		}
	}

	localize_address_fields('billing');
	localize_address_fields('shipping');

	$( '#billing_country, #shipping_country' ).change(function() {
		id = $(this).attr('id');
		address_type = 	id.replace('_country','');
		localize_address_fields(address_type);
	});

});