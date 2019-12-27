MyParcel = {
    /*
     * Init
     *
     * Initialize the MyParcel checkout.
     *
     */
    data:            {},
    currentLocation: {},

    DELIVERY_NORMAL:         'standard',
    DELIVERY_SATURDAY:       'saturday',
    DELIVERY_PICKUP:         'retail',
    DELIVERY_SIGNATURE:      0,

    SPLIT_STREET_REGEX: /(.*?)\s?(\d{1,4})[/\s\-]{0,2}([a-zA-Z]{1}\d{1,3}|-\d{1,4}|\d{2}\w{1,2}|[a-zA-Z]{1}[a-zA-Z\s]{0,3})?$/g,

    init: function() {
        this.data = JSON.parse(wcmp_config);
        isMobile = jQuery(window).width() < 980;

        /* Titles of the options*/
        if (MyParcel.data.config.deliveryTitle) {
            jQuery('#mypabe-delivery-title').html(MyParcel.data.config.deliveryTitle);
        }
        if (MyParcel.data.config.headerDeliveryOptions) {
            jQuery('#mypabe-delivery-options-title').html(MyParcel.data.config.headerDeliveryOptions);
            jQuery('#header-delivery-options-title').show();
        }
        if (MyParcel.data.config.signatureTitle) {
            jQuery('#mypabe-signature-title').html(MyParcel.data.config.signatureTitle);
        }
        if (MyParcel.data.config.pickupTitle) {
            jQuery('#mypabe-pickup-title').html(MyParcel.data.config.pickupTitle);
            jQuery('.mypabe-pickup-delivery-title').html(MyParcel.data.textToTranslate.pickUpFrom +': 15:00');
        }

        /* Prices */
        var prices = {
            'normal':         this.data.config.priceNormalDelivery,
            'signature':      this.data.config.priceSignature,
            'pickup':         this.data.config.pricePickup,
            'saturday':       this.data.config.priceSaturdayDelivery
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
            jQuery('#mypabe-' + selectName + '-delivery, #mypabe-' + selectName + '-price').html(MyParcel.getPriceHtml(price));
        });
    },

    getPriceHtml: function(priceOfDeliveryOption = '') {
        var price;

        if (!priceOfDeliveryOption) {
            price = "";
        }

        if (parseFloat(priceOfDeliveryOption) >= 0) {
            price = '+ &euro; ' + Number(priceOfDeliveryOption).toFixed(2).replace(".", ",");
        }

        if (parseFloat(priceOfDeliveryOption) < 0) {
            price = "<p class='colorGreen'>"+'- &euro; ' + Number(priceOfDeliveryOption).toFixed(2).replace(/-|\./g,function(match) {return (match==".")?",":""})+"</p>";
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

        var selectedDate = jQuery('#mypabe-select-date').val();
        var selectDateKey = MyParcel.storeDeliveryOptions.data.delivery[selectedDate]['time'];

        jQuery.each(selectDateKey, function(key, value) {

            if (value['price_comment'] == 'standard') {
                var standardTitle = MyParcel.data.config.deliveryStandardTitle;
                MyParcel.getDeliveryTime(standardTitle, 'standard', value['start'], value['end']);
            }
        });
    },

    getDeliveryTime: function(configDeliveryTitle, deliveryMoment, startTime, endTime) {
        startTime = startTime.replace(/(.*)\D\d+/, '$1');
        endTime = endTime.replace(/(.*)\D\d+/, '$1');

        jQuery('#mypabe-' + deliveryMoment + '-title').html(configDeliveryTitle);

        if (!configDeliveryTitle) {
            jQuery('#mypabe-' + deliveryMoment + '-title').html(startTime + ' - ' + endTime);
        }
    },

    setCurrentLocation: function() {
        var locationId = jQuery('#mypabe-pickup-location').val();
        this.currentLocation = this.getPickupByLocationId(MyParcel.storeDeliveryOptions.data.pickup, locationId);
    },

    /*
     * Bind
     *
     * Bind actions to selectors.
     *
     */
    bind: function() {
        jQuery('#mypabe-submit').on('click', function(e) {
            e.preventDefault();
            MyParcel.exportDeliveryOptionToWebshop();
        });

        /* show default delivery options and hide postnl options */
        jQuery('#mypabe-select-delivery').on('click', function() {
            MyParcel.setCurrentDeliveryOptions();
            MyParcel.showDelivery();
            MyParcel.showShippingAddress();
            MyParcel.hidePickUpLocations();
        });

        /* hide default delivery options and show postnl options */
        jQuery('#mypabe-pickup-delivery').on('click', function() {
            MyParcel.hideDelivery();
            MyParcel.hideShippingAddress();
            MyParcel.showPickUpLocations();
        });

        /* Mobile specific triggers */
        if (isMobile) {
            jQuery('#mypabe-show-location-details').on('click', function() {
                MyParcel.setCurrentLocation();
                MyParcel.showLocationDetails();
                MyParcel.hideDelivery();
            });
        }

        /* Desktop specific triggers */
        else {
            jQuery('#mypabe-show-location-details').on('click', function() {
                MyParcel.setCurrentLocation();
                MyParcel.showLocationDetails();
            });
        }

        jQuery('#mypabe-location-details').on('click', function() {
            MyParcel.hideLocationDetails();
        });

        jQuery('#mypabe-pickup-delivery, #mypabe-pickup-location').on('change', function(e) {
            MyParcel.setCurrentLocation();
            MyParcel.toggleDeliveryOptions();
            MyParcel.mapExternalWebshopTriggers();
        });

        jQuery('#mypabe-select-date').on('change', function(e) {
            MyParcel.setCurrentDeliveryOptions();
            MyParcel.mapExternalWebshopTriggers();
        });

        /* External webshop triggers */
        jQuery('#mypabe-load input, #mypabe-load select').on('input', function() {
            MyParcel.mapExternalWebshopTriggers()
        });

        fields = window.myparcel_is_using_split_address_fields
            ? '#billing_house_number, #shipping_house_number'
            : '#billing_address_1, #shipping_address_1';

        jQuery('#billing_country, #shipping_country, #billing_postcode, #shipping_postcode, #billing_city, #shipping_city, ' + fields).on('change', function() {
            MyParcel.callDeliveryOptions();
        });
    },

    mapExternalWebshopTriggers: function() {
        MyParcel.DELIVERY_SIGNATURE = 0;
        MyParcel.removeStyleFromPrice();

        /**
         * Normal delivery
         *
         */
        if (jQuery('#mypabe-pickup-delivery').prop('checked') === false && jQuery('#method-myparcel-normal').prop('checked')) {
            MyParcel.addStyleToPrice('#mypabe-normal-delivery');

            /**
             * Signature
             */
            if (jQuery('#mypabe-signature-selector').prop('checked')) {
                MyParcel.DELIVERY_SIGNATURE = 1;
                MyParcel.addStyleToPrice('#mypabe-signature-price');

            }

            MyParcel.addDeliveryToExternalInput(MyParcel.DELIVERY_NORMAL);
            return;
        }

        /**
         * Pickup
         *
         */
        if (jQuery('#mypabe-pickup-delivery').prop('checked') || jQuery('#mypabe-pickup-selector').prop('checked')) {
            MyParcel.addStyleToPrice('#mypabe-pickup-price');

            jQuery('#s_method_myparcel_pickup').click();
            MyParcel.addPickupToExternalInput(MyParcel.DELIVERY_PICKUP);
        }
    },

    addPickupToExternalInput: function(selectedPriceComment) {
        var locationId = jQuery('#mypabe-pickup-location').val();
        var currentLocation = MyParcel.getPickupByLocationId(MyParcel.storeDeliveryOptions.data.pickup, locationId);

        var result = jQuery.extend({}, currentLocation);

        /* If pickup; convert pickup express to pickup */
        if (selectedPriceComment === MyParcel.DELIVERY_PICKUP) {
            result.price_comment = MyParcel.DELIVERY_PICKUP;
        }

        jQuery('body').trigger('update_checkout');
        jQuery('#mypabe-input').val(JSON.stringify(result));
    },

    addDeliveryToExternalInput: function(deliveryMomentOfDay) {
        var deliveryDateId = jQuery('#mypabe-select-date').val();
        var currentDeliveryData = MyParcel.triggerDefaultOptionDelivery(deliveryDateId, deliveryMomentOfDay);

        if (currentDeliveryData !== null) {
            currentDeliveryData.signature = MyParcel.DELIVERY_SIGNATURE;
            jQuery('#mypabe-input').val(JSON.stringify(currentDeliveryData));
        }
        jQuery('body').trigger('update_checkout');
    },

    addStyleToPrice: function(chosenDelivery) {
        jQuery(chosenDelivery).addClass('mypabe-bold-price');
    },

    removeStyleFromPrice: function() {
        jQuery('.mypabe-delivery-option-table').find("span").removeClass('mypabe-bold-price');
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
            jQuery('#method-myparcel-normal').prop('checked', true);
            MyParcel.mapExternalWebshopTriggers();
        }

        return currentDeliveryData;
    },

    /*
     * toggleDeliveryOptions
     *
     * Shows and hides the display options that are valid for the recipient only and signature required pre-selectors
     *
     */
    toggleDeliveryOptions: function() {
        var isPickup = jQuery('#mypabe-pickup-delivery').is(':checked');
        jQuery('#mypabe-pickup-selector').prop('checked', true);

    },

    /*
     * exportDeliverOptionToWebshop
     *
     * Exports the selected delivery option to the webshop.
     *
     */
    exportDeliveryOptionToWebshop: function() {
        var deliveryOption = "";
        var selected = jQuery("#mypabe-delivery-option-form").find("input[type='radio']:checked");
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
        jQuery('.mypabe-message-model').hide();
        jQuery('#mypabe-delivery-option-form').show();
    },

    /*
     * hideMessage
     *
     * Hides pop-up message.
     *
     */
    showMessage: function(message) {
        jQuery('.mypabe-message-model').show();
        jQuery('#mypabe-message').html(message).show();
        jQuery('#mypabe-delivery-option-form').hide();

    },

    /*
     * hideDelivery
     *
     * Hides interface part for delivery.
     *
     */
    hideDelivery: function() {
        jQuery('#mypabe-delivery-date-text,.mypabe-extra-delivery-options').hide();
        jQuery('#mypabe-select-date').parent().parent().hide();
        jQuery('#mypabe-delivery').parent().parent().hide();
        MyParcel.hideSignature();
    },

    /*
     * showDelivery
     *
     * Shows interface part for delivery.
     *
     */
    showDelivery: function() {

        jQuery('#mypabe-delivery').parent().parent().show();

        if (MyParcel.data.address.cc === "BE") {
            jQuery('#mypabe-delivery-selectors-' + this.data.address.cc.toLowerCase()).show();
            jQuery('.mypabe-extra-delivery-options').show();


            if (this.data.config.deliverydaysWindow >= 2) {
                jQuery('#mypabe-delivery-date-select').show();
            }

            MyParcel.hideSignature();
            if (this.data.config.allowSignature) {
                MyParcel.showSignature();
            }
        }
    },

    hideAllDeliveryOptions: function() {
        jQuery('#mypabe-load').hide();
    },

    showAllDeliveryOptions: function() {
        jQuery('#mypabe-load').show();
    },

    /*
     * showSpinner
     *
     * Shows the MyParcel spinner.
     *
     */
    showSpinner: function() {
        jQuery('#mypabe-delivery-option-form').hide();
        jQuery('.mypabe-message-model').hide();
        jQuery('#mypabe-spinner-model').show();
    },

    /*
     * hideSpinner
     *
     * Hides the MyParcel spinner.
     *
     */
    hideSpinner: function() {
        jQuery('#mypabe-spinner-model').hide();
    },

    showSignature: function() {
        jQuery('.mypabe-extra-delivery-option-signature, #mypabe-signature-price').show();
    },

    hideSignature: function() {
        jQuery('.mypabe-extra-delivery-option-signature, #mypabe-signature-price').hide();
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
            jQuery('#mypabe-delivery-date-select').hide();
        }

        /* When deliverydaysWindow is 1, hide the day selector and show a div to show the date */
        if (deliveryWindow === 1) {
            jQuery('#mypabe-select-date').hide();
            jQuery('#mypabe-delivery-date-text').show();
        }

        /* When deliverydaysWindow > 1, show the day selector */
        if (deliveryWindow > 1) {
            jQuery('#mypabe-select-date').show();
        }

        jQuery('#mypabe-select-date, #mypabe-date').html(html);
    },

    hideDeliveryDates: function() {
        jQuery('#mypabe-delivery-date-text').parent().hide();
    },

    /*
     * clearPickupLocations
     *
     * Clear pickup locations and show a non-value option.
     *
     */
    clearPickUpLocations: function() {
        var html = '<option value="">---</option>';
        jQuery('#mypabe-pickup-location').html(html);
    },

    /*
     * hidePickupLocations
     *
     * Hide the pickup location option.
     *
     */
    hidePickUpLocations: function() {
        if (!MyParcel.data.config.allowPickupPoints) {
            jQuery('#mypabe-pickup-location-selector').hide();
        }

        jQuery('#mypabe-pickup-options, #mypabe-pickup, #mypabe-pickup-express').hide();
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
        jQuery('#mypabe-pickup-location').html(html).prop("checked", true);
        jQuery('#mypabe-pickup-location-selector, #mypabe-pickup-options, #mypabe-pickup').show();
    },

    /*
     * hideLocationDetails
     *
     * Hide the detailed information pop-up for selected location.
     *
     */
    hideLocationDetails: function() {
        jQuery('#mypabe-delivery-option-form').show();
        jQuery('#mypabe-location-details').hide();
    },

    /*
     * showLocationDetails
     *
     * Shows the detailed information pop-up for the selected pick-up location.
     */
    showLocationDetails: function() {
        var html = "";
        var locationId = jQuery('#mypabe-pickup-location').val();

        var currentLocation = MyParcel.getPickupByLocationId(MyParcel.storeDeliveryOptions.data.pickup, locationId);
        var startTime = currentLocation.start_time;

        /* Strip seconds if present */
        if (startTime.length > 5) {
            startTime = startTime.slice(0, -3);
        }

        html += '<svg class="svg-inline--fa mypabe-fa-times fa-w-12" aria-hidden="true" data-prefix="fas" data-icon="times" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" data-fa-i2svg=""><path fill="currentColor" d="M323.1 441l53.9-53.9c9.4-9.4 9.4-24.5 0-33.9L279.8 256l97.2-97.2c9.4-9.4 9.4-24.5 0-33.9L323.1 71c-9.4-9.4-24.5-9.4-33.9 0L192 168.2 94.8 71c-9.4-9.4-24.5-9.4-33.9 0L7 124.9c-9.4 9.4-9.4 24.5 0 33.9l97.2 97.2L7 353.2c-9.4 9.4-9.4 24.5 0 33.9L60.9 441c9.4 9.4 24.5 9.4 33.9 0l97.2-97.2 97.2 97.2c9.3 9.3 24.5 9.3 33.9 0z"></path></svg>'
        html += '<span class="mypabe-pickup-location-details-location"><h3>' + currentLocation.location + '</h3></span>';
        html += '<svg class="mypabe-postnl-logo" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 99" enable-background="new 0 0 100 99" xml:space="preserve"><image id="postnl-logo" width="100" height="99" href="http://localhost/woocommerce/wp-content/plugins/woocommerce-belgie/assets/img/wcmp-postnl-logo.png"></image></svg>';
        html += '<span class="mypabe-pickup-location-details-street">' + currentLocation.street + '&nbsp;' + this.currentLocation.number + '</span>';
        html += '<span class="mypabe-pickup-location-details-city">' + currentLocation.postal_code + '&nbsp;' + currentLocation.city + '</span>';

        if (currentLocation.phone_number) {
            html += '<span class="mypabe-pickup-location-details-phone">' + currentLocation.phone_number + '</span>';
        }
        html += '<span class="mypabe-pickup-location-details-time">' + MyParcel.data.textToTranslate.pickUpFrom + ':&nbsp;' + startTime + '</span>';
        html += '<h3>' + this.data.textToTranslate.openingHours + '</h3>';

        jQuery.each(
            currentLocation.opening_hours, function(weekday, value) {
                html += '<span class="mypabe-pickup-location-details-day">' + MyParcel.data.textToTranslate[weekday] + "</span>";

                if (value[0] === undefined) {
                    html += '<span class="mypabe-time">' + MyParcel.data.textToTranslate.closed +'</span>';
                }

                jQuery.each(value, function(key2, times) {
                    html += '<span class="mypabe-time">' + times + "</span>";
                });
                html += "<br>";
            });
        jQuery('#mypabe-delivery-option-form').hide();
        jQuery('#mypabe-location-details').html(html).css('display', 'inline-block');
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
        var retryPostalCode = jQuery('#mypabe-error-postcode').val();
        var retryCity = jQuery('#mypabe-error-city').val();

        jQuery('#billing_city').val(retryCity);

        jQuery('#billing_postcode').val(retryPostalCode);

        MyParcel.callDeliveryOptions();
        jQuery('#mypabe-select-delivery').click();
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
        jQuery('#mypabe-select-date, #method-myparcel-normal-div, .mypabe-is-pickup-element').hide();
        jQuery('#mypabe-select-delivery-title').html('Zo snel mogelijk bezorgen');
    },

    /*
     * showRetry
     *
     * If a customer enters an unrecognised postal code & house number combination show a
     * pop-up so they can try again.
     */
    showRetry: function() {
        MyParcel.showMessage(
            '<h3>' + MyParcel.data.textToTranslate.wrongHouseNumberCity+ '</h3>' +
            '<div class="mypabe-full-width mypabe-error">' +
            '<label for="mypabe-error-postcode">' + MyParcel.data.textToTranslate.postcode + '</label>' +
            '<input type="text" name="mypabe-error-postcode" id="mypabe-error-postcode" value="' + MyParcel.data.address.postalCode + '">' +
            '</div><div class="mypabe-full-width mypabe-error">' +
            '<label for="mypabe-error-city">' + MyParcel.data.textToTranslate.city + '</label>' +
            '<input type="text" name="mypabe-error-city" id="mypabe-error-city" value="' + MyParcel.data.address.city + '">' +
            '<br><div id="mypabe-error-try-again" class="button btn">' + MyParcel.data.textToTranslate.retry + '</div>' +
            '</div>'
        );

        /* remove trigger that closes message */
        jQuery('#mypabe-message').off('click');

        /* bind trigger to new button */
        jQuery('#mypabe-error-try-again').on('click', function() {
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

        if (this.data.address.cc !== "BE"){
            MyParcel.hideDelivery();
        }
    },

    getAddressInputValues: function(type) {
        streetParts = {};
        input = {
            'addressLine1': jQuery('#' + type + '_address_1').val(),
            'addressLine2': jQuery('#' + type + '_address_2').val(),
            'postalCode': jQuery('#' + type + '_postcode').val(),
            'city':       jQuery('#' + type + '_city').val(),
            'country':    jQuery('#' + type + '_country').val(),
        };

        if (window.myparcel_is_using_split_address_fields) {
            input.streetName = jQuery('#' + type + '_street_name').val();
            input.houseNumber = jQuery('#' + type + '_house_number').val();
            input.houseNumberSuffix = jQuery('#' + type + '_house_number_suffix').val();
        } else {
            streetParts = MyParcel.splitFullStreetFromInput(input.addressLine1, input.addressLine2);
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
    splitFullStreetFromInput: function(addressLine1, addressLine2) {
        result = {
            streetName:        '',
            houseNumber:       '',
            houseNumberSuffix: '',
        };

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

        // Hide postnl field if there is no address entered
        if (this.data.address.postalCode == '' || this.data.address.city == '') {
            MyParcel.hideSpinner();
            MyParcel.showMessage(
                '<h3>'+ this.data.textToTranslate.addressNotFound + '</h3>'
            );
            return;
        }

        /* Check if the deliverydaysWindow == 0 and hide the select input*/
        this.deliveryDaysWindow = this.data.config.deliverydaysWindow;

        if (this.deliveryDaysWindow === 0) {
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
                saturday_delivery:   this.data.config.allowSaturdayDelivery,
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
                jQuery('#mypabe-select-delivery').click();
            });
    }
};
// timeout because postcode api might take too long to respond
setTimeout(function() {
    MyParcel.init();
}, 2000);
