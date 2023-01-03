/**
 * Loaded in checkout when using split address fields.
 *
 * Compatible with 5.6 and 7.1 version of the plugin.
 */
window.addEventListener('load', () => {
  const addressRegex = /(.*?)\s?(\d{1,4})[/\s-]{0,2}([a-zA-Z]\d{1,3}|-\d{1,4}|\d{2}\w{1,2}|[a-zA-Z][a-zA-Z\s]{0,3})?$/g;

  const billingStreet = document.querySelector('#billing_street_name');
  const shippingStreet = document.querySelector('#shipping_street_name');

  /**
   * Trigger hiding of address line 1 and 2 if necessary. The timeout is necessary, otherwise the order summary is going
   * to flash.
   *
   * It checks for MyParcel_Frontend before running as that script has the same code.
   */
  if (!window.hasOwnProperty('MyParcel_Frontend')) {
    setTimeout(() => {
      const event = document.createEvent('HTMLEvents');
      event.initEvent('change', true, false);

      document.querySelectorAll('.country_to_state').forEach((selector) => {
        selector.dispatchEvent(event);
      });
    }, 100);
  }

  /**
   * Set the correct autocomplete attribute on the street fields if none is present.
   */
  [billingStreet, shippingStreet].forEach((element) => {
    if (!element) {
      return;
    }

    if (!element.getAttribute('autocomplete')) {
      element.setAttribute('autocomplete', 'street-address');
    }

    element.addEventListener('load', setAddress);
    element.addEventListener('animationend', setAddress);
  });

  /**
   * Autofill helper for layout with separate number field.
   * Split street to 3 fields on autofill.
   *
   * @param {Event} event
   */
  function setAddress(event) {
    const element = event.target;
    const type = element.getAttribute('id').search('billing') ? 'shipping' : 'billing';

    /* filter out empty values */
    const address = element.value.split(addressRegex).filter((value) => !!value);

    /* Fill in the hidden address line 1 field in case a theme forces it to be required */
    document.querySelector(`#${type}_address_1`).value = element.value;

    const [street, number, suffix] = address;

    const streetElement: HTMLInputElement = document.querySelector(`#${type}_street_name`);
    const houseNumberElement: HTMLInputElement = document.querySelector(`#${type}_house_number`);
    const houseNumberSuffixElement: HTMLInputElement = document.querySelector(`#${type}_house_number_suffix`);

    streetElement.value = street || '';
    houseNumberElement.value = number || '';
    houseNumberSuffixElement.value = suffix || '';

    /* Update settings after filling if the frontend script is loaded and initialized and address is not empty */
    if (address.length) {
      document.dispatchEvent(new Event('myparcelnl-woocommerce:updateAddress'));
    }
  }
});
