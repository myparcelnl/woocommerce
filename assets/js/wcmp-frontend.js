/* eslint-disable max-lines-per-function */
window.addEventListener('load', function() {
  /**
   * The following jsdoc blocks are for declaring the types of the injected variables from php.
   */

  /**
   * @var {Object} MyParcelDisplaySettings
   *
   * @property {Boolean} MyParcelDisplaySettings.isUsingSplitAddressFields
   *
   * @see \wcmp_checkout::inject_delivery_options_variables
   */

  /**
   * @var {Object} MyParcelDeliveryOptions
   *
   * @property {Array} MyParcelDeliveryOptions.shippingMethods
   * @property {String} MyParcelDeliveryOptions.alwaysDisplay
   * @property {String} MyParcelDeliveryOptions.hiddenInputName
   *
   * @see \wcmp_checkout::inject_delivery_options_variables
   */

  var MyParcelFrontend = {
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
    shippingMethods: JSON.parse(MyParcelDeliveryOptions.shippingMethods),

    /**
     * @type {Boolean}
     */
    alwaysDisplay: !!parseInt(MyParcelDeliveryOptions.alwaysDisplay),

    /**
     * @type {Boolean}
     */
    forceUpdate: false,

    /**
     * @type {Boolean}
     */
    changedShippingMethod: false,

    /**
     * @type {Boolean}
     */
    selectedCountry: false,

    /**
     * @type {Boolean}
     */
    selectedShippingMethod: false,

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
    countryField: 'country',
    houseNumberField: 'house_number',
    postcodeField: 'postcode',

    updateDeliveryOptionsEvent: 'myparcel_update_delivery_options',
    updatedDeliveryOptionsEvent: 'myparcel_updated_delivery_options',
    updatedAddressEvent: 'myparcel_updated_address',

    updateWooCommerceCheckoutEvent: 'update_checkout',

    /**
     * Initialize the script.
     */
    init: function() {
      this.addListeners();
      this.createHiddenInput();

      document.querySelector(this.shipToDifferentAddressField).addEventListener('load', this.addListeners);
      document.querySelector(this.shipToDifferentAddressField).addEventListener('change', this.addListeners);

      document.addEventListener(this.updatedAddressEvent, this.onDeliveryOptionsAddressUpdate);

      document.addEventListener(this.updatedDeliveryOptionsEvent, this.onDeliveryOptionsUpdate);
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
      /* The fields to add listeners to. */
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
     * Trigger an event on the document body.
     *
     * @param {String} identifier - Name of the event.
     */
    triggerEvent: function(identifier) {
      var event = document.createEvent('HTMLEvents');
      event.initEvent(identifier, true, false);
      document.querySelector('body').dispatchEvent(event);
    },

    /**
     * Get data from form fields and put it in the global MyParcelConfig.
     */
    updateAddress: function(e) {
      console.log('updated', e);
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
    createHiddenInput: function() {
      MyParcelFrontend.hiddenDataInput = document.createElement('input');
      MyParcelFrontend.hiddenDataInput.setAttribute('hidden', true);
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
  };

  MyParcelFrontend.init();
  window.MyParcelFrontend = MyParcelFrontend;
});

