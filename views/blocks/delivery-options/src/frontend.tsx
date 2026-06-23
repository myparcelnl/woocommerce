/* eslint-disable no-underscore-dangle */
import React, {useEffect} from 'react';
import {registerPlugin} from '@wordpress/plugins';
import {getSetting} from '@woocommerce/settings';
import {ExperimentalOrderShippingPackages, extensionCartUpdate} from '@woocommerce/blocks-checkout';
import {CHECKOUT_STORE_KEY} from '@woocommerce/block-data';

const NAME = 'myparcelcom-delivery-options';

// Debounce pushes to the Store API so rapid widget changes don't fire a request per tick.
const CART_UPDATE_DEBOUNCE_MS = 300;

const isPlainObject = (value: unknown): value is Record<string, unknown> =>
  typeof value === 'object' && value !== null && !Array.isArray(value);

// eslint-disable-next-line @typescript-eslint/naming-convention
const DeliveryOptionsWrapper = () => {
  const data = getSetting<{style?: string; context: string}>(`${NAME}_data`, {});

  useEffect(() => {
    const dispatch = wp.data.dispatch(CHECKOUT_STORE_KEY);

    // Public checkout action since WooCommerce 9.9.0; fall back to the deprecated one on older versions.
    const setExtensionData = dispatch.setExtensionData ?? dispatch.__internalSetExtensionData;

    let cartUpdateTimeout: ReturnType<typeof setTimeout> | undefined;

    const handleUpdatedDeliveryOptions = (event: Event): void => {
      const {detail} = event as CustomEvent;

      // The widget emits `null`/`undefined` when there's no valid selection yet (initial load,
      // loading, or reset). Forwarding that as `data` makes the Store API reject the request, so
      // skip until we have an actual delivery-options object.
      if (!isPlainObject(detail)) {
        return;
      }

      // Persist the selection so it's saved on order placement.
      void setExtensionData(NAME, detail);

      // Push to the Store API so the cart recalculates delivery-options fees in the order summary.
      if (cartUpdateTimeout) {
        clearTimeout(cartUpdateTimeout);
      }

      cartUpdateTimeout = setTimeout(() => {
        void extensionCartUpdate({namespace: NAME, data: detail}).catch((error) => {
          // eslint-disable-next-line no-console
          console.warn('[woocommerce-myparcel] delivery-options cart update failed', error);
        });
      }, CART_UPDATE_DEBOUNCE_MS);
    };

    document.addEventListener('myparcel_updated_delivery_options', handleUpdatedDeliveryOptions);
    document.dispatchEvent(new CustomEvent('myparcel_wc_delivery_options_ready'));

    return () => {
      document.removeEventListener('myparcel_updated_delivery_options', handleUpdatedDeliveryOptions);

      if (cartUpdateTimeout) {
        clearTimeout(cartUpdateTimeout);
      }
    };
  }, []);

  return (
    <div>
      <style>{data.style ?? ''}</style>
      <div
        id="mypa-delivery-options-wrapper"
        className="myparcelnl__delivery-options"
        data-context={data.context}>
        <div id="myparcel-delivery-options"></div>
      </div>
    </div>
  );
};

const render: React.FC = () => {
  return (
    <ExperimentalOrderShippingPackages>
      <DeliveryOptionsWrapper />
    </ExperimentalOrderShippingPackages>
  );
};

registerPlugin(NAME, {render, scope: 'woocommerce-checkout'});
