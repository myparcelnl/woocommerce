import {EVENT_UPDATE_DELIVERY_OPTIONS, FIELD_CITY, FIELD_COUNTRY, FIELD_POSTCODE} from './data';
import {getAddressField} from './utils';
import {getHouseNumber} from './getHouseNumber';
import {getStoreValue} from './store';
import {validateMyParcelConfig} from './delivery-options';

/**
 * Get data from form fields, put it in the global MyParcelConfig, then trigger updating the delivery options.
 */
export const updateAddress = (): void => {
  validateMyParcelConfig();

  // window.MyParcelConfig.address = {
  //   cc: getAddressField(FIELD_COUNTRY).value,
  //   postalCode: getAddressField(FIELD_POSTCODE).value,
  //   number: getHouseNumber(),
  //   city: getAddressField(FIELD_CITY).value,
  // };

  if (getStoreValue('hasDeliveryOptions')) {
    document.dispatchEvent(
      new CustomEvent(EVENT_UPDATE_DELIVERY_OPTIONS, {
        detail: {
          address: {
            cc: getAddressField(FIELD_COUNTRY).value,
            postalCode: getAddressField(FIELD_POSTCODE).value,
            number: getHouseNumber(),
            city: getAddressField(FIELD_CITY).value,
          },
        },
      }),
    );

    // triggerEvent(EVENT_UPDATE_DELIVERY_OPTIONS);
  }
};
