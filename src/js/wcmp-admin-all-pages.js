jQuery(document).ready(function() {
  jQuery(document).on( 'click', '.myparcel-dismiss-notice .notice-dismiss', function() {
    var data = {
      action: 'dismissNotice',
    };

    jQuery.post(wcmp.ajax_url, data, function() {
    });
  })
});
