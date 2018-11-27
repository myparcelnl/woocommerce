MyParcel = {
    /*
     * Init
     *
     * Initialize the MyParcel checkout.
     *
     */
    data:            {},
    currentLocation: {},

    DELIVERY_MORNING:        'morning',
    DELIVERY_NORMAL:         'standard',
    DELIVERY_EVENING:        'avond',
    DELIVERY_SIGNATURE:      0,
    DELIVERY_ONLY_RECIPIENT: 0,

    SPLIT_STREET_REGEX: /(.*?)\s?(\d{1,4})[/\s\-]{0,2}([a-zA-Z]{1}\d{1,3}|-\d{1,4}|\d{2}\w{1,2}|[a-zA-Z]{1}[a-zA-Z\s]{0,3})?$/g,

    init: function() {

        this.data = JSON.parse(wcmp_config);
        isMobile = jQuery(window).width() < 980;

        /* Titles of the options*/
        if (MyParcel.data.config.deliveryTitle) {
            jQuery('#mypa-delivery-title').html(MyParcel.data.config.deliveryTitle);
        }
        if (MyParcel.data.config.headerDeliveryOptions) {
            jQuery('#mypa-delivery-options-title').html(MyParcel.data.config.headerDeliveryOptions);
            jQuery('#header-delivery-options-title').show();
        }
        if (MyParcel.data.config.onlyRecipientTitle) {
            jQuery('#mypa-only-recipient-title').html(MyParcel.data.config.onlyRecipientTitle);
        }
        if (MyParcel.data.config.signatureTitle) {
            jQuery('#mypa-signature-title').html(MyParcel.data.config.signatureTitle);
        }
        if (MyParcel.data.config.pickupTitle) {
            jQuery('#mypa-pickup-title').html(MyParcel.data.config.pickupTitle);
        }

        /* Prices */
        var prices = {
            'morning':        this.data.config.priceMorningDelivery,
            'evening':        this.data.config.priceEveningDelivery,
            'normal':         this.data.config.priceNormalDelivery,
            'signature':      this.data.config.priceSignature,
            'only-recipient': this.data.config.priceOnlyRecipient,
            'pickup':         this.data.config.pricePickup
        };

        MyParcel.showPrices(prices);
        MyParcel.callDeliveryOptions();

        /* Engage defaults */
        MyParcel.hideDelivery();
        jQuery('#method-myparcel-normal').click();

        MyParcel.bind();
    },

    showPrices: function(prices) {
        jQuery.each(prices, function(selectName, price) {
            jQuery('#mypa-' + selectName + '-delivery, #mypa-' + selectName + '-price').html(MyParcel.getPriceHtml(price));
        });
    },

    getPriceHtml: function(priceOfDeliveryOption) {
        var price;

        if (!priceOfDeliveryOption) {
            price = "";
        }

        if (parseFloat(priceOfDeliveryOption) >= 0) {
            price = '+ &euro; ' + Number(priceOfDeliveryOption).toFixed(2).replace(".", ",");
        }

        if (priceOfDeliveryOption && isNaN(parseFloat(priceOfDeliveryOption))) {
            price = priceOfDeliveryOption;
        }

        return price;
    },

    setCurrentDeliveryOptions: function() {
        if (typeof MyParcel.storeDeliveryOptions === 'undefined') {
            console.error('setCurrentDeliveryOptions() MyParcel.storeDeliveryOptions === undefined');
            return;
        }

        var selectedDate = jQuery('#mypa-select-date').val();
        var selectDateKey = MyParcel.storeDeliveryOptions.data.delivery[selectedDate]['time'];

        MyParcel.hideMorningDelivery();
        MyParcel.hideEveningDelivery();

        jQuery.each(selectDateKey, function(key, value) {
            if (value['price_comment'] == 'morning' && MyParcel.data.config.allowMorningDelivery) {
                var morningTitle = MyParcel.data.config.deliveryMorningTitle;
                MyParcel.getDeliveryTime(morningTitle, 'morning', value['start'], value['end']);
                MyParcel.showMorningDelivery();
            }

            if (value['price_comment'] == 'standard') {
                var standardTitle = MyParcel.data.config.deliveryStandardTitle;
                if (MyParcel.data.address.cc === 'BE') {
                    standardTitle = MyParcel.data.config.BEdeliveryStandardTitle;
                }
                MyParcel.getDeliveryTime(standardTitle, 'standard', value['start'], value['end']);
            }
            if (value['price_comment'] == 'avond' && MyParcel.data.config.allowEveningDelivery) {
                var eveningTitle = MyParcel.data.config.deliveryEveningTitle;
                MyParcel.getDeliveryTime(eveningTitle, 'evening', value['start'], value['end']);
                MyParcel.showEveningDelivery();
            }
        });
    },

    getDeliveryTime: function(configDeliveryTitle, deliveryMoment, startTime, endTime) {
        startTime = startTime.replace(/(.*)\D\d+/, '$1');
        endTime = endTime.replace(/(.*)\D\d+/, '$1');

        jQuery('#mypa-' + deliveryMoment + '-title').html(configDeliveryTitle);

        if (!configDeliveryTitle) {
            jQuery('#mypa-' + deliveryMoment + '-title').html(startTime + ' - ' + endTime);
        }

    },

    setCurrentLocation: function() {
        var locationId = jQuery('#mypa-pickup-location').val();
        this.currentLocation = this.getPickupByLocationId(MyParcel.storeDeliveryOptions.data.pickup, locationId);
    },

    /*
     * Bind
     *
     * Bind actions to selectors.
     *
     */
    bind: function() {
        jQuery('#mypa-submit').on('click', function(e) {
            e.preventDefault();
            MyParcel.exportDeliveryOptionToWebshop();
        });

        /* show default delivery options and hide PostNL options */
        jQuery('#mypa-select-delivery').on('click', function() {
            MyParcel.setCurrentDeliveryOptions();
            MyParcel.showDelivery();
            MyParcel.showShippingAddress();
            MyParcel.hidePickUpLocations();
        });

        /* hide default delivery options and show PostNL options */
        jQuery('#mypa-pickup-delivery').on('click', function() {
            MyParcel.hideDelivery();
            MyParcel.hideShippingAddress();
            MyParcel.showPickUpLocations();
        });

        jQuery('#method-myparcel-delivery-morning, #method-myparcel-delivery-evening').on('click', function() {
            MyParcel.defaultCheckCheckbox('mypa-only-recipient');
        });

        /* Mobile specific triggers */
        if (isMobile) {
            jQuery('#mypa-show-location-details').on('click', function() {
                MyParcel.setCurrentLocation();
                MyParcel.showLocationDetails();
                MyParcel.hideDelivery();
            });
        }

        /* Desktop specific triggers */
        else {
            jQuery('#mypa-show-location-details').on('click', function() {
                MyParcel.setCurrentLocation();
                MyParcel.showLocationDetails();
            });
        }

        jQuery('#mypa-location-details').on('click', function() {
            MyParcel.hideLocationDetails();
        });

        jQuery('#method-myparcel-normal').on('click', function() {
            MyParcel.defaultCheckCheckbox('method-myparcel-normal');
        });

        // jQuery('#mypa-pickup-express').hide();  /* todo: move */

        jQuery('#mypa-pickup-delivery, #mypa-pickup-location').on('change', function(e) {
            MyParcel.setCurrentLocation();
            MyParcel.toggleDeliveryOptions();
            MyParcel.mapExternalWebshopTriggers();
        });

        jQuery('#mypa-select-date').on('change', function(e) {
            MyParcel.setCurrentDeliveryOptions();
            MyParcel.mapExternalWebshopTriggers();
        });

        /* External webshop triggers */
        jQuery('#mypa-load input, #mypa-load select').on('input', function() {
            MyParcel.mapExternalWebshopTriggers()
        });

        fields = window.myparcel_is_using_split_address_fields
            ? '#billing_house_number, #shipping_house_number'
            : '#billing_address_1, #shipping_address_1';

        jQuery('#billing_postcode, #shipping_postcode, ' + fields).on('input', function() {
            MyParcel.callDeliveryOptions();
        });
    },

    mapExternalWebshopTriggers: function() {
        MyParcel.DELIVERY_SIGNATURE = 0;
        MyParcel.DELIVERY_ONLY_RECIPIENT = 0;
        MyParcel.removeStyleFromPrice();

        /**
         * Morning delivery
         *
         */
        if (jQuery('#mypa-pickup-delivery').prop('checked') === false && jQuery('#method-myparcel-delivery-morning').prop('checked')) {
            jQuery('#s_method_myparcel_morning').click();
            MyParcel.DELIVERY_ONLY_RECIPIENT = 1;
            MyParcel.addStyleToPrice('#mypa-morning-delivery, #mypa-only-recipient-price');

            /**
             * Signature
             */
            if (jQuery('#mypa-signature-selector').prop('checked')) {
                jQuery('#s_method_myparcel_morning_signature').click();
                MyParcel.DELIVERY_SIGNATURE = 1;
                MyParcel.addStyleToPrice('#mypa-signature-price');
            }

            MyParcel.addDeliveryToExternalInput(MyParcel.DELIVERY_MORNING);
            return;
        }

        /**
         * Normal delivery
         *
         */
        if (jQuery('#mypa-pickup-delivery').prop('checked') === false && jQuery('#method-myparcel-normal').prop('checked')) {
            MyParcel.addStyleToPrice('#mypa-normal-delivery');

            /**
             * Signature and only recipient
             */
            if (jQuery('#mypa-signature-selector').prop('checked') && jQuery('#mypa-only-recipient-selector').prop('checked')) {
                jQuery('#s_method_myparcel_delivery_signature_and_only_recipient_fee').click();
                MyParcel.DELIVERY_SIGNATURE = 1;
                MyParcel.DELIVERY_ONLY_RECIPIENT = 1;
                MyParcel.addStyleToPrice('#mypa-signature-price, #mypa-only-recipient-price');
            } else

            /**
             * Signature
             */
            if (jQuery('#mypa-signature-selector').prop('checked')) {
                jQuery('#s_method_myparcel_delivery_signature').click();
                MyParcel.DELIVERY_SIGNATURE = 1;
                MyParcel.addStyleToPrice('#mypa-signature-price');

            } else

            /**
             * Only recipient
             */
            if (jQuery('#mypa-only-recipient-selector').prop('checked')) {
                jQuery('#s_method_myparcel_delivery_only_recipient').click();
                MyParcel.DELIVERY_ONLY_RECIPIENT = 1;
                MyParcel.addStyleToPrice('#mypa-only-recipient-price');

            } else {
                jQuery('#s_method_myparcel_flatrate, #s_method_myparcel_tablerate').click();
            }

            MyParcel.addDeliveryToExternalInput(MyParcel.DELIVERY_NORMAL);
            return;
        }

        /**
         * Evening delivery
         *
         */
        if (jQuery('#mypa-pickup-delivery').prop('checked') === false && jQuery('#method-myparcel-delivery-evening').prop('checked')) {
            jQuery('#s_method_myparcel_evening').click();
            MyParcel.DELIVERY_ONLY_RECIPIENT = 1;
            MyParcel.addStyleToPrice('#mypa-evening-delivery, #mypa-only-recipient-price');

            /**
             * Signature
             */
            if (jQuery('#mypa-signature-selector').prop('checked')) {
                jQuery('#s_method_myparcel_evening_signature').click();
                MyParcel.DELIVERY_SIGNATURE = 1;
                MyParcel.addStyleToPrice('#mypa-signature-price');
            }

            MyParcel.addDeliveryToExternalInput(MyParcel.DELIVERY_EVENING);
            return;
        }

        /**
         * Pickup
         *
         */
        if (jQuery('#mypa-pickup-delivery').prop('checked') || jQuery('#mypa-pickup-selector').prop('checked')) {
            /**
             * Early morning pickup
             */
            if (jQuery('#mypa-pickup-express-selector').prop('checked')) {
                jQuery('#s_method_myparcel_pickup_express').click();
                MyParcel.addPickupToExternalInput('retailexpress');
                MyParcel.addStyleToPrice('#mypa-pickup-express-price');
                return;
            } else {
                MyParcel.addStyleToPrice('#mypa-pickup-price');
            }

            jQuery('#s_method_myparcel_pickup').click();
            MyParcel.addPickupToExternalInput('retail');
        }
    },

    addPickupToExternalInput: function(selectedPriceComment) {
        var locationId = jQuery('#mypa-pickup-location').val();
        var currentLocation = MyParcel.getPickupByLocationId(MyParcel.storeDeliveryOptions.data.pickup, locationId);

        var result = jQuery.extend({}, currentLocation);

        /* If retail; convert retailexpress to retail */
        if (selectedPriceComment === "retail") {
            result.price_comment = "retail";
        }
        jQuery('body').trigger('update_checkout');
        jQuery('#mypa-input').val(JSON.stringify(result));
    },

    addDeliveryToExternalInput: function(deliveryMomentOfDay) {

        var deliveryDateId = jQuery('#mypa-select-date').val();

        var currentDeliveryData = MyParcel.triggerDefaultOptionDelivery(deliveryDateId, deliveryMomentOfDay);

        if (currentDeliveryData !== null) {
            currentDeliveryData.signature = MyParcel.DELIVERY_SIGNATURE;
            currentDeliveryData.only_recipient = MyParcel.DELIVERY_ONLY_RECIPIENT;
            jQuery('#mypa-input').val(JSON.stringify(currentDeliveryData));
        }
        jQuery('body').trigger('update_checkout');
    },

    addStyleToPrice: function(chosenDelivery) {
        jQuery(chosenDelivery).addClass('mypa-bold-price');
    },

    removeStyleFromPrice: function() {
        jQuery('.mypa-delivery-option-table').find("span").removeClass('mypa-bold-price');
    },

    triggerDefaultOptionDelivery: function(deliveryDateId, deliveryMomentOfDay) {
        var dateArray = MyParcel.data.deliveryOptions.data.delivery[deliveryDateId];
        var currentDeliveryData = null;

        jQuery.each(dateArray['time'], function(key, value) {
            if (value.price_comment === deliveryMomentOfDay) {
                currentDeliveryData = jQuery.extend({}, dateArray);
                currentDeliveryData['time'] = [value];
            }
        });

        if (currentDeliveryData === null) {
            jQuery('#mypa-only-recipient-selector').prop('disabled', false).prop('checked', false);
            jQuery('#method-myparcel-normal').prop('checked', true);
            MyParcel.mapExternalWebshopTriggers();
        }

        return currentDeliveryData;
    },

    /*
     * defaultCheckCheckbox
     *
     * Check the additional options that are required for certain delivery options
     *
     */
    defaultCheckCheckbox: function(selectedOption) {
        if (selectedOption === 'mypa-only-recipient') {
            jQuery('#mypa-only-recipient-selector').prop('checked', true).prop({disabled: true});
            jQuery('#mypa-only-recipient-price').html('Inclusief');
        } else {
            jQuery('#mypa-only-recipient-selector').prop('checked', false).removeAttr("disabled");
            jQuery('#mypa-only-recipient-price').html(MyParcel.getPriceHtml(this.data.config.priceOnlyRecipient));
        }
    },

    /*
     * toggleDeliveryOptions
     *
     * Shows and hides the display options that are valid for the recipient only and signature required pre-selectors
     *
     */
    toggleDeliveryOptions: function() {
        var isPickup = jQuery('#mypa-pickup-delivery').is(':checked');
        jQuery('#mypa-pickup-selector').prop('checked', true);

        if (isPickup && this.currentLocation.price_comment === "retailexpress" && this.data.config.allowPickupExpress) {
            jQuery('#mypa-pickup-express-price').html(MyParcel.getPriceHtml(this.data.config.pricePickupExpress));
            jQuery('#mypa-pickup-express').show();
        } else {
            jQuery('#mypa-pickup-express-selector').attr("checked", false);
            jQuery('#mypa-pickup-express').hide();
        }
    },

    /*
     * exportDeliverOptionToWebshop
     *
     * Exports the selected delivery option to the webshop.
     *
     */
    exportDeliveryOptionToWebshop: function() {
        var deliveryOption = "";
        var selected = jQuery("#mypa-delivery-option-form").find("input[type='radio']:checked");
        if (selected.length > 0) {
            deliveryOption = selected.val();
        }
    },

    /*
     * hideMessage
     *
     * Hides pop-up message.
     *
     */
    hideMessage: function() {
        jQuery('.mypa-message-model').hide();
        jQuery('#mypa-delivery-option-form').show();
    },

    /*
     * hideMessage
     *
     * Hides pop-up message.
     *
     */
    showMessage: function(message) {
        jQuery('.mypa-message-model').show();
        jQuery('#mypa-message').html(message).show();
        jQuery('#mypa-delivery-option-form').hide();

    },

    /*
     * hideDelivery
     *
     * Hides interface part for delivery.
     *
     */
    hideDelivery: function() {
        jQuery('#mypa-delivery-date-text,.mypa-extra-delivery-options').hide();
        jQuery('#mypa-select-date').parent().parent().hide();
        jQuery('#mypa-delivery').parent().parent().hide();
        MyParcel.hideSignature();
        MyParcel.hideOnlyRecipient();
        MyParcel.hideMorningDelivery();
        MyParcel.hideEveningDelivery();
    },

    /*
     * showDelivery
     *
     * Shows interface part for delivery.
     *
     */
    showDelivery: function() {

        jQuery('#mypa-delivery').parent().parent().show();

        if (MyParcel.data.address.cc === "NL") {
            jQuery('#mypa-delivery-selectors-' + this.data.address.cc.toLowerCase()).show();
            jQuery('.mypa-extra-delivery-options').show();

            if (this.data.config.deliverydaysWindow >= 2) {
                jQuery('#mypa-delivery-date-select').show();
            }

            MyParcel.hideSignature();
            if (this.data.config.allowSignature) {
                MyParcel.showSignature();
            }

            MyParcel.hideOnlyRecipient();
            if (this.data.config.allowOnlyRecipient) {
                MyParcel.showOnlyRecipient();
            }
        }

        if (MyParcel.data.address.cc === 'BE') {
            jQuery('#mypa-delivery-title').html(MyParcel.data.config.BEdeliveryTitle);
            jQuery('#mypa-delivery-date-text').hide();
        }
    },

    hideAllDeliveryOptions: function() {
        jQuery('#mypa-load').hide();
    },

    showAllDeliveryOptions: function() {
        jQuery('#mypa-load').show();
    },

    /*
     * showSpinner
     *
     * Shows the MyParcel spinner.
     *
     */
    showSpinner: function() {
        jQuery('#mypa-delivery-option-form').hide();
        jQuery('.mypa-message-model').hide();
        jQuery('#mypa-spinner-model').show();
    },

    /*
     * hideSpinner
     *
     * Hides the MyParcel spinner.
     *
     */
    hideSpinner: function() {
        jQuery('#mypa-spinner-model').hide();
    },

    showMorningDelivery: function() {
        jQuery('#method-myparcel-delivery-morning-div').show();
    },

    hideMorningDelivery: function() {
        jQuery('#method-myparcel-delivery-morning-div').hide();
    },

    showEveningDelivery: function() {
        jQuery('#method-myparcel-delivery-evening-div').show();
    },

    hideEveningDelivery: function() {
        jQuery('#method-myparcel-delivery-evening-div').hide();
    },

    showSignature: function() {
        jQuery('.mypa-extra-delivery-option-signature, #mypa-signature-price').show();
    },

    hideSignature: function() {
        jQuery('.mypa-extra-delivery-option-signature, #mypa-signature-price').hide();
    },

    showOnlyRecipient: function() {
        jQuery('#mypa-only-recipient, #mypa-only-recipient-price').parent().show();
    },

    hideOnlyRecipient: function() {
        jQuery('#mypa-only-recipient, #mypa-only-recipient-price').parent().hide();
    },

    /*
     * dateToString
     *
     * Convert api date string format to human readable string format
     *
     */
    dateToString: function(apiDate) {
        var deliveryDate = apiDate;
        var dateArr = deliveryDate.split('-');
        var dateObj = new Date(dateArr[0], dateArr[1] - 1, dateArr[2]);
        var day = ("0" + (dateObj.getDate())).slice(-2);
        var month = ("0" + (dateObj.getMonth() + 1)).slice(-2);

        return this.data.txtWeekDays[dateObj.getDay()] + " " + day + "-" + month + "-" + dateObj.getFullYear();
    },

    /*
     * showDeliveryDates
     *
     * Show possible delivery dates.
     *
     */
    showDeliveryDates: function() {
        var html = "";
        var deliveryWindow = parseInt(MyParcel.data.config.deliverydaysWindow);

        jQuery.each(MyParcel.data.deliveryOptions.data.delivery, function(key, value) {
            html += '<option value="' + key + '">' + MyParcel.dateToString(value.date) + ' </option>\n';
        });

        /* Hide the day selector when the value of the deliverydaysWindow is 0*/
        if (deliveryWindow === 0) {
            jQuery('#mypa-delivery-date-select').hide();
        }

        /* When deliverydaysWindow is 1, hide the day selector and show a div to show the date */
        if (deliveryWindow === 1) {
            jQuery('#mypa-select-date').hide();
            jQuery('#mypa-delivery-date-text').show();
        }

        /* When deliverydaysWindow > 1, show the day selector */
        if (deliveryWindow > 1) {
            jQuery('#mypa-select-date').show();
        }

        jQuery('#mypa-select-date, #mypa-date').html(html);
    },

    hideDeliveryDates: function() {
        jQuery('#mypa-delivery-date-text').parent().hide();
    },

    /*
     * clearPickupLocations
     *
     * Clear pickup locations and show a non-value option.
     *
     */
    clearPickUpLocations: function() {
        var html = '<option value="">---</option>';
        jQuery('#mypa-pickup-location').html(html);
    },

    /*
     * hidePickupLocations
     *
     * Hide the pickup location option.
     *
     */
    hidePickUpLocations: function() {
        if (!MyParcel.data.config.allowPickupPoints) {
            jQuery('#mypa-pickup-location-selector').hide();
        }

        jQuery('#mypa-pickup-options, #mypa-pickup, #mypa-pickup-express').hide();
    },

    /*
     * showPickupLocations
     *
     * Shows possible pickup locations, from closest to further.
     *
     */
    showPickUpLocations: function() {
        if (false === MyParcel.data.config.allowPickupPoints) {
            return;
        }

        var html = "";
        jQuery.each(MyParcel.data.deliveryOptions.data.pickup, function(key, value) {
            var distance = parseFloat(Math.round(value.distance) / 1000).toFixed(1);
            html += '<option value="' + value.location_code + '">' + value.location + ', ' + value.street + ' ' + value.number + ", " + value.city + " (" + distance + " km) </option>\n";
        });
        jQuery('#mypa-pickup-location').html(html).prop("checked", true);
        jQuery('#mypa-pickup-location-selector, #mypa-pickup-options, #mypa-pickup').show();
    },

    /*
     * hideLocationDetails
     *
     * Hide the detailed information pop-up for selected location.
     *
     */
    hideLocationDetails: function() {
        jQuery('#mypa-delivery-option-form').show();
        jQuery('#mypa-location-details').hide();
    },

    /*
     * showLocationDetails
     *
     * Shows the detailed information pop-up for the selected pick-up location.
     */
    showLocationDetails: function() {
        var html = "";
        var locationId = jQuery('#mypa-pickup-location').val();

        var currentLocation = MyParcel.getPickupByLocationId(MyParcel.storeDeliveryOptions.data.pickup, locationId);
        var startTime = currentLocation.start_time;

        /* Strip seconds if present */
        if (startTime.length > 5) {
            startTime = startTime.slice(0, -3);
        }

        html += '<svg class="svg-inline--fa mypa-fa-times fa-w-12" aria-hidden="true" data-prefix="fas" data-icon="times" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" data-fa-i2svg=""><path fill="currentColor" d="M323.1 441l53.9-53.9c9.4-9.4 9.4-24.5 0-33.9L279.8 256l97.2-97.2c9.4-9.4 9.4-24.5 0-33.9L323.1 71c-9.4-9.4-24.5-9.4-33.9 0L192 168.2 94.8 71c-9.4-9.4-24.5-9.4-33.9 0L7 124.9c-9.4 9.4-9.4 24.5 0 33.9l97.2 97.2L7 353.2c-9.4 9.4-9.4 24.5 0 33.9L60.9 441c9.4 9.4 24.5 9.4 33.9 0l97.2-97.2 97.2 97.2c9.3 9.3 24.5 9.3 33.9 0z"></path></svg>'
        html += '<span class="mypa-pickup-location-details-location"><h3>' + currentLocation.location + '</h3></span>';
        html += '<svg class="mypa-postnl-logo"version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 100 99" enable-background="new 0 0 100 99" xml:space="preserve"><image id="postnl-logo" width="100" height="99" xlink:href="data:image/png;base64,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" /></svg>';
        html += '<span class="mypa-pickup-location-details-street">' + currentLocation.street + '&nbsp;' + this.currentLocation.number + '</span>';
        html += '<span class="mypa-pickup-location-details-city">' + currentLocation.postal_code + '&nbsp;' + currentLocation.city + '</span>';

        if (currentLocation.phone_number) {
            html += '<span class="mypa-pickup-location-details-phone">' + currentLocation.phone_number + '</span>';
        }
        html += '<span class="mypa-pickup-location-details-time">Ophalen vanaf:&nbsp;' + startTime + '</span>';
        html += '<h3>Openingstijden</h3>';

        jQuery.each(
            currentLocation.opening_hours, function(weekday, value) {
                html += '<span class="mypa-pickup-location-details-day">' + MyParcel.data.translateENtoNL[weekday] + "</span>";

                if (value[0] === undefined) {
                    html += '<span class="mypa-time">Gesloten</span>';
                }

                jQuery.each(value, function(key2, times) {
                    html += '<span class="mypa-time">' + times + "</span>";
                });
                html += "<br>";
            });
        jQuery('#mypa-delivery-option-form').hide();
        jQuery('#mypa-location-details').html(html).css('display', 'inline-block');
    },

    /*
     * hideShippingAddress
     *
     * Hide the "ship to different address" section.
     *
     */
    hideShippingAddress: function() {
        jQuery('.woocommerce-shipping-fields').hide();
    },

    /*
     * showShippingAddress
     *
     * Show the "ship to different address" section.
     *
     */
    showShippingAddress: function() {
        jQuery('.woocommerce-shipping-fields').show();
    },

    /*
     * getPickupByLocationId
     *
     * Find the location by id and return the object.
     *
     */
    getPickupByLocationId: function(obj, locationId) {
        var object;

        jQuery.each(obj, function(key, info) {
            if (info.location_code === locationId) {
                object = info;
                return false;
            }
        });

        return object;
    },

    /*
     * retryPostalCodeHouseNumber
     *
     * After detecting an unrecognised postal code / house number combination the user can try again.
     * This function copies the newly entered data back into the webshop forms.
     *
     */
    retryPostalCodeHouseNumber: function() {
        var retryPostalCode = jQuery('#mypa-error-postcode').val();
        var retryNumber = jQuery('#mypa-error-number').val();

        jQuery('#billing_postcode').val(retryPostalCode);
        jQuery('#billing_house_number').val(retryNumber);

        MyParcel.callDeliveryOptions();
        jQuery('#mypa-select-delivery').click();
    },

    /*
     * showFallBackDelivery
     *
     * If the API call fails and we have no data about delivery or pick up options
     * show the customer an "As soon as possible" option.
     */
    showFallBackDelivery: function() {
        MyParcel.hideSpinner();
        MyParcel.hideDelivery();
        jQuery('#mypa-select-date, #method-myparcel-normal-div, .mypa-is-pickup-element').hide();
        jQuery('#mypa-select-delivery-title').html('Zo snel mogelijk bezorgen');
    },

    /*
     * showRetry
     *
     * If a customer enters an unrecognised postal code & house number combination show a
     * pop-up so they can try again.
     */
    showRetry: function() {
        MyParcel.showMessage(
            '<h3>Huisnummer/postcode combinatie onbekend</h3>' +
            '<div class="mypa-full-width mypa-error">' +
            '<label for="mypa-error-postcode">Postcode</label>' +
            '<input type="text" name="mypa-error-postcode" id="mypa-error-postcode" value="' + MyParcel.data.address.postalCode + '">' +
            '</div><div class="mypa-full-width mypa-error">' +
            '<label for="mypa-error-number">Huisnummer</label>' +
            '<input type="text" name="mypa-error-number" id="mypa-error-number" value="' + MyParcel.data.address.number + '">' +
            '<br><div id="mypa-error-try-again" class="button btn">Opnieuw</div>' +
            '</div>'
        );

        /* remove trigger that closes message */
        jQuery('#mypa-message').off('click');

        /* bind trigger to new button */
        jQuery('#mypa-error-try-again').on('click', function() {
            MyParcel.retryPostalCodeHouseNumber();
        });
    },

    setAddressFromInputFields: function() {
        addressType = jQuery('#ship-to-different-address-checkbox').prop('checked') ? 'shipping' : 'billing';
        address = MyParcel.getAddressInputValues(addressType);

        if (!MyParcel.getAddressInputValues('billing').postalCode) {
            return;
        }

        this.data.address.street = address.streetName;
        this.data.address.number = address.houseNumber;
        this.data.address.numberSuffix = address.houseNumberSuffix;
        this.data.address.cc = address.country;
        this.data.address.postalCode = address.postalCode;
        this.data.address.city = address.city;
    },

    getAddressInputValues: function(type) {
        streetParts = {};
        input = {
            'fullStreet': jQuery('#' + type + '_address_1').val(),
            'postalCode': jQuery('#' + type + '_postcode').val(),
            'city':       jQuery('#' + type + '_city').val(),
            'country':    jQuery('#' + type + '_country').val(),
        };

        if (window.myparcel_is_using_split_address_fields) {
            input.streetName = jQuery('#' + type + '_street_name').val();
            input.houseNumber = jQuery('#' + type + '_house_number').val();
            input.houseNumberSuffix = jQuery('#' + type + '_house_number_suffix').val();
        } else {
            streetParts = MyParcel.splitFullStreetFromInput(input.fullStreet);
            input.streetName = streetParts.streetName;
            input.houseNumber = streetParts.houseNumber;
            input.houseNumberSuffix = streetParts.houseNumberSuffix;
        }

        return input;
    },

    /*
     * splitFullStreetFromInput
     *
     * Split full street into parts and returning empty array if there's no street entered
     */
    splitFullStreetFromInput: function(fullStreet) {
        result = {
            streetName:        '',
            houseNumber:       '',
            houseNumberSuffix: '',
        };

        if (fullStreet.length) {
            streetParts = new RegExp(MyParcel.SPLIT_STREET_REGEX).exec(fullStreet);
            result.streetName = streetParts[1];
            result.houseNumber = streetParts[2];
            result.houseNumberSuffix = streetParts[3];
        }

        return result;
    },

    /*
     * callDeliveryOptions
     *
     * Calls the MyParcel API to retrieve the pickup and delivery options for given house number and
     * Postal Code.
     *
     */
    callDeliveryOptions: function() {
        MyParcel.showSpinner();
        MyParcel.clearPickUpLocations();
        MyParcel.hideDelivery();
        MyParcel.setAddressFromInputFields();

        // Hide PostNL field if there is no address entered
        if (this.data.address.postalCode == '' || this.data.address.number == '') {
            MyParcel.hideSpinner();
            MyParcel.showMessage(
                '<h3>Adresgegevens zijn niet ingevuld</h3>'
            );
            return;
        }

        /* Check if the deliverydaysWindow == 0 and hide the select input*/
        this.deliveryDaysWindow = this.data.config.deliverydaysWindow;

        if (this.deliveryDaysWindow === '0') {
            this.deliveryDaysWindow = 1;
        }

        /* Make the api request */
        jQuery.get(this.data.config.apiBaseUrl + "delivery_options",
            {
                cc:                  this.data.address.cc,
                postal_code:         this.data.address.postalCode.trim(),
                number:              this.data.address.number.trim(),
                city:                this.data.address.city,
                carrier:             this.data.config.carrier,
                dropoff_days:        this.data.config.dropOffDays,
                monday_delivery:     this.data.config.allowMondayDelivery,
                deliverydays_window: this.deliveryDaysWindow,
                cutoff_time:         this.data.config.cutoffTime,
                dropoff_delay:       this.data.config.dropoffDelay
            })
        .done(function(response) {
            MyParcel.data.deliveryOptions = response;
            if (response.errors) {
                jQuery.each(response.errors, function(key, value) {
                    /* Postal code & house number combination not found or not recognised. */
                    if (value.code == '3212' || value.code == '3505') {
                        MyParcel.showRetry();
                    }

                    /* Any other error */
                    else {
                        MyParcel.showFallBackDelivery();
                    }
                });
            }

            /* No errors */
            else {
                MyParcel.hideMessage();
                MyParcel.showPickUpLocations();
                MyParcel.showDeliveryDates();

                if (MyParcel.data.deliveryOptions.data.delivery.length <= 0) {
                    MyParcel.hideDeliveryDates();
                }
                MyParcel.storeDeliveryOptions = response;
            }
            MyParcel.hideSpinner();
        })
        .fail(function() {
            MyParcel.showFallBackDelivery();
        })
        .always(function() {
            jQuery('#mypa-select-delivery').click();
        });
    }
};
// timeout because postcode api might take too long to respond
setTimeout(function() {
    MyParcel.init();
}, 3000);

