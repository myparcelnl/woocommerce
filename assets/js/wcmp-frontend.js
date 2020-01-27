/**
 * The following jsdoc blocks are for declaring the types of the injected variables from php.
 */

/**
 * @var {Object} MyParcelDisplaySettings
 *
 * @property {String} MyParcelDisplaySettings.isUsingSplitAddressFields
 *
 * @see \wcmp_checkout::inject_delivery_options_variables
 */

/**
 * @var {Object} MyParcelDeliveryOptions
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
    isUsingSplitAddressFields: !!parseInt(MyParcelDisplaySettings.isUsingSplitAddressFields),

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

    addressField: 'address_1',
    cityField: 'city',
    countryRow: 'country_field',
    countryField: 'country',
    houseNumberField: 'house_number',
    postcodeField: 'postcode',

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

      /**
       * After the "updated_checkout" event the shipping methods will be rendered, restore the event listener and delete
       *  this one in the process.
       */
      $(document.body).on(MyParcelFrontend.updatedWooCommerceCheckoutEvent, restoreEventListener);

      function restoreEventListener() {
        $(document.body).on(MyParcelFrontend.updatedWooCommerceCheckoutEvent, MyParcelFrontend.updateShippingMethod);
        $(document.body).off(MyParcelFrontend.updatedWooCommerceCheckoutEvent, restoreEventListener);
      };
    },

    /**
     * If split fields are used add house number to the fields. Otherwise use address line 1.
     *
     * @returns {string}
     */
    getSplitField: function() {
      return MyParcelFrontend.isUsingSplitAddressFields
        ? MyParcelFrontend.houseNumberField
        : MyParcelFrontend.addressField;
    },

    /**
     * Add all event listeners.
     */
    addListeners: function() {
      MyParcelFrontend.addAddressListeners();
      MyParcelFrontend.updateShippingMethod();

      document.querySelector(MyParcelFrontend.shipToDifferentAddressField)
        .addEventListener('change', MyParcelFrontend.addAddressListeners);

      document.addEventListener(MyParcelFrontend.updatedAddressEvent, MyParcelFrontend.onDeliveryOptionsAddressUpdate);
      document.addEventListener(MyParcelFrontend.updatedDeliveryOptionsEvent, MyParcelFrontend.onDeliveryOptionsUpdate);

      /*
       * jQuery events.
       */
      $(document.body).on(MyParcelFrontend.countryToStateChangedEvent, MyParcelFrontend.updateAddress);
      $(document.body).on(MyParcelFrontend.updatedWooCommerceCheckoutEvent, MyParcelFrontend.updateShippingMethod);
    },

    /**
     * Get field by name. Will return element with MyParcelFrontend selector: "#<billing|shipping>_<name>".
     *
     * @param {string} name - The part after `shipping/billing` in the id of an element in WooCommerce.
     *
     * @returns {Element}
     */
    getField: function(name) {
      if (!MyParcelFrontend.addressType) {
        MyParcelFrontend.getAddressType();
      }

      return document.querySelector('#' + MyParcelFrontend.addressType + '_' + name);
    },

    /**
     * Update address type.
     */
    getAddressType: function() {
      var useShipping = document.querySelector(MyParcelFrontend.shipToDifferentAddressField).checked;

      MyParcelFrontend.addressType = useShipping ? 'shipping' : 'billing';
    },

    /**
     * Get the house number from either the house_number field or the address_1 field. If it's the address field use
     * the split street regex to extract the house number.
     *
     * @returns {String}
     */
    getHouseNumber: function() {
      var address = MyParcelFrontend.getField(MyParcelFrontend.addressField).value;
      var result = MyParcelFrontend.splitStreetRegex.exec(address);
      var numberIndex = 2;

      if (MyParcelFrontend.isUsingSplitAddressFields) {
        return MyParcelFrontend.getField(MyParcelFrontend.houseNumberField).value;
      }

      return result ? result[numberIndex] : null;
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
     * Set the values of the WooCommerce fields.
     *
     * @param {Object} address - The new address.
     */
    setAddress: function(address) {
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
     * Set the house number.
     *
     * @param {String|Number} number - New house number to set.
     */
    setHouseNumber: function(number) {
      var address = MyParcelFrontend.getField(MyParcelFrontend.addressField).value;
      var oldHouseNumber = MyParcelFrontend.getHouseNumber();

      if (MyParcelFrontend.isUsingSplitAddressFields) {
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
      MyParcelFrontend.setAddress(event.detail);
    },

    /**
     * Update the shipping method to the new selections. Triggers hiding/showing of the delivery options.
     */
    updateShippingMethod: function() {
      var shipping_method;
      var shippingMethodField = document.querySelectorAll(MyParcelFrontend.shippingMethodField);
      var selectedShippingMethodField = document.querySelector(MyParcelFrontend.shippingMethodField + ':checked');

      /**
       * Check if shipping method field exists. It doesn't exist if there are no shipping methods available for the
       *  current address/product combination or in general.
       *
       * If there is no shipping method the delivery options will always be hidden.
       */
      if (shippingMethodField.length) {
        shipping_method = selectedShippingMethodField ? selectedShippingMethodField.value : shippingMethodField[0].value;
        MyParcelFrontend.selectedShippingMethod = shipping_method;
      } else {
        MyParcelFrontend.selectedShippingMethod = null;
      }

      MyParcelFrontend.toggleDeliveryOptions();
    },

    /**
     * Hides/shows the delivery options based on the current shipping method. Makes sure to not update the checkout
     * unless necessary by checking if hasDeliveryOptions is true or false.
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
     * Check if the currently selected shipping method is allowed to have delivery options.
     *
     * @returns {Boolean}
     */
    currentShippingMethodHasDeliveryOptions: function() {
      var currentClass;
      var display = false;
      var invert = false;
      var list = MyParcelFrontend.allowedShippingMethods;

      if (MyParcelFrontend.selectedShippingMethod) {
        currentClass = MyParcelFrontend.getShippingMethodWithoutClass();
      } else {
        return false;
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
        /**
         * If the type of the given method is enabled in its entirety.
         */
        var currentMethodGroupIsAllowed = method.indexOf(currentClass) > -1;

        /**
         * If the specific method is enabled.
         *
         * @type {boolean}
         */
        var currentMethodIsAllowed = method.indexOf(MyParcelFrontend.selectedShippingMethod) > -1;

        if (currentMethodGroupIsAllowed || currentMethodIsAllowed) {
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
      var shippingMethod = MyParcelFrontend.selectedShippingMethod;
      var indexOfSemicolon = shippingMethod.indexOf(':');

      shippingMethod = shippingMethod.substring(0, indexOfSemicolon === -1 ? shippingMethod.length : indexOfSemicolon);

      return shippingMethod;
    },
  };

  /**
   * Debounce function. Copied from below link.
   *
   * @see https://stackoverflow.com/a/6658537/10225966
   *
   * @param {Function} func - Function to debounce.
   * @param {Number?} threshold - Timing.
   * @param {Boolean?} execAsap - Skips the timeout.
   *
   * @returns {function}
   */
  function debounce(func, threshold, execAsap) {
    var timeout;

    return function debounced() {
      var obj = this;
      var args = arguments;

      function delayed() {
        if (!execAsap) {
          func.apply(obj, args);
        }
        timeout = null;
      };

      if (timeout) {
        clearTimeout(timeout);
      } else if (execAsap) {
        func.apply(obj, args);
      }

      timeout = setTimeout(delayed, threshold || 100);
    };
  }

  window.MyParcelFrontend = MyParcelFrontend;
  MyParcelFrontend.init();
});
