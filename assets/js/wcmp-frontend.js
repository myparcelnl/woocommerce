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
 * @property {String} MyParcelDeliveryOptions.alwaysShow
 * @property {String} MyParcelDeliveryOptions.hiddenInputName
 * @see \wcmp_checkout::inject_delivery_options_variables
 */

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
   * @type {Boolean}
   */
  alwaysShow: Boolean(parseInt(MyParcelDeliveryOptions.alwaysShow)),

  /**
   * @type {Boolean}
   */
  changedShippingMethod: false,

  /**
   * @type {Boolean}
   */
  selectedCountry: false,

  /**
   * @type {String}
   */
  selectedShippingMethod: null,

  /**
   * @type {Boolean}
   */
  updatedCountry: false,

  /**
   * @type {Boolean}
   */
  updatedShippingMethod: false,

  /**
   * @type {Element}
   */
  hiddenDataInput: null,

  /**
   * @type {String}
   */
  addressType: null,

  /**
   * Ship to different address field.
   *
   * @type {String}
   */
  shipToDifferentAddressField: '#ship-to-different-address-checkbox',

  addressField: 'address_1',
  cityField: 'city',
  countryRow: 'country_field',
  countryField: 'country',
  houseNumberField: 'house_number',
  postcodeField: 'postcode',

  updateDeliveryOptionsEvent: 'myparcel_update_delivery_options',
  updatedDeliveryOptionsEvent: 'myparcel_updated_delivery_options',
  updatedAddressEvent: 'myparcel_updated_address',

  showDeliveryOptionsEvent: 'myparcel_show_delivery_options',
  hideDeliveryOptionsEvent: 'myparcel_hide_delivery_options',

  updateWooCommerceCheckoutEvent: 'update_checkout',

  shippingMethodField: '[name^="shipping_method["]',

  /**
   * Initialize the script.
   */
  init: function() {
    this.addListeners();
    this.injectHiddenInput();
    this.updateShippingMethod();
  },

  /**
   * When the delivery options are updated trigger the WooCommerce update_checkout event.
   *
   * @param {CustomEvent} event - The update event.
   */
  onDeliveryOptionsUpdate: function(event) {
    MyParcelFrontend.hiddenDataInput.value = JSON.stringify(event.detail);

    MyParcelFrontend.triggerEvent(MyParcelFrontend.updateWooCommerceCheckoutEvent);
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
   * Add event listeners to the address fields. Remove them first if they already exist.
   */
  addListeners: function() {
    MyParcelFrontend.addAddressListeners();

    document.querySelector(this.shipToDifferentAddressField).addEventListener('load', this.addListeners);
    document.querySelector(this.shipToDifferentAddressField).addEventListener('change', this.addListeners);

    document.querySelector('form[name="checkout"]').addEventListener('change', this.onFormChange);

    document.addEventListener(this.updatedAddressEvent, this.onDeliveryOptionsAddressUpdate);
    document.addEventListener(this.updatedDeliveryOptionsEvent, this.onDeliveryOptionsUpdate);
  },

  /**
   * Get field by name. Will return element with this selector: "#<billing|shipping>_<name>".
   *
   * @param {string} name - The part after `shipping/billing` in the id of an element in WooCommerce.
   *
   * @returns {Element}
   */
  getField: function(name) {
    return document.querySelector('#' + MyParcelFrontend.addressType + '_' + name);
  },

  /**
   * Update address type.
   */
  getAddressType: function() {
    this.addressType = document.querySelector(MyParcelFrontend.shipToDifferentAddressField).checked
      ? 'shipping'
      : 'billing';
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
   * Get data from form fields and put it in the global MyParcelConfig.
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

    MyParcelFrontend.triggerEvent(MyParcelFrontend.updateDeliveryOptionsEvent);
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
    this.setAddress(event.detail);
  },

  /**
   * Update the shipping method to the new selections. Triggers hiding/showing of the delivery options.
   */
  updateShippingMethod: function() {
    var shipping_method;
    var shippingMethodField = document.querySelector('#order_review .shipping_method');
    var selectedShippingMethodField = document.querySelector('#order_review .shipping_method:checked');

    /**
     * Check if shipping method field exists. It doesn't exist if there are no shipping methods available for the
     *  current address/product combination or in general.
     *
     * If there is no shipping method the delivery options will always be hidden.
     */
    if (shippingMethodField) {
      shipping_method = selectedShippingMethodField ? selectedShippingMethodField.value : shippingMethodField.value;

      MyParcelFrontend.selectedShippingMethod = shipping_method;
    }

    MyParcelFrontend.toggleDeliveryOptions();
  },

  /**
   * Hides/shows the delivery options based on the current shipping method. Makes sure to not update the checkout
   * unless necessary by checking if hasDeliveryOptions is true or false.
   */
  toggleDeliveryOptions: function() {
    if (!MyParcelFrontend.selectedShippingMethod) {
      this.hasDeliveryOptions = false;
      return;
    }

    if (MyParcelFrontend.currentShippingMethodHasDeliveryOptions() && !this.hasDeliveryOptions) {
      this.hasDeliveryOptions = true;
      MyParcelFrontend.triggerEvent(MyParcelFrontend.showDeliveryOptionsEvent, document);
    } else if (this.hasDeliveryOptions) {
      this.hasDeliveryOptions = false;
      MyParcelFrontend.triggerEvent(MyParcelFrontend.hideDeliveryOptionsEvent, document);
    }
  },

  /**
   * Check if the currently selected shipping method is allowed to have delivery options.
   *
   * @returns {Boolean}
   */
  currentShippingMethodHasDeliveryOptions: function() {
    var display = false;

    if (MyParcelFrontend.alwaysShow) {
      return true;
    }

    MyParcelFrontend.allowedShippingMethods.forEach(function(method) {
      /**
       * If the type of the given method is enabled in its entirety.
       */
      var currentMethodGroupIsAllowed = method.indexOf(MyParcelFrontend.getShippingMethodWithoutClass()) > -1;

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

    return display;
  },

  /**
   * Fired on form change. Used right now to check for shipping method because these inputs just won't work with
   *  regular listeners...
   *
   * @param {Event} e - Event.
   */
  onFormChange: function(e) {
    /**
     * @type {HTMLInputElement}
     */
    var el = e.target;
    var name = el.getAttribute('name');

    if (name.startsWith('shipping_method')) {
      MyParcelFrontend.updateShippingMethod();
    }
  },

  /**
   * Add listeners to the address fields, then update shipping method and delivery options if needed.
   *
   * Uses the country field's parent row because there is no better way to catch the select2 (or selectWoo) events as
   *  we never know when the select is loaded and can't add a normal change event. The delivery options has a debounce
   *  function on the update event so it doesn't matter if we send 5 updates at once.
   */
  addAddressListeners: function() {
    /*
     * The fields to add listeners to.
     */
    var fields = [MyParcelFrontend.countryRow, MyParcelFrontend.postcodeField, MyParcelFrontend.getSplitField()];

    /* If address type is already set, remove the existing listeners before adding new ones. */
    if (MyParcelFrontend.addressType) {
      fields.forEach(function(field) {
        var element = MyParcelFrontend.getField(field);

        if (MyParcelFrontend.countryRow === field) {
          element.removeEventListener('DOMSubtreeModified', MyParcelFrontend.updateCountry);
          return;
        }

        element.removeEventListener('change', MyParcelFrontend.updateAddress);
      });
    }

    MyParcelFrontend.getAddressType();

    fields.forEach(function(field) {
      var element = MyParcelFrontend.getField(field);

      if (MyParcelFrontend.countryRow === field) {
        element.addEventListener('DOMSubtreeModified', MyParcelFrontend.updateCountry);
        return;
      }

      element.addEventListener('change', MyParcelFrontend.updateAddress);
    });

    /**
     * Update the shipping method before updating the address for the first time.
     */
    MyParcelFrontend.updateShippingMethod();

    if (MyParcelFrontend.currentShippingMethodHasDeliveryOptions()) {
      MyParcelFrontend.updateAddress();
    }
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

  /**
   * When country updates, check the shipping methods before updating the address.
   */
  updateCountry: function() {
    MyParcelFrontend.updateShippingMethod();
    MyParcelFrontend.updateAddress();
  },
};

window.MyParcelFrontend = MyParcelFrontend;
MyParcelFrontend.init();
