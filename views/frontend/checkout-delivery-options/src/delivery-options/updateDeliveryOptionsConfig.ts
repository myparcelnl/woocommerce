import {EVENT_UPDATE_CONFIG} from '../data';
import {triggerEvent} from '../triggerEvent';
import {useSettingsStore} from '../store';

/**
 * Fetch and update the delivery options config. For use with changing shipping methods, for example, as doing so
 *  changes the prices of delivery and any extra options.
 */
export const updateDeliveryOptionsConfig = (): void => {
  const settings = useSettingsStore();

  void jQuery.ajax({
    type: 'GET',
    url: settings.state.ajaxUrl,
    data: {
      action: settings.state.ajaxHookGetConfig,
    },
    success(data) {
      const {config} = JSON.parse(data);

      triggerEvent(EVENT_UPDATE_CONFIG, {config});
    },
  });
};
