jQuery( function( $ ) {
	// enable color picker
	$('.wcmp-color-picker').wpColorPicker();

	// move shipment options to 'Ship to' column
	$('.wp-list-table .wcmp_shipment_options, .wp-list-table .wcmp_shipment_summary').each( function( index ) {
		var $ship_to_column = $( this ).closest('tr').find('td.shipping_address');
		$( this ).appendTo( $ship_to_column );
		// hidden by default - make visible
		$( this ).show();
	});

	// disable ALL shipment options form fiels to avoid conflicts with order search field
	$('.wp-list-table .wcmp_shipment_options_form :input').prop('disabled', true);

	// show and enable options when clicked
	$('.wcmp_show_shipment_options').click( function ( event ) {
		event.preventDefault();
		$form = $( this ).next('.wcmp_shipment_options_form');
		if( $form.is(':visible') ) {
			// disable all input fields again
			$form.find(':input').prop('disabled', true);
			// hide form
			$form.slideUp();
		} else {
			// enable all fields on this form
			$form.find(':input').prop('disabled', false);
			// set init states according to change events
			$form.find(':input').change();
			// show form
			$form.slideDown();
		}
	});
	// hide options form when click outside
	$(document).click(function(event) {
		if(!$(event.target).closest('.wcmp_shipment_options_form').length) {
			if( !( $(event.target).hasClass('wcmp_show_shipment_options') || $(event.target).parent().hasClass('wcmp_show_shipment_options') ) && $('.wcmp_shipment_options_form').is(":visible")) {
				// disable all input fields again
				$('.wcmp_shipment_options_form :input').prop('disabled', true);
				// hide form
				$('.wcmp_shipment_options_form').slideUp();
			}
		}
	})

	// show summary when clicked
	$('.wcmp_show_shipment_summary').click( function ( event ) {
		event.preventDefault();
		$( this ).next('.wcmp_shipment_summary_list').slideToggle();
	});
	// hide summary when click outside
	$(document).click(function(event) {
		if(!$(event.target).closest('.wcmp_shipment_summary_list').length) {
			if( !( $(event.target).hasClass('wcmp_show_shipment_summary') || $(event.target).parent().hasClass('wcmp_shipment_summary') ) && $('.wcmp_shipment_summary_list').is(":visible")) {
				$('.wcmp_shipment_summary_list').slideUp();
			}
		}
	})



	// hide automatic order status if automation not enabled
	$('.wcmp_shipment_options input#order_status_automation').change(function () {
		var order_status_select = $( '.wcmp_shipment_options select.automatic_order_status');
		if (this.checked) {
			$( order_status_select ).prop('disabled', false);
			$( '.wcmp_shipment_options tr.automatic_order_status').show();
		} else {
			$( order_status_select ).prop('disabled', true);
			$( '.wcmp_shipment_options tr.automatic_order_status').hide();
		}
	});


	// select > 500 if insured amount input is >499
	$( '.wcmp_shipment_options input.insured_amount' ).each( function( index ) {
		if ( $( this ).val() > 499 ) {
			var insured_select = $( this ).closest('table').parent().find('select.insured_amount');
			$( insured_select ).val('');
		};
	});

	// hide insurance options if insured not checked
	$('.wcmp_shipment_options input.insured').change(function () {
		var insured_select = $( this ).closest('table').parent().find('select.insured_amount');
		var insured_input  = $( this ).closest('table').parent().find('input.insured_amount');
		if (this.checked) {
			$( insured_select ).prop('disabled', false);
			$( insured_select ).closest('tr').show();
			$('select.insured_amount').change();
		} else {
			$( insured_select ).prop('disabled', true);
			$( insured_select ).closest('tr').hide();
			$( insured_input ).closest('tr').hide();
		}
	});

	// hide & disable insured amount input if not needed
	$('.wcmp_shipment_options select.insured_amount').change(function () {
		var insured_check  = $( this ).closest('table').parent().find('input.insured');
		var insured_select = $( this ).closest('table').parent().find('select.insured_amount');
		var insured_input  = $( this ).closest('table').find('input.insured_amount');
		if ( $( insured_select ).val() ) {
			$( insured_input ).val('');
			$( insured_input ).prop('disabled', true);
			$( insured_input ).closest('tr').hide();
		} else {
			$( insured_input ).prop('disabled', false);
			$( insured_input ).closest('tr').show();
		}
	});

	// hide all options if not a parcel
	$('.wcmp_shipment_options select.package_type').change(function () {
		var parcel_options  = $( this ).closest('table').parent().find('.parcel_options');
		if ( $( this ).val() == '1') {
			// parcel
			$( parcel_options ).find('input, textarea, button, select').prop('disabled', false);
			$( parcel_options ).show();
			$( parcel_options ).find('.insured').change();
		} else {
			// not a parcel
			$( parcel_options ).find('input, textarea, button, select').prop('disabled', true);
			$( parcel_options ).hide();
			$( parcel_options ).find('.insured').prop('checked', false);
			$( parcel_options ).find('.insured').change();
		}
	});

	// hide delivery options details if disabled
	$('input.wcmp_delivery_option').change(function () {
		if ($(this).is(':checked')) {
			$(this).parent().find('.wcmp_delivery_option_details').show();
		} else {
			$(this).parent().find('.wcmp_delivery_option_details').hide();
		}
	});

	// check witch radio button of A4 or A6 is activated and disable/enable print position
	$('input[id^=\'label_format\']').change(function () {

		//when the options are not selected
		if (!$(this).is(':checked')) {
			return;
        }

        var parent_offset = $("#print_position_offset").parent().parent();

		if ($(this).attr("value") == "A4") {
            parent_offset.show();
			return;
		}

		// Always A6
        parent_offset.hide();
		$("#print_position_offset").prop("checked", false);
	});


	// Hide all checkout options if disabled
	$('#woocommerce-myparcelbe-settings #myparcelbe_checkout').change(function () {
		$next_settings_rows = $(this).closest('tr').nextAll('tr');
		$next_settings_headers = $(this).closest('table').nextAll('h2');
		$next_settings_forms = $(this).closest('table').nextAll('table');
		if ($(this).is(':checked')) {
			$next_settings_rows.show();
			$next_settings_forms.show()
			$next_settings_headers.show();
		} else {
			$next_settings_rows.hide();
			$next_settings_forms.hide();
			$next_settings_headers.hide();
		}
	});

	// init options on settings page and in bulk form
	$('#woocommerce-myparcelbe-settings :input, .wcmp_bulk_options_form :input').change();

	// myparcelbe_checkout

	// saving shipment options via AJAX
	$( '.wcmp_save_shipment_settings' )
		.on( 'click', 'a.button.save', function() {
			var order_id = $( this ).data().order;
			var $form = $( this ).closest('.wcmp_shipment_options').find('.wcmp_shipment_options_form');
			var package_type = $form.find('select.package_type option:selected').text();
			var $package_type_text_element = $( this ).closest('.wcmp_shipment_options').find('.wcpm_package_type');

			// show spinner
			$form.find('.wcmp_save_shipment_settings .waiting').show();

			var form_data = $form.find(":input").serialize();
			var data = {
				action:     'wcmp_save_shipment_options',
				order_id:   order_id,
				form_data:  form_data,
				security:   wc_myparcelbe.nonce,
			};

			$.post( wc_myparcelbe.ajax_url, data, function( response ) {
				// console.log(response);

				// set main text to selection
				$package_type_text_element.text(package_type);

				// hide spinner
				$form.find('.wcmp_save_shipment_settings .waiting').hide();

				// disable all input fields again
				$form.find(':input').prop('disabled', true);

				// hide the form
				$form.slideUp();
			});
		});

	// Print queued labels
	var print_queue = $("#wcmp_printqueue").val();
	var print_queue_offset = $("#wcmp_printqueue_offset").val();
	if ( typeof print_queue !== 'undefined' ) {
		if (typeof print_queue_offset === 'undefined') { print_queue_offset = 0; }
		myparcelbe_print( $.parseJSON(print_queue), print_queue_offset );
	}

	// Bulk actions
	$("#doaction, #doaction2").click(function (event) {
		var actionselected = $(this).attr("id").substr(2);
		// check if action starts with 'wcmp_'
		if ( $('select[name="' + actionselected + '"]').val().substring(0,5) == "wcmp_") {
			event.preventDefault();
			// remove notices
			$( '.myparcelbe_notice' ).remove();

			// strip 'wcmp_' from action
			var action = $('select[name="' + actionselected + '"]').val().substring(5);

			// Get array of checked orders (order_ids)
			var order_ids = [];
			$('tbody th.check-column input[type="checkbox"]:checked').each(
				function() {
					order_ids.push($(this).val());
				}
			);

			// execute action
			switch (action) {
				case 'export':
					bulk_spinner( this, 'show' );
					myparcelbe_export( order_ids );
					break;
				case 'print':
					bulk_spinner( this, 'show' );
					var offset = wc_myparcelbe.offset == 1 ? $('.wc_myparcelbe_offset').val() : 0;
					myparcelbe_print( order_ids, offset );
					break;
				case 'export_print':
					bulk_spinner( this, 'show' );
					myparcelbe_export( order_ids, 'after_reload' ); /* 'yes' inits print mode and disables refresh */
					break;
			}

			return;
		}
	});


	// Single actions click. The .wc_actions .single_wc_actions for support wc > 3.3.0
	$(".order_actions, .single_order_actions, .wc_actions, .single_wc_actions")
		.on( 'click', 'a.button.myparcelbe', function( event ) {
			event.preventDefault();
			var button_action = $( this ).data('request');
			var order_ids = [ $( this ).data('order-id') ];

			// execute action
			switch (button_action) {
				case 'add_shipment':
					var button = this;
					button_spinner( button, 'show' );
					myparcelbe_export( order_ids );
					// setTimeout(function() {
						// button_spinner( button, 'hide' );
					// }, 500);
					break;
				case 'get_labels':
					if (wc_myparcelbe.offset == 1) {
						contextual_offset_dialog( order_ids, event );
					} else {
						myparcelbe_print( order_ids );
					}
					break;
				case 'add_return':
					myparcelbe_modal_dialog( order_ids, 'return' );
					// myparcelbe_return( order_ids );
					break;
			}
		});		

	$(window).bind('tb_unload', function() {
		// re-enable scrolling after closing thickbox
		$("body").css({ overflow: 'inherit' })
	});

	// Add offset dialog when address labels option is selected
	$("select[name='action'], select[name='action2']").change( function () {
		var actionselected = $(this).val();
		// alert(actionselected);
		if ( ( actionselected == 'wcmp_print' ||  actionselected == 'wcmp_export_print' ) && wc_myparcelbe.offset == 1) {
			var insert_position = $(this).attr("name") == 'action' ? 'top' : 'bottom';
			$( '#wcmyparcelbe_offset_dialog' )
				.attr('style', 'clear:both') // reset styles
				.insertAfter( 'div.tablenav.'+insert_position )
				.show()

			// make sure button is not shown
			$( '#wcmyparcelbe_offset_dialog' ).find('button').hide();
			// clear input
			$( '#wcmyparcelbe_offset_dialog' ).find('input').val('');
		} else {
			$( '#wcmyparcelbe_offset_dialog' )
				.appendTo( 'body' )
				.hide();
		}
	});

	// Click offset dialog button (single export)
	$("#wcmyparcelbe_offset_dialog button").click( function (event) {
		$dialog = $(this).parent();

		// set print variables
		var order_ids = [$dialog.find('input.order_id').val()];
		var offset = $dialog.find('input.wc_myparcelbe_offset').val();

		// hide dialog
		$dialog.hide();

		// print labels
		myparcelbe_print( order_ids, offset );
	});


	function contextual_offset_dialog( order_ids, event ) {
		// place offset dialog at mouse tip
		$( '#wcmyparcelbe_offset_dialog' )
			.show()
			.appendTo( 'body' )
			.css( {
				position: "absolute", 
				"background-color": "white",
				padding: "6px",
				width: "100px",
				border: "1px solid #ccc",
				top: event.pageY,
				left: event.pageX,
				"margin-left": "-100px",
			} );

		$( '#wcmyparcelbe_offset_dialog' ).find('button')
			.show()
			.data( 'order_id', order_ids );

		// clear input
		$( '#wcmyparcelbe_offset_dialog' ).find('input').val('');

		$( '#wcmyparcelbe_offset_dialog' ).append('<input type=hidden class="order_id"/>');
		$( '#wcmyparcelbe_offset_dialog input.order_id' ).val(order_ids);
	}

	function button_spinner( button, display ) {
		if (display == 'show') {
			$button_img = $( button ).find( '.wcmp_button_img' );
			$button_img.hide();
			// console.log($( button ).parent().find('.wcmp_spinner'));
			$( button ).parent().find('.wcmp_spinner')
				.insertAfter( $button_img )
				.show();
		} else {
			$( button ).parent().find('.wcmp_spinner').hide();
			$( button ).find( '.wcmp_button_img' ).show();
		}
	}

	function bulk_spinner( action, display ) {
		if (display == 'show') {
			$submit_button = $( action ).parent().find('.button.action');
			$('.wcmp_bulk_spinner').insertAfter($submit_button).show();
		} else {
			$('.wcmp_bulk_spinner').hide();
		}
	}

	// export orders to MyParcelbe via AJAX
	function myparcelbe_export( order_ids, print ) {
		if (typeof print === 'undefined') { print = 'no'; }
		var offset = wc_myparcelbe.offset == 1 ? $('.wc_myparcelbe_offset').val() : 0;
		// console.log('exporting order to myparcelbe...');
		var data = {
			action:           'wc_myparcelbe',
			request:          'add_shipments',
			order_ids:        order_ids,
			offset:           offset, 
			print:            print,
			security:         wc_myparcelbe.nonce,
		};

		$.post( wc_myparcelbe.ajax_url, data, function( response ) {
			response = $.parseJSON(response);

			if (print == 'no' || print == 'after_reload') {
				// refresh page, admin notices are stored in options and will be displayed automatically
				// location.reload(true);
				redirect_url = updateUrlParameter( window.location.href, 'myparcelbe_done', 'true' );
				window.location.href = redirect_url;
				return;
			} else {
				// when printing, output notices directly so that we can init print in the same run
				if ( response !== null && typeof response === 'object' && 'error' in response) {
					myparcelbe_admin_notice( response.error, 'error' );
				}

				if ( response !== null && typeof response === 'object' && 'success' in response) {
					myparcelbe_admin_notice( response.success, 'success' );
				}

				// load PDF
				myparcelbe_print( order_ids, offset );
			}

			return;
		});

	}

	function myparcelbe_modal_dialog( order_ids, dialog ) {
		var request_prefix = (wc_myparcelbe.ajax_url.indexOf("?") != -1) ? '&' : '?';
		var thickbox_height = $(window).height()-120;
		var thickbox_parameters = '&TB_iframe=true&height='+thickbox_height+'&width=720';
		var url = wc_myparcelbe.ajax_url+request_prefix+'order_ids='+order_ids+'&action=wc_myparcelbe&request=modal_dialog&dialog='+dialog+'&security='+wc_myparcelbe.nonce+thickbox_parameters;

		// disable background scrolling
		$("body").css({ overflow: 'hidden' })
	
		tb_show('', url);
	}

	// export orders to MyParcelbe via AJAX
	function myparcelbe_return( order_ids ) {
		// console.log('creating return for orders...');
		var data = {
			action:           'wc_myparcelbe',
			request:          'add_return',
			order_ids:        order_ids,
			security:         wc_myparcelbe.nonce,
		};

		$.post( wc_myparcelbe.ajax_url, data, function( response ) {
			response = $.parseJSON(response);
			// console.log(response);
			if ( response !== null && typeof response === 'object' && 'error' in response) {
				myparcelbe_admin_notice( response.error, 'error' );
			}
			return;
		});

	}


	// Request MyParcelbe labels
	function myparcelbe_print( order_ids, offset ) {
		// console.log('requesting myparcelbe labels...');
		if (typeof offset === 'undefined') { offset = 0; }

		var request_prefix = (wc_myparcelbe.ajax_url.indexOf("?") != -1) ? '&' : '?';
		var url = wc_myparcelbe.ajax_url+request_prefix+'action=wc_myparcelbe&request=get_labels&security='+wc_myparcelbe.nonce;

		// create form to send order_ids via POST
		$('body').append('<form action="'+url+'" method="post" target="_blank" id="myparcelbe_post_data"></form>');
		$('#myparcelbe_post_data').append('<input type="hidden" name="offset" class="offset"/>');
		$('#myparcelbe_post_data input.offset').val( offset );
		$('#myparcelbe_post_data').append('<input type="hidden" name="order_ids" class="order_ids"/>');
		$('#myparcelbe_post_data input.order_ids').val( JSON.stringify( order_ids ) );

		// submit data to open or download pdf
		$('#myparcelbe_post_data').submit();

		bulk_spinner( '', 'hide' );


		/* alternate method:
		var data = {
			action:               'wc_myparcelbe',
			request:              'get_labels',
			order_ids:            order_ids,
			security:             wc_myparcelbe.nonce,
			label_response_type:  'url',
		};

		$.post( wc_myparcelbe.ajax_url, data, function( response ) {
			response = $.parseJSON(response);
			console.log(response);
			if ( response !== null && typeof response === 'object' && 'error' in response) {
				myparcelbe_admin_notice( response.error, 'error' );
			} else if ( response !== null && typeof response === 'object' && 'url' in response) {
				window.open( response.url, '_blank' );
			}
			return;
		});
		*/

	}

	function myparcelbe_admin_notice( message, type ) {
		$main_header = $( '#wpbody-content > .wrap > h1:first' );
		var notice = '<div class="myparcelbe_notice notice notice-'+type+'"><p>'+message+'</p></div>';
		$main_header.after( notice );
		$('html, body').animate({ scrollTop: 0 }, 'slow');
	}

	// Add / Update a key-value pair in the URL query parameters
	// https://gist.github.com/niyazpk/f8ac616f181f6042d1e0
	function updateUrlParameter(uri, key, value) {
		// remove the hash part before operating on the uri
		var i = uri.indexOf('#');
		var hash = i === -1 ? ''  : uri.substr(i);
			 uri = i === -1 ? uri : uri.substr(0, i);

		var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
		var separator = uri.indexOf('?') !== -1 ? "&" : "?";
		if (uri.match(re)) {
			uri = uri.replace(re, '$1' + key + "=" + value + '$2');
		} else {
			uri = uri + separator + key + "=" + value;
		}
		return uri + hash;  // finally append the hash as well
	}

	$( document.body ).trigger( 'wc-enhanced-select-init' );

});

