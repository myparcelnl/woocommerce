import {defineComponent, h} from 'vue';

export default defineComponent({
  name: 'WcButtonGroup',
  render() {
    return h('div', {class: 'btn-group'}, this.$slots);
  },
});
