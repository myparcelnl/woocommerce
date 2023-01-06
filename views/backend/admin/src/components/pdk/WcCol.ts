import {defineComponent, h} from 'vue';

export default defineComponent({
  name: 'WcCol',
  render() {
    return h('div', {class: 'col'}, this.$slots);
  },
});
