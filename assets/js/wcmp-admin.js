/**
 * @var {Object} wc_myparcelbe
 *
 * @property {Object} wc_myparcelbe.actions
 * @property {{add_shipment: String, add_shipments: String, add_return: String, get_labels: String}}
 *   wc_myparcelbe.actions
 * @property {String} wc_myparcelbe.ajax_url
 * @property {String} wc_myparcelbe.nonce
 * @property {String} wc_myparcelbe.download_display
 * @property {String} wc_myparcelbe.offset
 * @property {String} wc_myparcelbe.offset_icon
 */

// eslint-disable-next-line max-lines-per-function
jQuery(function($) {

  var selectors = {
    offsetDialog: '.wcmp__offset-dialog',
    offsetDialogInput: '.wcmp__offset-dialog__offset',
    printQueue: '.wcmp__print-queue',
    printQueueOffset: '.wcmp__print-queue__offset',
    saveShipmentSettings: '.wcmp__shipment-settings__save',
    shipmentOptions: '.wcmp__shipment-options',
    shipmentOptionsForm: '.wcmp__shipment-options__form',
    shipmentSummary: 'wcmp__shipment-summary',
    shipmentSummaryList: '.wcmp__shipment-summary__list',
    showShipmentOptionsForm: '.wcmp__shipment-options__show',
    showShipmentSummary: '.wcmp__shipment-summary__show',
    spinner: '.wcmp__spinner',
    notice: '.wcmp__notice',
    orderAction: '.wcmp__action',
    bulkSpinner: '.wcmp__bulk-spinner',
    orderActionImage: '.wcmp__action__img',
  };

  addListeners();
  runTriggers();
  addDependencies();
  printQueuedLabels();

  /**
   * Add event listeners.
   */
  function addListeners() {
    /**
     * Click offset dialog button (single export).
     */
    $(selectors.offsetDialog + ' button').click(printOrder);

    /**
     * Show and enable options when clicked.
     */
    $(selectors.showShipmentOptionsForm).click(showShipmentOptionsForm);

    // Add listeners to save buttons in shipment options forms.
    $(selectors.saveShipmentSettings).click(saveShipmentOptions);

    /**
     * Show summary when clicked.
     */
    $(selectors.showShipmentSummary).click(showShipmentSummary);

    /**
     * Bulk actions.
     */
    $('#doaction, #doaction2').click(doBulkAction);

    /**
     * Add offset dialog when address labels option is selected.
     */
    $('select[name=\'action\'], select[name=\'action2\']').change(showOffsetDialog);

    /**
     * Single actions click. The .wc_actions .single_wc_actions for support wc > 3.3.0.
     */
    $('.order_actions, .single_order_actions, .wc_actions, .single_wc_actions')
      .on('click', selectors.orderAction, onActionClick);

    $(window).bind('tb_unload', onThickBoxUnload);
  }

  /**
   * Run the things that need to be done on load.
   */
  function runTriggers() {
    /* init options on settings page and in bulk form */
    $('#wcmp_settings :input, .wcmp__bulk-options :input').change();

    // Initialize enhanced selects
    $(document.body).trigger('wc-enhanced-select-init');

    $([selectors.shipmentOptions, selectors.shipmentSummary].join(' ')).each(function() {
      var $ship_to_column = $(this).closest('tr')
        .find('td.shipping_address');
      $(this).appendTo($ship_to_column);
      /* hidden by default - make visible */
      $(this).show();
    });
  }

  /**
   * Add dependencies for form elements with conditions.
   */
  function addDependencies() {
    /**
     * Get all nodes with a data-parent attribute.
     */
    var nodesWithParent = document.querySelectorAll('[data-parent]');

    /**
     * Dependency object.
     *
     * @type {Object.<String, Node[]>}
     */
    var dependencies = {};

    /**
     * Loop through the classes to create a dependency like this: { [parent]: node[] }.
     */
    nodesWithParent.forEach(function(node) {
      var parent = node.getAttribute('data-parent');

      if (dependencies.hasOwnProperty(parent)) {
        dependencies[parent].push(node);
      } else {
        // Or create the list with the node inside it
        dependencies[parent] = [node];
      }
    });

    createDependencies(dependencies);
  }

  /**
   * Print queued labels.
   */
  function printQueuedLabels() {
    var print_queue = $(selectors.printQueue).val();

    var print_queue_offset = $(selectors.printQueueOffset).val();

    if (typeof print_queue !== 'undefined') {
      if (typeof print_queue_offset === 'undefined') {
        print_queue_offset = 0;
      }
      myparcelbe_print($.parseJSON(print_queue), print_queue_offset);
    }
  }

  /**
   * Handle showing and hiding of settings.
   *
   * @param {Object<String, Node[]>} deps - Dependency names and all the nodes that depend on them.
   */
  function createDependencies(deps) {
    var baseEasing = 400;

    Object.keys(deps).forEach(function(relatedInputId) {
      var relatedInput = document.querySelector('[name="' + relatedInputId + '"]');

      /**
       * Loop through all the deps.
       *
       * @param {Event|null} event - Event.
       * @param {Number} easing - Amount of easing.
       */
      function handle(event, easing) {
        if (easing === undefined) {
          easing = baseEasing;
        }

        /**
         * @type {Element} dependant
         */
        deps[relatedInputId].forEach(function(dependant) {
          handleDependency(relatedInput, dependant, null, easing);

          if (relatedInput.hasAttribute('data-parent')) {
            var otherRelatedInput = document.querySelector('[name="' + relatedInput.getAttribute('data-parent') + '"]');

            handleDependency(otherRelatedInput, relatedInput, dependant, easing);

            otherRelatedInput.addEventListener('change', function() {
              return handleDependency(otherRelatedInput, relatedInput, dependant, easing);
            });
          }
        });
      };

      relatedInput.addEventListener('change', handle);

      // Do this on load too.
      handle(null, 0);
    });
  }

  /**
   * @param {Element|Node} relatedInput - Parent of element.
   * @param {Element|Node} element  - Element that will be handled.
   * @param {Element|Node|null} element2 - Optional extra dependency of element.
   * @param {Number} easing - Amount of easing on the transitions.
   */
  function handleDependency(relatedInput, element, element2, easing) {
    var dataParentValue = element.getAttribute('data-parent-value');

    var type = element.getAttribute('data-parent-type');
    var wantedValue = dataParentValue || '1';
    var setValue = element.getAttribute('data-parent-set') || null;
    var value = relatedInput.value;

    var elementContainer = $(element).closest('tr');

    /**
     * @type {Boolean}
     */
    var matches;

    // If the data-parent-value contains any semicolons it's an array, check it as an array instead.
    if (dataParentValue.indexOf(';') > -1) {
      matches = dataParentValue
        .split(';')
        .indexOf(value) > -1;
    } else {
      matches = value === wantedValue;
    }

    switch (type) {
      case 'child':
        elementContainer[matches ? 'show' : 'hide'](easing);
        break;
      case 'show':
        elementContainer[matches ? 'show' : 'hide'](easing);
        break;
      case 'disable':
        $(element).prop('disabled', !matches);
        if (matches && setValue) {
          element.value = setValue;
        }
        break;
    }

    relatedInput.setAttribute('data-enabled', matches.toString());
    element.setAttribute('data-enabled', matches.toString());

    if (element2) {
      var showOrHide = element2.getAttribute('data-enabled') === 'true'
        && element.getAttribute('data-enabled') === 'true';

      $(element2).closest('tr')
        [showOrHide ? 'show' : 'hide'](easing);
      relatedInput.setAttribute('data-enabled', showOrHide.toString());
    }
  }

  /**
   * Show a shipment options form.
   *
   * @param {Event} event - Click event.
   */
  function showShipmentOptionsForm(event) {
    event.preventDefault();
    var form = $(this).next(selectors.shipmentOptionsForm);

    if (form.is(':visible')) {
      // Form is already visible, hide it
      form.slideUp();

      // Remove the listener to close the form.
      document.addEventListener('click', hideShipmentOptionsForm);
    } else {
      // Form is invisible, show it
      form.find(':input').change();
      form.slideDown();
      // Add the listener to close the form.
      document.addEventListener('click', hideShipmentOptionsForm);
    }
  }

  /**
   * @param {Event} event
   * @property {Element} event.target
   */
  function hideShipmentSummary(event) {
    if (!$(event.target).closest(selectors.shipmentSummaryList).length) {
      if (!($(event.target).hasClass(selectors.showShipmentSummary) || $(event.target)
        .parent()
        .hasClass(selectors.shipmentSummary)) && $(selectors.shipmentSummaryList).is(':visible')) {
        $(selectors.shipmentSummaryList).slideUp();
      }
    }
  }

  /**
   * Save the shipment options in the bulk form.
   */
  function saveShipmentOptions() {
    var order_id = $(this).data().order;
    var form = $(this).parent(selectors.shipmentOptionsForm);

    $(this).find(selectors.spinner)
      .show();

    var form_data = form.find(':input').serialize();

    var data = {
      action: 'wcmp_save_shipment_options',
      order_id: order_id,
      form_data: form_data,
      security: wc_myparcelbe.nonce,
    };

    $.post(wc_myparcelbe.ajax_url, data, function() {
      $(this).find(selectors.spinner)
        .hide();

      /* hide the form */
      form.slideUp();
    });
  }

  /**
   * @param {Event} event - Click event.
   */
  function doBulkAction(event) {
    var actionselected = $(this).attr('id')
      .substr(2);
    /* check if action starts with 'wcmp_' */
    var element = $('select[name="' + actionselected + '"]');
    if (element.val().substring(0, 5) === 'wcmp_') {
      event.preventDefault();
      /* remove notices */
      $(selectors.notice).remove();

      /* strip 'wcmp_' from action */
      var action = element.val().substring(5);

      /* Get array of checked orders (order_ids) */
      var order_ids = [];
      $('tbody th.check-column input[type="checkbox"]:checked').each(
        function() {
          order_ids.push($(this).val());
        }
      );

      showBulkSpinner(this, true);

      /* execute action */
      switch (action) {
        case 'export':
          myparcelbe_export(order_ids);
          break;

        case 'print':
          var offset = wc_myparcelbe.offset === 1 ? $(selectors.offsetDialogInput).val() : 0;
          myparcelbe_print(order_ids, offset);
          break;

        case 'export_print':
          /* 'yes' initializes print mode and disables refresh */
          myparcelbe_export(order_ids, 'after_reload');
          break;
      }
    }
  }

  /**
   * On clicking the actions in a single order.
   *
   * @param {Event} event - Click event.
   */
  function onActionClick(event) {
    event.preventDefault();
    var button_action = $(this).data('request');
    var order_ids = [$(this).data('order-id')];

    /* execute action */
    switch (button_action) {
      case wc_myparcelbe.actions.add_shipment:
        var button = this;
        showButtonSpinner(button, true);
        myparcelbe_export(order_ids);
        break;
      case wc_myparcelbe.actions.get_labels:
        if (wc_myparcelbe.offset === 1) {
          contextual_offset_dialog(order_ids, event);
        } else {
          myparcelbe_print(order_ids);
        }
        break;
      case wc_myparcelbe.actions.add_return:
        myparcelbe_modal_dialog(order_ids, 'return');
        break;
    }
  }

  function showOffsetDialog() {
    var actionselected = $(this).val();
    var offsetDialog = $(selectors.offsetDialog);

    if ((actionselected === 'wcmp_print' || actionselected === 'wcmp_export_print') && wc_myparcelbe.offset === 1) {
      var insert_position = $(this).attr('name') === 'action' ? 'top' : 'bottom';

      offsetDialog
        .attr('style', 'clear:both') /* reset styles */
        .insertAfter('div.tablenav.' + insert_position)
        .show();

      /* make sure button is not shown */
      offsetDialog.find('button').hide();
      /* clear input */
      offsetDialog.find('input').val('');
    } else {
      offsetDialog
        .appendTo('body')
        .hide();
    }
  }

  function printOrder() {
    var dialog = $(this).parent();

    /* set print variables */
    var order_ids = [dialog.find('input.order_id').val()];
    var offset = dialog.find(selectors.offsetDialogInput).val();

    /* hide dialog */
    dialog.hide();

    /* print labels */
    myparcelbe_print(order_ids, offset);
  }

  /**
   * Place offset dialog at mouse tip.
   */
  function contextual_offset_dialog(order_ids, event) {
    var offsetDialog = $(selectors.offsetDialog);

    offsetDialog
      .show()
      .appendTo('body')
      .css({
        top: event.pageY,
        left: event.pageX,
      });

    offsetDialog.find('button')
      .show()
      .data('order_id', order_ids);

    /* clear input */
    offsetDialog.find('input').val('');

    offsetDialog.append('<input type="hidden" class="order_id"/>');
    $(selectors.offsetDialog + ' input.order_id').val(order_ids);
  }

  /**
   * @param {Element} button - The button that was clicked.
   * @param {Boolean} display - To display or not to display.
   */
  function showButtonSpinner(button, display) {
    if (display) {
      var buttonImage = $(button).find(selectors.orderActionImage);
      buttonImage.hide();
      $(button).parent()
        .find(selectors.spinner)
        .insertAfter(buttonImage)
        .show();
    } else {
      $(button).parent()
        .find(selectors.spinner)
        .hide();
      $(button).find(selectors.orderActionImage)
        .show();
    }
  }

  /**
   * @param {Element} action - The action that was clicked.
   * @param {Boolean} display - To display or not to display.
   */
  function showBulkSpinner(action, display) {
    var submit_button = $(action)
      .parent()
      .find('.button.action');

    if (display) {
      $(selectors.bulkSpinner)
        .insertAfter(submit_button)
        .show();
    } else {
      $(selectors.bulkSpinner).hide();
    }
  }

  /* export orders to MyParcel via AJAX */
  function myparcelbe_export(order_ids, print) {
    if (typeof print === 'undefined') {
      print = 'no';
    }

    var offset = wc_myparcelbe.offset === 1 ? $(selectors.offsetDialogInput).val() : 0;
    var data = {
      action: 'wc_myparcelbe',
      request: wc_myparcelbe.actions.add_shipments,
      order_ids: order_ids,
      offset: offset,
      print: print,
      security: wc_myparcelbe.nonce,
    };

    $.post(wc_myparcelbe.ajax_url, data, function(response) {
      response = $.parseJSON(response);

      if (print === 'no' || print === 'after_reload') {
        /* refresh page, admin notices are stored in options and will be displayed automatically */
        redirect_url = updateUrlParameter(window.location.href, 'myparcelbe_done', 'true');
        window.location.href = redirect_url;

      } else {
        /* when printing, output notices directly so that we can init print in the same run */
        if (response !== null && typeof response === 'object' && 'error' in response) {
          myparcelbe_admin_notice(response.error, 'error');
        }

        if (response !== null && typeof response === 'object' && 'success' in response) {
          myparcelbe_admin_notice(response.success, 'success');
        }

        /* load PDF */
        myparcelbe_print(order_ids, offset);
      }
    });
  }

  function myparcelbe_modal_dialog(order_ids, dialog) {
    var request_prefix = (wc_myparcelbe.ajax_url.indexOf('?') !== -1) ? '&' : '?';
    var thickbox_parameters = '&TB_iframe=true&height=380&width=720';
    var url = wc_myparcelbe.ajax_url
      + request_prefix
      + 'order_ids='
      + order_ids
      + '&action=wc_myparcelbe&request=modal_dialog&dialog='
      + dialog
      + '&security='
      + wc_myparcelbe.nonce
      + thickbox_parameters;

    /* disable background scrolling */
    $('body').css({overflow: 'hidden'});

    tb_show('', url);
  }

  /**
   *  Re-enable scrolling after closing thickbox.
   */
  function onThickBoxUnload() {
    $('body').css({overflow: 'inherit'});
  }

  /* export orders to MyParcel via AJAX */
  function myparcelbe_return(order_ids) {
    var data = {
      action: 'wc_myparcelbe',
      request: wc_myparcelbe.actions.add_return,
      order_ids: order_ids,
      security: wc_myparcelbe.nonce,
    };

    $.post(wc_myparcelbe.ajax_url, data, function(response) {
      response = $.parseJSON(response);
      if (response !== null && typeof response === 'object' && 'error' in response) {
        myparcelbe_admin_notice(response.error, 'error');
      }

    });

  }

  /* Request MyParcel BE labels */
  function myparcelbe_print(order_ids, offset) {
    if (typeof offset === 'undefined') {
      offset = 0;
    }

    var request_prefix = (wc_myparcelbe.ajax_url.indexOf('?') !== -1) ? '&' : '?';
    var url = wc_myparcelbe.ajax_url
      + request_prefix
      + 'action=wc_myparcelbe&request='
      + wc_myparcelbe.actions.get_labels
      + '&security='
      + wc_myparcelbe.nonce;

    /* create form to send order_ids via POST */
    $('body').append('<form action="' + url + '" method="post" target="_blank" id="myparcelbe_post_data"></form>');
    $('#myparcelbe_post_data').append('<input type="hidden" name="offset" class="offset"/>');
    $('#myparcelbe_post_data input.offset').val(offset);
    $('#myparcelbe_post_data').append('<input type="hidden" name="order_ids" class="order_ids"/>');
    $('#myparcelbe_post_data input.order_ids').val(JSON.stringify(order_ids));

    /* submit data to open or download pdf */
    $('#myparcelbe_post_data').submit();

    showBulkSpinner('', false);
  }

  function myparcelbe_admin_notice(message, type) {
    var mainHeader = $('#wpbody-content > .wrap > h1:first');
    var notice = '<div class="' + selectors.notice + ' notice notice-' + type + '"><p>' + message + '</p></div>';
    mainHeader.after(notice);
    $('html, body').animate({scrollTop: 0}, 'slow');
  }

  /* Add / Update a key-value pair in the URL query parameters */

  /* https://gist.github.com/niyazpk/f8ac616f181f6042d1e0 */
  function updateUrlParameter(uri, key, value) {
    /* remove the hash part before operating on the uri */
    var i = uri.indexOf('#');
    var hash = i === -1 ? '' : uri.substr(i);
    uri = i === -1 ? uri : uri.substr(0, i);

    var re = new RegExp('([?&])' + key + '=.*?(&|$)', 'i');
    var separator = uri.indexOf('?') !== -1 ? '&' : '?';
    if (uri.match(re)) {
      uri = uri.replace(re, '$1' + key + '=' + value + '$2');
    } else {
      uri = uri + separator + key + '=' + value;
    }
    return uri + hash; /* finally append the hash as well */
  }

  function showShipmentSummary() {
    var summaryList = $(this).next('.wcmp__shipment-summary__list');

    if (summaryList.is(':visible') || summaryList.data('loaded') !== '') {
      summaryList.slideUp();
    } else if (summaryList.is(':hidden') && summaryList.data('loaded') === '') {
      summaryList.addClass('ajax-waiting');
      summaryList.find(selectors.spinner).show();
      summaryList.slideDown();

      /* hide summary when click outside */
      document.addEventListener('click', hideShipmentSummary);

      var data = {
        security: wc_myparcelbe.nonce,
        action: 'wcmp_get_shipment_summary_status',
        order_id: summaryList.data('order_id'),
        shipment_id: summaryList.data('shipment_id'),
      };

      $.ajax({
        type: 'POST',
        url: wc_myparcelbe.ajax_url,
        data: data,
        context: summaryList,
        success: function(response) {
          this.removeClass('ajax-waiting');
          this.html(response);
          this.data('loaded', 'yes');
        },
      });
    }
  }

  /**
   * Hide any shipment options form(s) by checking if the element clicked is not in the list of allowed elements and
   *  not inside the shipment options form.
   *
   * @param {MouseEvent} event - The click event.
   * @param {Element} event.target - Click target.
   */
  function hideShipmentOptionsForm(event) {
    event.preventDefault();
    console.log(event);

    var clickedOutside = true;

    [selectors.shipmentOptionsForm, selectors.showShipmentOptionsForm].forEach(function(cls) {
      if ((clickedOutside && event.target.matches(cls))
        || document.querySelector(selectors.shipmentOptionsForm).contains(event.target)) {
        clickedOutside = false;
      }
    });

    if (clickedOutside) {
      document.removeEventListener('click', hideShipmentOptionsForm);
      $(selectors.shipmentOptionsForm).slideUp();
    }
  }
});
