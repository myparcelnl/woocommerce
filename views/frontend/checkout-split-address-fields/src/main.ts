import '../assets/scss/index.scss';
import {
  AddressType,
  EVENT_WOOCOMMERCE_COUNTRY_TO_STATE_CHANGED,
  FIELD_NUMBER,
  FIELD_STREET,
  StoreListener,
  getAddressField,
  useCheckoutStore,
} from '@myparcel-woocommerce/frontend-common/src';
import {setAddress, synchronizeAddress} from './utils';

const getFullStreet = (addressType: AddressType): string => {
  return [FIELD_STREET, FIELD_NUMBER]
    .reduce((acc, fieldName) => {
      const field = getAddressField(fieldName, addressType);

      if (field) {
        acc.push(field.value);
      }

      return acc;
    }, [] as string[])
    .join(' ')
    .trim();
};

jQuery(() => {
  console.log('[split-address-fields:START]');
  const checkout = useCheckoutStore();

  // @ts-expect-error this is a string.
  jQuery(document.body).on(EVENT_WOOCOMMERCE_COUNTRY_TO_STATE_CHANGED, synchronizeAddress);

  checkout.on(StoreListener.UPDATE, (data, oldData) => {
    [AddressType.SHIPPING, AddressType.BILLING].forEach((addressType) => {
      ['street', 'number'].forEach((fieldName) => {
        if (data.form[`${addressType}_${fieldName}`] === oldData.form[`${addressType}_${fieldName}`]) {
          return;
        }

        console.log(`${addressType}_${fieldName} changed`);
      });
    });
  });

  /**
   * Set the correct autocomplete attribute on the street fields if none is present.
   */
  [AddressType.SHIPPING, AddressType.BILLING].forEach((addressType) => {
    const streetField = getAddressField(FIELD_STREET, addressType);
    const numberField = getAddressField(FIELD_NUMBER, addressType);

    if (!streetField || !numberField) {
      // eslint-disable-next-line no-console
      console.error(`Could not find street or number field for ${addressType}.`);
      return;
    }

    // streetField?.addEventListener('change', () => {
    //   console.log('streetField change');
    //   setFieldValue(FIELD_ADDRESS_1, getFullStreet(addressType), addressType);
    // });
    //
    // numberField?.addEventListener('change', () => {
    //   console.log('numberField change');
    //   setFieldValue(FIELD_ADDRESS_1, getFullStreet(addressType), addressType);
    // });

    if (!streetField?.getAttribute('autocomplete')) {
      streetField?.setAttribute('autocomplete', 'street-address');
    }

    streetField?.addEventListener('load', setAddress);
    streetField?.addEventListener('animationend', setAddress);

    setAddress({target: streetField} as Event & {target: HTMLInputElement});
  });

  console.log('[split-address-fields:END]');
});
