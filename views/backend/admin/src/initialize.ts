// noinspection JSUnusedGlobalSymbols

import {h} from 'vue';
import {get} from '@vueuse/core';
import {
  DefaultBadge,
  DefaultCurrencyInput,
  DefaultHeading,
  DefaultLink,
  DefaultMultiSelectInput,
  DefaultNumberInput,
  DefaultRadioGroup,
  DefaultSettingsDivider,
  DefaultTimeInput,
  DefaultShippingMethodsInput,
} from '@myparcel-pdk/admin-preset-default';
import {DashIconsIcon} from '@myparcel-pdk/admin-preset-dashicons';
import {
  FORM_KEY_CHILD_PRODUCT_SETTINGS,
  FORM_KEY_MODAL,
  FORM_KEY_PRODUCT_SETTINGS,
  LogLevel,
  createPdkAdmin,
  AdminComponent,
} from '@myparcel-pdk/admin';
import {closeModalOnEscape, listenForBulkActions} from './hooks';
import {
  WcBox,
  WcButton,
  WcButtonGroup,
  WcCheckboxGroup,
  WcCheckboxInput,
  WcChildProductSettingsFormGroup,
  WcCodeEditor,
  WcCol,
  WcDropOffInput,
  WcDropdownButton,
  WcFormGroup,
  WcImage,
  WcLoader,
  WcModal,
  WcNotification,
  WcPluginSettingsWrapper,
  WcProductSettingsFormGroup,
  WcRadioInput,
  WcRow,
  WcSelectInput,
  WcShipmentLabelWrapper,
  WcTabNavButton,
  WcTabNavButtonWrapper,
  WcTable,
  WcTableCol,
  WcTableRow,
  WcTextArea,
  WcTextInput,
  WcToggleInput,
  WcTriStateInput,
} from './components/pdk';

const FADE = 'mypa-fade';

// eslint-disable-next-line max-lines-per-function
export const initialize = (): void => {
  createPdkAdmin({
    logLevel: import.meta.env.MODE === 'development' ? LogLevel.Debug : LogLevel.Info,

    formConfig: {
      fieldDefaults: {
        afterValidate(field) {
          const valid = get(field.isValid);

          field.form.element.classList.toggle('form-invalid', !valid);
        },
      },

      form: {
        attributes: {
          class: 'wrap woocommerce',
        },
        wrapper: h('table', {class: 'form-table'}),
      },
    },

    formConfigOverrides: {
      [FORM_KEY_MODAL]: {
        form: {
          attributes: {
            // Omit the "wrap" class in modal forms to avoid the horizontal padding.
            class: 'woocommerce',
          },
        },
      },

      [FORM_KEY_PRODUCT_SETTINGS]: {
        form: {
          tag: 'div',
        },
        field: {
          wrapper: WcProductSettingsFormGroup,
        },
      },

      [FORM_KEY_CHILD_PRODUCT_SETTINGS]: {
        field: {
          wrapper: WcChildProductSettingsFormGroup,
        },
        generateFieldId(field) {
          return `myparcelnl-${field.form.name}-${field.name}`;
        },
      },
    },

    components: {
      [AdminComponent.Badge]: DefaultBadge,
      [AdminComponent.Box]: WcBox,
      [AdminComponent.ButtonGroup]: WcButtonGroup,
      [AdminComponent.Button]: WcButton,
      [AdminComponent.CheckboxGroup]: WcCheckboxGroup,
      [AdminComponent.CheckboxInput]: WcCheckboxInput,
      [AdminComponent.CodeEditor]: WcCodeEditor,
      [AdminComponent.Col]: WcCol,
      [AdminComponent.CurrencyInput]: DefaultCurrencyInput,
      [AdminComponent.DropOffInput]: WcDropOffInput,
      [AdminComponent.DropdownButton]: WcDropdownButton,
      [AdminComponent.FormGroup]: WcFormGroup,
      [AdminComponent.Heading]: DefaultHeading,
      [AdminComponent.Icon]: DashIconsIcon,
      [AdminComponent.Image]: WcImage,
      [AdminComponent.Link]: DefaultLink,
      [AdminComponent.Loader]: WcLoader,
      [AdminComponent.Modal]: WcModal,
      [AdminComponent.MultiSelectInput]: DefaultMultiSelectInput,
      [AdminComponent.Notification]: WcNotification,
      [AdminComponent.NumberInput]: DefaultNumberInput,
      [AdminComponent.PluginSettingsWrapper]: WcPluginSettingsWrapper,
      [AdminComponent.RadioGroup]: DefaultRadioGroup,
      [AdminComponent.RadioInput]: WcRadioInput,
      [AdminComponent.Row]: WcRow,
      [AdminComponent.SelectInput]: WcSelectInput,
      [AdminComponent.SettingsDivider]: DefaultSettingsDivider,
      [AdminComponent.ShipmentLabelWrapper]: WcShipmentLabelWrapper,
      [AdminComponent.ShippingMethodsInput]: DefaultShippingMethodsInput,
      [AdminComponent.TabNavButtonWrapper]: WcTabNavButtonWrapper,
      [AdminComponent.TabNavButton]: WcTabNavButton,
      [AdminComponent.TableCol]: WcTableCol,
      [AdminComponent.TableRow]: WcTableRow,
      [AdminComponent.Table]: WcTable,
      [AdminComponent.TextArea]: WcTextArea,
      [AdminComponent.TextInput]: WcTextInput,
      [AdminComponent.TimeInput]: DefaultTimeInput,
      [AdminComponent.ToggleInput]: WcToggleInput,
      [AdminComponent.TriStateInput]: WcTriStateInput,
    },

    cssUtilities: {
      animationSpin: 'mypa-animate-spin',
      animationLoading: 'mypa-opacity-50 mypa-pointer-events-none mypa-select-none mypa-animate-pulse',
      cursorDefault: 'mypa-cursor-default',
      displayFlex: 'mypa-flex',
      flexGrow: 'mypa-flex-grow',
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
      tabNavigation: FADE,
      tableRow: 'mypa-table-row',
    },

    onCreateStore() {
      closeModalOnEscape();
    },

    onInitialized() {
      listenForBulkActions();
    },

    generateFieldId(field) {
      return `myparcelnl-${field.name}`;
    },
  });
};
