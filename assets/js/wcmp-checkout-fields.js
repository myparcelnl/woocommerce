// Loaded in checkout when using split address fields
jQuery(function($) {
    // trigger hiding of address line 1 and 2 if necessary
    // The timeout is necessary, otherwise the order summary is going to flash
    setTimeout(function() {
        $(':input.country_to_state').change();
    }, 100);

    // Auto fill helper for layout with separate number field
    // Split street to 3 fields on autofill
    $('#billing_street_name, #shipping_street_name').on('load, animationend', function() {
        setAddress(this);
    });

    function setAddress(el) {
        type = $(el).attr('id').search('billing') ? 'shipping' : 'billing';

        $('#' + type + '_address_1').val(el.value); // Fill in the hidden address line 1 field in case a theme forces it to be required
        address = el.value.split(
            /(.*?)\s?(\d{1,4})[/\s\-]{0,2}([a-zA-Z]{1}\d{1,3}|-\d{1,4}|\d{2}\w{1,2}|[a-zA-Z]{1}[a-zA-Z\s]{0,3})?$/g
        )
        .filter(function(value) {
            return value !== ''
        }); // filter out empty values

        $('#' + type + '_street_name').val(address[0]);
        $('#' + type + '_house_number').val(address[1]);
        $('#' + type + '_house_number_suffix').val(address[2]);

        // Update delivery options after filling if myparcelbe.js is loaded and initialized
        if (typeof MyParcelBE != 'undefined' && MyParcel.data.length === 0) {
            MyParcel.callDeliveryOptions();
        }
    }
});