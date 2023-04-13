import {AddressType, useCheckoutStore} from '@myparcel-woocommerce/frontend-common';
import {EU_COUNTRIES} from './countries';
import {usesAddressType} from '@myparcel-woocommerce/frontend-common/src/address/usesAddressType';

export const showHideTaxFields = (): void => {
  const store = useCheckoutStore();
  const hiddenInput = store.state.hiddenInput?.value;
  const carrier = hiddenInput ? JSON.parse(hiddenInput)?.carrier : null;
  const eoriCountries = ['NL'];
  const nonVatCountries = EU_COUNTRIES;
  // const nonVatCountries = ['NL'];
  const $ = jQuery;
  const checkout = useCheckoutStore();
  let country = checkout.state.form.billing_country ?? 'NL';
  let activeAddressType = AddressType.BILLING;

  if (usesAddressType(AddressType.SHIPPING)) {
    activeAddressType = AddressType.SHIPPING;
    country = checkout.state.form.shipping_country ?? country;
    $('#billing_eori_field').hide();
    $('#billing_vat_field').hide();
  }

  if (carrier === 'dhleuroplus') {
    if (eoriCountries.includes(country)) {
      $(`#${activeAddressType}_eori_field`).show();
    } else {
      $(`#${activeAddressType}_eori_field`).hide();
    }

    if (nonVatCountries.includes(country)) {
      $(`#${activeAddressType}_vat_field`).hide();
    } else {
      $(`#${activeAddressType}_vat_field`).show();
    }
  } else {
    $(`#${activeAddressType}_eori_field`).hide();
    $(`#${activeAddressType}_vat_field`).hide();
  }
};
