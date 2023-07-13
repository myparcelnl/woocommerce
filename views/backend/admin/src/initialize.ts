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
} from '@myparcel-pdk/admin-preset-default';
import {DashIconsIcon} from '@myparcel-pdk/admin-preset-dashicons';
import {LogLevel, createPdkAdmin} from '@myparcel-pdk/admin';
import {closeModalOnEscape, listenForBulkActions} from './hooks';
import WcProductSettingsFormGroup from './components/pdk/WcProductSettingsFormGroup.vue';
import {
  WcBox,
  WcButton,
  WcButtonGroup,
  WcCheckboxGroup,
  WcCheckboxInput,
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

const FADE = 'fade';

// eslint-disable-next-line max-lines-per-function
export const initialize = (): void => {
  createPdkAdmin({
    logLevel: import.meta.env.MODE === 'development' ? LogLevel.Info : LogLevel.Off,

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
      modal: {
        form: {
          attributes: {
            // Omit the "wrap" class in modal forms to avoid the horizontal padding.
            class: 'woocommerce',
          },
        },
      },
      productSettings: {
        form: {
          tag: 'div',
        },
        field: {
          wrapper: WcProductSettingsFormGroup,
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
      shipmentRow: 'mypa-shipment-row',
      tabNavigation: FADE,
      tableRow: FADE,
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
