import './assets/scss/index.scss';
import {Bootstrap4ButtonGroup, Bootstrap4DropdownButton, Bootstrap4RadioInput} from '@myparcel/pdk-preset-bootstrap4';
import {
  DefaultCurrencyInput,
  DefaultLink,
  DefaultMultiRadio,
  DefaultPluginSettingsWrapper,
  DefaultTableCol,
  DefaultTableRow,
} from '@myparcel/pdk-components';
import {LogLevel, createPdkFrontend, useModalStore} from '@myparcel/pdk-frontend';
import WcButton from './components/pdk/WcButton.vue';
import WcCard from './components/pdk/WcCard.vue';
import WcCheckboxInput from './components/pdk/WcCheckboxInput.vue';
import WcCol from './components/pdk/WcCol';
import WcFormGroup from './components/pdk/WcFormGroup.vue';
import WcIcon from './components/pdk/WcIcon.vue';
import WcImage from './components/pdk/WcImage.vue';
import WcModal from './components/pdk/WcModal.vue';
import WcMultiCheckbox from './components/pdk/WcMultiCheckbox.vue';
import WcNotification from './components/pdk/WcNotification.vue';
import WcNumberInput from './components/pdk/WcNumberInput.vue';
import WcRow from './components/pdk/WcRow';
import WcSelectInput from './components/pdk/WcSelectInput.vue';
import WcTable from './components/pdk/WcTable.vue';
import WcTextInput from './components/pdk/WcTextInput.vue';
import WcToggleInput from './components/pdk/WcToggleInput.vue';
import {h} from 'vue';

createPdkFrontend({
  logLevel: LogLevel.DEBUG,

  formConfig: {
    form: {
      attributes: {
        class: 'woocommerce',
      },
      wrapper: h('table', {class: 'form-table'}),
    },
  },

  components: {
    PdkButton: WcButton,
    PdkButtonGroup: Bootstrap4ButtonGroup,
    PdkCard: WcCard,
    PdkCheckboxInput: WcCheckboxInput,
    PdkCol: WcCol,
    PdkCurrencyInput: DefaultCurrencyInput,
    PdkDropdownButton: Bootstrap4DropdownButton,
    PdkFormGroup: WcFormGroup,
    PdkIcon: WcIcon,
    PdkImage: WcImage,
    PdkLink: DefaultLink,
    PdkModal: WcModal,
    PdkMultiCheckbox: WcMultiCheckbox,
    PdkMultiRadio: DefaultMultiRadio,
    PdkNotification: WcNotification,
    PdkNumberInput: WcNumberInput,
    PdkPluginSettingsWrapper: DefaultPluginSettingsWrapper,
    PdkRadioInput: Bootstrap4RadioInput,
    PdkRow: WcRow,
    PdkSelectInput: WcSelectInput,
    PdkTable: WcTable,
    PdkTableCol: DefaultTableCol,
    PdkTableRow: DefaultTableRow,
    PdkTextInput: WcTextInput,
    PdkToggleInput: WcToggleInput,
  },

  cssUtilities: {
    textCenter: 'text-center',
    whitespaceNoWrap: 'whitespace-nowrap',
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
});
