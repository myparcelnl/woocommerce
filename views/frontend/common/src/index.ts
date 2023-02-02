/* eslint-disable @typescript-eslint/naming-convention */

declare global {
  type StringBoolean = '0' | '1';

  interface Window {
    woocommerce_params: {
      locale: string;
      i18n_required_text: string;
    };
    MyParcelNLData: {
      ajaxUrl: string;
      allowedShippingMethods: string[];
      alwaysShow: StringBoolean;
      disallowedShippingMethods: string[];
      hiddenInputName: string;
      isUsingSplitAddressFields: StringBoolean;
      splitAddressFieldsCountries: string[];
    };
  }
}

export {};
