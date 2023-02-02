import {EVENT_UPDATE_CONFIG} from '../data';
import {triggerEvent} from '../triggerEvent';
import {validateMyParcelConfig} from './validateMyParcelConfig';

/**
 * Fetch and update the delivery options config. For use with changing shipping methods, for example, as doing so
 *  changes the prices of delivery and any extra options.
 */
export const updateDeliveryOptionsConfig = () => {
  validateMyParcelConfig();
  jQuery.ajax({
    type: 'GET',
    url: MyParcelNLData.ajaxUrl,
    async: false,
    data: {
      action: 'myparcelnl_get_delivery_options_config',
    },
    success(data) {
      console.log(data);
      console.log(JSON.parse(data));
      const {config} = JSON.parse(data);
      window.MyParcelConfig.config = config;
      triggerEvent(EVENT_UPDATE_CONFIG);
    },
  });
};
