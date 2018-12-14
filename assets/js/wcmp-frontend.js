jQuery(function($) {
    window.myparcelbe_is_using_split_address_fields = wcmp_display_settings.isUsingSplitAddressFields;

    // The timeout is necessary, otherwise the order summary is going to flash
    setTimeout(function() {
        $(':input.country_to_state').change();
    }, 100);

    var MyParcelBE_Frontend = {
        checkout_updating: false,
        force_update:      false,

        selected_shipping_method: false,
        updated_shipping_method:  false,
        selected_country:         false,
        updated_country:          false,

        shipping_methods:              JSON.parse(wcmp_delivery_options.shipping_methods),
        always_display:                wcmp_delivery_options.always_display,

        init: function() {
            MyParcelBE_Frontend.selected_country = MyParcelBE_Frontend.get_shipping_country();

            $('#shipping_country, #billing_country').change(function() {
                MyParcelBE_Frontend.updated_country = MyParcel_FrontendBE.get_shipping_country();
            });

            // hide checkout options for non parcel shipments
            $(document).on('updated_checkout', function() {
                MyParcelBE_Frontend.checkout_updating = false; //done updating

                if (!MyParcel_FrontendBE.check_country()) return;

                if (MyParcelBE_Frontend.always_display) {
                    MyParcelBE_Frontend.force_update = true;
                    MyParcelBE.showAllDeliveryOptions();
                } else if (MyParcelBE_Frontend.shipping_methods.length > 0) {
                    var shipping_method = MyParcelBE_Frontend.get_shipping_method();

                    // no shipping method selected, hide by default
                    if (typeof shipping_method === 'undefined') {
                        MyParcelBE_Frontend.hide_delivery_options();
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
                        var shipping_class = $('#myparcelbe_highest_shipping_class').val();
                        // add class refinement if we have a shipping class
                        if (shipping_class) {
                            shipping_method_class = shipping_method + ':' + shipping_class;
                        }
                    }

                    if (shipping_class && $.inArray(shipping_method_class, MyParcelBE_Frontend.shipping_methods) > -1) {
                        MyParcelBE_Frontend.updated_shipping_method = shipping_method_class;
                        MyParcelBE.showAllDeliveryOptions();
                        MyParcelBE_Frontend.myparcelbe_selected_shipping_method = shipping_method_class;
                    } else if ($.inArray(shipping_method, MyParcelBE_Frontend.shipping_methods) > -1) {
                        // fallback to bare method if selected in settings
                        MyParcelBE_Frontend.myparcelbe_updated_shipping_method = shipping_method;
                        MyParcelBE.showAllDeliveryOptions();
                        MyParcelBE_Frontend.myparcelbe_selected_shipping_method = shipping_method;
                    } else {
                        var shipping_method_now = typeof shipping_method_class !== 'undefined' ? shipping_method_class : shipping_method;
                        MyParcelBE_Frontend.myparcelbe_updated_shipping_method = shipping_method_now;
                        MyParcelBE_Frontend.myparcelbe_selected_shipping_method = shipping_method_now;
                    }
                } else {

                    // not sure if we should already hide by default?
                    MyParcelBE_Frontend.hide_delivery_options();
                }
            });
            // any delivery option selected/changed - update checkout for fees
            $('#mypabe-chosen-delivery-options').on('change', 'input', function() {
                MyParcelBE_Frontend.checkout_updating = true;
                // disable signature & recipient only when switching to pickup location
                var mypabe_postnl_data = JSON.parse($('#mypabe-chosen-delivery-options #mypabe-input').val());
                if (typeof mypabe_postnl_data.location !== 'undefined') {
                    $('#mypabe-signature, #mypabe-recipient-only').prop("checked", false);
                }
                $('body').trigger('update_checkout');
            });
        },

        check_country: function() {
            if (MyParcelBE_Frontend.updated_country !== false
                && MyParcelBE_Frontend.updated_country !== MyParcelBE_Frontend.selected_country
                && $.isEmptyObject(MyParcelBE.data) === false
            ) {
                MyParcelBE.callDeliveryOptions();
                MyParcelBE.showAllDeliveryOptions();
                MyParcelBE_Frontend.selected_country = MyParcelBE_Frontend.updated_country;
            }

            if (MyParcelBE_Frontend.selected_country !== 'BE') {

                MyParcelBE_Frontend.hide_delivery_options();
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
            MyParcelBE.hideAllDeliveryOptions();
            if (MyParcelBE_Frontend.is_updated()) {
                jQuery('body').trigger('update_checkout');
            }
        },

        is_updated: function() {
            if (MyParcelBE_Frontend.updated_shipping_method !== MyParcelBE_Frontend.selected_shipping_method
                || MyParcelBE_Frontend.updated_country !== MyParcelBE_Frontend.selected_country
                || MyParcelBE_Frontend.force_update === true
            ) {
                MyParcelBE_Frontend.force_update = false; // only force once
                return true;
            }
            return false;
        }
    };

    MyParcelBE_Frontend.init();
});
