import './assets/scss/index.scss';
import {
  Bootstrap4Button,
  Bootstrap4ButtonGroup,
  Bootstrap4Card,
  Bootstrap4CheckboxInput,
  Bootstrap4Col,
  Bootstrap4DropdownButton,
  Bootstrap4Image,
  Bootstrap4Notification,
  Bootstrap4NumberInput,
  Bootstrap4RadioInput,
  Bootstrap4Row,
  Bootstrap4SelectInput,
  Bootstrap4Table,
  Bootstrap4TextInput,
} from '@myparcel/pdk-preset-bootstrap4';
import {
  DefaultCurrencyInput,
  DefaultIcon,
  DefaultLink,
  DefaultMultiCheckbox,
  DefaultMultiRadio,
  DefaultPluginSettingsWrapper,
  DefaultTableCol,
  DefaultTableRow,
  DefaultToggleInput,
} from '@myparcel/pdk-components';
import {LogLevel, createPdkFrontend, useModalStore} from '@myparcel/pdk-frontend';
import WcFormGroup from './components/pdk/WcFormGroup.vue';
import WcModal from './components/pdk/WcModal.vue';
import WcButton from './components/pdk/WcButton.vue';
import WcCard from './components/pdk/WcCard.vue';
import WcToggleInput from './components/pdk/WcToggleInput.vue';
import WcNotification from './components/pdk/WcNotification.vue';
import WcTable from './components/pdk/WcTable.vue';
import WcCheckboxInput from './components/pdk/WcCheckboxInput.vue';
import WcMultiCheckbox from './components/pdk/WcMultiCheckbox.vue';

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
    PdkNumberInput: Bootstrap4NumberInput,
    PdkPluginSettingsWrapper: DefaultPluginSettingsWrapper,
    PdkRadioInput: Bootstrap4RadioInput,
    PdkRow: Bootstrap4Row,
    PdkSelectInput: Bootstrap4SelectInput,
    PdkTable: WcTable,
    PdkTableCol: DefaultTableCol,
    PdkTableRow: DefaultTableRow,
    PdkTextInput: Bootstrap4TextInput,
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
