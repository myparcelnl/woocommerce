import './assets/scss/index.scss';
import {DefaultCurrencyInput, DefaultHeading, DefaultLink, DefaultMultiRadio} from '@myparcel/pdk-components';
import {LogLevel, createPdkFrontend, useModalStore} from '@myparcel/pdk-frontend';
import {
  WcButton,
  WcButtonGroup,
  WcCard,
  WcCheckboxInput,
  WcCol,
  WcDropdownButton,
  WcFormGroup,
  WcIcon,
  WcImage,
  WcModal,
  WcMultiCheckbox,
  WcNotification,
  WcNumberInput,
  WcOrderCardShipmentsWrapper,
  WcOrderGridShipmentWrapper,
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
import {h} from 'vue';

const FADE = 'fade';

createPdkFrontend({
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
    PdkButton: WcButton,
    PdkButtonGroup: WcButtonGroup,
    PdkCard: WcCard,
    PdkCheckboxInput: WcCheckboxInput,
    PdkCol: WcCol,
    PdkCurrencyInput: DefaultCurrencyInput,
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
    PdkOrderCardShipmentsWrapper: WcOrderCardShipmentsWrapper,
    PdkOrderGridShipmentWrapper: WcOrderGridShipmentWrapper,
    PdkPluginSettingsWrapper: WcPluginSettingsWrapper,
    PdkRadioInput: WcRadioInput,
    PdkRow: WcRow,
    PdkSelectInput: WcSelectInput,
    PdkTabNavButton: WcTabNavButton,
    PdkTabNavButtonWrapper: WcTabNavButtonWrapper,
    PdkTable: WcTable,
    PdkTableCol: WcTableCol,
    PdkTableRow: WcTableRow,
    PdkTextInput: WcTextInput,
    PdkToggleInput: WcToggleInput,
  },

  cssUtilities: {
    textCenter: 'mypa-text-center',
    whitespaceNoWrap: 'mypa-whitespace-nowrap',
  },

  transitions: {
    modal: FADE,
    modalBackdrop: FADE,
    notification: FADE,
    shipmentCard: FADE,
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
