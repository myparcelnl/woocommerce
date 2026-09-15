import {AddressField, AddressType, PdkField, type PdkFormData} from '@myparcel-dev/pdk-checkout-common';
import {PdkUtil, useUtil, updateContext, SeparateAddressField} from '@myparcel-dev/pdk-checkout';
import {type CheckoutConfig} from '../../types';
import {useWcCartStore} from './useWcCartStore';
import {getShippingRate} from './getShippingRate';

const MYPARCEL_BLOCK_FIELDS_PREFIX = 'myparcelcom/';

/**
 * Whether the recipient counts as a business: a filled-in company name. Mirrors the PDK's
 * `Address::deriveIsBusiness()`. The blocks checkout ships to the shipping address only.
 *
 * The flag decides which carriers the capabilities call returns — DHL Euro Plus is business only —
 * and it is derived server-side, so a flip needs a fresh checkout context.
 */
const isBusinessRecipient = (customerData: {shippingAddress: Record<string, string>}): boolean =>
  Boolean((customerData.shippingAddress?.company ?? '').trim());

// eslint-disable-next-line max-lines-per-function
export const getBlocksCheckoutConfig = (): CheckoutConfig => {
  const addressFields = {
    eoriNumber: `eori_number`,
    vatNumber: `vat_number`,
    [AddressField.Address1]: `address_1`,
    [AddressField.Address2]: `address_2`,
    [AddressField.City]: `city`,
    [AddressField.Country]: `country`,
    [AddressField.PostalCode]: `postcode`,
    [SeparateAddressField.Street]: `${MYPARCEL_BLOCK_FIELDS_PREFIX}street_name`,
    [SeparateAddressField.Number]: `${MYPARCEL_BLOCK_FIELDS_PREFIX}house_number`,
    [SeparateAddressField.NumberSuffix]: `${MYPARCEL_BLOCK_FIELDS_PREFIX}house_number_suffix`,
  };

  const hasAddressType = (addressType: AddressType): boolean => {
    const billingElement = document.querySelector('#billing-fields');

    return AddressType.Shipping === addressType || billingElement !== null;
  };

  return {
    addressFields,
    fieldAddressType: 'checkbox-control-0',
    fieldShippingMethod: 'shipping_method',
    prefixBilling: 'billing-',
    prefixShipping: 'shipping-',

    shippingMethodFormDataKey: PdkField.ShippingMethod,
    addressTypeFormDataKey: PdkField.AddressType,

    config: {
      /**
       * Update whenever the shipping method or the address changes.
       */
      formChange(callback) {
        const wcCartStore = useWcCartStore();
        let previousShippingRate = getShippingRate();
        let previousCustomerData = JSON.stringify(wcCartStore.selectors.getCustomerData());
        let previousIsBusiness = isBusinessRecipient(wcCartStore.selectors.getCustomerData());
        // Older WooCommerce Blocks versions have no way to report a save in progress. Without it
        // there is no moment that is known to be safe to refetch, so the business flag is left
        // alone there, the same way the blocks integration treats this selector as optional.
        const canDetectSaving = typeof wcCartStore.selectors.isCustomerDataUpdating === 'function';
        // Set when the business flag flipped. The refetch waits for WooCommerce to save the new
        // company: the customer data in the store updates on every keystroke, while the save to the
        // server is debounced, and a context built before it lands still carries the old flag.
        let businessRefreshPending = false;
        let previousSaving = false;

        wp.data.subscribe(async () => {
          const currentShippingRate = getShippingRate();
          const currentCustomerData = wcCartStore.selectors.getCustomerData();

          const shippingMethodChanged = previousShippingRate?.rate_id !== currentShippingRate?.rate_id;
          const customerDataChanged = previousCustomerData !== JSON.stringify(currentCustomerData);
          const currentIsBusiness = isBusinessRecipient(currentCustomerData);

          if (currentIsBusiness !== previousIsBusiness) {
            previousIsBusiness = currentIsBusiness;
            businessRefreshPending = canDetectSaving;
          }

          // An address change always goes through a save request, so its end is the moment the
          // server knows the new company.
          const saving = Boolean(wcCartStore.selectors.isCustomerDataUpdating?.());
          const businessChangeSaved = businessRefreshPending && previousSaving && !saving;

          previousSaving = saving;

          if (businessChangeSaved) {
            businessRefreshPending = false;
          }

          if (customerDataChanged) {
            previousCustomerData = JSON.stringify(currentCustomerData);
          }

          if (shippingMethodChanged) {
            previousShippingRate = currentShippingRate;
          }

          // Both reasons need the same fresh context, so one refetch per tick is enough. While a
          // company change is still being saved, a shipping method change waits for it: fetching
          // now would build the context from the company the server still has, and that response
          // can land after the fresh one. The pending refetch carries the new shipping method too.
          if ((shippingMethodChanged || businessChangeSaved) && !businessRefreshPending) {
            await updateContext();
          }

          if (!shippingMethodChanged && !customerDataChanged) {
            return;
          }

          callback();
        });
      },

      getFormData() {
        const wcCartStore = useWcCartStore();
        const customerData = wcCartStore.selectors.getCustomerData();
        const formData: PdkFormData = {};

        [AddressType.Shipping, AddressType.Billing].forEach((addressType) => {
          Object.keys(addressFields).forEach((field) => {
            formData[`${addressType}-${addressFields[field]}`] =
              customerData[`${addressType}Address`][addressFields[field]];
          });
        });

        const shippingRates = wcCartStore.selectors.getShippingRates();
        const selectedRate = shippingRates[0]?.shipping_rates.find((rate) => rate.selected);

        formData[PdkField.ShippingMethod] = selectedRate?.rate_id;

        // In the blocks checkout, the shipping address is *always* shown and billing address is optional. MyParcel uses the shipping address only.
        formData[PdkField.AddressType] = AddressType.Shipping;
        return formData;
      },

      getAddressType() {
        // Always use shipping in the blocks checkout.
        return AddressType.Shipping;
      },

      getForm() {
        const getElement = useUtil(PdkUtil.GetElement);

        // eslint-disable-next-line @typescript-eslint/no-non-null-assertion
        return getElement('.wc-block-checkout__form')!;
      },

      hasAddressType,

      initialize() {
        return new Promise((resolve) => {
          document.addEventListener('myparcel_wc_delivery_options_ready', () => {
            resolve();
          });
        });
      },
    },
  } satisfies CheckoutConfig;
};
