import WcButton from '../components/pdk/WcButton.vue';
import WcCard from '../components/pdk/WcCard.vue';
import WcCheckboxInput from '../components/pdk/WcCheckboxInput.vue';
import WcFormGroup from '../components/pdk/WcFormGroup.vue';
import WcModal from '../components/pdk/WcModal.vue';
import WcMultiCheckbox from '../components/pdk/WcMultiCheckbox.vue';
import WcNotification from '../components/pdk/WcNotification.vue';
import WcNumberInput from '../components/pdk/WcNumberInput.vue';
import WcSelectInput from '../components/pdk/WcSelectInput.vue';
import WcTable from '../components/pdk/WcTable.vue';
import WcTextInput from '../components/pdk/WcTextInput.vue';
import WcToggleInput from '../components/pdk/WcToggleInput.vue';
import {executePdkComponentTests} from '@myparcel/pdk-component-tests';

executePdkComponentTests({
  PdkButton: WcButton,
  // PdkButtonGroup: Bootstrap4ButtonGroup,
  PdkCard: WcCard,
  PdkCheckboxInput: WcCheckboxInput,
  // PdkCol: Bootstrap4Col,
  // PdkCurrencyInput: DefaultCurrencyInput,
  // PdkDropdownButton: Bootstrap4DropdownButton,
  PdkFormGroup: WcFormGroup,
  // PdkIcon: DefaultIcon,
  // PdkImage: Bootstrap4Image,
  // PdkLink: DefaultLink,
  PdkModal: WcModal,
  PdkMultiCheckbox: WcMultiCheckbox,
  // PdkMultiRadio: DefaultMultiRadio,
  PdkNotification: WcNotification,
  PdkNumberInput: WcNumberInput,
  // PdkPluginSettingsWrapper: DefaultPluginSettingsWrapper,
  // PdkRadioInput: Bootstrap4RadioInput,
  // PdkRow: Bootstrap4Row,
  PdkSelectInput: WcSelectInput,
  PdkTable: WcTable,
  // PdkTableCol: DefaultTableCol,
  // PdkTableRow: DefaultTableRow,
  PdkTextInput: WcTextInput,
  PdkToggleInput: WcToggleInput,
});
