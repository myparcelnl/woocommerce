MyParcel = {
    /*
     * Init
     *
     * Initialize the MyParcel checkout.
     *
     */
    data: {},
    currentLocation: {},

    DELIVERY_MORNING: 'morning',
    DELIVERY_NORMAL: 'standard',
    DELIVERY_NIGHT: 'avond',
    DELIVERY_SIGNED: 0,
    DELIVERY_ONLY_RECIPIENT: 0,

    init: function(externalData)
    {
        this.data = externalData;

        isMobile     = true;
        if(jQuery( window ).width() > 980 ) {
            isMobile = false;
        }

        /* Titels of the options*/
        if (MyParcel.data.config.deliveryTitel){
            jQuery('#mypa-delivery-titel').html(MyParcel.data.config.deliveryTitel);
        }
        if (MyParcel.data.config.onlyRecipientTitel){
            jQuery('#mypa-only-recipient-titel').html(MyParcel.data.config.onlyRecipientTitel);
        }
        if (MyParcel.data.config.signatureTitel){
            jQuery('#mypa-signature-titel').html(MyParcel.data.config.signatureTitel);
        }
        if (MyParcel.data.config.pickupTitel){
            jQuery('#mypa-pickup-titel').html(MyParcel.data.config.pickupTitel);
        }

        /* Prices */
        jQuery('#mypa-morning-delivery').html(MyParcel.getPriceHtml(this.data.config.priceMorningDelivery));
        jQuery('#mypa-evening-delivery').html(MyParcel.getPriceHtml(this.data.config.priceEveningDelivery));
        jQuery('#mypa-normal-delivery').html(MyParcel.getPriceHtml(this.data.config.priceNormalDelivery));
        jQuery('#mypa-signature-price').html(MyParcel.getPriceHtml(this.data.config.priceSignature));
        jQuery('#mypa-only-recipient-price').html(MyParcel.getPriceHtml(this.data.config.priceOnlyRecipient));
        jQuery('#mypa-pickup-price').html(MyParcel.getPriceHtml(this.data.config.pricePickup));

        /* Call delivery options */
        MyParcel.callDeliveryOptions();

        /* Engage defaults */
        MyParcel.hideDelivery();
        jQuery('#method-myparcel-normal').click();

        MyParcel.bind();
    },

    getPriceHtml: function(priceOfDeliveryOption){

        if (!priceOfDeliveryOption) {
            var price = "";
        }

        if (parseFloat(priceOfDeliveryOption) >= 0){
            var price = '+ &euro; ' + Number(priceOfDeliveryOption).toFixed(2).replace(".", ",");
        }

        if (priceOfDeliveryOption && isNaN(parseFloat(priceOfDeliveryOption))){
            var price = priceOfDeliveryOption ;
        }

        return price;
    },

    setCurrentDeliveryOptions: function () {
        if (typeof MyParcel.storeDeliveryOptions === 'undefined') {
            console.error('setCurrentDeliveryOptions() MyParcel.storeDeliveryOptions === undefined');
            return;
        }

        var selectedDate 	= jQuery('#mypa-select-date').val();
        var selectDateKey 	= MyParcel.storeDeliveryOptions.data.delivery[selectedDate]['time'];

        MyParcel.hideMorningDelivery();
        MyParcel.hideEveningDelivery();

        jQuery.each(selectDateKey, function(key, value){

            if(value['price_comment'] == 'morning' && MyParcel.data.config.allowMorningDelivery){
                var morningTitel = MyParcel.data.config.deliveryMorningTitel;
                MyParcel.getDeliveryTime(morningTitel,'morning', MyParcel.data.config.deliveryMorningTitel, value['start'], value['end']);
                MyParcel.showMorningDelivery();
            }
            if(value['price_comment'] == 'standard'){
                var standardTitel = MyParcel.data.config.deliveryStandardTitel;
                MyParcel.getDeliveryTime(standardTitel,'standard', MyParcel.data.config.deliveryStandardTitel, value['start'], value['end']);

            }
            if(value['price_comment'] == 'avond' && MyParcel.data.config.allowEveningDelivery){
                var eveningTitel = MyParcel.data.config.deliveryEveningTitel;
                MyParcel.getDeliveryTime(eveningTitel, 'evening', MyParcel.data.config.deliveryEveningTitel, value['start'], value['end'] );
                MyParcel.showEveningDelivery();
            }

        });
    },
    getDeliveryTime: function (configDeliveryTitel, deliveryMoment, deliveryTitel, startTime, endTime) {
        startTime = startTime.replace(/(.*)\D\d+/, 'jQuery1');
        endTime = endTime.replace(/(.*)\D\d+/, 'jQuery1');

        jQuery('#mypa-'+deliveryMoment+'-titel').html(deliveryTitel);

        if (!configDeliveryTitel){
            jQuery('#mypa-'+deliveryMoment+'-titel').html(startTime + ' - ' + endTime);
        }

    },

    setCurrentLocation: function () {
        var locationId 			= jQuery('#mypa-pickup-location').val();
        this.currentLocation 	= this.getPickupByLocationId(MyParcel.storeDeliveryOptions.data.pickup, locationId);

    },
    /*
     * Bind
     *
     * Bind actions to selectors.
     *
     */

    bind: function ()
    {
        jQuery('#mypa-submit').on('click', function(e)
        {
            e.preventDefault();
            MyParcel.exportDeliveryOptionToWebshop();
        });

        /* show default delivery options and hide PostNL options */
        jQuery('#mypa-select-delivery').on('click', function(){
            MyParcel.setCurrentDeliveryOptions();
            MyParcel.showDelivery();
            MyParcel.hidePickUpLocations();
        });

        /* hide default delivery options and show PostNL options */
        jQuery('#mypa-pickup-delivery').on('click', function(){
            MyParcel.hideDelivery();
            MyParcel.showPickUpLocations();
        });

        jQuery('#method-myparcel-delivery-morning, #method-myparcel-delivery-evening').on('click', function(){
            MyParcel.defaultCheckCheckbox('mypa-only-recipient');
        });

        /* Mobile specific triggers */
        if(isMobile){
            jQuery('#mypa-show-location-details').on('click', function(){
                MyParcel.setCurrentLocation();
                MyParcel.showLocationDetails();
                MyParcel.hideDelivery();
            });
        }

        /* Desktop specific triggers */
        else {
            jQuery('#mypa-show-location-details').on('mouseenter', function(){
                MyParcel.setCurrentLocation();
                MyParcel.showLocationDetails();
            });
        }

        jQuery('#mypa-location-details').on('click', function(){
            MyParcel.hideLocationDetails();
        });

        jQuery('#method-myparcel-normal').on('click', function(){
            MyParcel.defaultCheckCheckbox('method-myparcel-normal');
        });

        jQuery('#mypa-pickup-express').hide();  /* todo: move */


        jQuery('#mypa-pickup-delivery, #mypa-pickup-location').on('change', function(e){
            MyParcel.setCurrentLocation();
            MyParcel.toggleDeliveryOptions();
            MyParcel.mapExternalWebshopTriggers();
        });

        jQuery('#mypa-select-date').on('change', function(e){
            MyParcel.setCurrentDeliveryOptions();
            MyParcel.mapExternalWebshopTriggers();
        });

        /* External webshop triggers */
        jQuery('#mypa-load').on('click', function () {

            MyParcel.mapExternalWebshopTriggers()
        });
    },

    mapExternalWebshopTriggers: function () {
        MyParcel.DELIVERY_SIGNED = 0;
        MyParcel.DELIVERY_ONLY_RECIPIENT = 0;
        MyParcel.removeStyleFromPrice();

        /**
         * Morning delivery
         *
         */
        if (jQuery('#method-myparcel-delivery-morning').prop('checked'))
        {
            jQuery('#s_method_myparcel_morning').click();
            MyParcel.DELIVERY_ONLY_RECIPIENT = 1;
            MyParcel.addStyleToPrice('#mypa-morning-delivery, #mypa-only-recipient-price');

            /**
             * Signature
             */
            if (jQuery('#mypa-signature-selector').prop('checked'))
            {
                jQuery('#s_method_myparcel_morning_signature').click();
                MyParcel.DELIVERY_SIGNED = 1;
                MyParcel.addStyleToPrice('#mypa-signature-price');
            }

            MyParcel.addDeliveryToExternalInput(MyParcel.DELIVERY_MORNING);
            return;
        }

        /**
         * Normal delivery
         *
         */
        if (jQuery('#mypa-pickup-delivery').prop('checked') === false && jQuery('#method-myparcel-normal').prop('checked'))
        {
            MyParcel.addStyleToPrice('#mypa-normal-delivery');

            /**
             * Signature and only recipient
             */
            if (jQuery('#mypa-signature-selector').prop('checked') && jQuery('#mypa-only-recipient-selector').prop('checked'))
            {
                jQuery('#s_method_myparcel_delivery_signature_and_only_recipient_fee').click();
                MyParcel.DELIVERY_SIGNED = 1;
                MyParcel.DELIVERY_ONLY_RECIPIENT = 1;
                MyParcel.addStyleToPrice('#mypa-signature-price, #mypa-only-recipient-price');
            } else

            /**
             * Signature
             */
            if (jQuery('#mypa-signature-selector').prop('checked'))
            {
                jQuery('#s_method_myparcel_delivery_signature').click();
                MyParcel.DELIVERY_SIGNED = 1;
                MyParcel.addStyleToPrice('#mypa-signature-price');
            } else

            /**
             * Only recipient
             */
            if (jQuery('#mypa-only-recipient-selector').prop('checked'))
            {
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
        if (jQuery('#method-myparcel-delivery-evening').prop('checked'))
        {
            jQuery('#s_method_myparcel_evening').click();
            MyParcel.DELIVERY_ONLY_RECIPIENT = 1;
            MyParcel.addStyleToPrice('#mypa-evening-delivery, #mypa-only-recipient-price');

            /**
             * Signature
             */
            if (jQuery('#mypa-signature-selector').prop('checked'))
            {
                jQuery('#s_method_myparcel_evening_signature').click();
                MyParcel.DELIVERY_SIGNED = 1;
                MyParcel.addStyleToPrice('#mypa-signature-price');
            }

            MyParcel.addDeliveryToExternalInput(MyParcel.DELIVERY_NIGHT);
            return;
        }

        /**
         * Pickup
         *
         */
        if (jQuery('#mypa-pickup-delivery').prop('checked') || jQuery('#mypa-pickup-selector').prop('checked'))
        {

            /**
             * Early morning pickup
             */
            if (jQuery('#mypa-pickup-express-selector').prop('checked'))
            {
                jQuery('#s_method_myparcel_pickup_express').click();
                MyParcel.addPickupToExternalInput('retailexpress');
                MyParcel.addStyleToPrice('#mypa-pickup-express-price');
                return;
            }else{
                MyParcel.addStyleToPrice('#mypa-pickup-price');
            }


            jQuery('#s_method_myparcel_pickup').click();
            MyParcel.addPickupToExternalInput('retail');
        }
    },

    addPickupToExternalInput: function (selectedPriceComment) {
        var locationId = jQuery('#mypa-pickup-location').val();
        var currentLocation = MyParcel.getPickupByLocationId(MyParcel.storeDeliveryOptions.data.pickup, locationId);

        var result = jQuery.extend({}, currentLocation);

        /* If retail; convert retailexpress to retail */
        if (selectedPriceComment === "retail") {
            result.price_comment = "retail";
        }

        jQuery('#mypa-input').val(JSON.stringify(result));
    },

    addStyleToPrice: function (chosenDelivery) {
        jQuery(chosenDelivery).addClass('mypa-bold-price');
    },

    removeStyleFromPrice: function (){
        jQuery('.mypa-delivery-option-table').find( "span" ).removeClass('mypa-bold-price');
    },

    addDeliveryToExternalInput: function (deliveryMomentOfDay) {

        var deliveryDateId = jQuery('#mypa-select-date').val();

        var currentDeliveryData = MyParcel.triggerDefaultOptionDelivery(deliveryDateId, deliveryMomentOfDay);

        if (currentDeliveryData !== null) {
            currentDeliveryData.signed = MyParcel.DELIVERY_SIGNED;
            currentDeliveryData.only_recipient = MyParcel.DELIVERY_ONLY_RECIPIENT;
            jQuery('#mypa-input').val(JSON.stringify(currentDeliveryData));
        }
    },


    triggerDefaultOptionDelivery: function (deliveryDateId, deliveryMomentOfDay) {
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
    defaultCheckCheckbox: function(selectedOption){
        if(selectedOption === 'mypa-only-recipient'){
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

    toggleDeliveryOptions: function()
    {
        var isPickup	= jQuery('#mypa-pickup-delivery').is(':checked');
        jQuery('#mypa-pickup-selector').prop('checked', true);

        if(isPickup && this.currentLocation.price_comment === "retailexpress"){
            jQuery('#mypa-pickup-express-price').html(MyParcel.getPriceHtml(this.data.config.pricePickupExpress));
            jQuery('#mypa-pickup-express').show();

        } else{
            jQuery('#mypa-pickup-express-selector').attr("checked", false);
            jQuery('#mypa-pickup-express').hide();

        }
    },


    /*
     * exportDeliverOptionToWebshop
     *
     * Exports the selected deliveryoption to the webshop.
     *
     */

    exportDeliveryOptionToWebshop: function()
    {
        var deliveryOption = "";
        var selected       = jQuery("#mypa-delivery-option-form").find("input[type='radio']:checked");
        if (selected.length > 0) {
            deliveryOption = selected.val();
        }

        /* XXX Send to appropriate webshop field */
    },


    /*
     * hideMessage
     *
     * Hides pop-up message.
     *
     */

    hideMessage: function()
    {
        jQuery('.mypa-message-model').hide().html(' ');
        jQuery('#mypa-delivery-option-form').show();
    },

    /*
     * hideMessage
     *
     * Hides pop-up essage.
     *
     */

    showMessage: function(message)
    {
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

    hideDelivery: function()
    {
        jQuery('#mypa-delivery-date-select, #mypa-pre-selectors-nl, #mypa-delivery, #mypa-normal-delivery').hide();
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

    showDelivery: function()
    {
        jQuery('#mypa-pre-selectors-' +      this.data.address.cc.toLowerCase()).show();
        jQuery('#mypa-delivery-selectors-' + this.data.address.cc.toLowerCase()).show();
        jQuery('#mypa-delivery, #mypa-normal-delivery, #mypa-delivery-date-select').show();

        MyParcel.hideSignature();
        if(this.data.config.allowSignature){
            MyParcel.showSignature();
        }

        MyParcel.hideOnlyRecipient();
        if(this.data.config.allowOnlyRecipient){
            MyParcel.showOnlyRecipient();
        }
    },

    /*
     * showSpinner
     *
     * Shows the MyParcel spinner.
     *
     */

    showSpinner: function()
    {
        jQuery('.mypa-message-model').hide();
        jQuery('#mypa-spinner').show();
    },


    /*
     * hideSpinner
     *
     * Hides the MyParcel spinner.
     *
     */

    hideSpinner: function()
    {
        jQuery('#mypa-spinner').hide();
    },

    showMorningDelivery: function()
    {
        jQuery('#method-myparcel-delivery-morning-div').show();
    },

    hideMorningDelivery: function()
    {
        jQuery('#method-myparcel-delivery-morning-div').hide();
    },

    showEveningDelivery: function()
    {
        jQuery('#method-myparcel-delivery-evening-div').show();
    },

    hideEveningDelivery: function()
    {
        jQuery('#method-myparcel-delivery-evening-div').hide();
    },

    showSignature: function()
    {
        jQuery('.mypa-extra-delivery-option-signature, #mypa-signature-price').show();
    },

    hideSignature: function()
    {
        jQuery('.mypa-extra-delivery-option-signature, #mypa-signature-price').hide();
    },

    showOnlyRecipient: function()
    {
        jQuery('#mypa-only-recipient, #mypa-only-recipient-price').show();
    },

    hideOnlyRecipient: function()
    {
        jQuery('#mypa-only-recipient, #mypa-only-recipient-price').hide();
    },

    /*
     * dateToString
     *
     * Convert api date string format to human readable string format
     *
     */

    dateToString: function(apiDate)
    {
        var deliveryDate 	= apiDate;
        var dateArr      	= deliveryDate.split('-');
        var dateObj      	= new Date(dateArr[0],dateArr[1]-1,dateArr[2]);
        var day				= ("0" + (dateObj.getDate())).slice(-2);
        var month        	= ("0" + (dateObj.getMonth() + 1)).slice(-2);

        return this.data.txtWeekDays[dateObj.getDay()] + " " + day + "-" + month + "-" + dateObj.getFullYear();
    },

    /*
     * showDeliveryDates
     *
     * Show possible delivery dates.
     *
     */

    showDeliveryDates: function()
    {
        var html = "";
        var deliveryWindow = parseInt(MyParcel.data.config.deliverydaysWindow);

        jQuery.each(MyParcel.data.deliveryOptions.data.delivery, function(key, value){
            html += '<option value="' + key + '">' + MyParcel.dateToString(value.date) + ' </option>\n';
        });

        /* Hide the day selector when the value of the deliverydaysWindow is 0*/
        if (deliveryWindow === 0){
            jQuery('#mypa-select-date').hide();
        }

        /* When deliverydaysWindow is 1, hide the day selector and show a div to show the date */
        if (deliveryWindow === 1){
            jQuery('#mypa-select-date').hide();
            jQuery('#mypa-delivery-date-text').show();
        }

        /* When deliverydaysWindow > 1, show the day selector */
        if (deliveryWindow > 1){
            jQuery('#mypa-select-date').show();
        }

        jQuery('#mypa-select-date, #mypa-date').html(html);
    },

    hideDeliveryDates: function()
    {
        jQuery('#mypa-delivery-date-text').parent().hide();
    },

    /*
     * clearPickupLocations
     *
     * Clear pickup locations and show a non-value option.
     *
     */

    clearPickUpLocations: function()
    {
        var html = '<option value="">---</option>';
        jQuery('#mypa-pickup-location').html(html);
    },


    /*
     * hidePickupLocations
     *
     * Hide the pickup location option.
     *
     */

    hidePickUpLocations: function()
    {
        if(!MyParcel.data.config.allowPickupPoints) {
            jQuery('#mypa-pickup-location-selector').hide();
        }

        jQuery('#mypa-pickup-options, #mypa-pickup, #mypa-pickup-express, #mypa-pickup-google-maps').hide();

    },


    /*
     * showPickupLocations
     *
     * Shows possible pickup locations, from closest to furdest.
     *
     */

    showPickUpLocations: function()
    {
        if(MyParcel.data.config.allowPickupPoints) {

            var html = "";
            jQuery.each(MyParcel.data.deliveryOptions.data.pickup, function (key, value) {
                var distance = parseFloat(Math.round(value.distance) / 1000).toFixed(1);
                html += '<option value="' + value.location_code + '">' + value.location + ', ' + value.street + ' ' + value.number + ", " + value.city + " (" + distance + " km) </option>\n";
            });
            jQuery('#mypa-pickup-location').html(html).prop("checked", true);
            jQuery('#mypa-pickup-location-selector, #mypa-pickup-options, #mypa-pickup').show();
        }

        if (MyParcel.data.config.allowGoogleMaps) {
            MyParcel.showPostnlPickupOnGoogleMaps();
        }
    },

    /*
     * hideLocationDetails
     *
     * Hide the detailed information pop-up for selected location.
     *
     */

    hideLocationDetails: function()
    {
        jQuery('#mypa-delivery-option-form').show();
        jQuery('#mypa-location-details').hide();
    },

    /*
     * showLocationDetails
     *
     * Shows the detailed information pop-up for the selected pick-up location.
     */

    showLocationDetails: function()
    {
        var html       		= "";
        var locationId 		= jQuery('#mypa-pickup-location').val();

        var currentLocation = MyParcel.getPickupByLocationId(MyParcel.storeDeliveryOptions.data.pickup, locationId);
        var startTime = currentLocation.start_time;

        /* Strip seconds if present */
        if(startTime.length > 5){
            startTime = startTime.slice(0,-3);
        }

        html += '<svg  class="svg-inline--fa mypa-fa-times fa-w-12" aria-hidden="true" data-prefix="fas" data-icon="times" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" data-fa-i2svg=""><path fill="currentColor" d="M323.1 441l53.9-53.9c9.4-9.4 9.4-24.5 0-33.9L279.8 256l97.2-97.2c9.4-9.4 9.4-24.5 0-33.9L323.1 71c-9.4-9.4-24.5-9.4-33.9 0L192 168.2 94.8 71c-9.4-9.4-24.5-9.4-33.9 0L7 124.9c-9.4 9.4-9.4 24.5 0 33.9l97.2 97.2L7 353.2c-9.4 9.4-9.4 24.5 0 33.9L60.9 441c9.4 9.4 24.5 9.4 33.9 0l97.2-97.2 97.2 97.2c9.3 9.3 24.5 9.3 33.9 0z"></path></svg>'
        html += '<span class="mypa-pickup-location-details-location"><h3>' + currentLocation.location  + '</h3></span>'
        html += '<img class="mypa-postnl-logo" src="../images/PostNL_Logo.jpg" />';
        html += '<span class="mypa-pickup-location-details-street">' + currentLocation.street + '&nbsp;' + this.currentLocation.number + '</span>';
        html += '<span class="mypa-pickup-location-details-city">' + currentLocation.postal_code + '&nbsp;' + currentLocation.city + '</span>';

        if(currentLocation.phone_number){
            html += '<span class="mypa-pickup-location-details-phone">' + currentLocation.phone_number  + '</span>'
        }
        html += '<span class="mypa-pickup-location-details-time">Ophalen vanaf:&nbsp;' + startTime + '</span>'
        html += '<h3>Openingstijden</h3>';

        jQuery.each(
            currentLocation.opening_hours, function(weekday, value){
                html += '<span class="mypa-pickup-location-details-day">' + MyParcel.data.translateENtoNL[weekday] + "</span> ";

                if(value[0] === undefined ){
                    html +=  '<span class="mypa-time">Gesloten</span>';
                }

                jQuery.each(value, function(key2, times){
                    html +=  '<span class="mypa-time">' + times + "</span>";
                });
                html += "<br>";
            });

        jQuery('#mypa-delivery-option-form').hide();
        jQuery('#mypa-location-details').html(html).show();
    },

    showPostnlPickupOnGoogleMaps: function () {

        jQuery.getScript("https://maps.googleapis.com/maps/api/js?key=AIzaSyAyBuzlPSNhmRIEhIl-3ZUidj3fwXfsDSw&amp;sensor=false", function() {
            jQuery('#mypa-pickup-google-maps').show();

            var bounds = new google.maps.LatLngBounds();
            var infowindow = new google.maps.InfoWindow();
            var marker, i;

            var locations = [
                jQuery.each(MyParcel.data.deliveryOptions.data.pickup, function (key, value) {
                    [value.location, value.latitude, value.longitude, key]
                }),
            ];

            var map = new google.maps.Map(document.getElementById('mypa-map'),{
                mapTypeId: google.maps.MapTypeId.ROADMAP
            });

            for (i = 0; i < locations[0].length; i++) {
                marker = new google.maps.Marker({
                    position: new google.maps.LatLng(locations[0][i]['latitude'], locations[0][i]['longitude']),
                    map: map
                });
                bounds.extend(marker.position);

                google.maps.event.addListener(marker, 'click', (function(marker, i) {
                    return function() {
                        infowindow.setContent(locations[0][i]['location']);
                        infowindow.open(map, marker);
                        map.setZoom(18);
                        map.setCenter(marker.getPosition());
                    }
                })(marker, i));
            }
            map.fitBounds(bounds);

            /* zoom in the middel to 12 */
            var listener = google.maps.event.addListener(map, "idle", function () {
                map.setZoom(12);
                google.maps.event.removeListener(listener);
            });
        });
    },

    /*
     * getPickupByLocationId
     *
     * Find the location by id and return the object.
     *
     */
    getPickupByLocationId: function (obj, locationId) {
        var object;

        jQuery.each(obj, function (key, info) {
            if (info.location_code === locationId) {
                object = info;
                return false;
            };
        });

        return object;
    },

    /*
     * retryPostalcodeHouseNumber
     *
     * After detecting an unrecognised postcal code / house number combination the user can try again.
     * This function copies the newly entered data back into the webshop forms.
     *
    */
    retryPostalcodeHouseNumber: function()
    {
        this.data.address.postalCode = jQuery('#mypa-error-postcode').val();
        this.data.address.number = jQuery('#mypa-error-number').val();
        MyParcel.callDeliveryOptions();
        jQuery('#mypa-select-delivery').click();
    },

    /*
     * showFallBackDelivery
     *
     * If the API call fails and we have no data about delivery or pick up options
     * show the customer an "As soon as possible" option.
     */

    showFallBackDelivery: function()
    {
        MyParcel.hideSpinner();
        MyParcel.hideDelivery();
        jQuery('#mypa-select-date, #method-myparcel-normal').hide();
        jQuery('.mypa-is-pickup-element').hide();
        jQuery('#mypa-select-delivery-titel').html('Zo snel mogelijk bezorgen');
    },


    /*
     * showRetru
     *
     * If a customer enters an unrecognised postal code housenumber combination show a
     * pop-up so they can try again.
     */

    showRetry: function()
    {
        MyParcel.showMessage(
            '<h3>Huisnummer/postcode combinatie onbekend</h3>' +
            '<div class="mypa-full-width mypa-error">'+
            '<label for="mypa-error-postcode">Postcode</label>' +
            '<input type="text" name="mypa-error-postcode" id="mypa-error-postcode" value="'+ MyParcel.data.address.postalCode +'">' +
            '</div><div class="mypa-full-width mypa-error">' +
            '<label for="mypa-error-number">Huisnummer</label>' +
            '<input type="text" name="mypa-error-number" id="mypa-error-number" value="'+ MyParcel.data.address.number +'">' +
            '<br><button id="mypa-error-try-again">Opnieuw</button>' +
            '</div>'
        );

        /* remove trigger that closes message */
        jQuery('#mypa-message').off('click');

        /* bind trigger to new button */
        jQuery('#mypa-error-try-again').on('click', function(){
            MyParcel.retryPostalcodeHouseNumber();
        });
    },


    /*
     * callDeliveryOptions
     *
     * Calls the MyParcel API to retrieve the pickup and delivery options for given house number and
     * Postal Code.
     *
     */

    callDeliveryOptions: function()
    {
        MyParcel.showSpinner();
        MyParcel.clearPickUpLocations();

        var cc 				        = this.data.address.cc;
        var postalCode 	     	    = this.data.address.postalCode;
        var number 			        = this.data.address.number;
        var city 			        = this.data.address.city;
        var mondayDeliveryActive    = 0;

        if (postalCode == '' || number == ''){
            MyParcel.showMessage(
                '<h3>Adres gegevens zijn niet ingevuld</h3>'
            );
        }
        if (cc === "BE") {
            var numberExtra 	= this.data.address.numberExtra;
            var street 			= this.data.address.street;
        }

        if(numberExtra){
            number = number + numberExtra;
        }

        /* Don't call API unless both Postcode and House Number are set */
        if(!number || !postalCode) {
            MyParcel.showFallBackDelivery();
            return;
        }

        /* Check if the deliverydaysWindow == 0 and hide the select input*/
        this.deliveryDaysWindow = this.data.config.deliverydaysWindow;

        if(this.deliveryDaysWindow === "0"){
            this.deliveryDaysWindow = 1;
        }

        if (this.data.config.allowMondayDelivery === true){
            mondayDeliveryActive = 1;
        }

        /* Make the api request */
        jQuery.get(this.data.config.apiBaseUrl + "delivery_options",
            {
                cc                      :this.data.address.cc,
                postal_code             :postalCode,
                number                  :number,
                city                    :city,
                carrier                 :this.data.config.carrier,
                dropoff_days            :this.data.config.dropOffDays,
                monday_delivery         :mondayDeliveryActive,
                deliverydays_window     :this.deliveryDaysWindow,
                cutoff_time             :this.data.config.cutoffTime,
                dropoff_delay           :this.data.config.dropoffDelay
            })
            .done(function(response){

                MyParcel.data.deliveryOptions = response;
                if(response.errors){
                    jQuery.each(response.errors, function(key, value){
                        /* Postalcode housenumber combination not found or not recognised. */
                        if(value.code == '3212' || value.code == '3505'){
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
                    MyParcel.showDeliveryDates();
                    if(MyParcel.data.deliveryOptions.data.delivery.length <= 0 ){
                        MyParcel.hideDeliveryDates();
                    }
                    MyParcel.storeDeliveryOptions = response;
                }
                MyParcel.hideSpinner();
            })
            .fail(function(){
                MyParcel.showFallBackDelivery();
            })
            .always(function(){
                jQuery('#mypa-select-delivery').click();
            });
    }
}