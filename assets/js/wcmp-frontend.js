/* eslint-disable max-lines-per-function */
window.addEventListener('load', function() {

  if (!window.hasOwnProperty('MyParcel')) {
    return
  }

  /* The timeout is necessary, otherwise the order summary is going to flash */
  setTimeout(function() {
    var event = document.createEvent('HTMLEvents');
    event.initEvent('change', true, false);
    document.querySelectorAll('.country_to_state').forEach(function(selector) {
      selector.dispatchEvent(event)
    });
  }, 100);

  var MyParcel_Frontend = {
    split_street_regex: /(.*?)\s?(\d{1,4})[/\s-]{0,2}([A-z]\d{1,3}|-\d{1,4}|\d{2}\w{1,2}|[A-z][A-z\s]{0,3})?$/,
    is_using_split_address_fields: parseInt(wcmp_display_settings.isUsingSplitAddressFields),

    // checkout_updating: false,
    shipping_method_changed: false,
    force_update: false,

    selected_shipping_method: false,
    updated_shipping_method: false,
    selected_country: false,
    updated_country: false,

    shipping_methods: JSON.parse(wcmp_delivery_options.shipping_methods),
    always_display: wcmp_delivery_options.always_display,

    /**
     * @type {Element}
     */
    shippingFields: document.querySelector('.woocommerce-shipping-fields'),

    /**
     * @type {String}
     */
    addressType: null,

    /**
     * Ship to different address field
     *
     * @type {String}
     */
    shipToDifferentAddressField: '#ship-to-different-address-checkbox',
    checkoutDataField: '#mypa-input',

    houseNumberField: 'house_number',
    addressField: 'address_1',
    countryField: 'country',
    postcodeField: 'postcode',

    updateCheckoutEvent: 'myparcel_update_checkout',
    updatedCheckoutEvent: 'myparcel_checkout_updated',
    updatedAddressEvent: 'address_updated',

    /**
     * Initialize the script.
     */
    init: function() {
      MyParcel_Frontend.addListeners();

      document.querySelector(this.shipToDifferentAddressField).addEventListener('load', this.addListeners);
      document.querySelector(this.shipToDifferentAddressField).addEventListener('change', this.addListeners);

      document.addEventListener(this.updatedAddressEvent, function(event) {
        this.setAddress(event.detail);
      });

      document.addEventListener(this.updatedCheckoutEvent, function() {
        console.warn(MyParcel_Frontend.updatedCheckoutEvent, document.querySelector(this.checkoutDataField).value);
      });

      /**
       * Hide checkout options for non parcel shipments.
       */
      function showOrHideCheckoutOptions() {
        console.log('showOrHideCheckoutOptions');
        // MyParcel_Frontend.checkout_updating = false; /* done updating */
        var shipping_method_class;

        console.log('MyParcel_Frontend.checkCountry()', MyParcel_Frontend.checkCountry());
        if (!MyParcel_Frontend.checkCountry()) {
          return;
        }

        if (MyParcel_Frontend.always_display) {
          MyParcel_Frontend.force_update = true;
          this.triggerEvent('myparcel_update_checkout');
        } else if (MyParcel_Frontend.shipping_methods.length > 0) {
          var shipping_method = MyParcel_Frontend.getShippingMethod();

          /* no shipping method selected, hide by default */
          if (typeof shipping_method === 'undefined') {
            MyParcel_Frontend.hideDeliveryOptions();
            return;
          }

          if (shipping_method.indexOf('table_rate:') !== -1 || shipping_method.indexOf('betrs_shipping:') !== -1) {
            /* WC Table Rates
                         * use shipping_method = method_id:instance_id:rate_id */
            if (shipping_method.indexOf('betrs_shipping:') !== -1) {
              shipping_method = shipping_method.replace(":", "_");
            }
          } else {
            /* none table rates
                         * strip instance_id if present */
            if (shipping_method.indexOf(':') !== -1) {
              shipping_method = shipping_method.substring(0, shipping_method.indexOf(':'));
            }
            var shipping_class = document.querySelector('#myparcel_highest_shipping_class').value;
            /* add class refinement if we have a shipping class */
            if (shipping_class) {
              shipping_method_class = shipping_method + ':' + shipping_class;
            }
          }

          if (shipping_class && MyParcel_Frontend.shipping_methods.indexOf(shipping_method_class) > -1) {
            MyParcel_Frontend.updated_shipping_method = shipping_method_class;
            MyParcel.showAllDeliveryOptions();
            MyParcel_Frontend.myparcel_selected_shipping_method = shipping_method_class;
          } else if (MyParcel_Frontend.shipping_methods.indexOf(shipping_method) > -1) {
            /* fallback to bare method if selected in settings */
            MyParcel_Frontend.myparcel_updated_shipping_method = shipping_method;
            MyParcel.showAllDeliveryOptions();
            MyParcel_Frontend.myparcel_selected_shipping_method = shipping_method;
          } else {
            var shipping_method_now = typeof shipping_method_class === 'undefined'
              ? shipping_method
              : shipping_method_class;

            MyParcel_Frontend.myparcel_updated_shipping_method = shipping_method_now;
            MyParcel_Frontend.hideDeliveryOptions();

            MyParcel_Frontend.updateInput();

            MyParcel_Frontend.myparcel_selected_shipping_method = shipping_method_now;

            /* Hide extra fees when selecting local pickup */
            if (MyParcel_Frontend.shipping_method_changed === false) {
              MyParcel_Frontend.shipping_method_changed = true;

              /* Update woocommerce checkout when selecting other method */
              MyParcel_Frontend.triggerEvent('update_checkout');

              /* Only update when the method change after 2seconds */
              setTimeout(function() {
                MyParcel_Frontend.shipping_method_changed = false;
              }, 2000);
            }
          }
        } else {
          /* not sure if we should already hide by default? */
          MyParcel_Frontend.hideDeliveryOptions();
          MyParcel_Frontend.updateInput();
        }
      }

      /* hide checkout options for non parcel shipments */
      document.addEventListener('updated_checkout', showOrHideCheckoutOptions);
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
      return this.is_using_split_address_fields ? MyParcel_Frontend.houseNumberField : MyParcel_Frontend.addressField;
    },

    updateCountry: function() {
      MyParcel_Frontend.updated_country = MyParcel_Frontend.getField('country').value;
    },

    /**
     * Add event listeners to the address fields. Remove them first if they already exist.
     */
    addListeners: function() {
      // The fields to add listeners to.
      var fields = [MyParcel_Frontend.countryField, MyParcel_Frontend.postcodeField, this.getSplitField()];

      // If address type is already set, remove the existing listeners before adding new ones.
      if (MyParcel_Frontend.addressType) {
        MyParcel_Frontend.getField(MyParcel_Frontend.countryField).removeEventListener(
          'change',
          MyParcel_Frontend.updateCountry
        );

        fields.forEach(function(field) {
          MyParcel_Frontend.getField(field).removeEventListener('change', MyParcel_Frontend.update_settings);
        })
      }

      MyParcel_Frontend.getAddressType();
      MyParcel_Frontend.selected_country = MyParcel_Frontend.getField(MyParcel_Frontend.countryField).value;

      MyParcel_Frontend.getField(MyParcel_Frontend.countryField).addEventListener(
        'change',
        MyParcel_Frontend.updateCountry
      );

      fields.forEach(function(field) {
        MyParcel_Frontend.getField(field).addEventListener('change', MyParcel_Frontend.update_settings);
      });

      MyParcel_Frontend.update_settings();
    },

    /**
     * Get field by name. Will return element with this selector: "#<billing|shipping>_<name>".
     *
     * @param {string} name - The part after `shipping/billing` in the id of an element in WooCommerce.
     *
     * @returns {Element}
     */
    getField: function(name) {
      return document.querySelector('#' + MyParcel_Frontend.addressType + '_' + name);
    },

    /**
     * Update address type.
     */
    getAddressType: function() {
      this.addressType = document.querySelector(MyParcel_Frontend.shipToDifferentAddressField).checked
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
      if (MyParcel_Frontend.is_using_split_address_fields) {
        return MyParcel_Frontend.getField('house_number').value;
      }

      var address = MyParcel_Frontend.getField('address_1').value;
      var result = MyParcel_Frontend.split_street_regex.exec(address);
      var numberIndex = 2;

      return result ? result[numberIndex] : null;
    },

    /**
     * @return {boolean}
     */
    checkCountry: function() {
      console.log('checkCountry');
      if (MyParcel_Frontend.updated_country !== false
        && MyParcel_Frontend.updated_country !== MyParcel_Frontend.selected_country
        // && !isEmptyObject(window.MyParcel.data)
      ) {
        this.update_settings();
        MyParcel_Frontend.triggerEvent(MyParcel_Frontend.updateCheckoutEvent);
        MyParcel_Frontend.selected_country = MyParcel_Frontend.updated_country;
      }

      if (MyParcel_Frontend.selected_country !== 'NL' && MyParcel_Frontend.selected_country !== 'BE') {
        MyParcel_Frontend.hideDeliveryOptions();
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
      if (MyParcel_Frontend.isUpdated()) {
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
      if (MyParcel_Frontend.updated_country !== MyParcel_Frontend.selected_country
        || MyParcel_Frontend.force_update === true) {
        MyParcel_Frontend.force_update = false; /* only force once */
        return true;
      }

      return false;
    },

    /**
     * Get data from form fields and put it in the global MyParcelConfig.
     */
    update_settings: function() {
      var data = JSON.parse(window.MyParcelConfig);

      data.address = {
        cc: MyParcel_Frontend.getField('country').value,
        postalCode: MyParcel_Frontend.getField('postcode').value,
        number: MyParcel_Frontend.getHouseNumber(),
        city: MyParcel_Frontend.getField('city').value,
      };

      window.MyParcelConfig = JSON.stringify(data);
      MyParcel_Frontend.triggerEvent('myparcel_update_checkout');
    },

    /**
     * Set the values of the WooCommerce fields.
     * @param {Object} address
     */
    setAddress: function(address) {
      if (address.postalCode) {
        MyParcel_Frontend.getField('postcode').value = address.postalCode;
      }

      if (address.city) {
        MyParcel_Frontend.getField('city').value = address.city
      }

      if (address.number) {
        MyParcel_Frontend.setHouseNumber(address.number);
      }
    },

    /**
     * Set the house number.
     *
     * @param {String|Number} number
     */
    setHouseNumber: function(number) {
      if (MyParcel_Frontend.is_using_split_address_fields) {
        var address = MyParcel_Frontend.getField('address_1').value;
        var oldHouseNumber = MyParcel_Frontend.getHouseNumber();

        console.log(oldHouseNumber);
        if (oldHouseNumber) {
          MyParcel_Frontend.getField('address_1').value = address.replace(oldHouseNumber, number);
        } else {
          MyParcel_Frontend.getField('address_1').value = address + number;
        }
      } else {
        MyParcel_Frontend.getField('number').value = number;
      }
    },
  };

  /**
   * Check if given variable is an empty object.
   *
   * @param {Object} obj - Object to check.
   *
   * @returns {boolean}
   */
  function isEmptyObject(obj) {
    return Object.keys(obj).length === 0 && obj.constructor === Object;
  }

  MyParcel_Frontend.init();
  window.MyParcel_Frontend = MyParcel_Frontend;
});
