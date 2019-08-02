/**
 * Loaded in checkout when using split address fields
 */
window.addEventListener('load', function() {

  /**
   * Trigger hiding of address line 1 and 2 if necessary. The timeout is necessary, otherwise the order summary is going
   * to flash.
   *
   * It checks for MyParcel_Frontend before running as that script has the same code.
   */
  if (!window.hasOwnProperty('MyParcel_Frontend')) {
    setTimeout(function() {
      var event = document.createEvent('HTMLEvents');
      event.initEvent('change', true, false);
      document.querySelectorAll('.country_to_state').forEach(function(selector) {
        selector.dispatchEvent(event);
      });
    }, 100);
  }

  var addressRegex = /(.*?)\s?(\d{1,4})[/\s-]{0,2}([a-zA-Z]\d{1,3}|-\d{1,4}|\d{2}\w{1,2}|[a-zA-Z][a-zA-Z\s]{0,3})?$/g;

  var billingStreet = document.querySelector('#billing_street_name');
  var shippingStreet = document.querySelector('#shipping_street_name');

  /**
   * Set the correct autocomplete attribute on the street fields if none is present.
   */
  [billingStreet, shippingStreet].forEach(function(el) {
    if (!el.getAttribute('autocomplete')) {
      el.setAttribute('autocomplete', 'street-address');
    }
  });

  billingStreet.addEventListener('load', setAddress);
  billingStreet.addEventListener('animationend', setAddress);

  shippingStreet.addEventListener('load', setAddress);
  shippingStreet.addEventListener('animationend', setAddress);

  /**
   * Autofill helper for layout with separate number field.
   * Split street to 3 fields on autofill.
   */
  function setAddress() {
    var type = this.getAttribute('id').search('billing') ? 'shipping' : 'billing';

    // Fill in the hidden address line 1 field in case a theme forces it to be required
    document.querySelector('#' + type + '_address_1').value = this.value;

    var address = this.value.split(addressRegex).filter(function(value) {
      return !!value; // filter out empty values
    });

    document.querySelector('#' + type + '_street_name').value = address[0] || '';
    document.querySelector('#' + type + '_house_number').value = address[1] || '';
    document.querySelector('#' + type + '_house_number_suffix').value = address[2] || '';

    // Update settings after filling if the frontend script is loaded and initialized and address is not empty
    if (address.length && window.hasOwnProperty('MyParcel_Frontend')) {
      window.MyParcel_Frontend.update_settings();
    }
  }
});
