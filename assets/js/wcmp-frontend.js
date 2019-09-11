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
   * @property {Boolean} MyParcelDeliveryOptions.alwaysDisplay
   * @property {String} MyParcelDeliveryOptions.hiddenInputName
   *
   * @see \wcmp_checkout::inject_delivery_options_variables
   */

  /* The timeout is necessary, otherwise the order summary is going to flash */
  setTimeout(function() {
    var event = document.createEvent('HTMLEvents');
    event.initEvent('change', true, false);
    document.querySelectorAll('.country_to_state').forEach(function(selector) {
      selector.dispatchEvent(event);
    });
  }, 100);

  var MyParcelFrontend = {
    /**
     * @type RegExp
     */
    splitStreetRegex: /(.*?)\s?(\d{1,4})[/\s-]{0,2}([A-z]\d{1,3}|-\d{1,4}|\d{2}\w{1,2}|[A-z][A-z\s]{0,3})?$/,

    /**
     * @type Boolean
     */
    isUsingSplitAddressFields: !!parseInt(MyParcelDisplaySettings.isUsingSplitAddressFields),

    /**
     * @type Array
     */
    shippingMethods: JSON.parse(MyParcelDeliveryOptions.shippingMethods),

    /**
     * @type Boolean
     */
    alwaysDisplay: !!parseInt(MyParcelDeliveryOptions.alwaysDisplay),

    /**
     * @type Boolean
     */
    forceUpdate: false,

    /**
     * @type Boolean
     */
    changedShippingMethod: false,

    /**
     * @type Boolean
     */
    selectedCountry: false,

    /**
     * @type Boolean
     */
    selectedShippingMethod: false,

    /**
     * @type Boolean
     */
    updatedCountry: false,

    /**
     * @type Boolean
     */
    updatedShippingMethod: false,

    /**
     * @type Element
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

    houseNumberField: 'house_number',
    addressField: 'address_1',
    countryField: 'country',
    postcodeField: 'postcode',

    updateCheckoutEvent: 'myparcel_update_checkout',
    updatedCheckoutEvent: 'myparcel_updated_checkout',
    updatedAddressEvent: 'myparcel_address_updated',

    updateWooCommerceCheckoutEvent: 'update_checkout',

    /**
     * Initialize the script.
     */
    init: function() {
      this.addListeners();
      this.createHiddenInput();

      document.querySelector(this.shipToDifferentAddressField).addEventListener('load', this.addListeners);
      document.querySelector(this.shipToDifferentAddressField).addEventListener('change', this.addListeners);

      document.addEventListener(this.updatedAddressEvent, function(event) {
        this.setAddress(event.detail);
      });

      document.addEventListener(this.updatedCheckoutEvent, this.onCheckoutUpdate);
    },

    /**
     * When the checkout is updated trigger the WooCommerce update_checkout event.
     */
    onCheckoutUpdate: function(event) {
      MyParcelFrontend.hiddenDataInput.value = JSON.stringify(event.detail);

      MyParcelFrontend.triggerEvent(MyParcelFrontend.updateWooCommerceCheckoutEvent);
    },

    /**
     * Update the #mypa-input with new data.
     *
     * @param {Object} content - Content that will be converted to JSON string.
     */
    updateInput: function(content) {
      content = content || '';
      document.querySelector('#mypa-input').value = JSON.stringify(content);
    },

    /**
     * If split fields are used add house number to the fields. Otherwise use address line 1.
     *
     * @return {string}
     */
    getSplitField: function() {
      return this.isUsingSplitAddressFields ? MyParcelFrontend.houseNumberField : MyParcelFrontend.addressField;
    },

    updateCountry: function() {
      MyParcelFrontend.updatedCountry = MyParcelFrontend.getField('country').value;
    },

    /**
     * Add event listeners to the address fields. Remove them first if they already exist.
     */
    addListeners: function() {
      /* The fields to add listeners to. */
      var fields = [MyParcelFrontend.countryField, MyParcelFrontend.postcodeField, this.getSplitField()];

      /* If address type is already set, remove the existing listeners before adding new ones. */
      if (MyParcelFrontend.addressType) {
        MyParcelFrontend.getField(MyParcelFrontend.countryField).removeEventListener(
          'change',
          MyParcelFrontend.updateCountry,
        );

        fields.forEach(function(field) {
          MyParcelFrontend.getField(field).removeEventListener('change', MyParcelFrontend.updateAddress);
        });
      }

      MyParcelFrontend.getAddressType();
      MyParcelFrontend.selectedCountry = MyParcelFrontend.getField(MyParcelFrontend.countryField).value;

      MyParcelFrontend.getField(MyParcelFrontend.countryField).addEventListener(
        'change',
        MyParcelFrontend.updateCountry,
      );

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
     * @return {String}
     */
    getHouseNumber: function() {
      if (MyParcelFrontend.isUsingSplitAddressFields) {
        return MyParcelFrontend.getField('house_number').value;
      }

      var address = MyParcelFrontend.getField('address_1').value;
      var result = MyParcelFrontend.splitStreetRegex.exec(address);
      var numberIndex = 2;

      return result ? result[numberIndex] : null;
    },

    /**
     * @return {boolean}
     */
    checkCountry: function() {
      if (MyParcelFrontend.updatedCountry !== false
        && MyParcelFrontend.updatedCountry !== MyParcelFrontend.selectedCountry
      ) {
        this.updateAddress();
        MyParcelFrontend.triggerEvent(MyParcelFrontend.updateCheckoutEvent);
        MyParcelFrontend.selectedCountry = MyParcelFrontend.updatedCountry;
      }

      if (MyParcelFrontend.selectedCountry !== 'NL' && MyParcelFrontend.selectedCountry !== 'BE') {
        MyParcelFrontend.hideDeliveryOptions();
        return false;
      }

      return true;
    },

    /**
     *
     * @return {*}
     */
    getShippingMethod: function() {
      var shipping_method;
      /* check if shipping is user choice or fixed */
      if (document.querySelector('#order_review .shipping_method').length > 1) {
        shipping_method = document.querySelector('#order_review .shipping_method:checked').value;
      } else {
        shipping_method = document.querySelector('#order_review .shipping_method').value;
      }
      return shipping_method;
    },

    /**
     * Tell the checkout to hide itself.
     */
    hideDeliveryOptions: function() {
      this.triggerEvent('myparcel_hide_checkout');
      if (MyParcelFrontend.isUpdated()) {
        this.triggerEvent('update_checkout');
      }
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
     *
     * @return {boolean}
     */
    isUpdated: function() {
      if (MyParcelFrontend.updatedCountry !== MyParcelFrontend.selectedCountry
        || MyParcelFrontend.forceUpdate === true) {
        MyParcelFrontend.forceUpdate = false; /* only force once */
        return true;
      }

      return false;
    },

    /**
     * Get data from form fields and put it in the global MyParcelConfig.
     */
    updateAddress: function() {
      var data = JSON.parse(window.MyParcelConfig);

      data.address = {
        cc: MyParcelFrontend.getField('country').value,
        postalCode: MyParcelFrontend.getField('postcode').value,
        number: MyParcelFrontend.getHouseNumber(),
        city: MyParcelFrontend.getField('city').value,
      };

      window.MyParcelConfig = JSON.stringify(data);
      MyParcelFrontend.triggerEvent('myparcel_update_checkout');
    },

    /**
     * Set the values of the WooCommerce fields.
     *
     * @param {Object} address
     */
    setAddress: function(address) {
      if (address.postalCode) {
        MyParcelFrontend.getField('postcode').value = address.postalCode;
      }

      if (address.city) {
        MyParcelFrontend.getField('city').value = address.city;
      }

      if (address.number) {
        MyParcelFrontend.setHouseNumber(address.number);
      }
    },

    /**
     * Set the house number.
     *
     * @param {String|Number} number
     */
    setHouseNumber: function(number) {
      if (MyParcelFrontend.isUsingSplitAddressFields) {
        var address = MyParcelFrontend.getField('address_1').value;
        var oldHouseNumber = MyParcelFrontend.getHouseNumber();

        if (oldHouseNumber) {
          MyParcelFrontend.getField('address_1').value = address.replace(oldHouseNumber, number);
        } else {
          MyParcelFrontend.getField('address_1').value = address + number;
        }
      } else {
        MyParcelFrontend.getField('number').value = number;
      }
    },

    /**
     * Create an input field in the checkout form to be able to pass the checkout data to the $_POST variable when
     * placing the order.
     *
     * @see includes/class-wcmp-checkout.php::save_delivery_options();
     */
    createHiddenInput() {
      MyParcelFrontend.hiddenDataInput = document.createElement('input');
      MyParcelFrontend.hiddenDataInput.setAttribute('hidden', true);
      MyParcelFrontend.hiddenDataInput.setAttribute('name', MyParcelDeliveryOptions.hiddenInputName);
      document.querySelector('form[name="checkout"]').appendChild(MyParcelFrontend.hiddenDataInput);
    },
  };

  MyParcelFrontend.init();
  window.MyParcelFrontend = MyParcelFrontend;
});

