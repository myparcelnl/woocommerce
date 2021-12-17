jQuery(($) => {
  $(document).ready(() => {
    $(document).on('click', '.myparcel-dismiss-notice .notice-dismiss', () => {
      const data = {
        action: 'dismissNotice',
        security: wcmp_params.nonce,
      };

      $.post(wcmp_params.ajax_url, data, () => {
      });
    });
  });
});
