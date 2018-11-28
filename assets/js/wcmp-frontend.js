jQuery(function($) {
    var MyParcel_Frontend = {
        checkout_updating: false,
        force_update:      false,

        selected_shipping_method: false,
        updated_shipping_method:  false,
        selected_country:         false,
        updated_country:          false,

        is_using_split_address_fields: wcmp_display_settings.isUsingSplitAddressFields,
        shipping_methods:              JSON.parse(wcmp_delivery_options.shipping_methods),
        always_display:                wcmp_delivery_options.always_display,

        init: function() {
            MyParcel_Frontend.selected_country = MyParcel_Frontend.get_shipping_country();

            $('#shipping_country, #billing_country').change(function() {
                MyParcel_Frontend.updated_country = MyParcel_Frontend.get_shipping_country();
            });

            // hide checkout options for non parcel shipments
            $(document).on('updated_checkout', function() {
                MyParcel_Frontend.checkout_updating = false; //done updating

                if (!MyParcel_Frontend.check_country()) return;

                if (MyParcel_Frontend.always_display) {
                    MyParcel_Frontend.force_update = true;
                    MyParcel_Frontend.show_delivery_options();
                } else if (MyParcel_Frontend.shipping_methods.length > 0) {
                    var shipping_method = MyParcel_Frontend.get_shipping_method();

                    // no shipping method selected, hide by default
                    if (typeof shipping_method === 'undefined') {
                        MyParcel_Frontend.hide_delivery_options();
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

                    if (shipping_class && $.inArray(shipping_method_class, MyParcel_Frontend.shipping_methods) > -1) {
                        MyParcel_Frontend.updated_shipping_method = shipping_method_class;
                        MyParcel_Frontend.show_delivery_options();
                        MyParcel_Frontend.myparcel_selected_shipping_method = shipping_method_class;
                    } else if ($.inArray(shipping_method, MyParcel_Frontend.shipping_methods) > -1) {
                        // fallback to bare method if selected in settings
                        MyParcel_Frontend.myparcel_updated_shipping_method = shipping_method;
                        MyParcel_Frontend.show_delivery_options();
                        MyParcel_Frontend.myparcel_selected_shipping_method = shipping_method;
                    } else {
                        var shipping_method_now = typeof shipping_method_class !== 'undefined' ? shipping_method_class : shipping_method;
                        MyParcel_Frontend.myparcel_updated_shipping_method = shipping_method_now;
                        MyParcel_Frontend.hide_delivery_options();
                        MyParcel_Frontend.myparcel_selected_shipping_method = shipping_method_now;
                    }
                } else {

                    // not sure if we should already hide by default?
                    MyParcel_Frontend.hide_delivery_options();
                }
            });
            // any delivery option selected/changed - update checkout for fees
            $('#mypa-chosen-delivery-options').on('change', 'input', function() {
                MyParcel_Frontend.checkout_updating = true;
                // disable signature & recipient only when switching to pickup location
                var mypa_postnl_data = JSON.parse($('#mypa-chosen-delivery-options #mypa-input').val());
                if (typeof mypa_postnl_data.location !== 'undefined') {
                    $('#mypa-signature, #mypa-recipient-only').prop("checked", false);
                }
                $('body').trigger('update_checkout');
            });
        },

        check_country: function() {
            if (MyParcel_Frontend.updated_country !== false
                && MyParcel_Frontend.updated_country !== MyParcel_Frontend.selected_country
                && $.isEmptyObject(MyParcel.data) === false
            ) {
                MyParcel.callDeliveryOptions();
                MyParcel_Frontend.show_delivery_options();
                MyParcel_Frontend.selected_country = MyParcel_Frontend.updated_country;
            }

            if (MyParcel_Frontend.selected_country !== 'NL' && MyParcel_Frontend.selected_country !== 'BE') {

                MyParcel_Frontend.hide_delivery_options();
                return false;
            }

            return true;
        },

        get_shipping_method: function() {
            var shipping_method;
            // check if shipping is user choice or fixed
            if ($('#order_review .shipping_method').length > 1) {
                shipping_method = $('#order_review .shipping_method:checked').val();
            } else {
                shipping_method = $('#order_review .shipping_method').val();
            }
            return shipping_method;
        },

        get_shipping_country: function() {
            var country;
            if ($('#ship-to-different-address-checkbox').is(':checked')) {
                country = $('#shipping_country').val();
            } else {
                country = $('#billing_country').val();
            }
            return country;
        },

        hide_delivery_options: function() {
            MyParcel.hideAllDeliveryOptions();
            if (MyParcel_Frontend.is_updated()) {
                jQuery('body').trigger('update_checkout');
            }
        },

        show_delivery_options: function() {
            if (MyParcel_Frontend.is_updated()) {
                MyParcel.showAllDeliveryOptions();
            }
        },

        is_updated: function() {
            if (MyParcel_Frontend.updated_shipping_method !== MyParcel_Frontend.selected_shipping_method
                || MyParcel_Frontend.updated_country !== MyParcel_Frontend.selected_country
                || MyParcel_Frontend.force_update === true
            ) {
                MyParcel_Frontend.force_update = false; // only force once
                return true;
            }
            return false;
        }
    };

    MyParcel_Frontend.init();
});
