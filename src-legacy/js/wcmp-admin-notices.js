jQuery(($) => {
  $(document).on('click', '.myparcel-dismiss-notice .notice-dismiss', function triggerAction(event) {
    const data = {
      action: 'dismissNotice',
      messageid: event.target.parentNode.getAttribute('data-messageid') || null,
      security: wcmp_params.nonce,
    };

    $.post(wcmp_params.ajax_url, data);
  });
});
