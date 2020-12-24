/**
 * The following jsdoc blocks are for declaring the types of the injected variables from php.
 */

/**
 * @property {Object} MyParcelDisplaySettings
 * @property {String} MyParcelDisplaySettings.isUsingSplitAddressFields
 * @property {String[]} MyParcelDisplaySettings.splitAddressFieldsCountries
 *
 * @see \wcmp_checkout::inject_delivery_options_variables
 */

/**
 * @property {Object} wcmp
 * @property {String} wcmp.ajax_url
 *
 * @see \wcmp_checkout::inject_delivery_options_variables
 */

/**
 * @property {Object} MyParcelDeliveryOptions
 * @property {String} MyParcelDeliveryOptions.allowedShippingMethods
 * @property {String} MyParcelDeliveryOptions.disallowedShippingMethods
 * @property {String} MyParcelDeliveryOptions.hiddenInputName
 * @see \wcmp_checkout::inject_delivery_options_variables
 */
/* eslint-disable-next-line max-lines-per-function */
jQuery(function($) {
  var MyParcelFrontend = {
    /**
     * Whether the delivery options are currently shown or not. Defaults to true and can be set to false depending on
     *  shipping methods.
     *
     * @type {Boolean}
     */
    hasDeliveryOptions: true,

    /**
     * @type {RegExp}
     */
    splitStreetRegex: /(.*?)\s?(\d{1,4})[/\s-]{0,2}([A-z]\d{1,3}|-\d{1,4}|\d{2}\w{1,2}|[A-z][A-z\s]{0,3})?$/,

    /**
     * @type {Boolean}
     */
    isUsingSplitAddressFields: Boolean(Number(MyParcelDisplaySettings.isUsingSplitAddressFields)),

    /**
     * @type {String[]}
     */
    splitAddressFieldsCountries: MyParcelDisplaySettings.splitAddressFieldsCountries,

    /**
     * @type {Array}
     */
    allowedShippingMethods: JSON.parse(MyParcelDeliveryOptions.allowedShippingMethods),

    /**
     * @type {Array}
     */
    disallowedShippingMethods: JSON.parse(MyParcelDeliveryOptions.disallowedShippingMethods),

    /**
     * @type {Boolean}
     */
    alwaysShow: Boolean(parseInt(MyParcelDeliveryOptions.alwaysShow)),

    /**
     * @type {Object<String, String>}
     */
    previousCountry: {},

    /**
     * @type {String}
     */
    selectedShippingMethod: null,

    /**
     * @type {Element}
     */
    hiddenDataInput: null,

    /**
     * @type {String}
     */
    addressType: null,

    /**
     * Ship to different address checkbox.
     *
     * @type {String}
     */
    shipToDifferentAddressField: '#ship-to-different-address-checkbox',

    /**
     * Shipping method radio buttons.
     *
     * @type {String}
     */
    shippingMethodField: '[name="shipping_method[0]"]',

    /**
     * Highest shipping class field.
     *
     * @type {String}
     */
    highestShippingClassField: '[name="myparcel_highest_shipping_class"]',

    addressField: 'address_1',
    cityField: 'city',
    countryField: 'country',
    countryRow: 'country_field',
    houseNumberField: 'house_number',
    houseNumberSuffixField: 'house_number_suffix',
    postcodeField: 'postcode',
    streetNameField: 'street_name',

    /**
     * Delivery options events.
     */
    updateDeliveryOptionsEvent: 'myparcel_update_delivery_options',
    updatedDeliveryOptionsEvent: 'myparcel_updated_delivery_options',
    updatedAddressEvent: 'myparcel_updated_address',

    showDeliveryOptionsEvent: 'myparcel_show_delivery_options',
    hideDeliveryOptionsEvent: 'myparcel_hide_delivery_options',

    /**
     * WooCommerce checkout events.
     */
    countryToStateChangedEvent: 'country_to_state_changed',
    updateWooCommerceCheckoutEvent: 'update_checkout',
    updatedWooCommerceCheckoutEvent: 'updated_checkout',

    /**
     * Initialize the script.
     */
    init: function() {
      MyParcelFrontend.addListeners();
      MyParcelFrontend.injectHiddenInput();
    },

    /**
     * When the delivery options are updated, fill the hidden input with the new data and trigger the WooCommerce
     *  update_checkout event.
     *
     * @param {CustomEvent} event - The update event.
     */
    onDeliveryOptionsUpdate: function(event) {
      MyParcelFrontend.hiddenDataInput.value = JSON.stringify(event.detail);

      /**
       * Remove this event before triggering and re-add it after because it will cause an infinite loop otherwise.
       */
      $(document.body).off(MyParcelFrontend.updatedWooCommerceCheckoutEvent, MyParcelFrontend.updateShippingMethod);
      MyParcelFrontend.triggerEvent(MyParcelFrontend.updateWooCommerceCheckoutEvent);

      $(document.body).on(MyParcelFrontend.updatedWooCommerceCheckoutEvent, restoreEventListener);

      /**
       * After the "updated_checkout" event the shipping methods will be rendered, restore the event listener and delete
       *  this one in the process.
       */
      function restoreEventListener() {
        $(document.body).on(MyParcelFrontend.updatedWooCommerceCheckoutEvent, MyParcelFrontend.updateShippingMethod);
        $(document.body).off(MyParcelFrontend.updatedWooCommerceCheckoutEvent, restoreEventListener);
      }
    },

    /**
     * If split fields are used add house number to the fields. Otherwise use address line 1.
     *
     * @returns {String}
     */
    getSplitField: function() {
      return MyParcelFrontend.hasSplitAddressFields()
        ? MyParcelFrontend.houseNumberField
        : MyParcelFrontend.addressField;
    },

    /**
     * Add all event listeners.
     */
    addListeners: function() {
      MyParcelFrontend.addAddressListeners();
      MyParcelFrontend.updateShippingMethod();

      var addressCheckbox = $(MyParcelFrontend.shipToDifferentAddressField).val();

      if (addressCheckbox) {
        document.querySelector(MyParcelFrontend.shipToDifferentAddressField).addEventListener('change', MyParcelFrontend.addAddressListeners);
      }

      document.addEventListener(MyParcelFrontend.updatedAddressEvent, MyParcelFrontend.onDeliveryOptionsAddressUpdate);
      document.addEventListener(MyParcelFrontend.updatedDeliveryOptionsEvent, MyParcelFrontend.onDeliveryOptionsUpdate);

      /*
       * jQuery events.
       */
      $(document.body).on(MyParcelFrontend.countryToStateChangedEvent, MyParcelFrontend.synchronizeAddress);
      $(document.body).on(MyParcelFrontend.countryToStateChangedEvent, MyParcelFrontend.updateAddress);
      $(document.body).on(MyParcelFrontend.updatedWooCommerceCheckoutEvent, MyParcelFrontend.updateShippingMethod);
    },

    /**
     * Get field by name. Will return element with MyParcelFrontend selector: "#<billing|shipping>_<name>".
     *
     * @param {String} name - The part after `shipping/billing` in the id of an element in WooCommerce.
     * @param {?String} addressType - "shipping" or "billing".
     *
     * @returns {Element}
     */
    getField: function(name, addressType) {
      if (!addressType) {
        if (!MyParcelFrontend.addressType) {
          MyParcelFrontend.getAddressType();
        }

        addressType = MyParcelFrontend.addressType;
      }

      var selector = '#' + addressType + '_' + name;
      var field = document.querySelector(selector);

      if (!field) {
        // eslint-disable-next-line no-console
        console.warn('Field ' + selector + ' not found.');
      }

      return field;
    },

    /**
     * Update address type.
     *
     * @returns {String}
     */
    getAddressType: function() {

      var useShipping = false;
      var addressCheckbox = $(MyParcelFrontend.shipToDifferentAddressField).val();

      if (addressCheckbox) {
        useShipping = document.querySelector(MyParcelFrontend.shipToDifferentAddressField).checked;
      }


      MyParcelFrontend.addressType = useShipping ? 'shipping' : 'billing';

      return MyParcelFrontend.addressType;
    },

    /**
     * Get the house number from either the house_number field or the address_1 field. If it's the address field use
     * the split street regex to extract the house number.
     *
     * @returns {String}
     */
    getHouseNumber: function() {
      var hasBillingNumber = $('#billing_' + MyParcelFrontend.houseNumberField).val() !== '';
      var hasShippingNumber = $('#shipping_' + MyParcelFrontend.houseNumberField).val() !== '';
      var hasNumber = hasBillingNumber || hasShippingNumber;

      if (MyParcelFrontend.hasSplitAddressFields() && hasNumber) {
        return MyParcelFrontend.getField(MyParcelFrontend.houseNumberField).value;
      }

      return MyParcelFrontend.getAddressParts().house_number;
    },

    /**
     * @returns {{house_number_suffix: (String | null), house_number: (String | null), street_name: (String | null)}}
     */
    getAddressParts: function() {
      var address = MyParcelFrontend.getField(MyParcelFrontend.addressField).value;
      var result = MyParcelFrontend.splitStreetRegex.exec(address);

      var parts = {};

      parts[MyParcelFrontend.streetNameField] = result ? result[1] : null;
      parts[MyParcelFrontend.houseNumberField] = result ? result[2] : null;
      parts[MyParcelFrontend.houseNumberSuffixField] = result ? result[3] : null;

      return parts;
    },

    /**
     * Trigger an event on a given element. Defaults to body.
     *
     * @param {String} identifier - Name of the event.
     * @param {String|HTMLElement|Document} [element] - Element to trigger from. Defaults to 'body'.
     */
    triggerEvent: function(identifier, element) {
      var event = document.createEvent('HTMLEvents');
      event.initEvent(identifier, true, false);
      element = !element || typeof element === 'string' ? document.querySelector(element || 'body') : element;
      element.dispatchEvent(event);
    },

    /**
     * Check if the country changed by comparing the old value with the new value before overwriting the MyParcelConfig
     *  with the new value. Returns true if none was set yet.
     *
     * @returns {Boolean}
     */
    countryHasChanged: function() {
      if (window.MyParcelConfig.address && window.MyParcelConfig.address.hasOwnProperty('cc')) {
        return window.MyParcelConfig.address.cc !== MyParcelFrontend.getField(MyParcelFrontend.countryField).value;
      }

      return true;
    },

    /**
     * Get data from form fields, put it in the global MyParcelConfig, then trigger updating the delivery options.
     */
    updateAddress: function() {
      if (!window.hasOwnProperty('MyParcelConfig')) {
        throw 'window.MyParcelConfig not found!';
      }

      if (typeof window.MyParcelConfig === 'string') {
        window.MyParcelConfig = JSON.parse(window.MyParcelConfig);
      }

      window.MyParcelConfig.address = {
        cc: MyParcelFrontend.getField(MyParcelFrontend.countryField).value,
        postalCode: MyParcelFrontend.getField(MyParcelFrontend.postcodeField).value,
        number: MyParcelFrontend.getHouseNumber(),
        city: MyParcelFrontend.getField(MyParcelFrontend.cityField).value,
      };

      if (MyParcelFrontend.hasDeliveryOptions) {
        MyParcelFrontend.triggerEvent(MyParcelFrontend.updateDeliveryOptionsEvent);
      }
    },

    /**
     * Set the values of the WooCommerce fields from delivery options data.
     *
     * @param {Object|null} address - The new address.
     */
    setAddressFromDeliveryOptions: function(address) {
      if (!address) {
        return;
      }

      if (address.postalCode) {
        MyParcelFrontend.getField(MyParcelFrontend.postcodeField).value = address.postalCode;
      }

      if (address.city) {
        MyParcelFrontend.getField(MyParcelFrontend.cityField).value = address.city;
      }

      if (address.number) {
        MyParcelFrontend.setHouseNumber(address.number);
      }
    },

    /**
     * Set the values of the WooCommerce fields. Ignores empty values.
     *
     * @param {Object|null} address - The new address.
     */
    fillCheckoutFields: function(address) {
      if (!address) {
        return;
      }

      Object
        .keys(address)
        .forEach(function(fieldName) {
          var field = MyParcelFrontend.getField(fieldName);
          var value = address[fieldName];

          if (!field || !value) {
            return;
          }

          field.value = value;
        });
    },

    /**
     * Set the house number.
     *
     * @param {String|Number} number - New house number to set.
     */
    setHouseNumber: function(number) {
      var address = MyParcelFrontend.getField(MyParcelFrontend.addressField).value;
      var oldHouseNumber = MyParcelFrontend.getHouseNumber();

      if (MyParcelFrontend.hasSplitAddressFields()) {
        if (oldHouseNumber) {
          MyParcelFrontend.getField(MyParcelFrontend.addressField).value = address.replace(oldHouseNumber, number);
        } else {
          MyParcelFrontend.getField(MyParcelFrontend.addressField).value = address + number;
        }
      } else {
        MyParcelFrontend.getField(MyParcelFrontend.houseNumberField).value = number;
      }
    },

    /**
     * Create an input field in the checkout form to be able to pass the checkout data to the $_POST variable when
     * placing the order.
     *
     * @see includes/class-wcmp-checkout.php::save_delivery_options();
     */
    injectHiddenInput: function() {
      MyParcelFrontend.hiddenDataInput = document.createElement('input');
      MyParcelFrontend.hiddenDataInput.setAttribute('hidden', 'hidden');
      MyParcelFrontend.hiddenDataInput.setAttribute('name', MyParcelDeliveryOptions.hiddenInputName);

      document.querySelector('form[name="checkout"]').appendChild(MyParcelFrontend.hiddenDataInput);
    },

    /**
     * When the delivery options module has updated the address, using the "retry" option.
     *
     * @param {CustomEvent} event - The event containing the new address.
     */
    onDeliveryOptionsAddressUpdate: function(event) {
      MyParcelFrontend.setAddressFromDeliveryOptions(event.detail);
    },

    /**
     * Update the shipping method to the new selections. Triggers hiding/showing of the delivery options.
     */
    updateShippingMethod: function() {
      var shippingMethod;
      var shippingMethodField = document.querySelectorAll(MyParcelFrontend.shippingMethodField);
      var selectedShippingMethodField = document.querySelector(MyParcelFrontend.shippingMethodField + ':checked');

      /**
       * Check if shipping method field exists. It doesn't exist if there are no shipping methods available for the
       *  current address/product combination or in general.
       *
       * If there is no shipping method the delivery options will always be hidden.
       */
      if (shippingMethodField.length) {
        shippingMethod = selectedShippingMethodField ? selectedShippingMethodField.value : shippingMethodField[0].value;

        /**
         * This shipping method will have a suffix in the checkout, but this is not present in the array of
         *  selected shipping methods from the SETTING_DELIVERY_OPTIONS_DISPLAY setting.
         *
         * All variants of flat_rate (including shipping classes) do already have their suffix set properly.
         */
        if (shippingMethod.indexOf('flat_rate') === 0) {
          var shippingClass = MyParcelFrontend.getHighestShippingClass();

          if (shippingClass) {
            shippingMethod = 'flat_rate:' + shippingClass;
          }
        }

        MyParcelFrontend.selectedShippingMethod = shippingMethod;
      } else {
        MyParcelFrontend.selectedShippingMethod = null;
      }

      MyParcelFrontend.toggleDeliveryOptions();
    },

    /**
     * Hides/shows the delivery options based on the current shipping method. Makes sure to not update the checkout
     *  unless necessary by checking if hasDeliveryOptions is true or false.
     */
    toggleDeliveryOptions: function() {
      if (MyParcelFrontend.currentShippingMethodHasDeliveryOptions()) {
        MyParcelFrontend.hasDeliveryOptions = true;
        MyParcelFrontend.triggerEvent(MyParcelFrontend.showDeliveryOptionsEvent, document);
        MyParcelFrontend.updateAddress();
      } else {
        MyParcelFrontend.hasDeliveryOptions = false;
        MyParcelFrontend.triggerEvent(MyParcelFrontend.hideDeliveryOptionsEvent, document);
      }
    },

    /**
     * Check if the currently selected shipping method is allowed to have delivery options by checking if the name
     *  starts with any value in a list of shipping methods.
     *
     * Most of the values in this list will be full shipping method names, with an instance id, but some can't have one.
     *  That's the reason we're checking if it starts with this value instead of whether it's equal.
     *
     * @returns {Boolean}
     */
    currentShippingMethodHasDeliveryOptions: function() {
      var display = false;
      var invert = false;
      var list = MyParcelFrontend.allowedShippingMethods;
      var shippingMethod = MyParcelFrontend.getSelectedShippingMethod();

      if (!shippingMethod) {
        return false;
      }

      if (shippingMethod.indexOf('free_shipping') === 0) {
        shippingMethod = 'free_shipping';
      }

      /**
       * If "all" is selected for allowed shipping methods check if the current method is NOT in the
       *  disallowedShippingMethods array.
       */
      if (MyParcelFrontend.alwaysShow) {
        list = MyParcelFrontend.disallowedShippingMethods;
        invert = true;
      }

      list.forEach(function(method) {
        var currentMethodIsAllowed = shippingMethod.indexOf(method) > -1;

        if (currentMethodIsAllowed) {
          display = true;
        }
      });

      if (invert) {
        display = !display;
      }

      return display;
    },

    /**
     * Add listeners to the address fields remove them before adding new ones if they already exist, then update
     *  shipping method and delivery options if needed.
     *
     * Uses the country field's parent row because there is no better way to catch the select2 (or selectWoo) events as
     *  we never know when the select is loaded and can't add a normal change event. The delivery options has a debounce
     *  function on the update event so it doesn't matter if we send 5 updates at once.
     */
    addAddressListeners: function() {
      var fields = [MyParcelFrontend.countryField, MyParcelFrontend.postcodeField, MyParcelFrontend.getSplitField()];

      /* If address type is already set, remove the existing listeners before adding new ones. */
      if (MyParcelFrontend.addressType) {
        fields.forEach(function(field) {
          MyParcelFrontend.getField(field).removeEventListener('change', MyParcelFrontend.updateAddress);
        });
      }

      MyParcelFrontend.getAddressType();

      fields.forEach(function(field) {
        MyParcelFrontend.getField(field).addEventListener('change', MyParcelFrontend.updateAddress);
      });

      MyParcelFrontend.updateAddress();
    },

    /**
     * Get the current shipping method without the shipping class.
     *
     * @returns {String}
     */
    getShippingMethodWithoutClass: function() {
      var shippingMethod = MyParcelFrontend.getSelectedShippingMethod();
      var indexOfSemicolon = shippingMethod.indexOf(':');

      shippingMethod = shippingMethod.substring(0, indexOfSemicolon === -1 ? shippingMethod.length : indexOfSemicolon);

      return shippingMethod;
    },

    /**
     * Get the highest shipping class by doing a call to WordPress. We're getting it this way and not from the
     *  highest_shipping_class input because that causes some kind of timing issue which makes the delivery options not
     *  show up.
     *
     * @returns {String|null}
     */
    getHighestShippingClass: function() {
      var shippingClass = null;

      $.ajax({
        type: 'POST',
        url: wcmp.ajax_url,
        async: false,
        data: {
          action: 'get_highest_shipping_class',
        },
        success: function(data) {
          shippingClass = data;
        },
      });

      return shippingClass;
    },

    /**
     * @returns {String}
     */
    getSelectedShippingMethod: function() {
      var shippingMethod = MyParcelFrontend.selectedShippingMethod;

      if (shippingMethod === 'flat_rate') {
        shippingMethod += ':' + document.querySelectorAll(MyParcelFrontend.highestShippingClassField).length;
      }

      return shippingMethod;
    },

    /**
     * Sync addresses between split and non-split address fields.
     *
     * @param {Event} event
     * @param {String} newCountry
     */
    synchronizeAddress: function(event, newCountry) {
      if (!MyParcelFrontend.isUsingSplitAddressFields) {
        return;
      }

      var data = $('form').serializeArray();

      ['shipping', 'billing'].forEach(function(addressType) {
        var typeCountry = data.find(function(item) {
          return item.name === addressType + '_country';
        });
        var hasAddressTypeCountry = MyParcelFrontend.previousCountry.hasOwnProperty(addressType);
        var countryChanged = MyParcelFrontend.previousCountry[addressType] !== newCountry;

        var addressField = MyParcelFrontend.getField(MyParcelFrontend.addressField, addressType);
        var houseNumberField = MyParcelFrontend.getField(MyParcelFrontend.houseNumberField, addressType);
        var houseNumberSuffixField = MyParcelFrontend.getField(MyParcelFrontend.houseNumberSuffixField, addressType);
        var streetNameField = MyParcelFrontend.getField(MyParcelFrontend.streetNameField, addressType);

        if (!hasAddressTypeCountry || countryChanged) {
          MyParcelFrontend.previousCountry[addressType] = typeCountry.value;
        }

        if (!countryChanged) {
          return;
        }

        if (MyParcelFrontend.hasSplitAddressFields(newCountry)) {
          var parts = MyParcelFrontend.getAddressParts();

          MyParcelFrontend.fillCheckoutFields(parts);
        } else {
          var number = houseNumberField.value || '';
          var street = streetNameField.value || '';
          var suffix = houseNumberSuffixField.value || '';

          MyParcelFrontend.fillCheckoutFields({
            address_1: (street + ' ' + number + suffix).trim(),
          });
        }

        MyParcelFrontend.updateAddress();
      });
    },

    /**
     * @param {?String} country
     *
     * @returns {Boolean}
     */
    hasSplitAddressFields: function(country) {
      if (!country) {
        country = MyParcelFrontend.getField(MyParcelFrontend.countryField).value;
      }

      if (!MyParcelFrontend.isUsingSplitAddressFields) {
        return false;
      }

      return MyParcelFrontend.splitAddressFieldsCountries.includes(country.toUpperCase());
    },
  };

  window.MyParcelFrontend = MyParcelFrontend;
  MyParcelFrontend.init();
});
