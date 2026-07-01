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

// A stable key over only the fee-relevant fields of a selection (carrier, deliveryType, packageType,
// isPickup, shipmentOptions) — the chosen `date` is dropped because it doesn't affect the fee and the
// widget recomputes it on remount. Used to dedupe pushes: a shipping toggle remounts the widget and
// re-emits the same selection, which must NOT trigger a cart refresh (that would race the shipping
// commit); only a genuine option change produces a different key.
const feeKey = (selection: Record<string, unknown>): string => {
  const {date, ...rest} = selection;
  void date;

  return JSON.stringify(rest);
};

// Tracks whether the wrapper has mounted before this page load. The first mount drives the PDK's
// one-time initialization (which mounts the widget); only later remounts need an explicit render.
let hasInitiallyMounted = false;

// The fee-relevant key of the last selection actually pushed. Module-level so it survives the
// remounts Blocks does on every shipping/pickup toggle — otherwise the post-toggle re-emit wouldn't
// be recognized as already-pushed and would fire a clobbering cart refresh.
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
    // The latest valid selection that still needs to reach the Store API session.
    let pendingSelection: Record<string, unknown> | undefined;
    // The selected shipping rate observed on the previous settle check; the push only fires once this
    // is unchanged across a settle window AND the cart is idle (see flushCartUpdate).
    let lastSettleRate: string | undefined;

    // Snapshot of the cart's shipping selection + busy flags, used to gate our push and to enforce the
    // self-heal invariant below. `rate` is the selected rate id (or 'NONE'); `pkg` is its package id.
    const cartState = (): {rate: string; pkg?: unknown; busy: boolean} => {
      const cart = wp.data.select(CART_STORE_KEY) as Record<string, undefined | ((...a: unknown[]) => unknown)>;
      const pkg = (
        cart?.getShippingRates?.() as
          | undefined
          | {package_id?: unknown; shipping_rates?: {rate_id: string; selected: boolean}[]}[]
      )?.[0];
      const rate = (pkg?.shipping_rates ?? []).find((r) => r.selected);

      return {
        rate: rate?.rate_id ?? 'NONE',
        pkg: pkg?.package_id,
        busy: Boolean(cart?.isShippingRateBeingSelected?.() || cart?.isCustomerDataUpdating?.()),
      };
    };

    // Push the pending selection to the Store API now, cancelling any scheduled push. This drives the
    // live fee in the cart/order summary; the selection is persisted for the order separately via
    // setExtensionData. Deciding *when* it is safe to push is the scheduler's job (scheduleCartUpdate);
    // this function just pushes.
    const flushCartUpdate = (): Promise<unknown> => {
      if (cartUpdateTimeout) {
        clearTimeout(cartUpdateTimeout);
        cartUpdateTimeout = undefined;
      }

      if (!pendingSelection) {
        return Promise.resolve();
      }

      // Never push while the order is being placed. A cart-extensions request concurrent with the
      // order POST races WooCommerce's draft-order build and duplicates its line items/fees. The
      // selection is still persisted via setExtensionData and the server stashes it for the fee at
      // order time (CartFeesHooks::stashBlocksCheckoutSelection), so skipping the push here is safe.
      // Keep the pending selection so a push still happens if the order doesn't go through.
      if (select.isBeforeProcessing?.() || select.isProcessing?.()) {
        return Promise.resolve();
      }

      const selection = pendingSelection;
      pendingSelection = undefined;
      lastPushedFeeKey = feeKey(selection);

      // The shipping selection the customer has chosen, captured the instant before our push, so the
      // self-heal below can detect (and undo) any change the push's response makes to it.
      const before = cartState();

      // Mark the checkout "calculating" for the whole push. WooCommerce's place-order flow waits for
      // isCalculating to clear before building the order, so this makes it block on our in-flight
      // request instead of running concurrently — an overlapping cart request duplicates the order's
      // line items, and an in-flight request can't be cancelled, so we make the checkout wait for it.
      const checkoutCalc = dispatch as {
        __internalIncrementCalculating?: () => void;
        __internalDecrementCalculating?: () => void;
      };
      checkoutCalc.__internalIncrementCalculating?.();

      return extensionCartUpdate({namespace: NAME, data: selection})
        .then(() => {
          // Invariant: extensionCartUpdate must not change the chosen shipping rate. It applies the
          // server's *entire* cart response, which during a rapid Verzenden/Afhalen toggle can carry
          // a stale rate (the server momentarily still has the previous method) and overwrite the
          // customer's choice — making the delivery options vanish. No client-side "cart is busy"
          // signal reliably prevents this (the flags read idle while a selection is committing
          // server-side), so we enforce the invariant after the fact: if our push moved the rate,
          // re-select the original one to reconverge client and server. selectShippingRate commits to
          // the server, so once it lands every subsequent push echoes the correct rate and this stops.
          const after = cartState();

          if (before.rate !== 'NONE' && after.rate !== before.rate) {
            const cartDispatch = wp.data.dispatch(CART_STORE_KEY) as {
              selectShippingRate?: (rateId: string, packageId?: unknown) => unknown;
            };

            void cartDispatch.selectShippingRate?.(before.rate, before.pkg);
          }
        })
        .catch((error) => {
          // eslint-disable-next-line no-console
          console.warn('[woocommerce-myparcel] delivery-options cart update failed', error);
        })
        .finally(() => {
          checkoutCalc.__internalDecrementCalculating?.();
        });
    };

    // Debounced, settle-aware scheduler for the cart push. extensionCartUpdate applies the *entire*
    // server cart response back into the cart store, so pushing while a shipping-rate selection is in
    // flight — or in the brief idle gap between two rapid selections — races WooCommerce's own commit:
    // the server returns a snapshot with the previous method still selected, which overwrites the
    // client and drops the pending selection (a fast "Afhalen" -> "Verzenden" toggle snaps back to
    // local pickup, hiding the delivery options). So we wait until the cart is idle AND the selected
    // rate has held steady across a settle window before pushing; until then we keep re-arming.
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

      // The widget emits `null`/`undefined` when there's no valid selection yet (initial load,
      // loading, or reset). Forwarding that as `data` makes the Store API reject the request, so
      // skip until we have an actual delivery-options object.
      if (!isPlainObject(detail)) {
        return;
      }

      // Persist the selection so it's saved on order placement.
      void setExtensionData(NAME, detail);

      // The widget re-emits its current selection whenever it remounts — which Blocks does every time
      // the customer toggles "Verzenden"/"Afhalen in de winkel". That re-emit carries the same
      // fee-relevant selection we already pushed, so skip it: pushing here would fire a cart update
      // mid-toggle and race the shipping-rate commit. Genuine option changes differ and fall through.
      if (feeKey(detail) === lastPushedFeeKey) {
        return;
      }

      // Schedule a debounced, settle-aware push so the cart recalculates delivery-options fees in the
      // order summary without firing a request per tick. Only the latest selection is sent.
      pendingSelection = detail;
      scheduleCartUpdate();
    };

    // We never push to the cart at order placement: forcing an extensionCartUpdate here raced
    // WooCommerce's own shipping-rate commit and reverted the chosen method on the order. Instead the
    // selection rides along in the order POST's `extensions` payload (setExtensionData above) and the
    // server primes the fee from it (CartFeesHooks::stashBlocksCheckoutSelection).
    //
    // What we *do* need here: cancel any still-pending debounced push so it can't fire concurrently
    // with the order POST. A cart-extensions request overlapping the checkout request races the
    // draft-order build and duplicates its line items/fees. This only clears a timer — no cart write,
    // no shipping touch — so it can't affect the chosen method.
    let wasBeforeProcessing = false;
    const unsubscribe = wp.data.subscribe(() => {
      const isBeforeProcessing = Boolean(select.isBeforeProcessing?.());

      if (isBeforeProcessing && !wasBeforeProcessing && cartUpdateTimeout) {
        clearTimeout(cartUpdateTimeout);
        cartUpdateTimeout = undefined;
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
