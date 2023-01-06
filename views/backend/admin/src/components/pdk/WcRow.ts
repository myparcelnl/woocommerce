import {defineComponent, h} from 'vue';

export default defineComponent({
  name: 'WcRow',
  render() {
    return h('div', {class: 'row'}, this.$slots);
  },
});
