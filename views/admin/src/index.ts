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
import WcFormGroup from './components/WcFormGroup.vue';
import WcModal from './components/WcModal.vue';

createPdkFrontend({
  logLevel: LogLevel.DEBUG,
  components: {
    PdkButton: Bootstrap4Button,
    PdkButtonGroup: Bootstrap4ButtonGroup,
    PdkCard: Bootstrap4Card,
    PdkCheckboxInput: Bootstrap4CheckboxInput,
    PdkCol: Bootstrap4Col,
    PdkCurrencyInput: DefaultCurrencyInput,
    PdkDropdownButton: Bootstrap4DropdownButton,
    PdkFormGroup: WcFormGroup,
    PdkIcon: DefaultIcon,
    PdkImage: Bootstrap4Image,
    PdkLink: DefaultLink,
    PdkModal: WcModal,
    PdkMultiCheckbox: DefaultMultiCheckbox,
    PdkMultiRadio: DefaultMultiRadio,
    PdkNotification: Bootstrap4Notification,
    PdkNumberInput: Bootstrap4NumberInput,
    PdkPluginSettingsWrapper: DefaultPluginSettingsWrapper,
    PdkRadioInput: Bootstrap4RadioInput,
    PdkRow: Bootstrap4Row,
    PdkSelectInput: Bootstrap4SelectInput,
    PdkTable: Bootstrap4Table,
    PdkTableCol: DefaultTableCol,
    PdkTableRow: DefaultTableRow,
    PdkTextInput: Bootstrap4TextInput,
    PdkToggleInput: DefaultToggleInput,
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
