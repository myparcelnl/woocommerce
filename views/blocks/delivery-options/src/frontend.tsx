/* eslint-disable no-underscore-dangle */
import React, {useEffect} from 'react';
import {registerPlugin} from '@wordpress/plugins';
import {getSetting} from '@woocommerce/settings';
import {ExperimentalOrderShippingPackages, extensionCartUpdate} from '@woocommerce/blocks-checkout';
import {CART_STORE_KEY, CHECKOUT_STORE_KEY} from '@woocommerce/block-data';

const NAME = 'myparcelcom-delivery-options';

// Debounce pushes to the Store API so rapid widget changes don't fire a request per tick.
const CART_UPDATE_DEBOUNCE_MS = 300;

const isPlainObject = (value: unknown): value is Record<string, unknown> =>
  typeof value === 'object' && value !== null && !Array.isArray(value);

// Fee-relevant key of a selection (drops the volatile `date`). Dedupes pushes so the widget's
// remount re-emit on a shipping toggle — same options — doesn't fire a needless, race-prone refresh.
const feeKey = (selection: Record<string, unknown>): string => {
  const {date, ...rest} = selection;
  void date;

  return JSON.stringify(rest);
};

// First mount runs the PDK's one-time widget init; later remounts (one per toggle) need an explicit re-render.
let hasInitiallyMounted = false;

// Last pushed fee-key. Module-level so it survives the toggle remounts (else the re-emit looks new).
let lastPushedFeeKey: string | undefined;

// eslint-disable-next-line @typescript-eslint/naming-convention
const DeliveryOptionsWrapper = () => {
  const data = getSetting<{style?: string; context: string}>(`${NAME}_data`, {});

  useEffect(() => {
    const dispatch = wp.data.dispatch(CHECKOUT_STORE_KEY);
    const select = wp.data.select(CHECKOUT_STORE_KEY);

    // Public checkout action since WooCommerce 9.9.0; fall back to the deprecated one on older versions.
    const setExtensionData = dispatch.setExtensionData ?? dispatch.__internalSetExtensionData;

    let cartUpdateTimeout: ReturnType<typeof setTimeout> | undefined;
    // Latest selection awaiting a push.
    let pendingSelection: Record<string, unknown> | undefined;
    // Selected rate seen on the previous settle check; the push waits until it holds steady.
    let lastSettleRate: string | undefined;

    // Selected shipping rate id (or undefined when none) and whether the cart is mid-update — the
    // scheduler holds the push until the cart is idle and the rate has settled.
    const cartState = (): {rate: string | undefined; busy: boolean} => {
      const cart = wp.data.select(CART_STORE_KEY) as Record<string, undefined | ((...a: unknown[]) => unknown)>;
      const rates = (
        cart?.getShippingRates?.() as undefined | {shipping_rates?: {rate_id: string; selected: boolean}[]}[]
      )?.[0]?.shipping_rates;
      const rate = (rates ?? []).find((r) => r.selected);

      return {
        rate: rate?.rate_id,
        busy: Boolean(cart?.isShippingRateBeingSelected?.() || cart?.isCustomerDataUpdating?.()),
      };
    };

    // Push the pending selection now (drives the live summary fee). The scheduler decides when this is
    // safe to call; the selection is separately persisted for the order via setExtensionData.
    const flushCartUpdate = (): Promise<unknown> => {
      if (cartUpdateTimeout) {
        clearTimeout(cartUpdateTimeout);
        cartUpdateTimeout = undefined;
      }

      if (!pendingSelection) {
        return Promise.resolve();
      }

      // Don't push while the order is being placed — a cart request concurrent with the order POST
      // duplicates its line items. The server stashes the fee at order time, so skipping here is safe.
      if (select.isBeforeProcessing?.() || select.isProcessing?.()) {
        return Promise.resolve();
      }

      const selection = pendingSelection;
      pendingSelection = undefined;
      lastPushedFeeKey = feeKey(selection);

      // Mark the checkout "calculating" for the push so WooCommerce's place-order flow blocks on it:
      // an in-flight cart request overlapping the order POST duplicates the order's line items, and it
      // can't be cancelled once sent — so we make the checkout wait for it to finish.
      const checkoutCalc = dispatch as {
        __internalIncrementCalculating?: () => void;
        __internalDecrementCalculating?: () => void;
      };
      checkoutCalc.__internalIncrementCalculating?.();

      return extensionCartUpdate({namespace: NAME, data: selection})
        .catch((error) => {
          // eslint-disable-next-line no-console
          console.warn('[woocommerce-myparcel] delivery-options cart update failed', error);
        })
        .finally(() => {
          checkoutCalc.__internalDecrementCalculating?.();
        });
    };

    // Debounced push scheduler. extensionCartUpdate replaces the whole cart, so pushing during a
    // shipping change reverts the chosen method; wait out the debounce, then hold until the cart is
    // idle and the rate has settled before pushing. Re-arms until then.
    const scheduleCartUpdate = (): void => {
      if (cartUpdateTimeout) {
        clearTimeout(cartUpdateTimeout);
      }

      cartUpdateTimeout = setTimeout(() => {
        cartUpdateTimeout = undefined;

        const {busy, rate} = cartState();

        if (busy || rate !== lastSettleRate) {
          lastSettleRate = rate;
          scheduleCartUpdate();
          return;
        }

        void flushCartUpdate();
      }, CART_UPDATE_DEBOUNCE_MS);
    };

    const handleUpdatedDeliveryOptions = (event: Event): void => {
      const {detail} = event as CustomEvent;

      // The widget emits null/undefined when there's no valid selection yet; the Store API rejects
      // that, so wait for a real object.
      if (!isPlainObject(detail)) {
        return;
      }

      // Persist the selection so it's saved on the order.
      void setExtensionData(NAME, detail);

      // Skip re-emits of the same options — the widget re-emits on the remount every toggle causes,
      // and a push here would race the shipping commit. Genuine changes differ and fall through.
      if (feeKey(detail) === lastPushedFeeKey) {
        return;
      }

      // Debounced push so the summary fee recalculates without a request per tick.
      pendingSelection = detail;
      scheduleCartUpdate();
    };

    document.addEventListener('myparcel_updated_delivery_options', handleUpdatedDeliveryOptions);
    document.dispatchEvent(new CustomEvent('myparcel_wc_delivery_options_ready'));

    // Blocks recreates this wrapper on every Verzenden/Afhalen toggle, but the PDK inits the widget
    // once per page load — so on remounts tell it to re-mount into the fresh element via its public
    // render event (it unmounts any stale app and mounts fresh).
    if (hasInitiallyMounted) {
      document.dispatchEvent(new Event('myparcel_render_delivery_options'));
    }

    hasInitiallyMounted = true;

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
