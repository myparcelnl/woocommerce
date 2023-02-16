import './assets/scss/index.scss';
import {DefaultCurrencyInput, DefaultHeading, DefaultLink, DefaultMultiRadio} from '@myparcel-pdk/admin-components/src';
import {LogLevel, createPdkAdmin, useModalStore} from '@myparcel-pdk/admin/src';
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
  WcModal,
  WcMultiCheckbox,
  WcNotification,
  WcNumberInput,
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
  WcTimeInput,
  WcToggleInput,
} from './components/pdk';
import {h} from 'vue';

const FADE = 'fade';

createPdkAdmin({
  logLevel: LogLevel.DEBUG,

  formConfig: {
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
    PdkModal: WcModal,
    PdkMultiCheckbox: WcMultiCheckbox,
    PdkMultiRadio: DefaultMultiRadio,
    PdkNotification: WcNotification,
    PdkNumberInput: WcNumberInput,
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
    PdkTimeInput: WcTimeInput,
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

  onCreateStore() {
    const modalStore = useModalStore();

    const closeOnEscape = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        modalStore.close();
      }
    };

    modalStore.onOpen(() => {
      document.addEventListener('keydown', closeOnEscape);
    });

    modalStore.onClose(() => {
      document.removeEventListener('keydown', closeOnEscape);
    });
  },

  onCreated() {
    jQuery('#doaction').on('click', (event) => {
      const bulkSelect = jQuery('#bulk-action-selector-top');
      const value = bulkSelect.val();
      const action: string | null = value ? String(value) : null;

      if (action?.startsWith('myparcelnl')) {
        event.preventDefault();
      }
    });
  },
});
