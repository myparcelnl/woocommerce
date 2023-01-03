/* eslint-disable @typescript-eslint/naming-convention */

declare global {
  interface Window {
    woocommerce_params: {
      locale: string;
      i18n_required_text: string;
    };
    MyParcelNLData: {
      ajaxUrl: string;
      allowedShippingMethods: string[];
      alwaysShow: 0 | 1;
      disallowedShippingMethods: string[];
      hiddenInputName: string;
      isUsingSplitAddressFields: 0 | 1;
      splitAddressFieldsCountries: string[];
    };
  }
}

export {};
