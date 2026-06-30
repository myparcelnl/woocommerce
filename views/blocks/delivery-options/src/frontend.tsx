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
    // JSON of the last selection actually pushed, so widget re-emits with identical data (e.g. on the
    // remount that happens when toggling Verzenden/Afhalen) don't trigger a redundant cart push.
    let lastPushedSelection: string | undefined;
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

    // Push the pending selection to the Store API right now, cancelling any scheduled push. Returns
    // the request promise so the pre-submit flush below can rely on extensionCartUpdate having been
    // dispatched (it marks the cart as calculating, which the blocks checkout waits on before placing
    // the order — so the session is written server-side before the order POST). Deciding *when* it is
    // safe to push is the scheduler's job (see scheduleCartUpdate); this function just pushes.
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
      lastPushedSelection = JSON.stringify(selection);

      // The shipping selection the customer has chosen, captured the instant before our push, so the
      // self-heal below can detect (and undo) any change the push's response makes to it.
      const before = cartState();

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
      // the customer toggles "Verzenden"/"Afhalen in de winkel". That re-emit carries the *same* data
      // we already pushed, so skip it: pushing here would fire a cart update mid-toggle and race the
      // shipping-rate commit. Genuine selection changes still differ and fall through.
      if (JSON.stringify(detail) === lastPushedSelection) {
        return;
      }

      // Schedule a debounced, settle-aware push so the cart recalculates delivery-options fees in the
      // order summary without firing a request per tick. Only the latest selection is sent.
      pendingSelection = detail;
      scheduleCartUpdate();
    };

    // Flush any debounced selection the moment the customer places the order. Without this, an
    // order placed within the debounce window would recalculate fees from the *previous* selection
    // (the session the fee calc reads is only written by extensionCartUpdate) while the order is
    // saved with the latest one — charging a fee that doesn't match the chosen delivery option.
    let wasBeforeProcessing = false;
    const unsubscribe = wp.data.subscribe(() => {
      const isBeforeProcessing = Boolean(select.isBeforeProcessing?.());

      // Rising edge only, and only when there's actually something pending to flush. Push immediately
      // (cancelling any scheduled settle wait): the session must be written before the order POST, and
      // no shipping toggle is in progress at order placement.
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
