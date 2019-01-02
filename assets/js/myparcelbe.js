MyParcelBE = {
    /*
     * Init
     *
     * Initialize the MyParcel BE checkout.
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
        if (MyParcelBE.data.config.deliveryTitle) {
            jQuery('#mypabe-delivery-title').html(MyParcelBE.data.config.deliveryTitle);
        }
        if (MyParcelBE.data.config.headerDeliveryOptions) {
            jQuery('#mypabe-delivery-options-title').html(MyParcelBE.data.config.headerDeliveryOptions);
            jQuery('#header-delivery-options-title').show();
        }
        if (MyParcelBE.data.config.signatureTitle) {
            jQuery('#mypabe-signature-title').html(MyParcelBE.data.config.signatureTitle);
        }
        if (MyParcelBE.data.config.pickupTitle) {
            jQuery('#mypabe-pickup-title').html(MyParcelBE.data.config.pickupTitle);
        }

        /* Prices */
        var prices = {
            'normal':         this.data.config.priceNormalDelivery,
            'signature':      this.data.config.priceSignature,
            'pickup':         this.data.config.pricePickup,
            'saturday':       this.data.config.priceSaturdayDelivery
        };

        MyParcelBE.showPrices(prices);
        MyParcelBE.callDeliveryOptions();

        /* Engage defaults */
        MyParcelBE.hideDelivery();
        jQuery('#method-myparcelbe-normal').click();

        MyParcelBE.bind();
    },

    showPrices: function(prices) {
        jQuery.each(prices, function(selectName, price) {
            jQuery('#mypabe-' + selectName + '-delivery, #mypabe-' + selectName + '-price').html(MyParcelBE.getPriceHtml(price));
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
        if (typeof MyParcelBE.storeDeliveryOptions === 'undefined') {
            console.error('setCurrentDeliveryOptions() MyParcelBE.storeDeliveryOptions === undefined');
            return;
        }

        var selectedDate = jQuery('#mypabe-select-date').val();
        var selectDateKey = MyParcelBE.storeDeliveryOptions.data.delivery[selectedDate]['time'];

        jQuery.each(selectDateKey, function(key, value) {

            if (value['price_comment'] == 'standard') {
                var standardTitle = MyParcelBE.data.config.deliveryStandardTitle;
                MyParcelBE.getDeliveryTime(standardTitle, 'standard', value['start'], value['end']);
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
        this.currentLocation = this.getPickupByLocationId(MyParcelBE.storeDeliveryOptions.data.pickup, locationId);
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
            MyParcelBE.exportDeliveryOptionToWebshop();
        });

        /* show default delivery options and hide bpost options */
        jQuery('#mypabe-select-delivery').on('click', function() {
            MyParcelBE.setCurrentDeliveryOptions();
            MyParcelBE.showDelivery();
            MyParcelBE.showShippingAddress();
            MyParcelBE.hidePickUpLocations();
        });

        /* hide default delivery options and show bpost options */
        jQuery('#mypabe-pickup-delivery').on('click', function() {
            MyParcelBE.hideDelivery();
            MyParcelBE.hideShippingAddress();
            MyParcelBE.showPickUpLocations();
        });

        /* Mobile specific triggers */
        if (isMobile) {
            jQuery('#mypabe-show-location-details').on('click', function() {
                MyParcelBE.setCurrentLocation();
                MyParcelBE.showLocationDetails();
                MyParcelBE.hideDelivery();
            });
        }

        /* Desktop specific triggers */
        else {
            jQuery('#mypabe-show-location-details').on('click', function() {
                MyParcelBE.setCurrentLocation();
                MyParcelBE.showLocationDetails();
            });
        }

        jQuery('#mypabe-location-details').on('click', function() {
            MyParcelBE.hideLocationDetails();
        });

        jQuery('#mypabe-pickup-delivery, #mypabe-pickup-location').on('change', function(e) {
            MyParcelBE.setCurrentLocation();
            MyParcelBE.toggleDeliveryOptions();
            MyParcelBE.mapExternalWebshopTriggers();
        });

        jQuery('#mypabe-select-date').on('change', function(e) {
            MyParcelBE.setCurrentDeliveryOptions();
            MyParcelBE.mapExternalWebshopTriggers();
        });

        /* External webshop triggers */
        jQuery('#mypabe-load input, #mypabe-load select').on('input', function() {
            MyParcelBE.mapExternalWebshopTriggers()
        });

        fields = window.myparcelbe_is_using_split_address_fields
            ? '#billing_house_number, #shipping_house_number'
            : '#billing_address_1, #shipping_address_1';

        jQuery('#billing_country, #shipping_country, #billing_postcode, #shipping_postcode, #billing_city, #shipping_city, ' + fields).on('change', function() {
            MyParcelBE.callDeliveryOptions();
        });
    },

    mapExternalWebshopTriggers: function() {
        MyParcelBE.DELIVERY_SIGNATURE = 0;
        MyParcelBE.removeStyleFromPrice();

        /**
         * Normal delivery
         *
         */
        if (jQuery('#mypabe-pickup-delivery').prop('checked') === false && jQuery('#method-myparcelbe-normal').prop('checked')) {
            MyParcelBE.addStyleToPrice('#mypabe-normal-delivery');

            /**
             * Signature
             */
            if (jQuery('#mypabe-signature-selector').prop('checked')) {
                MyParcelBE.DELIVERY_SIGNATURE = 1;
                MyParcelBE.addStyleToPrice('#mypabe-signature-price');

            }

            MyParcelBE.addDeliveryToExternalInput(MyParcelBE.DELIVERY_NORMAL);
            return;
        }

        /**
         * Pickup
         *
         */
        if (jQuery('#mypabe-pickup-delivery').prop('checked') || jQuery('#mypabe-pickup-selector').prop('checked')) {
            MyParcelBE.addStyleToPrice('#mypabe-pickup-price');

            jQuery('#s_method_myparcelbe_pickup').click();
            MyParcelBE.addPickupToExternalInput(MyParcelBE.DELIVERY_PICKUP);
        }
    },

    addPickupToExternalInput: function(selectedPriceComment) {
        var locationId = jQuery('#mypabe-pickup-location').val();
        var currentLocation = MyParcelBE.getPickupByLocationId(MyParcelBE.storeDeliveryOptions.data.pickup, locationId);

        var result = jQuery.extend({}, currentLocation);

        /* If pickup; convert pickup express to pickup */
        if (selectedPriceComment === MyParcelBE.DELIVERY_PICKUP) {
            result.price_comment = MyParcelBE.DELIVERY_PICKUP;
        }

        jQuery('body').trigger('update_checkout');
        jQuery('#mypabe-input').val(JSON.stringify(result));
    },

    addDeliveryToExternalInput: function(deliveryMomentOfDay) {
        var deliveryDateId = jQuery('#mypabe-select-date').val();
        var currentDeliveryData = MyParcelBE.triggerDefaultOptionDelivery(deliveryDateId, deliveryMomentOfDay);

        if (currentDeliveryData !== null) {
            currentDeliveryData.signature = MyParcelBE.DELIVERY_SIGNATURE;
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
        var dateArray = MyParcelBE.data.deliveryOptions.data.delivery[deliveryDateId];
        var currentDeliveryData = null;

        jQuery.each(dateArray['time'], function(key, value) {
            if (value.price_comment === deliveryMomentOfDay) {
                currentDeliveryData = jQuery.extend({}, dateArray);
                currentDeliveryData['time'] = [value];
            }
        });

        if (currentDeliveryData === null) {
            jQuery('#method-myparcelbe-normal').prop('checked', true);
            MyParcelBE.mapExternalWebshopTriggers();
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
        MyParcelBE.hideSignature();
    },

    /*
     * showDelivery
     *
     * Shows interface part for delivery.
     *
     */
    showDelivery: function() {

        jQuery('#mypabe-delivery').parent().parent().show();

        if (MyParcelBE.data.address.cc === "BE") {
            jQuery('#mypabe-delivery-selectors-' + this.data.address.cc.toLowerCase()).show();
            jQuery('.mypabe-extra-delivery-options').show();


            if (this.data.config.deliverydaysWindow >= 2) {
                jQuery('#mypabe-delivery-date-select').show();
            }

            MyParcelBE.hideSignature();
            if (this.data.config.allowSignature) {
                MyParcelBE.showSignature();
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
     * Shows the MyParcel BE spinner.
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
     * Hides the MyParcel BE spinner.
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
        var deliveryWindow = parseInt(MyParcelBE.data.config.deliverydaysWindow);

        jQuery.each(MyParcelBE.data.deliveryOptions.data.delivery, function(key, value) {
            html += '<option value="' + key + '">' + MyParcelBE.dateToString(value.date) + ' </option>\n';
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
        if (!MyParcelBE.data.config.allowPickupPoints) {
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
        if (false === MyParcelBE.data.config.allowPickupPoints) {
            return;
        }

        var html = "";
        jQuery.each(MyParcelBE.data.deliveryOptions.data.pickup, function(key, value) {
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

        var currentLocation = MyParcelBE.getPickupByLocationId(MyParcelBE.storeDeliveryOptions.data.pickup, locationId);
        var startTime = currentLocation.start_time;

        /* Strip seconds if present */
        if (startTime.length > 5) {
            startTime = startTime.slice(0, -3);
        }

        html += '<svg class="svg-inline--fa mypabe-fa-times fa-w-12" aria-hidden="true" data-prefix="fas" data-icon="times" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" data-fa-i2svg=""><path fill="currentColor" d="M323.1 441l53.9-53.9c9.4-9.4 9.4-24.5 0-33.9L279.8 256l97.2-97.2c9.4-9.4 9.4-24.5 0-33.9L323.1 71c-9.4-9.4-24.5-9.4-33.9 0L192 168.2 94.8 71c-9.4-9.4-24.5-9.4-33.9 0L7 124.9c-9.4 9.4-9.4 24.5 0 33.9l97.2 97.2L7 353.2c-9.4 9.4-9.4 24.5 0 33.9L60.9 441c9.4 9.4 24.5 9.4 33.9 0l97.2-97.2 97.2 97.2c9.3 9.3 24.5 9.3 33.9 0z"></path></svg>'
        html += '<span class="mypabe-pickup-location-details-location"><h3>' + currentLocation.location + '</h3></span>';
        html += '<svg class="mypabe-bpost-logo" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 99" enable-background="new 0 0 100 99" xml:space="preserve"><image id="bpost-logo" width="100" height="99" href="http://localhost/woocommerce/wp-content/plugins/woocommerce-belgie/assets/img/wcmp-bpost-logo.png"></image></svg>';
        html += '<span class="mypabe-pickup-location-details-street">' + currentLocation.street + '&nbsp;' + this.currentLocation.number + '</span>';
        html += '<span class="mypabe-pickup-location-details-city">' + currentLocation.postal_code + '&nbsp;' + currentLocation.city + '</span>';

        if (currentLocation.phone_number) {
            html += '<span class="mypabe-pickup-location-details-phone">' + currentLocation.phone_number + '</span>';
        }
        html += '<span class="mypabe-pickup-location-details-time">' + MyParcelBE.data.textToTranslate.pickUpFrom + ':&nbsp;' + startTime + '</span>';
        html += '<h3>' + this.data.textToTranslate.openingHours + '</h3>';

        jQuery.each(
            currentLocation.opening_hours, function(weekday, value) {
                html += '<span class="mypabe-pickup-location-details-day">' + MyParcelBE.data.translateENtoNL[weekday] + "</span>";

                if (value[0] === undefined) {
                    html += '<span class="mypabe-time">' + MyParcelBE.data.textToTranslate.closed +'</span>';
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

        MyParcelBE.callDeliveryOptions();
        jQuery('#mypabe-select-delivery').click();
    },

    /*
     * showFallBackDelivery
     *
     * If the API call fails and we have no data about delivery or pick up options
     * show the customer an "As soon as possible" option.
     */
    showFallBackDelivery: function() {
        MyParcelBE.hideSpinner();
        MyParcelBE.hideDelivery();
        jQuery('#mypabe-select-date, #method-myparcelbe-normal-div, .mypabe-is-pickup-element').hide();
        jQuery('#mypabe-select-delivery-title').html('Zo snel mogelijk bezorgen');
    },

    /*
     * showRetry
     *
     * If a customer enters an unrecognised postal code & house number combination show a
     * pop-up so they can try again.
     */
    showRetry: function() {
        MyParcelBE.showMessage(
            '<h3>' + MyParcelBE.data.textToTranslate.wrongHouseNumberCity+ '</h3>' +
            '<div class="mypabe-full-width mypabe-error">' +
            '<label for="mypabe-error-postcode">' + MyParcelBE.data.textToTranslate.postcode + '</label>' +
            '<input type="text" name="mypabe-error-postcode" id="mypabe-error-postcode" value="' + MyParcelBE.data.address.postalCode + '">' +
            '</div><div class="mypabe-full-width mypabe-error">' +
            '<label for="mypabe-error-city">' + MyParcelBE.data.textToTranslate.city + '</label>' +
            '<input type="text" name="mypabe-error-city" id="mypabe-error-city" value="' + MyParcelBE.data.address.city + '">' +
            '<br><div id="mypabe-error-try-again" class="button btn">' + MyParcelBE.data.textToTranslate.retry + '</div>' +
            '</div>'
        );

        /* remove trigger that closes message */
        jQuery('#mypabe-message').off('click');

        /* bind trigger to new button */
        jQuery('#mypabe-error-try-again').on('click', function() {
            MyParcelBE.retryPostalCodeHouseNumber();
        });
    },

    setAddressFromInputFields: function() {
        addressType = jQuery('#ship-to-different-address-checkbox').prop('checked') ? 'shipping' : 'billing';
        address = MyParcelBE.getAddressInputValues(addressType);

        if (!MyParcelBE.getAddressInputValues('billing').postalCode) {
            return;
        }

        this.data.address.street = address.streetName;
        this.data.address.number = address.houseNumber;
        this.data.address.numberSuffix = address.houseNumberSuffix;
        this.data.address.cc = address.country;
        this.data.address.postalCode = address.postalCode;
        this.data.address.city = address.city;

        if (this.data.address.cc !== "BE"){
            MyParcelBE.hideDelivery();
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

        if (window.myparcelbe_is_using_split_address_fields) {
            input.streetName = jQuery('#' + type + '_street_name').val();
            input.houseNumber = jQuery('#' + type + '_house_number').val();
            input.houseNumberSuffix = jQuery('#' + type + '_house_number_suffix').val();
        } else {
            streetParts = MyParcelBE.splitFullStreetFromInput(input.addressLine1, input.addressLine2);
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
     * Calls the MyParcel BE API to retrieve the pickup and delivery options for given house number and
     * Postal Code.
     *
     */
    callDeliveryOptions: function() {
        MyParcelBE.showSpinner();
        MyParcelBE.clearPickUpLocations();
        MyParcelBE.hideDelivery();
        MyParcelBE.setAddressFromInputFields();

        // Hide bpost field if there is no address entered
        if (this.data.address.postalCode == '' || this.data.address.city == '') {
            MyParcelBE.hideSpinner();
            MyParcelBE.showMessage(
                '<h3>'+ this.data.textToTranslate.allDataNotFound + '</h3>'
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
            MyParcelBE.data.deliveryOptions = response;
            if (response.errors) {
                jQuery.each(response.errors, function(key, value) {
                    /* Postal code & house number combination not found or not recognised. */
                    if (value.code == '3212' || value.code == '3505') {
                        MyParcelBE.showRetry();
                    }

                    /* Any other error */
                    else {
                        MyParcelBE.showFallBackDelivery();
                    }
                });
            }

            /* No errors */
            else {
                MyParcelBE.hideMessage();
                MyParcelBE.showPickUpLocations();
                MyParcelBE.showDeliveryDates();

                if (MyParcelBE.data.deliveryOptions.data.delivery.length <= 0) {
                    MyParcelBE.hideDeliveryDates();
                }
                MyParcelBE.storeDeliveryOptions = response;
            }
            MyParcelBE.hideSpinner();
        })
        .fail(function() {
            MyParcelBE.showFallBackDelivery();
        })
        .always(function() {
            jQuery('#mypabe-select-delivery').click();
        });
    }
};
// timeout because postcode api might take too long to respond
setTimeout(function() {
    MyParcelBE.init();
}, 2000);
