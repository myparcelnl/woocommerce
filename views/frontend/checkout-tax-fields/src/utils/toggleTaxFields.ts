import {AddressType, getAddressField} from '@myparcel-woocommerce/frontend-common';
import {FIELD_EORI_NUMBER, FIELD_VAT_NUMBER} from '../data';
import {hasTaxFields} from './hasTaxFields';

export const toggleTaxFields = (): void => {
  const showTaxFields = hasTaxFields();

  [AddressType.BILLING, AddressType.SHIPPING].forEach((addressType) => {
    [FIELD_EORI_NUMBER, FIELD_VAT_NUMBER].forEach((fieldName) => {
      const wrapper = getAddressField(`${fieldName}_wrapper`, addressType);

      if (!wrapper) {
        return;
      }

      const $wrapper = jQuery(wrapper);

      if (showTaxFields) {
        $wrapper.show();
        return;
      }

      $wrapper.hide();
    });
  });
};
