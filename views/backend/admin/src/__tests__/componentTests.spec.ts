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
} from '../components/pdk';
import {executePdkComponentTests} from '@myparcel-pdk/component-tests';

executePdkComponentTests({
  PdkButtonGroup: WcButtonGroup,
  PdkCol: WcCol,
  PdkPluginSettingsWrapper: WcPluginSettingsWrapper,
  PdkRow: WcRow,
  PdkTabNavButtonWrapper: WcTabNavButtonWrapper,
  PdkButton: WcButton,
  PdkCard: WcCard,
  PdkCheckboxInput: WcCheckboxInput,
  PdkDropdownButton: WcDropdownButton,
  PdkFormGroup: WcFormGroup,
  PdkIcon: WcIcon,
  PdkImage: WcImage,
  PdkModal: WcModal,
  PdkMultiCheckbox: WcMultiCheckbox,
  PdkNotification: WcNotification,
  PdkNumberInput: WcNumberInput,
  PdkRadioInput: WcRadioInput,
  PdkSelectInput: WcSelectInput,
  PdkTable: WcTable,
  PdkTableCol: WcTableCol,
  PdkTableRow: WcTableRow,
  PdkTabNavButton: WcTabNavButton,
  PdkTextInput: WcTextInput,
  PdkToggleInput: WcToggleInput,
});
