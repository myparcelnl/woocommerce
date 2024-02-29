/* eslint-disable no-underscore-dangle */
import {useEffect} from 'react';
import {registerPlugin} from '@wordpress/plugins';
import {getSetting} from '@woocommerce/settings';
import {ExperimentalOrderShippingPackages} from '@woocommerce/blocks-checkout';

const NAME = 'myparcelnl-delivery-options';

// eslint-disable-next-line @typescript-eslint/naming-convention
const DeliveryOptionsWrapper = () => {
  const {CHECKOUT_STORE_KEY} = window.wc.wcBlocksData;
  const data = getSetting(`${NAME}_data`) || {};

  useEffect(() => {
    const dispatch = wp.data.dispatch(CHECKOUT_STORE_KEY) as Record<string, Function>;

    document.addEventListener('myparcel_updated_delivery_options', (event) => {
      dispatch.__internalSetExtensionData(NAME, (event as CustomEvent).detail);
    });

    document.dispatchEvent(new CustomEvent('myparcel_wc_delivery_options_ready'));
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

const render = () => {
  return (
    // eslint-disable-next-line prettier/prettier
    <ExperimentalOrderShippingPackages><DeliveryOptionsWrapper /></ExperimentalOrderShippingPackages>
  );
};

registerPlugin(NAME, {
  render,
  scope: 'woocommerce-checkout',
});
