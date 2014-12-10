// Most functionality copied from woocommerce/assets/js/checkout.js
// Thanks Mike!

jQuery(document).ready(function($) {
	
	// Hide custom NL fields by default when country not NL
	var billing_country = $('#billing_country').val();
	var shipping_country = $('#shipping_country').val();
	if (billing_country != 'NL') {
		$('#billing_street_name_field').hide();
		$('#billing_house_number_field').hide();
		$('#billing_house_number_suffix_field').hide();
	}	
	if (shipping_country != 'NL') {
		$('#shipping_street_name_field').hide();
		$('#shipping_house_number_field').hide();
		$('#shipping_house_number_suffix_field').hide();
	}
	

	/* Localisation */
	var locale_json = woocommerce_params.locale.replace(/&quot;/g, '"');
	var locale = $.parseJSON( locale_json );
	var required = ' <abbr class="required" title="' + woocommerce_params.i18n_required_text + '">*</abbr>';

	$('body')

	// Handle locale
	.bind('country_to_state_changing', function( event, country, wrapper ){
		var thisform = wrapper;

		var $postcodefield = thisform.find('#billing_postcode_field, #shipping_postcode_field');
		var $cityfield     = thisform.find('#billing_city_field, #shipping_city_field');
		var $statefield    = thisform.find('#billing_state_field, #shipping_state_field');
		var $emailfield    = thisform.find('#billing_email_field');
		var $phonefield    = thisform.find('#billing_phone_field');
		var $address1field = thisform.find('#billing_address_1_field, #shipping_address_1_field');
		var $address2field = thisform.find('#billing_address_2_field, #shipping_address_2_field');
		var $streetfield   = thisform.find('#billing_street_name_field, #shipping_street_name_field');
		var $numberfield   = thisform.find('#billing_house_number_field, #shipping_house_number_field');
		var $suffixfield   = thisform.find('#billing_house_number_suffix_field, #shipping_house_number_suffix_field');

		if (country == 'NL') {
			//show custom NL fields
			$streetfield.show();
			$numberfield.show();
			$suffixfield.show();
			//$emailfield.add( $phonefield ).removeClass('form-row-first form-row-last').addClass('form-row-wide');
			
			// Hide regular address classes
			$address1field.find('label abbr').remove();
			$address1field.hide();
			$address2field.hide();

			// Place postcode & city on one line
			$postcodefield.removeClass('form-row-wide').addClass('form-row-first');
			$cityfield.removeClass('form-row-wide').addClass('form-row-last');

			// Mark required fields
			if ($streetfield.find('label abbr').size()==0) $streetfield.find('label').append( required );
			if ($numberfield.find('label abbr').size()==0) $numberfield.find('label').append( required );
			
			// Add validation required classes
			$streetfield.addClass('validate-required');
			$numberfield.addClass('validate-required');
		} else {
			// Hide custom NL fields
			$streetfield.hide();
			$numberfield.hide();
			$suffixfield.hide();

			// Unmark required fields
			$streetfield.find('label abbr').remove();
			$numberfield.find('label abbr').remove();

			// Remove validation required classes
			$streetfield.removeClass('validate-required');
			$numberfield.removeClass('validate-required');
		}
		
		if ( typeof locale[country] != 'undefined' ) {
			var thislocale = locale[country];
		} else {
			var thislocale = locale['default'];
		}

		// Handle locale fields
		var locale_fields = {
			'address_1'	: 	'#billing_address_1_field, #shipping_address_1_field',
			'address_2'	: 	'#billing_address_2_field, #shipping_address_2_field',
		};

		$.each( locale_fields, function( key, value ) {

			var field = thisform.find( value );

			if ( thislocale[key] ) {

				if ( thislocale[key]['label'] ) {
					field.find('label').html( thislocale[key]['label'] );
				}

				if ( thislocale[key]['placeholder'] ) {
					field.find('input').attr( 'placeholder', thislocale[key]['placeholder'] );
				}

				field.find('label abbr').remove();

				if ( typeof thislocale[key]['required'] == 'undefined' && locale['default'][key]['required'] == true ) {
					field.find('label').append( required );
				} else if ( thislocale[key]['required'] == true ) {
					field.find('label').append( required );
				}

				if ( key !== 'state' ) {
					if ( thislocale[key]['hidden'] == true ) {
						field.hide().find('input').val('');
					} else {
						field.show();
					}
				}

			} else if ( locale['default'][key] ) {
				if ( locale['default'][key]['required'] == true ) {
					if (field.find('label abbr').size()==0) field.find('label').append( required );
				}
				if ( key !== 'state' && (typeof locale['default'][key]['hidden'] == 'undefined' || locale['default'][key]['hidden'] == false) ) {
					field.show();
				}
			}

		});


	
	})

	// Init trigger
	.bind('init_checkout', function() {
		$('#billing_country, #shipping_country, .country_to_state').change();
	});

	// Update on page load
	if ( woocommerce_params.is_checkout == 1 ) {
		$('body').trigger('init_checkout');
	}

});