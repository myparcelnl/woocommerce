import {defineComponent, h} from 'vue';

export default defineComponent({
  name: 'WcRow',
  props: {
    collapseGutters: {
      type: Boolean,
    },
    columns: {
      type: [Number, String],
      default: null,
    },
  },

  render() {
    const classes = ['mypa-grid', 'mypa-grid-flow-col', 'mypa-cols-auto', this.collapseGutters ? '' : 'mypa-gap-4'];

    return h(
      'div',
      {
        class: classes,
      },
      this.$slots,
    );
  },
});
