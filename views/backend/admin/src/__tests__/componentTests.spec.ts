import WcPdkModal from '../components/pdk/WcModal.vue';
import {executePdkComponentTests} from '@myparcel/pdk-component-tests';

executePdkComponentTests({
  PdkModal: WcPdkModal,
});
