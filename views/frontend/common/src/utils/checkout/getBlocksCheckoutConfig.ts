import {
  ADDRESS_FIELD_IS_BUSINESS,
  AddressField,
  AddressType,
  PdkField,
  type PdkFormData,
} from '@myparcel-dev/pdk-checkout-common';
import {
  PdkUtil,
  refreshContextIfBusinessChanged,
  useUtil,
  updateContext,
  SeparateAddressField,
} from '@myparcel-dev/pdk-checkout';
import {type CheckoutConfig} from '../../types';
import {useWcCartStore} from './useWcCartStore';
import {getShippingRate} from './getShippingRate';

const MYPARCEL_BLOCK_FIELDS_PREFIX = 'myparcelcom/';

/**
 * Whether an address belongs to a business: it has a company name. Mirrors the PDK's
 * `Address::deriveIsBusiness()`.
 *
 * Only the flag is reported, never the company name. The PDK holds the company itself and puts the
 * flag in the checkout context, which decides which carriers the recipient is offered.
 */
const isBusinessAddress = (address?: Record<string, string>): boolean =>
  Boolean((address?.company ?? '').trim());

type CartItem = {key: string; id: number; quantity: number};

/**
 * The cart items, with only the fields that change the cart weight: the cart item key, the product or
 * variation id, and the quantity. A fee or a new total does not change these fields.
 */
const getCartItems = (): CartItem[] =>
  useWcCartStore()
    .selectors.getCartData()
    .items.map(({key, id, quantity}) => ({key, id, quantity}));

/**
 * Whether WooCommerce is still saving a cart change to the server: a new quantity, a removed item, or a
 * new shipping method. The cart data shows a new quantity before the server has it, so a context fetched
 * at that moment holds the old weight.
 *
 * Pass the keys from before and after the change: a removed item is no longer in the current cart data.
 */
const isSavingCartChange = (itemKeys: string[]): boolean => {
  const {selectors} = useWcCartStore();

  return (
    selectors.isShippingRateBeingSelected() ||
    itemKeys.some((key) => selectors.isItemPendingQuantity(key) || selectors.isItemPendingDelete(key))
  );
};

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
    reportsBusinessFlag: true,

    config: {
      /**
       * Update whenever the shipping method, the address or the cart items change.
       */
      formChange(callback) {
        const wcCartStore = useWcCartStore();
        let previousShippingRate = getShippingRate();
        let previousCustomerData = JSON.stringify(wcCartStore.selectors.getCustomerData());
        // WooCommerce updates the customer data on every keystroke and pushes it to the server on
        // blur, debounced. The end of that push is the only moment the server is known to hold the
        // address, so it is the moment to let the PDK compare its context. Older WooCommerce Blocks
        // versions cannot report a save, and then there is no such moment to offer.
        let previousSaving = false;
        let previousCartItems = getCartItems();

        wp.data.subscribe(async () => {
          const saving = Boolean(wcCartStore.selectors.isCustomerDataUpdating?.());
          const saveFinished = previousSaving && !saving;

          previousSaving = saving;

          if (saveFinished) {
            await refreshContextIfBusinessChanged();
          }

          const currentCartItems = getCartItems();
          const itemKeys = [...new Set([...previousCartItems, ...currentCartItems].map(({key}) => key))];

          // Wait until WooCommerce has saved the change, so the context request reads the new cart and address.
          if (saving || isSavingCartChange(itemKeys)) {
            return;
          }

          const currentShippingRate = getShippingRate();
          const currentCustomerData = JSON.stringify(wcCartStore.selectors.getCustomerData());

          const shippingMethodChanged = previousShippingRate?.rate_id !== currentShippingRate?.rate_id;
          const customerDataChanged = previousCustomerData !== currentCustomerData;
          const cartItemsChanged = JSON.stringify(previousCartItems) !== JSON.stringify(currentCartItems);

          if (!shippingMethodChanged && !customerDataChanged && !cartItemsChanged) {
            return;
          }

          previousShippingRate = currentShippingRate;
          previousCustomerData = currentCustomerData;
          previousCartItems = currentCartItems;
          callback();

          // The weight in the context depends on the cart items and on the package type of the shipping method.
          if (shippingMethodChanged || cartItemsChanged) {
            try {
              await updateContext();
            } catch (error) {
              // eslint-disable-next-line no-console
              console.warn('[woocommerce-myparcel] delivery-options context update failed', error);
            }
          }
        });
      },

      getFormData() {
        const wcCartStore = useWcCartStore();
        const customerData = wcCartStore.selectors.getCustomerData();
        const formData: PdkFormData = {};

        [AddressType.Shipping, AddressType.Billing].forEach((addressType) => {
          const address = customerData[`${addressType}Address`];

          Object.keys(addressFields).forEach((field) => {
            formData[`${addressType}-${addressFields[field]}`] = address[addressFields[field]];
          });

          formData[`${addressType}-${ADDRESS_FIELD_IS_BUSINESS}`] = isBusinessAddress(address) ? '1' : '';
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
