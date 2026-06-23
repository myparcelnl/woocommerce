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

// Tracks whether the wrapper has mounted before this page load. The first mount drives the PDK's
// one-time initialization (which mounts the widget); only later remounts need an explicit render.
let hasInitiallyMounted = false;

// eslint-disable-next-line @typescript-eslint/naming-convention
const DeliveryOptionsWrapper = () => {
  const data = getSetting<{style?: string; context: string}>(`${NAME}_data`, {});

  useEffect(() => {
    const dispatch = wp.data.dispatch(CHECKOUT_STORE_KEY);
    const select = wp.data.select(CHECKOUT_STORE_KEY);

    // Public checkout action since WooCommerce 9.9.0; fall back to the deprecated one on older versions.
    const setExtensionData = dispatch.setExtensionData ?? dispatch.__internalSetExtensionData;

    let cartUpdateTimeout: ReturnType<typeof setTimeout> | undefined;
    // The latest valid selection that still needs to reach the Store API session.
    let pendingSelection: Record<string, unknown> | undefined;

    // Push the latest pending selection to the Store API now, cancelling any debounce. Returns the
    // request promise so the pre-submit flush below can rely on extensionCartUpdate having been
    // dispatched (it marks the cart as calculating, which the blocks checkout waits on before
    // placing the order — so the session is written server-side before the order POST).
    const flushCartUpdate = (): Promise<unknown> => {
      if (cartUpdateTimeout) {
        clearTimeout(cartUpdateTimeout);
        cartUpdateTimeout = undefined;
      }

      if (!pendingSelection) {
        return Promise.resolve();
      }

      const selection = pendingSelection;
      pendingSelection = undefined;

      return extensionCartUpdate({namespace: NAME, data: selection}).catch((error) => {
        // eslint-disable-next-line no-console
        console.warn('[woocommerce-myparcel] delivery-options cart update failed', error);
      });
    };

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

      // Debounce the Store API push so the cart recalculates delivery-options fees in the order
      // summary without firing a request per tick. Only the latest selection is sent.
      pendingSelection = detail;

      if (cartUpdateTimeout) {
        clearTimeout(cartUpdateTimeout);
      }

      cartUpdateTimeout = setTimeout(() => {
        cartUpdateTimeout = undefined;
        void flushCartUpdate();
      }, CART_UPDATE_DEBOUNCE_MS);
    };

    // Flush any debounced selection the moment the customer places the order. Without this, an
    // order placed within the debounce window would recalculate fees from the *previous* selection
    // (the session the fee calc reads is only written by extensionCartUpdate) while the order is
    // saved with the latest one — charging a fee that doesn't match the chosen delivery option.
    let wasBeforeProcessing = false;
    const unsubscribe = wp.data.subscribe(() => {
      const isBeforeProcessing = Boolean(select.isBeforeProcessing?.());

      // Rising edge only, and only when there's actually something pending to flush.
      if (isBeforeProcessing && !wasBeforeProcessing && (cartUpdateTimeout || pendingSelection)) {
        void flushCartUpdate();
      }

      wasBeforeProcessing = isBeforeProcessing;
    }, CHECKOUT_STORE_KEY);

    document.addEventListener('myparcel_updated_delivery_options', handleUpdatedDeliveryOptions);
    document.dispatchEvent(new CustomEvent('myparcel_wc_delivery_options_ready'));

    // Blocks recreates this wrapper's DOM when toggling "Verzenden"/"Afhalen in de winkel". The PDK
    // only initializes the widget once per page load, so on remounts we must tell the widget to
    // (re)mount into the fresh #myparcel-delivery-options element. `myparcel_render_delivery_options`
    // is the widget's public render event; it unmounts any stale app and mounts fresh.
    if (hasInitiallyMounted) {
      document.dispatchEvent(new Event('myparcel_render_delivery_options'));
    }

    hasInitiallyMounted = true;

    return () => {
      document.removeEventListener('myparcel_updated_delivery_options', handleUpdatedDeliveryOptions);
      unsubscribe();

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
