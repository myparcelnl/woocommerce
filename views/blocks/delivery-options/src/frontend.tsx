/* eslint-disable no-underscore-dangle */
import React, {useEffect} from 'react';
import {registerPlugin} from '@wordpress/plugins';
import {ExperimentalOrderShippingPackages} from '@woocommerce/blocks-checkout';
import {CHECKOUT_STORE_KEY} from '@woocommerce/block-data';
import {getDeliveryOptionsData} from './utils/getDeliveryOptionsData';
import {NAME} from './constants';

const triggerPdkSetup = () => {
  document.dispatchEvent(new CustomEvent('myparcel_wc_delivery_options_ready'));
};

// eslint-disable-next-line @typescript-eslint/naming-convention
const DeliveryOptionsWrapper = () => {
  const data = getDeliveryOptionsData();

  useEffect(() => {
    const dispatch = wp.data.dispatch(CHECKOUT_STORE_KEY);

    document.addEventListener('myparcel_updated_delivery_options', (event) => {
      void dispatch.__internalSetExtensionData(NAME, (event as CustomEvent).detail);
    });

    triggerPdkSetup();
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

// eslint-disable-next-line @typescript-eslint/naming-convention
const RootComponent: React.FC = () => {
  const data = getDeliveryOptionsData();

  // If delivery options are disabled, dispatch the event that triggers pdk setup here.
  useEffect(() => {
    if (data.enabled) {
      return;
    }

    triggerPdkSetup();
  }, [data]);

  if (!data.enabled) {
    return <ExperimentalOrderShippingPackages />;
  }

  return (
    <ExperimentalOrderShippingPackages>
      <DeliveryOptionsWrapper />
    </ExperimentalOrderShippingPackages>
  );
};

registerPlugin(NAME, {render: RootComponent, scope: 'woocommerce-checkout'});
