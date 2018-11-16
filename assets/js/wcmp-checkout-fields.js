// Loaded in checkout when using split address fields
jQuery(function($) {
    // trigger hiding of address line 1 and 2 if necessary
    $(':input.country_to_state').change();

    // Auto fill helper for layout with separate number field
    // Split street to 3 fields on autofill
    jQuery('#billing_street_name, #shipping_street_name').on('animationend', function() {
        type = jQuery(this).attr('id').search('billing') ? 'shipping' : 'billing';
        jQuery('#' + type + '_address_1').val(this.value); // Fill in the hidden address line 1 field in case a theme forces it to be required
        address = this.value.split(
            /(.*?)\s?(\d{1,4})[/\s\-]{0,2}([a-zA-Z]{1}\d{1,3}|-\d{1,4}|\d{2}\w{1,2}|[a-zA-Z]{1}[a-zA-Z\s]{0,3})?$/g
        )
            .filter(function(value) {
                return value !== ''
            }); // filter out empty values

        jQuery('#' + type + '_street_name').val(address[0]);
        jQuery('#' + type + '_house_number').val(address[1]);
        jQuery('#' + type + '_house_number_suffix').val(address[2]);
    });
});
