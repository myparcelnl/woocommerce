import {AddressType} from '@myparcel-woocommerce/frontend-common';

export const keepTaxFieldsInSync = (): void => {
  const addressTypes = [AddressType.BILLING, AddressType.SHIPPING];
  const activate = (addressType: string, fieldtype: string) => {
    const field = document.getElementById(`${addressType}_${fieldtype}`) as HTMLInputElement;
    field?.addEventListener('keyup', () => {
      const thisAddressType = addressType === AddressType.BILLING ? AddressType.SHIPPING : AddressType.BILLING; // TODO
      const thisField = document.getElementById(`${thisAddressType}_${fieldtype}`) as HTMLInputElement;
      thisField.value = field.value;
    });
  };

  addressTypes.forEach((addressType) => {
    activate(addressType, 'vat');
    activate(addressType, 'eori');
  });
};
