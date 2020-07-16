/**
 * @member {Object} wcmp
 * @property {Object} wcmp.actions
 * @property {{export: String, add_shipments: String, add_return: String, get_labels: String, modal_dialog: String}} wcmp.actions
 * @property {String} wcmp.api_url - The API Url we use in MyParcel requests.
 * @property {String} wcmp.ajax_url
 * @property {String} wcmp.ask_for_print_position
 * @property {Object} wcmp.bulk_actions
 * @property {String} wcmp.download_display
 * @property {String} wcmp.nonce
 * @property {Object.<String, String>} wcmp.strings
 */

/* eslint-disable-next-line max-lines-per-function */
jQuery(function($) {
  // Object.values polyfill
  if (!Object.values) {
    Object.values = function(obj) {
      var values = [];

      for (var i in obj) {
        if (obj.hasOwnProperty(i)) {
          values.push(obj[i]);
        }
      }

      return values;
    };
  }

  /**
   * @type {Boolean}
   */
  var askForPrintPosition = Boolean(parseInt(wcmp.ask_for_print_position));

  var selectors = {
    bulkSpinner: '.wcmp__bulk-spinner',
    notice: '.wcmp__notice',
    offsetDialog: '.wcmp__offset-dialog',
    offsetDialogButton: '.wcmp__offset-dialog__button',
    offsetDialogClose: '.wcmp__offset-dialog__close',
    offsetDialogInputOffset: '.wcmp__offset-dialog__offset',
    orderAction: '.wcmp__action',
    orderActionImage: '.wcmp__action__img',
    printQueue: '.wcmp__print-queue',
    printQueueOffset: '.wcmp__print-queue__offset',
    shipmentOptions: '.wcmp__shipment-options',
    shipmentOptionsDialog: '.wcmp__shipment-options-dialog',
    shipmentOptionsSaveButton: '.wcmp__shipment-options__save',
    shipmentOptionsShowButton: '.wcmp__shipment-options__show',
    shipmentSettingsWrapper: '.wcmp__shipment-settings-wrapper',
    shipmentSummaryList: '.wcmp__shipment-summary__list',
    showShipmentSummaryList: '.wcmp__shipment-summary__show',
    spinner: '.wcmp__spinner',
  };

  var spinner = {
    loading: 'loading',
    success: 'success',
    failed: 'failed',
  };

  addListeners();
  runTriggers();
  addDependencies();
  printQueuedLabels();

  var timeoutAfterRequest = 200;
  var baseEasing = 400;

  /**
   * Add event listeners.
   */
  function addListeners() {
    /**
     * Click offset dialog button (single export).
     */
    $(selectors.offsetDialog + ' button').click(printOrder);

    $(selectors.offsetDialogClose).click(hideOffsetDialog);

    /**
     * Show and enable options when clicked.
     */
    $(selectors.shipmentOptionsShowButton).click(showShipmentOptionsForm);

    /**
     * Show summary when clicked.
     */
    $(selectors.showShipmentSummaryList).click(showShipmentSummaryList);

    /**
     * Bulk actions.
     */
    $('#doaction, #doaction2').click(doBulkAction);

    /**
     * Add offset dialog when address labels option is selected.
     */
    $('select[name=\'action\'], select[name=\'action2\']').change(showBulkOffsetDialog);

    /**
     * Single actions click. The .wc_actions .single_wc_actions for support wc > 3.3.0.
     */
    $(selectors.orderAction).click(onActionClick);

    $(window).bind('tb_unload', onThickBoxUnload);
  }

  /**
   * Run the things that need to be done on load.
   */
  function runTriggers() {
    /* init options on settings page and in bulk form */
    $('#wcmp_settings :input, .wcmp__bulk-options :input').change();

    /**
     * Move the shipment options form and the shipment summary from the actions column to the shipping address column.
     *
     * @see includes/admin/class-wcmp-admin.php:49
     */
    $(selectors.shipmentSettingsWrapper).each(function() {
      var shippingAddressColumn = $(this)
        .closest('tr')
        .find('td.shipping_address');

      $(this).appendTo(shippingAddressColumn);
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
    var printData = $(selectors.printQueue).val();

    if (printData) {
      printLabel(JSON.parse(printData));
    }
  }

  /**
   * Handle showing and hiding of settings.
   *
   * @param {Object<String, Node[]>} deps - Dependency names and all the nodes that depend on them.
   */
  function createDependencies(deps) {
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
      }

      relatedInput.addEventListener('change', handle);

      // Do this on load too.
      handle(null, 0);
    });
  }

  /**
   * @param {Element|Node} relatedInput - Parent of element.
   * @param {Element|Node} element - Element that will be handled.
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
    var elementCheckoutStringsTitleContainer = $('#checkout_strings');

    /**
     * @type {Boolean}
     */
    var matches;

    /*
     * If the data-parent-value contains any semicolons it's an array, check it as an array instead.
     */
    if (dataParentValue && dataParentValue.indexOf(';') > -1) {
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
        elementCheckoutStringsTitleContainer[matches ? 'show' : 'hide'](easing);
        break;
      case 'disable':
        $(element).prop('disabled', !matches);
        if (!matches && setValue) {
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
   * Show the shipment options form on the Woo Orders page.
   *
   * @param {Event} event - Click event.
   */
  function showShipmentOptionsForm(event) {
    event.preventDefault();
    var button = $(this);
    var orderId = button.data('order-id');

    var form = $(selectors.shipmentOptionsDialog);
    var isSameAsLast = form.data('order-id') === orderId;
    var isVisible = form.is(':visible');

    if (isVisible) {
      document.removeEventListener('click', hideShipmentOptionsForm);

      // Close form on second "details" click
      if (isSameAsLast) {
        form.slideUp(100);
        return;
      }

      // Hide other opened form before opening new one
      form.hide(0);
    }

    // Set the position for the dialog to be under the clicked "Details" link.
    var position = button.offset();
    position.top -= button.height();
    form.css(position);

    // Set the data-order-id attribute on the dialog to keep track of which dialog was last opened.
    form.data('order-id', orderId);

    doRequest.bind(this)({
      url: wcmp.ajax_url,
      data: {
        action: 'wcmp_get_shipment_options',
        orderId: orderId,
        security: wcmp.nonce,
      },

      /**
       * Show the correct data in the form and add event listeners for handling saving and clicking outside the form.
       *
       * @param {String} response - Html to put in the form.
       */
      afterDone: function(response) {
        form.html(response);
        $(selectors.shipmentOptionsSaveButton).on('click', saveShipmentOptions);
        document.addEventListener('click', hideShipmentOptionsForm);
        form.slideDown(100);
      },
      afterFail: function() {
        form.slideUp(100);
      },
    });
  }

  function setSpinner(element, state) {
    var baseSelector = selectors.spinner.replace('.', '');
    var spinner = $(element).find(selectors.spinner);

    if (state) {
      spinner
        .removeClass()
        .addClass(baseSelector)
        .addClass(baseSelector + '--' + state)
        .show();
    } else {
      spinner
        .removeClass()
        .addClass(baseSelector)
        .hide();
    }
  }

  /**
   * Save the shipment options in the bulk form.
   */
  function saveShipmentOptions() {
    var form = $(selectors.shipmentOptionsDialog);

    doRequest.bind(this)({
      url: wcmp.ajax_url,
      data: {
        action: 'wcmp_save_shipment_options',
        form_data: form.find(':input').serialize(),
        security: wcmp.nonce,
      },
      afterDone: function() {
        setTimeout(function() {
          form.slideUp();
        }, timeoutAfterRequest);
      },
    });
  }

  /**
   * @param {Event} event - Click event.
   */
  function doBulkAction(event) {
    var action = document.querySelector('[name="action"]').value;

    /**
     * Check the selected action is ours.
     */
    if (!Object.values(wcmp.bulk_actions).includes(action)) {
      return;
    }

    event.preventDefault();

    /*
     * Remove notices
     */
    $(selectors.notice).remove();
    var order_ids = [];
    var rows = [];

    /*
     * Get array of selected order_ids
     */
    $('tbody th.check-column input[type="checkbox"]:checked').each(
      function() {
        order_ids.push($(this).val());
        rows.push('.post-' + $(this).val());
      }
    );

    $(rows.join(', ')).addClass('wcmp__loading');

    if (!order_ids.length) {
      alert(wcmp.strings.no_orders_selected);
      return;
    }

    switch (action) {
      /**
       * Export orders.
       */
      case wcmp.bulk_actions.export:
        exportToMyParcel(order_ids);
        break;

      /**
       * Print labels.
       */
      case wcmp.bulk_actions.print:
        printLabel({
          order_ids: order_ids,
        });
        break;

      /**
       * Export and print.
       */
      case wcmp.bulk_actions.export_print:
        exportToMyParcel(order_ids, 'after_reload');
        break;
    }
  }

  /**
   * Do an ajax request.
   *
   * @param {Object} request - Request object.
   */
  function doRequest(request) {
    var button = this;

    $(button).prop('disabled', true);
    setSpinner(button, spinner.loading);

    if (!request.url) {
      request.url = wcmp.ajax_url;
    }

    $.ajax({
      url: request.url,
      method: request.method || 'POST',
      data: request.data || {},
    })
      .done(function(res) {
        setSpinner(button, spinner.success);

        if (request.hasOwnProperty('afterDone') && typeof request.afterDone === 'function') {
          request.afterDone(res);
        }
      })

      .fail(function(res) {
        setSpinner(button, spinner.failed);

        if (request.hasOwnProperty('afterFail') && typeof request.afterFail === 'function') {
          request.afterFail(res);
        }
      })

      .always(function(res) {
        $(button).prop('disabled', false);

        if (request.hasOwnProperty('afterAlways') && typeof request.afterAlways === 'function') {
          request.afterAlways(res);
        }
      });
  }

  function getParameterByName(name, url) {
    if (!url) {
      url = window.location.href;
    }
    name = name.replace(/[\[\]]/g, '\\$&');

    var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)');
    var results = regex.exec(url);

    if (!results) {
      return null;
    }

    if (!results[2]) {
      return '';
    }

    return decodeURIComponent(results[2].replace(/\+/g, ' '));
  }

  /**
   * On clicking the actions in a single order.
   *
   * @param {Event} event - Click event.
   */
  function onActionClick(event) {
    var button = this;

    var request = getParameterByName('request', button.href);
    var order_ids = getParameterByName('order_ids', button.href);

    if (!wcmp.actions.hasOwnProperty(request)) {
      return;
    }

    event.preventDefault();

    switch (request) {
      case wcmp.actions.add_shipments:
        exportToMyParcel.bind(button)();
        break;
      case wcmp.actions.get_labels:
        if (askForPrintPosition && !$(button).hasClass('wcmp__offset-dialog__button')) {
          showOffsetDialog.bind(button)();
        } else {
          printLabel.bind(button)();
        }
        break;
      case wcmp.actions.add_return:
        myparcelbe_modal_dialog(order_ids, 'return');
        break;
    }
  }

  /**
   * Show the offset dialog before printing.
   *
   * @param {String?} position - To position the dialog `left` or `right` relative to the bound element.
   * @param {String?} context - Context in which the dialog was created. Ex. 'bulk'.
   */
  function showOffsetDialog(position, context) {
    position = position || 'left';

    var parent = this;
    var offsetDialog = $(selectors.offsetDialog);
    var dialogButton = $(selectors.offsetDialogButton);
    var parentOffset = $(parent).offset();

    /**
     * Position it to the bottom left or right of the clicked button.
     */
    if (position === 'left') {
      offsetDialog.css({
        left: parentOffset.left - offsetDialog.width(),
        top: parentOffset.top,
      });
    } else {
      offsetDialog.css(parentOffset);
    }

    dialogButton.attr('href', parent.href);

    /**
     * Reset input(s).
     */
    offsetDialog.find('input').val(0);

    /**
     * Make sure button is not shown and there is no input listener to update it if context is bulk.
     */
    if (context === 'bulk') {
      dialogButton.hide();
      $(selectors.offsetDialogInputOffset).off('blur update change', onUpdateOffset);
    } else {
      dialogButton.show();
      $(selectors.offsetDialogInputOffset).on('blur update change', onUpdateOffset);
    }

    /**
     * Finally, show the dialog.
     */
    offsetDialog.slideDown();
  }

  /**
   * Hide the offset dialog and remove the input listener.
   *
   * @param {Event?} event - Click event if called from a button.
   */
  function hideOffsetDialog(event) {
    if (event) {
      event.preventDefault();
    }

    $(selectors.offsetDialogInputOffset).off('blur update change', onUpdateOffset);
    $(selectors.offsetDialog).slideUp();
  }

  /**
   * On changing the offset value in the dialog, update the offset parameter in the dialog button's href attribute.
   */
  function onUpdateOffset() {
    var dialogButton = $(selectors.offsetDialogButton);
    var hasOffset = dialogButton.attr('href').indexOf('offset=') > -1;
    var newOffset = this.value;

    if (hasOffset) {
      dialogButton.attr('href', dialogButton.attr('href').replace(/([?&]offset=)\d*/, '$1' + newOffset));
    } else {
      dialogButton.attr('href', dialogButton.attr('href') + '&offset=' + newOffset);
    }
  }

  /**
   * Show the offset dialog for bulk options that allow it.
   */
  function showBulkOffsetDialog() {
    if ([wcmp.bulk_actions.print, wcmp.bulk_actions.export_print].indexOf(this.value) === -1) {
      hideOffsetDialog();
      return;
    }

    showOffsetDialog.bind(this)('right', 'bulk');
  }

  function printOrder() {
    var dialog = $(this).parent();

    /* set print variables */
    var order_ids = [dialog.find('input.order_id').val()];
    var offset = dialog.find(selectors.offsetDialogInputOffset).val();

    /* hide dialog */
    dialog.hide();

    /* print labels */
    printLabel({
      order_ids: order_ids,
      offset: offset,
    });
  }

  /* export orders to MyParcel via AJAX */
  function exportToMyParcel(order_ids, print) {
    var url;
    var data;

    if (typeof print === 'undefined') {
      print = 'no';
    }

    if (this.href) {
      url = this.href;
    } else {
      data = {
        action: wcmp.actions.export,
        request: wcmp.actions.add_shipments,
        offset: getPrintOffset(),
        order_ids: order_ids,
        print: print,
        _wpnonce: wcmp.nonce,
      };
    }

    doRequest.bind(this)({
      url: url,
      data: data || {},
      afterDone: function(response) {
        var redirect_url = updateUrlParameter(window.location.href, 'myparcelbe_done', 'true');

        if (print === 'no' || print === 'after_reload') {
          /* refresh page, admin notices are stored in options and will be displayed automatically */
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
          printLabel({
            order_ids: order_ids,
          });
        }
      },
    });
  }

  function myparcelbe_modal_dialog(order_ids, dialog) {
    var data = {
      action: wcmp.actions.export,
      request: wcmp.actions.modal_dialog,
      height: 380,
      width: 720,
      order_ids: order_ids,
      dialog: dialog,
      _wpnonce: wcmp.nonce,
      // LEAVE THIS AT THE BOTTOM! The awful code behind the thickbox splits the url on "TB_" for some reason.
      TB_iframe: true,
    };

    var url = wcmp.ajax_url + '?' + $.param(data);

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

  /**
   * Open given pdf link. Depending on the link it will be either downloaded or viewed. Refreshes the original window.
   *
   * @param {String} pdfUrl - The url of the created pdf.
   * @param {Boolean?} waitForOnload - Wait for onload to refresh the original window. Refreshes immediately if false.
   *
   */
  function openPdf(pdfUrl, waitForOnload) {
    var pdfWindow = window.open(pdfUrl, '_blank');

    if (waitForOnload) {
      /*
       * When the pdf window is loaded reload the main window. If we reload earlier the track & trace code won't be
       * ready yet and can't be shown.
       */
      pdfWindow.onload = function() {
        window.location.reload();
      };
    } else {
      /* For when there is no onload event or there is no need to wait. */
      window.location.reload();
    }
  }

  /**
   * Get the offset from the offset dialog if it's present. Otherwise return 0.
   *
   * @returns {Number}
   */
  function getPrintOffset() {
    return parseInt(askForPrintPosition ? $(selectors.offsetDialogInputOffset).val() : 0);
  }

  /* Request MyParcel BE labels */
  function printLabel(data) {
    var button = this;
    var request;

    if (button.href) {
      request = {
        url: button.href,
      };
    } else {
      request = {
        data: Object.assign({
          action: wcmp.actions.export,
          request: wcmp.actions.get_labels,
          offset: getPrintOffset(),
          _wpnonce: wcmp.nonce,
        }, data),
      };
    }

    request.afterDone = function(response) {
      openPdf(response);
    };

    if (wcmp.download_display === 'download') {
      doRequest.bind(button)(request);
    } else {
      var url;

      if (request.hasOwnProperty('url')) {
        url = request.url;
      } else {
        url = wcmp.ajax_url + '?' + $.param(request.data);
      }

      openPdf(url, true);
    }
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

  function showShipmentSummaryList() {
    var summaryList = $(this).next(selectors.shipmentSummaryList);

    if (summaryList.is(':hidden')) {
      summaryList.slideDown();
      document.addEventListener('click', hideShipmentSummaryList);
    } else {
    }

    if (summaryList.data('loaded') === '') {
      summaryList.addClass('ajax-waiting');
      summaryList.find(selectors.spinner).show();

      var data = {
        security: wcmp.nonce,
        action: 'wcmp_get_shipment_summary_status',
        order_id: summaryList.data('order_id'),
        shipment_id: summaryList.data('shipment_id'),
      };

      $.ajax({
        type: 'POST',
        url: wcmp.ajax_url,
        data: data,
        context: summaryList,
        success: function(response) {
          this.removeClass('ajax-waiting');
          this.html(response);
          this.data('loaded', true);
        },
      });
    }
  }

  /**
   * @param {MouseEvent} event - The click event.
   * @param {Element} event.target - Click target.
   */
  function hideShipmentOptionsForm(event) {
    handleClickOutside.bind(hideShipmentOptionsForm)(event, {
      main: selectors.shipmentOptionsDialog,
      wrappers: [selectors.shipmentOptions, selectors.shipmentOptionsShowButton],
    });
  }

  /**
   * Main: The element that will be hidden.
   * Wrappers: Elements which don't count as "outside" when clicked.
   *
   * @param {MouseEvent} event - Click event.
   * @property {Element} event.target
   */
  function hideShipmentSummaryList(event) {
    handleClickOutside.bind(hideShipmentSummaryList)(event, {
      main: selectors.shipmentSummaryList,
      wrappers: [selectors.shipmentSummaryList, selectors.showShipmentSummaryList],
    });
  }

  /**
   * Hide any element by checking if the element clicked is not in the list of wrapper elements and not inside the
   *  element itself.
   *
   * @param {MouseEvent} event - The click event.
   * @param {Object} elements - The elements to show/hide and check inside.
   * @property {Node[]} elements.wrappers
   * @property {Node} elements.main
   */
  function handleClickOutside(event, elements) {
    event.preventDefault();
    var listener = this;
    var clickedOutside = true;

    elements.wrappers.forEach(function(cls) {
      if (clickedOutside && event.target.matches(cls) || event.target.closest(elements.main)) {
        clickedOutside = false;
      }
    });

    if (clickedOutside) {
      $(elements.main).slideUp();
      document.removeEventListener('click', listener);
    }
  }
});

/**
 * Object.assign() polyfill.
 */
if (typeof Object.assign !== 'function') {
  /* Must be writable: true, enumerable: false, configurable: true */
  Object.defineProperty(Object, 'assign', {
    value: function assign(target) {
      if (target === null || target === undefined) {
        throw new TypeError('Cannot convert undefined or null to object');
      }

      var to = Object(target);

      for (var index = 1; index < arguments.length; index++) {
        var nextSource = arguments[index];

        if (nextSource !== null && nextSource !== undefined) {
          for (var nextKey in nextSource) {
            /* Avoid bugs when hasOwnProperty is shadowed */
            if (Object.prototype.hasOwnProperty.call(nextSource, nextKey)) {
              to[nextKey] = nextSource[nextKey];
            }
          }
        }
      }
      return to;
    },
    writable: true,
    configurable: true,
  });
}
