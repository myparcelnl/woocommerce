import WcPdkModal from '../components/WcModal.vue';
import {executePdkComponentTests} from '@myparcel/pdk-frontend-component-tests';

executePdkComponentTests({
  PdkModal: WcPdkModal,
});
