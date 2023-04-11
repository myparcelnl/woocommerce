import {AddressType} from '@myparcel-woocommerce/frontend-common';
import {useCheckoutStore} from '@myparcel-woocommerce/frontend-common';
import {usesAddressType} from '@myparcel-woocommerce/frontend-common/src/address/usesAddressType';

export const showHideTaxFields = (carrier: string | null): void => {
  const eoriCountries = ['FR'];
  const vatCountries = ['FR', 'DE'];
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

  console.warn(activeAddressType, carrier);

  if (carrier === 'dhleuroplus') {
    if (eoriCountries.includes(country)) {
      $(`#${activeAddressType}_eori_field`).show();
    } else {
      $(`#${activeAddressType}_eori_field`).hide();
    }

    if (vatCountries.includes(country)) {
      $(`#${activeAddressType}_vat_field`).show();
    } else {
      $(`#${activeAddressType}_vat_field`).hide();
    }
  } else {
    $(`#${activeAddressType}_eori_field`).hide();
    $(`#${activeAddressType}_vat_field`).hide();
  }
};
