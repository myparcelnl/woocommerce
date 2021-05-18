jQuery(document).ready(($) => {
  /**
   * Hide custom NL fields by default when country not NL.
   *
   * @param {String} addressType
   */
  function localizeAddressFields(addressType) {
    const country = $(`#${addressType}_country`).val();

    if (!country) {
      return;
    }

    const streetName = $(`#${addressType}_street_name_field`);
    const houseNumber = $(`#${addressType}_house_number_field`);
    const numberSuffix = $(`#${addressType}_number_suffix_field`);
    const boxNumber = $(`#${addressType}_box_number_field`);
    const addressLine1 = $(`#${addressType}_address_1_field`);
    const addressLine2 = $(`#${addressType}_address_2_field`);

    switch (country) {
      case 'NL':
        streetName.show();
        houseNumber.show();
        numberSuffix.show();
        boxNumber.hide();
        addressLine1.hide();
        addressLine2.hide();
        break;
      case 'BE':
        streetName.show();
        houseNumber.show();
        numberSuffix.hide();
        boxNumber.show();
        addressLine1.hide();
        addressLine2.hide();
        break;
      default:
        streetName.hide();
        houseNumber.hide();
        numberSuffix.hide();
        boxNumber.hide();
        addressLine1.show();
        addressLine2.show();
        break;
    }
  }

  localizeAddressFields('billing');
  localizeAddressFields('shipping');

  document.querySelectorAll('select')
    .forEach((select) => {
      select.addEventListener('change', (event) => {
        const addressType = event.target.id.replace('_country', '');
        localizeAddressFields(addressType);
      });
    });
});
