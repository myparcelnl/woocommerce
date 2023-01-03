import './assets/scss/index.scss';
import {
  Bootstrap4ButtonGroup,
  Bootstrap4Col,
  Bootstrap4DropdownButton,
  Bootstrap4Image,
  Bootstrap4RadioInput,
  Bootstrap4Row,
} from '@myparcel/pdk-preset-bootstrap4';
import {
  DefaultCurrencyInput,
  DefaultIcon,
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
import WcFormGroup from './components/pdk/WcFormGroup.vue';
import WcModal from './components/pdk/WcModal.vue';
import WcMultiCheckbox from './components/pdk/WcMultiCheckbox.vue';
import WcNotification from './components/pdk/WcNotification.vue';
import WcNumberInput from './components/pdk/WcNumberInput.vue';
import WcSelectInput from './components/pdk/WcSelectInput.vue';
import WcTable from './components/pdk/WcTable.vue';
import WcTextInput from './components/pdk/WcTextInput.vue';
import WcToggleInput from './components/pdk/WcToggleInput.vue';

createPdkFrontend({
  logLevel: LogLevel.DEBUG,
  components: {
    PdkButton: WcButton,
    PdkButtonGroup: Bootstrap4ButtonGroup,
    PdkCard: WcCard,
    PdkCheckboxInput: WcCheckboxInput,
    PdkCol: Bootstrap4Col,
    PdkCurrencyInput: DefaultCurrencyInput,
    PdkDropdownButton: Bootstrap4DropdownButton,
    PdkFormGroup: WcFormGroup,
    PdkIcon: DefaultIcon,
    PdkImage: Bootstrap4Image,
    PdkLink: DefaultLink,
    PdkModal: WcModal,
    PdkMultiCheckbox: WcMultiCheckbox,
    PdkMultiRadio: DefaultMultiRadio,
    PdkNotification: WcNotification,
    PdkNumberInput: WcNumberInput,
    PdkPluginSettingsWrapper: DefaultPluginSettingsWrapper,
    PdkRadioInput: Bootstrap4RadioInput,
    PdkRow: Bootstrap4Row,
    PdkSelectInput: WcSelectInput,
    PdkTable: WcTable,
    PdkTableCol: DefaultTableCol,
    PdkTableRow: DefaultTableRow,
    PdkTextInput: WcTextInput,
    PdkToggleInput: WcToggleInput,
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
