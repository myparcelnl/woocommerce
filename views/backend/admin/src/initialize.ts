// noinspection JSUnusedGlobalSymbols

import {
  DefaultCurrencyInput,
  DefaultHeading,
  DefaultLink,
  DefaultNumberInput,
  DefaultRadioGroup,
  DefaultTimeInput,
} from '@myparcel-pdk/admin-preset-default/src';
import {LogLevel, createPdkAdmin} from '@myparcel-pdk/admin/src';
import {
  PdkShipmentLabelWrapper,
  WcBox,
  WcButton,
  WcButtonGroup,
  WcCheckboxGroup,
  WcCheckboxInput,
  WcCol,
  WcDropOffInput,
  WcDropdownButton,
  WcFormGroup,
  WcIcon,
  WcImage,
  WcLoader,
  WcModal,
  WcNotification,
  WcPluginSettingsWrapper,
  WcRadioInput,
  WcRow,
  WcSelectInput,
  WcTabNavButton,
  WcTabNavButtonWrapper,
  WcTable,
  WcTableCol,
  WcTableRow,
  WcTextArea,
  WcTextInput,
  WcToggleInput,
} from './components/pdk';
import {closeModalOnEscape, initializeTooltips, listenForBulkActions} from './hooks';
import {get} from '@vueuse/core';
import {h} from 'vue';

const FADE = 'fade';
// eslint-disable-next-line max-lines-per-function
export const initialize = (): void => {
  createPdkAdmin({
    logLevel: LogLevel.Debug,

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
    },

    components: {
      PdkBox: WcBox,
      PdkButton: WcButton,
      PdkButtonGroup: WcButtonGroup,
      PdkCheckboxGroup: WcCheckboxGroup,
      PdkCheckboxInput: WcCheckboxInput,
      PdkCodeEditor: WcTextArea,
      PdkCol: WcCol,
      PdkCurrencyInput: DefaultCurrencyInput,
      PdkDropOffInput: WcDropOffInput,
      PdkDropdownButton: WcDropdownButton,
      PdkFormGroup: WcFormGroup,
      PdkHeading: DefaultHeading,
      PdkIcon: WcIcon,
      PdkImage: WcImage,
      PdkLink: DefaultLink,
      PdkLoader: WcLoader,
      PdkModal: WcModal,
      PdkNotification: WcNotification,
      PdkNumberInput: DefaultNumberInput,
      PdkPluginSettingsWrapper: WcPluginSettingsWrapper,
      PdkRadioGroup: DefaultRadioGroup,
      PdkRadioInput: WcRadioInput,
      PdkRow: WcRow,
      PdkSelectInput: WcSelectInput,
      PdkShipmentLabelWrapper,
      PdkTabNavButton: WcTabNavButton,
      PdkTabNavButtonWrapper: WcTabNavButtonWrapper,
      PdkTable: WcTable,
      PdkTableCol: WcTableCol,
      PdkTableRow: WcTableRow,
      PdkTextArea: WcTextArea,
      PdkTextInput: WcTextInput,
      PdkTimeInput: DefaultTimeInput,
      PdkToggleInput: WcToggleInput,
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

    onRendered() {
      initializeTooltips();
    },
  });
};
