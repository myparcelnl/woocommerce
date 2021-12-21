jQuery(($) => {
  $(document).on('click', '.myparcel-dismiss-notice .notice-dismiss', function triggerAction() {
    const data = {
      action: 'dismissNotice',
      security: wcmp_params.nonce,
    };

    $.post(wcmp_params.ajax_url, data);
  });
});
