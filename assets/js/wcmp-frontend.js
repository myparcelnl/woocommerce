jQuery(function($) {
    window.postnl_is_using_split_address_fields = wcmp_display_settings.isUsingSplitAddressFields;

    // The timeout is necessary, otherwise the order summary is going to flash
    setTimeout(function() {
        $(':input.country_to_state').change();
    }, 100);

    var PostNL_Frontend = {
        checkout_updating: false,
        force_update:      false,

        selected_shipping_method: false,
        updated_shipping_method:  false,
        selected_country:         false,
        updated_country:          false,

        shipping_methods:              JSON.parse(wcmp_delivery_options.shipping_methods),
        always_display:                wcmp_delivery_options.always_display,

        init: function() {
            PostNL_Frontend.selected_country = PostNL_Frontend.get_shipping_country();

            $('#shipping_country, #billing_country').change(function() {
                PostNL_Frontend.updated_country = PostNL_Frontend.get_shipping_country();
            });

            // hide checkout options for non parcel shipments
            $(document).on('updated_checkout', function() {
                PostNL_Frontend.checkout_updating = false; //done updating

                if (!PostNL_Frontend.check_country()) return;

                if (PostNL_Frontend.always_display) {
                    PostNL_Frontend.force_update = true;
                    PostNL.showAllDeliveryOptions();
                } else if (PostNL_Frontend.shipping_methods.length > 0) {
                    var shipping_method = PostNL_Frontend.get_shipping_method();

                    // no shipping method selected, hide by default
                    if (typeof shipping_method === 'undefined') {
                        PostNL_Frontend.hide_delivery_options();
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
                        var shipping_class = $('#postnl_highest_shipping_class').val();
                        // add class refinement if we have a shipping class
                        if (shipping_class) {
                            shipping_method_class = shipping_method + ':' + shipping_class;
                        }
                    }

                    if (shipping_class && $.inArray(shipping_method_class, PostNL_Frontend.shipping_methods) > -1) {
                        PostNL_Frontend.updated_shipping_method = shipping_method_class;
                        PostNL.showAllDeliveryOptions();
                        PostNL_Frontend.postnl_selected_shipping_method = shipping_method_class;
                    } else if ($.inArray(shipping_method, PostNL_Frontend.shipping_methods) > -1) {
                        // fallback to bare method if selected in settings
                        PostNL_Frontend.postnl_updated_shipping_method = shipping_method;
                        PostNL.showAllDeliveryOptions();
                        PostNL_Frontend.postnl_selected_shipping_method = shipping_method;
                    } else {
                        var shipping_method_now = typeof shipping_method_class !== 'undefined' ? shipping_method_class : shipping_method;
                        PostNL_Frontend.postnl_updated_shipping_method = shipping_method_now;
                        PostNL.hideAllDeliveryOptions();
                        PostNL_Frontend.postnl_selected_shipping_method = shipping_method_now;
                    }
                } else {

                    // not sure if we should already hide by default?
                    PostNL_Frontend.hide_delivery_options();
                }
            });
            // any delivery option selected/changed - update checkout for fees
            $('#post-chosen-delivery-options').on('change', 'input', function() {
                PostNL_Frontend.checkout_updating = true;
                // disable signature & recipient only when switching to pickup location
                var post_postnl_data = JSON.parse($('#post-chosen-delivery-options #post-input').val());
                if (typeof post_postnl_data.location !== 'undefined') {
                    $('#post-signature, #post-recipient-only').prop("checked", false);
                }
                $('body').trigger('update_checkout');
            });
        },

        check_country: function() {
            if (PostNL_Frontend.updated_country !== false
                && PostNL_Frontend.updated_country !== PostNL_Frontend.selected_country
                && $.isEmptyObject(PostNL.data) === false
            ) {
                PostNL.callDeliveryOptions();
                PostNL.showAllDeliveryOptions();
                PostNL_Frontend.selected_country = PostNL_Frontend.updated_country;
            }

            if (PostNL_Frontend.selected_country !== 'NL' && PostNL_Frontend.selected_country !== 'BE') {

                PostNL_Frontend.hide_delivery_options();
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
            PostNL.hideAllDeliveryOptions();
            if (PostNL_Frontend.is_updated()) {
                jQuery('body').trigger('update_checkout');
            }
        },

        is_updated: function() {
            if (PostNL_Frontend.updated_country !== PostNL_Frontend.selected_country || PostNL_Frontend.force_update === true) {
                PostNL_Frontend.force_update = false; // only force once
                return true;
            }
            return false;
        }
    };

    PostNL_Frontend.init();
});
