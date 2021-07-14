/* eslint-disable prefer-object-spread */
/**
 * @property {Object} wcmp
 * @property {
 *  {
 *    export: String,
 *    export_order: String,
 *    export_return: String,
 *    get_labels: String,
 *    modal_dialog: String
 *  }
 * } wcmp.actions
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
 * @property {Object<String,*>} parents
 * @property {String|Number} set_value
 */
const DIALOG_HEIGHT = 380;
const DIALOG_WIDTH = 720;
const RECURSIVE_DEPTH_LIMIT = 20;

/* eslint-disable-next-line max-lines-per-function */
jQuery(($) => {
  /**
   * @type {Boolean}
   */
  const askForPrintPosition = Boolean(parseInt(wcmp.ask_for_print_position));

  const skeletonHtml
    = `<table class="wcmp__skeleton-loader">${
      '<tr><td><div></div></td><td><div></div></td></tr>'.repeat(5)
    }</table>`;

  const selectors = {
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
    tipTipHolder: '#tiptip_holder',
    tipTipContent: '#tiptip_content',
  };

  const spinner = {
    loading: 'loading',
    success: 'success',
    failed: 'failed',
  };

  addListeners();
  runTriggers();
  addDependencies();
  printQueuedLabels();

  const timeoutAfterRequest = 200;
  const baseEasing = 300;

  /**
   * Add event listeners.
   */
  function addListeners() {
    /**
     * Click offset dialog button (single export).
     */
    $(selectors.offsetDialogButton).click(printOrderFromOffsetDialog);

    /**
     * Close offset dialog.
     */
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
    $('#doaction').click(doBulkAction);

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
    document
      .querySelectorAll(selectors.shipmentSettingsWrapper)
      .forEach((element) => {
        const shippingAddressColumn = $(element)
          .closest('tr')
          .find('td.shipping_address');

        $(element).appendTo(shippingAddressColumn);
        $(element).show();
      });
  }

  /**
   * Add dependencies for form elements with conditions.
   */
  function addDependencies() {
    /**
     * Get all nodes with a data-conditions attribute.
     */
    const nodesWithConditions = document.querySelectorAll('[data-conditions]');

    /**
     * Dependency object.
     *
     * @type {Object.<String, Dependency[]>}
     */
    const dependencies = {};

    /**
     * Loop through the classes to create a dependency like this: { [parent]: [{condition: Condition, node: Node}] }.
     */
    nodesWithConditions.forEach((node) => {
      let conditions = node.getAttribute('data-conditions');
      conditions = JSON.parse(conditions);

      conditions
        .forEach((condition) => {
          Object
            .keys(condition.parents)
            .forEach((parent) => {
              /**
               * @type {Dependency}
               */
              const data = {
                condition: condition,
                node: node,
              };

              if (dependencies.hasOwnProperty(parent)) {
                dependencies[parent].push(data);
              } else {
                // Or create the list with the node inside it
                dependencies[parent] = [data];
              }
            });
        });
    });

    createDependencies(dependencies);
  }

  /**
   * Loops through dependants and collects changes that need to be done in queue.
   *
   * @param {Object<String, Dependency[]>} dependencies
   * @param {HTMLInputElement|Node} input
   * @param {?Number} level
   * @param {Object[]|null} queue
   *
   * @returns {Object[]} - Queue.
   */
  function checkDependenciesRecursively(dependencies, input, level, queue) {
    if (level >= RECURSIVE_DEPTH_LIMIT) {
      throw new Error(`Depth limit of ${level} exceeded (probably an infinite loop)`);
    }

    if (!dependencies.hasOwnProperty(input.name)) {
      return queue;
    }

    dependencies[input.name]
      .forEach((dependency) => {
        const data = handleDependency(dependency, level);

        queue.push({
          name: dependency.node.name.replace(/myparcel_options\[\d+\]/, ''),
          parent: input,
          node: dependency.node,
          type: dependency.condition.type,
          setValue: data.setValue,
          toggle: data.toggle,
        });

        if (dependencies.hasOwnProperty(dependency.node.name)) {
          const dependantInput = document.querySelector(`[name="${dependency.node.name}"]`);

          queue = checkDependenciesRecursively(dependencies, dependantInput, level + 1, queue);
        }
      });

    return queue;
  }

  /**
   * Executes a set of changes on an element and its parent.
   *
   * @param {Object} data
   * @param {HTMLInputElement} data.node
   * @param {HTMLInputElement} data.parent
   * @param {*} data.setValue
   * @param {Boolean} data.toggle
   * @param {String} data.type
   * @param {Number} easing
   */
  function toggleElement(data, easing) {
    const {node} = data;
    const {setValue} = data;
    const {toggle} = data;
    const elementContainer = $(node).closest('tr');

    switch (data.type) {
      case 'show':
        elementContainer[toggle ? 'hide' : 'show'](easing);
        break;
      case 'readonly':
        $(elementContainer).attr('data-readonly', toggle);
        $(node).prop('readonly', toggle);
        break;
      case 'disable':
        $(elementContainer).attr('data-disabled', toggle);
        $(node).prop('disabled', toggle);
        break;
    }

    if (toggle && setValue) {
      node.value = setValue;
      node.dispatchEvent(new Event('change'));
      // Sync toggles here as well as in the createDependencies because not all inputs listen to the change event.
      syncToggle(node);
    }

    data.parent.setAttribute('data-toggled', toggle.toString());
    node.setAttribute('data-toggled', toggle.toString());
  }

  /**
   * Sync the appearance of toggle elements with the value their hidden input.
   *
   * @param {EventTarget} target
   */
  function syncToggle(target) {
    const element = $(target);
    const toggle = element.siblings('.woocommerce-input-toggle');

    if (element.attr('data-type') !== 'toggle') {
      return;
    }

    const mismatch0 = element.val() === '0' && toggle.hasClass('woocommerce-input-toggle--enabled');
    const mismatch1 = element.val() === '1' && toggle.hasClass('woocommerce-input-toggle--disabled');

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
      .forEach((name) => {
        const inputSelector = `[name="${name}"]`;
        const input = document.querySelector(inputSelector);

        if (!input) {
          // eslint-disable-next-line no-console
          console.error(`Element ${inputSelector} not found.`);
          return;
        }

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

          const updateQueue = checkDependenciesRecursively(dependencies, input, 1, []);

          // Executes all needed updates gathered by checkDependenciesRecursively.
          updateQueue.forEach((dependency) => {
            toggleElement(dependency, easing);
          });
        }

        input.addEventListener('change', handle);

        // Do this on load too.
        handle(null, 0);
      });
  }

  /**
   * Determines if an element should be toggled and if its value should change by checking all parent elements' values.
   *
   * @param {Dependency} dependency
   * @param {Number} level
   *
   * @returns {Object}
   */
  function handleDependency(dependency, level) {
    const {parents} = dependency.condition;
    const setValue = dependency.condition.set_value || null;
    let toggle = false;

    Object
      .keys(parents)
      .forEach((parent) => {
        const parentInput = document.getElementsByName(parent)[0];
        let localToggle;
        const wantedValue = parents[parent] || '1';

        const parentToggled = parentInput.getAttribute('data-toggled') === 'true';
        const dependantToggled = dependency.node.getAttribute('data-toggled') === 'true';

        if (parentToggled && !dependantToggled && level > 1) {
          localToggle = true;
        } else if (typeof wantedValue === 'string') {
          localToggle = parentInput.value !== wantedValue;
        } else {
          localToggle = wantedValue.indexOf(parentInput.value) === -1;
        }

        if (localToggle === true) {
          toggle = true;
        }
      });

    return {
      toggle: toggle,
      setValue: setValue,
    };
  }

  /**
   * Add event listeners to all toggle elements.
   */
  function addToggleListeners() {
    document
      .querySelectorAll(selectors.toggle)
      .forEach((element) => {
        element.addEventListener('click', handleToggle);
      });
  }

  /**
   * Print queued labels.
   */
  function printQueuedLabels() {
    const printData = $(selectors.printQueue).val();

    if (printData) {
      printLabel(JSON.parse(printData));
    }
  }

  /**
   * Show the shipment options form on the Woo Orders page.
   *
   * @param {Event} event - Click event.
   */
  function showShipmentOptionsForm(event) {
    event.preventDefault();
    const element = $(event.currentTarget);
    const orderId = element.data('order-id');

    const form = $(selectors.shipmentOptionsDialog);
    const isSameAsLast = form.data('order-id') === orderId;
    const isVisible = form.is(':visible');

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
    const position = element.offset();
    position.top -= element.height();
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
      onStart() {
        form.html(skeletonHtml);
        form.slideDown(100);
      },

      /**
       * Show the correct data in the form and add event listeners for handling saving and clicking outside the form.
       *
       * @param {String} response - Html to put in the form.
       */
      afterDone(response) {
        form.html(response);

        addDependencies();
        addToggleListeners();

        $(selectors.shipmentOptionsSaveButton).on('click', saveShipmentOptions);
        document.addEventListener('click', hideShipmentOptionsForm);
        // Trigger WooCommerce's event to init any tipTips.
        document.body.dispatchEvent(new Event('init_tooltips'));
      },
      afterFail() {
        form.slideUp(100);
      },
    });
  }

  /**
   * @param {Node} element
   * @param {String} state
   */
  function setSpinner(element, state) {
    const baseSelector = selectors.spinner.replace('.', '');
    const spinner = $(element).find(selectors.spinner);

    if (state) {
      spinner
        .removeClass()
        .addClass(baseSelector)
        .addClass(`${baseSelector}--${state}`)
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
    const form = $(selectors.shipmentOptionsDialog);

    doRequest.bind(this)({
      url: wcmp.ajax_url,
      data: {
        action: 'wcmp_save_shipment_options',
        form_data: form.find(':input').serialize(),
        security: wcmp.nonce,
      },
      afterDone() {
        setTimeout(() => form.slideUp(), timeoutAfterRequest);
      },
    });
  }

  /**
   *
   */
  function removeNotices() {
    $(selectors.notice).remove();
  }

  /**
   * @param {Event} event - Click event.
   */
  function doBulkAction(event) {
    const targetElement = $(event.target);
    const action = targetElement.prev('select').val();
    const spinnerWrapper = targetElement.parent('.bulkactions');

    /**
     * Check the selected action is ours.
     */
    if (!Object.values(wcmp.bulk_actions).includes(action)) {
      return;
    }

    event.preventDefault();
    removeNotices();

    const checkedRowsSelector = '#the-list th.check-column input[type="checkbox"]:checked';
    const checkedRows = [...document.querySelectorAll(checkedRowsSelector)];
    const orderIds = checkedRows.map((element) => element.value);

    const rows = orderIds.map((id) => `.post-${id}`);
    $(rows.join(', ')).addClass('wcmp__loading');

    if (!orderIds.length) {
      showAdminNotice(wcmp.strings.no_orders_selected);
      return;
    }

    switch (action) {
      // Export orders.
      case wcmp.bulk_actions.export:
        exportToMyParcel.bind(spinnerWrapper)(orderIds);
        break;

      // Print labels.
      case wcmp.bulk_actions.print:
        printLabel.bind(spinnerWrapper)({
          order_ids: orderIds,
        });
        break;

      // Export and print.
      case wcmp.bulk_actions.export_print:
        exportToMyParcel.bind(spinnerWrapper)(orderIds, 'after_reload');
        break;
    }
  }

  /**
   * Add a callback to request object in given key.
   *
   * @param {Object} request
   * @param {String} key
   * @param {Function} callback
   */
  function addCallback(request, key, callback) {
    let requestCallback;

    if (request[key] && typeof request[key] === 'function') {
      requestCallback = [
        request[key],
        callback,
      ];
    } else if (Array.isArray(request[key])) {
      requestCallback = request[key];
      requestCallback.push(callback);
    } else {
      requestCallback = callback;
    }

    request[key] = requestCallback;
  }

  /**
   * Execute a function or array of functions from given request, key and parameters to pass to the function.
   *
   * @param {Object} request
   * @param {String} callbackKey
   * @param {...any} parameters
   */
  function doCallback(request, callbackKey, ...parameters) {
    if (!request.hasOwnProperty(callbackKey)) {
      return;
    }

    const requestCallback = request[callbackKey];

    if (typeof requestCallback === 'function') {
      requestCallback(...parameters);
    } else if (Array.isArray(requestCallback)) {
      requestCallback.forEach((callback) => {
        callback(...parameters);
      });
    }
  }

  /**
   * Do an ajax request.
   *
   * @param {Object} request - Request object.
   */
  function doRequest(request) {
    $(this).prop('disabled', true);
    setSpinner(this, spinner.loading);

    if (!request.url) {
      request.url = wcmp.ajax_url;
    }

    doCallback(request, 'onStart', request);

    $.ajax({
      url: request.url,
      method: request.method || 'POST',
      data: request.data || {},
    })
      .done((response) => {
        setSpinner(this, spinner.success);
        doCallback(request, 'afterDone', response);
      })

      .fail((response) => {
        setSpinner(this, spinner.failed);
        doCallback(request, 'afterFail', response);
      })

      .always((response) => {
        $(this).prop('disabled', false);
        doCallback(request, 'afterAlways', response);
      });
  }

  /**
   * @param {String} name
   * @param {String} url
   *
   * @returns {String}
   */
  function getParameterByName(name, url) {
    if (!url) {
      url = window.location.href;
    }
    name = name.replace(/[[]]/g, '\\$&');

    const regex = new RegExp(`[?&]${name}(?:=([^&#]*)|&|#|$)`);
    const results = regex.exec(url);

    if (!results) {
      return null;
    }

    if (!results[1]) {
      return '';
    }

    return decodeURIComponent(results[1].replace(/\+/g, ' '));
  }

  /**
   * On clicking the actions in a single order.
   *
   * @param {Event} event - Click event.
   */
  function onActionClick(event) {
    const request = getParameterByName('request', this.href);
    const orderIds = getParameterByName('order_ids', this.href);

    if (!wcmp.actions.hasOwnProperty(request)) {
      return;
    }

    event.preventDefault();

    switch (request) {
      case wcmp.actions.export_order:
        exportToMyParcel.bind(this)();
        break;
      case wcmp.actions.get_labels:
        if (askForPrintPosition && !$(this).hasClass('wcmp__offset-dialog__button')) {
          showOffsetDialog.bind(this)();
        } else {
          printLabel.bind(this)();
        }
        break;
      case wcmp.actions.export_return:
        showDialog(orderIds, 'return', request);
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

    const offsetDialog = $(selectors.offsetDialog);
    const dialogButton = $(selectors.offsetDialogButton);
    const parentOffset = $(this).offset();

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

    dialogButton.attr('href', this.href);

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
    const dialogButton = $(selectors.offsetDialogButton);
    const hasOffset = dialogButton.attr('href').indexOf('offset=') > -1;
    const newOffset = this.value;

    if (hasOffset) {
      dialogButton.attr('href', dialogButton.attr('href').replace(/([?&]offset=)\d*/, `$1${newOffset}`));
    } else {
      dialogButton.attr('href', `${dialogButton.attr('href')}&offset=${newOffset}`);
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
   * @param {MouseEvent} event
   */
  function printOrderFromOffsetDialog(event) {
    event.preventDefault();
    const dialog = $(this).closest(selectors.offsetDialog);

    printLabel.bind(this)({
      afterDone() {
        dialog.hide();
      },
    });
  }

  /**
   * Export orders to MyParcel via AJAX.
   *
   * @param {String[]} orderIds
   * @param {String} print
   */
  function exportToMyParcel(orderIds, print) {
    let url;
    let data;

    if (typeof print === 'undefined') {
      print = 'no';
    }

    if (this.href) {
      url = this.href;
    } else {
      data = {
        action: wcmp.actions.export,
        request: wcmp.actions.export_order,
        offset: getPrintOffset(),
        order_ids: orderIds,
        print: print,
        _wpnonce: wcmp.nonce,
      };
    }

    doRequest.bind(this)({
      url: url,
      data: data || {},
      afterDone(response) {
        const redirectUrl = updateUrlParameter(window.location.href, 'myparcel_done', 'true');

        if (print === 'no' || print === 'after_reload') {
          /* refresh page, admin notices are stored in options and will be displayed automatically */
          window.location.href = redirectUrl;
        } else {
          /* load PDF */
          printLabel({
            order_ids: orderIds,
          });
        }
      },
    });
  }

  /**
   * @param {String} orderIds
   * @param {String} dialog
   */
  function showDialog(orderIds, dialog) {
    const data = {
      action: wcmp.actions.export,
      request: wcmp.actions.modal_dialog,
      order_ids: orderIds,
      dialog,
      _wpnonce: wcmp.nonce,
      // LEAVE THIS AT THE BOTTOM! The awful code behind the thickbox splits the url on "TB_" for some reason.
      TB_iframe: true,
    };

    const url = `${wcmp.ajax_url}?${$.param(data)}`;

    /* disable background scrolling */
    $('body').css({overflow: 'hidden'});

    tb_show(wcmp.strings.dialog[dialog], url);
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
    const pdfWindow = window.open(pdfUrl, '_blank');

    if (waitForOnload) {
      /*
       * When the pdf window is loaded reload the main window. If we reload earlier the track & trace code won't be
       * ready yet and can't be shown.
       */
      pdfWindow.onload = () => {
        return window.location.reload();
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

  /**
   * Request MyParcel labels.
   *
   * @param {Object} data
   */
  function printLabel(data) {
    let request;

    if (this && this.href) {
      request = {
        url: this.href,
      };
    } else {
      request = {
        data: {
          action: wcmp.actions.export,
          request: wcmp.actions.get_labels,
          offset: getPrintOffset(),
          _wpnonce: wcmp.nonce,
          ...data,
        },
      };
    }

    addCallback(request, 'afterDone', (response) => {
      const isDisplay = wcmp.download_display === 'display';
      const isDownload = wcmp.download_display === 'download';
      const isPdf = response.includes('PDF');
      const isApi = response.includes('api.myparcel.nl');

      if (isDisplay && isPdf) {
        handlePDF(request);
      } else if (isDownload && isApi) {
        openPdf(response);
      } else {
        window.location.reload();
      }
    });

    doRequest.bind(this)(request);
  }

  /**
   * @param {Object} request
   */
  function handlePDF(request) {
    let url;

    if (request.hasOwnProperty('data')) {
      url = `${wcmp.ajax_url}?${$.param(request.data)}`;
    } else {
      url = request.url;
    }

    openPdf(url, true);
  }

  /**
   * @param {String} message
   * @param {String} type
   */
  function showAdminNotice(message, type = 'warning') {
    const mainHeader = $('#wpbody-content > .wrap > h1:first');
    const notice = `<div class="${selectors.notice} notice notice-${type}"><p>${message}</p></div>`;
    mainHeader.after(notice);
    $('html, body').animate({scrollTop: 0}, 'slow');
  }

  /**
   * Add/update a key-value pair in the URL query parameters.
   *
   * @see https://gist.github.com/niyazpk/f8ac616f181f6042d1e0
   *
   * @param {String} uri
   * @param {String} key
   * @param {String} value
   *
   * @returns {String}
   */
  function updateUrlParameter(uri, key, value) {
    /* remove the hash part before operating on the uri */
    const i = uri.indexOf('#');
    const hash = i === -1 ? '' : uri.substr(i);
    uri = i === -1 ? uri : uri.substr(0, i);

    const re = new RegExp(`([?&])${key}=.*?(&|$)`, 'i');
    const separator = uri.indexOf('?') === -1 ? '?' : '&';
    if (uri.match(re)) {
      uri = uri.replace(re, `$1${key}=${value}$2`);
    } else {
      uri = `${uri + separator + key}=${value}`;
    }
    // finally append the hash as well
    return uri + hash;
  }

  /**
   *
   */
  function showShipmentSummaryList() {
    const summaryList = $(this).next(selectors.shipmentSummaryList);

    if (summaryList.is(':hidden')) {
      summaryList.slideDown();
      document.addEventListener('click', hideShipmentSummaryList);
    }

    if (summaryList.data('loaded') === '') {
      summaryList.addClass('ajax-waiting');
      summaryList.find(selectors.spinner).show();

      const data = {
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
        success(response) {
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
      wrappers: [
        selectors.shipmentOptions,
        selectors.shipmentOptionsShowButton,
        // Add the tipTip ids as well so clicking a tipTip inside shipment options won't close the form.
        selectors.tipTipHolder,
        selectors.tipTipContent,
      ],
    });
  }

  /**
   * Main: The element that will be hidden.
   * Wrappers: Elements which don't count as "outside" when clicked.
   *
   * @param {MouseEvent} event - Click event.
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
   * @param {Node[]} elements.wrappers
   * @param {Node} elements.main
   */
  function handleClickOutside(event, elements) {
    event.preventDefault();
    let clickedOutside = true;

    elements.wrappers.forEach((className) => {
      if (clickedOutside && event.target.matches(className) || event.target.closest(elements.main)) {
        clickedOutside = false;
      }
    });

    if (clickedOutside) {
      $(elements.main).slideUp();
      document.removeEventListener('click', this);
    }
  }

  /**
   * On clicking a toggle. Doesn't do anything if the parent row has data-readonly or data-disabled set to true.
   *
   * @param {MouseEvent} event
   */
  function handleToggle(event) {
    const disabledClass = 'woocommerce-input-toggle--disabled';
    const enabledClass = 'woocommerce-input-toggle--enabled';
    const row = $(event.currentTarget).closest('tr');
    const [input] = $(event.currentTarget).find('input');
    const toggle = $(event.currentTarget).find('.woocommerce-input-toggle');

    const rowReadOnly = row.attr('data-readonly') === 'true';
    const rowDisabled = row.attr('data-disabled') === 'true';

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
