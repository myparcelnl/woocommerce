/* eslint-disable @typescript-eslint/no-magic-numbers */
import {defineComponent, h} from 'vue';

export default defineComponent({
  name: 'WcCol',
  props: {
    span: {
      type: Number,
      default: null,
    },
  },

  render() {
    return h('div', this.$attrs, this.$slots);
  },
});
