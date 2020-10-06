/* eslint-disable prefer-object-spread */
/**
 * @member {Object} wcmp
 * @property {Object} wcmp.actions
 * @property {{export: String, add_shipments: String, add_return: String, get_labels: String, modal_dialog: String}}
 *   wcmp.actions
 * @property {String} wcmp.api_url - The API Url we use in MyParcel requests.
 * @property {String} wcmp.ajax_url
 * @property {String} wcmp.ask_for_print_position
 * @property {Object} wcmp.bulk_actions
 * @property {String} wcmp.download_display
 * @property {String} wcmp.nonce
 * @property {Object.<String, String>} wcmp.strings
 */

/**
 * @typedef {Object} Dependency
 * @property {String} name
 * @property {Condition} condition
 * @property {HTMLInputElement} node
 */

/**
 * @typedef {Object} Condition
 * @property {String} parent_name
 * @property {String|Number} parent_value
 * @property {String|Number} set_value
 */

/**
 * Object.assign() polyfill.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Object/assign#Polyfill
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

/**
 * Array.find() polyfill.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Array/find#Polyfill
 */
if (!Array.prototype.find) {
  Object.defineProperty(Array.prototype, 'find', {
    value: function(predicate) {
      // 1. Let O be ? ToObject(this value).
      if (this == null) {
        throw TypeError('"this" is null or not defined');
      }

      var o = Object(this);

      // 2. Let len be ? ToLength(? Get(O, "length")).
      var len = o.length >>> 0;

      // 3. If IsCallable(predicate) is false, throw a TypeError exception.
      if (typeof predicate !== 'function') {
        throw TypeError('predicate must be a function');
      }

      // 4. If thisArg was supplied, let T be thisArg; else let T be undefined.
      var thisArg = arguments[1];

      // 5. Let k be 0.
      var k = 0;

      // 6. Repeat, while k < len
      while (k < len) {
        /*
         * a. Let Pk be ! ToString(k).
         * b. Let kValue be ? Get(O, Pk).
         * c. Let testResult be ToBoolean(? Call(predicate, T, « kValue, k, O »)).
         * d. If testResult is true, return kValue.
         */
        var kValue = o[k];
        if (predicate.call(thisArg, kValue, k, o)) {
          return kValue;
        }
        // e. Increase k by 1.
        k++;
      }

      // 7. Return undefined.
      return undefined;
    },
    configurable: true,
    writable: true,
  });
}

/**
 * Object.values() polyfill.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Object/values#Polyfill
 */
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

/* eslint-disable-next-line max-lines-per-function */
jQuery(function($) {
  /**
   * @type {Boolean}
   */
  var askForPrintPosition = Boolean(parseInt(wcmp.ask_for_print_position));

  var skeletonHtml
    = '<table class="wcmp__skeleton-loader">'
    + '<tr><td><div></div></td><td><div></div></td></tr>'.repeat(5)
    + '</table>';

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
    toggle: '.wcmp__toggle',
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

    addToggleListeners();
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
   *
   * @param {String} name
   */
  function addDependencies(name) {
    /**
     * Get all nodes with a data-conditions attribute.
     */
    var nodesWithConditions = document.querySelectorAll('[data-conditions]');

    /**
     * Dependency object.
     *
     * @type {Object.<String, Dependency[]>}
     */
    var dependencies = {};

    /**
     * Loop through the classes to create a dependency like this: { [parent]: [{condition: Condition, node: Node}] }.
     */
    nodesWithConditions.forEach(function(node) {
      var conditions = node.getAttribute('data-conditions');
      conditions = JSON.parse(conditions);

      conditions
        .forEach(

          /**
           * @param {Condition} condition
           */
          function(condition) {
            /**
             * @type {Dependency}
             */
            var data = {
              name: node.getAttribute('name'),
              condition: condition,
              node: node,
            };

            if (dependencies.hasOwnProperty(condition.parent_name)) {
              dependencies[condition.parent_name].push(data);
            } else {
              // Or create the list with the node inside it
              dependencies[condition.parent_name] = [data];
            }
          }
        );
    });

    createDependencies(dependencies);
  }

  /**
   * @param {Object<String, Dependency[]>} dependencies
   * @param {HTMLInputElement|Node} input
   * @param {Number} easing
   * @param level
   */
  function recursiveDep(dependencies, input, easing, level) {
    level = level || 1;

    if (level >= 20) {
      throw new Error('Depth limit of ' + level + ' exceeded (probably an infinite loop)');
    }

    if (!dependencies.hasOwnProperty(input.name)) {
      return;
    }

    dependencies[input.name]
      .forEach(function(dependency) {
        var banner = ' => '.repeat(level);

        var dependantName = dependency.name;
        handleDependency(input, dependency, easing);

        if (dependencies.hasOwnProperty(dependantName)) {
          var dependantInput = document.querySelector('[name="' + dependantName + '"]');

          recursiveDep(dependencies, dependantInput, easing, level + 1);
        }
      });
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
   * @param {EventTarget} target
   */
  function syncToggle(target) {
    var element = $(target);
    var toggle = element.siblings('.woocommerce-input-toggle');

    if (element.attr('data-type') !== 'toggle') {
      return;
    }

    var mismatch0 = element.val() === '0' && toggle.hasClass('woocommerce-input-toggle--enabled');
    var mismatch1 = element.val() === '1' && toggle.hasClass('woocommerce-input-toggle--disabled');

    if (mismatch0 || mismatch1) {
      toggle.toggleClass('woocommerce-input-toggle--disabled');
      toggle.toggleClass('woocommerce-input-toggle--enabled');
    }
  }

  /**
   * Handle showing and hiding of settings.
   *
   * @param {Object<String, Dependency[]>} dependencies - Dependency names and all the nodes that depend on them.
   */
  function createDependencies(dependencies) {
    Object
      .keys(dependencies)
      .forEach(function(name) {
        var input = document.querySelector('[name="' + name + '"]');

        /**
         * Loop through all the dependencies.
         *
         * @param {Event|null} event - Event.
         * @param {Number} easing - Amount of easing.
         */
        function handle(event, easing) {
          if (easing === undefined) {
            easing = baseEasing;
          }

          if (event) {
            syncToggle(event.target);
          }

          recursiveDep(dependencies, input, easing);
        }

        input.addEventListener('change', handle);

        // Do this on load too.
        handle(null, 0);
      });
  }

  /**
   * @param {Element|HTMLInputElement} relatedInput - Parent of element.
   * @param {Dependency} dependant
   *
   * @param {Number} easing - Amount of easing on the transitions.
   */
  function handleDependency(relatedInput, dependant, easing) {
    var parentValue = dependant.condition.parent_value;
    var setValue = dependant.condition.set_value || null;
    var wantedValue = parentValue || '1';
    var elementContainer = $(dependant.node).closest('tr');

    /**
     * To make the item disabled/disappear/readonly or any other option in the switch statement.
     *
     * @type {Boolean}
     */
    var toggle;

    var parentToggled = relatedInput.getAttribute('data-toggled') === 'true';
    var dependantToggled = dependant.node.getAttribute('data-toggled') === 'true';

    console.log(parentToggled, dependantToggled);
    if (parentToggled && !dependantToggled) {
      toggle = true;
    } else if (typeof wantedValue === 'string') {
      console.log(relatedInput.value, wantedValue);
      toggle = relatedInput.value !== wantedValue;
    } else {
      toggle = parentValue.indexOf(relatedInput.value) === -1;
    }

    switch (dependant.condition.type) {
      case 'child':
      case 'show':
        elementContainer[toggle ? 'hide' : 'show'](easing);
        break;
      case 'readonly':
        $(elementContainer).attr('data-readonly', toggle);
        $(dependant.node).prop('readonly', toggle);
        break;
      case 'disable':
        $(elementContainer).attr('data-disabled', toggle);
        $(dependant.node).prop('disabled', toggle);
        break;
    }

    if (toggle && setValue) {
      dependant.node.value = setValue;
      dependant.node.dispatchEvent(new Event('change'));
    }

    relatedInput.setAttribute('data-toggled', toggle.toString());
    dependant.node.setAttribute('data-toggled', toggle.toString());
  }

  /**
   * Add event listeners to all toggle elements.
   */
  function addToggleListeners() {
    $(selectors.toggle).each(function() {
      $(this).on('click', handleToggle);
    });
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
      onStart: function() {
        form.html(skeletonHtml);
        form.slideDown(100);
      },

      /**
       * Show the correct data in the form and add event listeners for handling saving and clicking outside the form.
       *
       * @param {String} response - Html to put in the form.
       */
      afterDone: function(response) {
        form.html(response);

        addDependencies();
        addToggleListeners();
        $(selectors.shipmentOptionsSaveButton).on('click', saveShipmentOptions);
        document.addEventListener('click', hideShipmentOptionsForm);
      },
      afterFail: function() {
        form.slideUp(100);
      },
    });
  }

  /**
   * @param {Node} element
   * @param {String} state
   */
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

    if (request.hasOwnProperty('onStart') && typeof request.onStart === 'function') {
      request.onStart();
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

  /**
   * @param name
   * @param url
   */
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
        myparcel_modal_dialog(order_ids, 'return');
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

  /**
   *
   */
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
  /**
   * @param order_ids
   * @param print
   */
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
        var redirect_url = updateUrlParameter(window.location.href, 'myparcel_done', 'true');

        if (print === 'no' || print === 'after_reload') {
          /* refresh page, admin notices are stored in options and will be displayed automatically */
          window.location.href = redirect_url;
        } else {
          /* when printing, output notices directly so that we can init print in the same run */
          if (response !== null && typeof response === 'object' && 'error' in response) {
            myparcel_admin_notice(response.error, 'error');
          }

          if (response !== null && typeof response === 'object' && 'success' in response) {
            myparcel_admin_notice(response.success, 'success');
          }

          /* load PDF */
          printLabel({
            order_ids: order_ids,
          });
        }
      },
    });
  }

  /**
   * @param order_ids
   * @param dialog
   */
  function myparcel_modal_dialog(order_ids, dialog) {
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

  /* Request MyParcel labels */
  /**
   * @param data
   */
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
      var isDisplay = wcmp.download_display === 'display';
      var isDownload = wcmp.download_display === 'download';
      var isPdf = response.includes('PDF');
      var isApi = response.includes('api.myparcel.nl');

      if (isDisplay && isPdf) {
        handlePDF(request);
      }

      if (isDownload && isApi) {
        openPdf(response);
      }

      window.location.reload();
    };

    doRequest.bind(button)(request);
  }

  /**
   * @param request
   */
  function handlePDF(request) {
    var url;

    if (request.hasOwnProperty('data')) {
      url = wcmp.ajax_url + '?' + $.param(request.data);
    } else {
      url = request.url;
    }

    openPdf(url, true);
  }

  /**
   * @param message
   * @param type
   */
  function myparcel_admin_notice(message, type) {
    var mainHeader = $('#wpbody-content > .wrap > h1:first');
    var notice = '<div class="' + selectors.notice + ' notice notice-' + type + '"><p>' + message + '</p></div>';
    mainHeader.after(notice);
    $('html, body').animate({scrollTop: 0}, 'slow');
  }

  /* Add / Update a key-value pair in the URL query parameters */

  /* https://gist.github.com/niyazpk/f8ac616f181f6042d1e0 */
  /**
   * @param uri
   * @param key
   * @param value
   */
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

  /**
   *
   */
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

  /**
   * On clicking a toggle. Doesn't do anything if the parent row has data-readonly or data-disabled set to true.
   */
  function handleToggle() {
    var disabledClass = 'woocommerce-input-toggle--disabled';
    var enabledClass = 'woocommerce-input-toggle--enabled';
    var row = $(this).closest('tr');
    var input = $(this).find('input')[0];
    var toggle = $(this).find('.woocommerce-input-toggle');

    var rowReadOnly = row.attr('data-readonly') === 'true';
    var rowDisabled = row.attr('data-disabled') === 'true';

    if (rowReadOnly || rowDisabled) {
      return;
    }

    input.value = toggle.hasClass(disabledClass) ? '1' : '0';
    toggle.toggleClass(disabledClass);
    toggle.toggleClass(enabledClass);

    // To trigger event listeners
    input.dispatchEvent(new Event('change'));
  }
});
