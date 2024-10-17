import {getSetting} from '@woocommerce/settings';
import {NAME} from '../constants';

type DeliveryOptionsInputs = {
  style?: string;
  context: string;
  enabled: boolean;
};

// eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
export const getDeliveryOptionsData = () => {
  return getSetting<DeliveryOptionsInputs>(`${NAME}_data`, {});
};
