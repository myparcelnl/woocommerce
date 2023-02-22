import {
  WcBox,
  WcButton,
  WcButtonGroup,
  WcCheckboxInput,
  WcCol,
  WcDropdownButton,
  WcFormGroup,
  WcIcon,
  WcImage,
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
} from '../components/pdk';
import {executePdkComponentTests} from '@myparcel-pdk/admin-component-tests';

executePdkComponentTests({
  PdkBox: WcBox,
  PdkButton: WcButton,
  PdkButtonGroup: WcButtonGroup,
  PdkCheckboxInput: WcCheckboxInput,
  PdkCol: WcCol,
  PdkDropdownButton: WcDropdownButton,
  PdkFormGroup: WcFormGroup,
  PdkIcon: WcIcon,
  PdkImage: WcImage,
  PdkModal: WcModal,
  PdkMultiCheckbox: WcMultiCheckbox,
  PdkNotification: WcNotification,
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
});
