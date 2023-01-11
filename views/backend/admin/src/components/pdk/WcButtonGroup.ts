import {defineComponent, h} from 'vue';

export default defineComponent({
  name: 'WcButtonGroup',
  render() {
    return h(
      'div',
      {
        class: 'mypa-inline-grid mypa-gap-1 mypa-cols-auto mypa-grid-flow-col',
      },
      this.$slots,
    );
  },
});
