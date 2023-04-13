import {AddressType} from '@myparcel-woocommerce/frontend-common';

export const keepTaxFieldsInSync = (): void => {
  const addressTypes = [AddressType.BILLING, AddressType.SHIPPING];
  const activate = (addressType: string, fieldType: string) => {
    const field = document.getElementById(`${addressType}_${fieldType}`) as HTMLInputElement;
    field?.addEventListener('keyup', () => {
      const otherAddressType = addressType === AddressType.BILLING ? AddressType.SHIPPING : AddressType.BILLING;
      const otherField = document.getElementById(`${otherAddressType}_${fieldType}`) as HTMLInputElement;
      otherField.value = field.value;
    });
  };

  addressTypes.forEach((addressType) => {
    activate(addressType, 'vat');
    activate(addressType, 'eori');
  });
};
