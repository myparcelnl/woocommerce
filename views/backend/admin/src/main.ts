import './assets/scss/index.scss';
import {
  DefaultCurrencyInput,
  DefaultHeading,
  DefaultLink,
  DefaultMultiRadio,
  DefaultNumberInput,
  DefaultTimeInput,
} from '@myparcel-pdk/admin-components/src';
import {LogLevel, createPdkAdmin} from '@myparcel-pdk/admin/src';
import {
  PdkShipmentLabelWrapper,
  WcBox,
  WcButton,
  WcButtonGroup,
  WcCheckboxInput,
  WcCol,
  WcDropOffInput,
  WcDropdownButton,
  WcFormGroup,
  WcIcon,
  WcImage,
  WcLoader,
  WcModal,
  WcMultiCheckbox,
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
  WcTextInput,
  WcToggleInput,
} from './components/pdk';
import {get} from '@vueuse/core';
import {h} from 'vue';
import {onCreateStore} from './hooks/onCreateStore';
import {onInitialized} from './hooks/onInitialized';

const FADE = 'fade';

createPdkAdmin({
  logLevel: LogLevel.DEBUG,

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

  components: {
    PdkBox: WcBox,
    PdkButton: WcButton,
    PdkButtonGroup: WcButtonGroup,
    PdkCheckboxInput: WcCheckboxInput,
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
    PdkMultiCheckbox: WcMultiCheckbox,
    PdkMultiRadio: DefaultMultiRadio,
    PdkNotification: WcNotification,
    PdkNumberInput: DefaultNumberInput,
    PdkPluginSettingsWrapper: WcPluginSettingsWrapper,
    PdkRadioInput: WcRadioInput,
    PdkRow: WcRow,
    PdkSelectInput: WcSelectInput,
    PdkShipmentLabelWrapper: PdkShipmentLabelWrapper,
    PdkTabNavButton: WcTabNavButton,
    PdkTabNavButtonWrapper: WcTabNavButtonWrapper,
    PdkTable: WcTable,
    PdkTableCol: WcTableCol,
    PdkTableRow: WcTableRow,
    PdkTextInput: WcTextInput,
    PdkTimeInput: DefaultTimeInput,
    PdkToggleInput: WcToggleInput,
  },

  cssUtilities: {
    animationSpin: 'mypa-animate-spin',
    displayFlex: 'mypa-flex',
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
    shipmentRow: FADE,
    tabNavigation: FADE,
    tableRow: FADE,
  },

  onCreateStore: onCreateStore,
  onInitialized: onInitialized,
});
