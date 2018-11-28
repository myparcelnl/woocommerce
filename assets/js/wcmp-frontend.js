jQuery(function($) {
    window.myparcel_checkout_updating = false;
    window.myparcel_force_update = false;

    window.myparcel_selected_shipping_method = '';
    window.myparcel_updated_shipping_method = '';
    window.myparcel_selected_country = '';
    window.myparcel_updated_country = '';

    window.myparcel_is_using_split_address_fields = wcmp_display_settings.isUsingSplitAddressFields;
    window.myparcel_shipping_methods = JSON.parse(wcmp_delivery_options.shipping_methods);
    window.myparcel_always_display = wcmp_delivery_options.always_display;

    // hide checkout options for non parcel shipments
    $(document).on('updated_checkout', function() {
        window.myparcel_checkout_updating = false; //done updating

        if (window.myparcel_always_display) {
            window.myparcel_force_update = true;
            show_myparcel_delivery_options();
        } else if (window.myparcel_shipping_methods.length > 0) {
            var shipping_method;
            window.myparcel_selected_country = window.myparcel_updated_country;

            // check if shipping is user choice or fixed
            if ($('#order_review .shipping_method').length > 1) {
                shipping_method = $('#order_review .shipping_method:checked').val();
            } else {
                shipping_method = $('#order_review .shipping_method').val();
            }

            if (typeof shipping_method === 'undefined') {
                // no shipping method selected, hide by default
                hide_myparcel_delivery_options();
                return;
            }

            if (shipping_method.indexOf('table_rate:') !== -1 || shipping_method.indexOf('betrs_shipping:') !== -1) {
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
                var shipping_class = $('#myparcel_highest_shipping_class').val();
                // add class refinement if we have a shipping class
                if (shipping_class) {
                    shipping_method_class = shipping_method + ':' + shipping_class;
                }
            }

            if (shipping_class && $.inArray(shipping_method_class, window.window.myparcel_shipping_methods) > -1) {
                window.myparcel_updated_shipping_method = shipping_method_class;
                show_myparcel_delivery_options();
                myparcel_selected_shipping_method = shipping_method_class;
            } else if ($.inArray(shipping_method, window.myparcel_shipping_methods) > -1) {
                // fallback to bare method if selected in settings
                myparcel_updated_shipping_method = shipping_method;
                show_myparcel_delivery_options();
                myparcel_selected_shipping_method = shipping_method;
            } else {
                shipping_method_now = typeof shipping_method_class !== 'undefined' ? shipping_method_class : shipping_method;
                myparcel_updated_shipping_method = shipping_method_now;
                hide_myparcel_delivery_options();
                myparcel_selected_shipping_method = shipping_method_now;
            }
        } else {
            // not sure if we should already hide by default?
            hide_myparcel_delivery_options();
        }
    });

    // any delivery option selected/changed - update checkout for fees
    $('#mypa-chosen-delivery-options').on('change', 'input', function() {
        window.myparcel_checkout_updating = true;
        // disable signature & recipient only when switching to pickup location
        mypa_postnl_data = JSON.parse($('#mypa-chosen-delivery-options #mypa-input').val());
        if (typeof mypa_postnl_data.location !== 'undefined') {
            $('#mypa-signature, #mypa-recipient-only').prop("checked", false);
        }
        $('body').trigger('update_checkout');
    });

    function check_country() {
        window.myparcel_updated_country = get_shipping_country();

        if (window.myparcel_updated_country !== 'NL' && window.myparcel_updated_country !== 'BE') {
            hide_myparcel_delivery_options();
        } else if (window.myparcel_updated_country !== window.myparcel_selected_country && $.isEmptyObject(MyParcel.data) === false) {
            MyParcel.callDeliveryOptions();
        }
    }

    function get_shipping_country() {
        if ($('#ship-to-different-address-checkbox').is(':checked')) {
            country = $('#shipping_country').val();
        } else {
            country = $('#billing_country').val();
        }

        return country;
    }

    function hide_myparcel_delivery_options() {
        MyParcel.hideAllDeliveryOptions();
        // clear delivery options
        if (is_updated()) {
            jQuery('body').trigger('update_checkout');
        }
    }

    function show_myparcel_delivery_options() {
        // show only if NL or BE
        check_country();

        if (is_updated()) {
            MyParcel.showAllDeliveryOptions();
        }
    }

    // prevents infinite updated_checkout - update_checkout loop
    function is_updated() {
        if (window.myparcel_updated_shipping_method !== window.myparcel_selected_shipping_method
            || window.myparcel_updated_country !== window.myparcel_selected_country
            || window.myparcel_force_update === true
        ) {
            window.myparcel_force_update = false; // only force once
            return true;
        }

        return false;
    }
});
