import {DefaultHeading} from '@myparcel-pdk/admin-preset-default';
import {executePdkComponentTests} from '@myparcel-pdk/admin-component-tests';
import {AdminComponent} from '@myparcel-pdk/admin';
import WcTriStateInput from './WcTriStateInput.vue';
import WcToggleInput from './WcToggleInput.vue';
import WcTextInput from './WcTextInput.vue';
import WcTextArea from './WcTextArea.vue';
import WcTableRow from './WcTableRow.vue';
import WcTableCol from './WcTableCol.vue';
import WcTable from './WcTable.vue';
import WcTabNavButtonWrapper from './WcTabNavButtonWrapper.vue';
import WcTabNavButton from './WcTabNavButton.vue';
import WcShipmentLabelWrapper from './WcShipmentLabelWrapper.vue';
import WcSelectInput from './WcSelectInput.vue';
import WcRow from './WcRow.vue';
import WcRadioInput from './WcRadioInput.vue';
import WcPluginSettingsWrapper from './WcPluginSettingsWrapper.vue';
import WcNotification from './WcNotification.vue';
import WcModal from './WcModal.vue';
import WcLoader from './WcLoader.vue';
import WcImage from './WcImage.vue';
import WcFormGroup from './WcFormGroup.vue';
import WcDropdownButton from './WcDropdownButton.vue';
import WcDropOffInput from './WcDropOffInput.vue';
import WcCol from './WcCol.vue';
import WcCodeEditor from './WcCodeEditor.vue';
import WcCheckboxInput from './WcCheckboxInput.vue';
import WcCheckboxGroup from './WcCheckboxGroup.vue';
import WcButtonGroup from './WcButtonGroup.vue';
import WcButton from './WcButton.vue';
import WcBox from './WcBox.vue';

executePdkComponentTests({
  [AdminComponent.Box]: WcBox,
  [AdminComponent.ButtonGroup]: WcButtonGroup,
  [AdminComponent.Button]: WcButton,
  [AdminComponent.CheckboxGroup]: WcCheckboxGroup,
  [AdminComponent.CheckboxInput]: WcCheckboxInput,
  [AdminComponent.CodeEditor]: WcCodeEditor,
  [AdminComponent.Col]: WcCol,
  [AdminComponent.DropOffInput]: WcDropOffInput,
  [AdminComponent.DropdownButton]: WcDropdownButton,
  [AdminComponent.Heading]: DefaultHeading,
  [AdminComponent.FormGroup]: WcFormGroup,
  [AdminComponent.Image]: WcImage,
  [AdminComponent.Loader]: WcLoader,
  [AdminComponent.Modal]: WcModal,
  [AdminComponent.Notification]: WcNotification,
  [AdminComponent.PluginSettingsWrapper]: WcPluginSettingsWrapper,
  [AdminComponent.RadioInput]: WcRadioInput,
  [AdminComponent.Row]: WcRow,
  [AdminComponent.SelectInput]: WcSelectInput,
  [AdminComponent.ShipmentLabelWrapper]: WcShipmentLabelWrapper,
  [AdminComponent.TabNavButtonWrapper]: WcTabNavButtonWrapper,
  [AdminComponent.TabNavButton]: WcTabNavButton,
  [AdminComponent.TableCol]: WcTableCol,
  [AdminComponent.TableRow]: WcTableRow,
  [AdminComponent.Table]: WcTable,
  [AdminComponent.TextArea]: WcTextArea,
  [AdminComponent.TextInput]: WcTextInput,
  [AdminComponent.ToggleInput]: WcToggleInput,
  [AdminComponent.TriStateInput]: WcTriStateInput,
});
