// noinspection JSUnusedGlobalSymbols

import {h} from 'vue';
import {get} from '@vueuse/core';
import {
  DefaultBadge,
  DefaultCurrencyInput,
  DefaultHeading,
  DefaultLink,
  DefaultMultiSelectInput,
  DefaultNumberInput,
  DefaultRadioGroup,
  DefaultSettingsDivider,
  DefaultTimeInput,
  DefaultShippingMethodsInput,
} from '@myparcel-pdk/admin-preset-default';
import {DashIconsIcon} from '@myparcel-pdk/admin-preset-dashicons';
import {
  FORM_KEY_CHILD_PRODUCT_SETTINGS,
  FORM_KEY_MODAL,
  FORM_KEY_PRODUCT_SETTINGS,
  LogLevel,
  createPdkAdmin,
} from '@myparcel-pdk/admin';
import {closeModalOnEscape, listenForBulkActions} from './hooks';
import {
  WcBox,
  WcButton,
  WcButtonGroup,
  WcCheckboxGroup,
  WcCheckboxInput,
  WcChildProductSettingsFormGroup,
  WcCodeEditor,
  WcCol,
  WcDropOffInput,
  WcDropdownButton,
  WcFormGroup,
  WcImage,
  WcLoader,
  WcModal,
  WcNotification,
  WcPluginSettingsWrapper,
  WcProductSettingsFormGroup,
  WcRadioInput,
  WcRow,
  WcSelectInput,
  WcShipmentLabelWrapper,
  WcTabNavButton,
  WcTabNavButtonWrapper,
  WcTable,
  WcTableCol,
  WcTableRow,
  WcTextArea,
  WcTextInput,
  WcToggleInput,
  WcTriStateInput,
} from './components/pdk';

const FADE = 'mypa-fade';

// eslint-disable-next-line max-lines-per-function
export const initialize = (): void => {
  createPdkAdmin({
    logLevel: import.meta.env.MODE === 'development' ? LogLevel.Debug : LogLevel.Info,

    formConfig: {
      fieldDefaults: {
        afterValidate(field) {
          const valid = get(field.isValid);

          field.form.element.classList.toggle('form-invalid', !valid);
        },
      },

      form: {
        attributes: {
          class: 'wrap woocommerce',
        },
        wrapper: h('table', {class: 'form-table'}),
      },
    },

    formConfigOverrides: {
      [FORM_KEY_MODAL]: {
        form: {
          attributes: {
            // Omit the "wrap" class in modal forms to avoid the horizontal padding.
            class: 'woocommerce',
          },
        },
      },

      [FORM_KEY_PRODUCT_SETTINGS]: {
        form: {
          tag: 'div',
        },
        field: {
          wrapper: WcProductSettingsFormGroup,
        },
      },

      [FORM_KEY_CHILD_PRODUCT_SETTINGS]: {
        field: {
          wrapper: WcChildProductSettingsFormGroup,
        },
        generateFieldId(field) {
          return `myparcelnl-${field.form.name}-${field.name}`;
        },
      },
    },

    components: {
      PdkBadge: DefaultBadge,
      PdkBox: WcBox,
      PdkButton: WcButton,
      PdkButtonGroup: WcButtonGroup,
      PdkCheckboxGroup: WcCheckboxGroup,
      PdkCheckboxInput: WcCheckboxInput,
      PdkCodeEditor: WcCodeEditor,
      PdkCol: WcCol,
      PdkCurrencyInput: DefaultCurrencyInput,
      PdkDropOffInput: WcDropOffInput,
      PdkDropdownButton: WcDropdownButton,
      PdkFormGroup: WcFormGroup,
      PdkHeading: DefaultHeading,
      PdkIcon: DashIconsIcon,
      PdkImage: WcImage,
      PdkLink: DefaultLink,
      PdkLoader: WcLoader,
      PdkModal: WcModal,
      PdkMultiSelectInput: DefaultMultiSelectInput,
      PdkNotification: WcNotification,
      PdkNumberInput: DefaultNumberInput,
      PdkPluginSettingsWrapper: WcPluginSettingsWrapper,
      PdkRadioGroup: DefaultRadioGroup,
      PdkRadioInput: WcRadioInput,
      PdkRow: WcRow,
      PdkSelectInput: WcSelectInput,
      PdkSettingsDivider: DefaultSettingsDivider,
      PdkShipmentLabelWrapper: WcShipmentLabelWrapper,
      PdkShippingMethodsInput: DefaultShippingMethodsInput,
      PdkTabNavButton: WcTabNavButton,
      PdkTabNavButtonWrapper: WcTabNavButtonWrapper,
      PdkTable: WcTable,
      PdkTableCol: WcTableCol,
      PdkTableRow: WcTableRow,
      PdkTextArea: WcTextArea,
      PdkTextInput: WcTextInput,
      PdkTimeInput: DefaultTimeInput,
      PdkToggleInput: WcToggleInput,
      PdkTriStateInput: WcTriStateInput,
    },

    cssUtilities: {
      animationSpin: 'mypa-animate-spin',
      animationLoading: 'mypa-opacity-50 mypa-pointer-events-none mypa-select-none mypa-animate-pulse',
      cursorDefault: 'mypa-cursor-default',
      displayFlex: 'mypa-flex',
      flexGrow: 'mypa-flex-grow',
      marginLAuto: 'mypa-ml-auto',
      marginYAuto: 'mypa-my-auto',
      textCenter: 'mypa-text-center',
      textColorError: 'mypa-text-red-500',
      textColorSuccess: 'mypa-text-green-500',
      whitespaceNoWrap: 'mypa-whitespace-nowrap',
    },

    transitions: {
      modal: FADE,
      modalBackdrop: FADE,
      notification: FADE,
      shipmentBox: FADE,
      tabNavigation: FADE,
      tableRow: 'mypa-table-row',
    },

    onCreateStore() {
      closeModalOnEscape();
    },

    onInitialized() {
      listenForBulkActions();
    },

    generateFieldId(field) {
      return `myparcelnl-${field.name}`;
    },
  });
};
