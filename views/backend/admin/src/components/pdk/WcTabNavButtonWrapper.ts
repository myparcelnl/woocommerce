import {defineComponent, h} from 'vue';

export default defineComponent({
  name: 'WcTabNavButtonWrapper',
  render() {
    return h('div', {class: ['nav-tab-wrapper', 'woo-nav-tab-wrapper']}, this.$slots);
  },
});
