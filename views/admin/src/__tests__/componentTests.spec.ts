import WcPdkModal from '../components/WcModal.vue';
import {executePdkComponentTests} from '@myparcel/pdk-component-tests';

executePdkComponentTests({
  PdkModal: WcPdkModal,
});
