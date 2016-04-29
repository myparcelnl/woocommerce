jQuery( function( $ ) {
	// move shipment options to 'Ship to' column
	$('.wcmp_shipment_options').each( function( index ) {
		$ship_to_column = $( this ).closest('tr').find('td.shipping_address');
		$( this ).appendTo( $ship_to_column );
		// hidden by default - make visible
		$( this ).show();
	});

	$('.wcmp_show_shipment_options').click( function ( event ) {
		event.preventDefault();
		$( this ).next('.wcmp_shipment_options_form').toggle();
	});

	

	// select > 500 if insured amount input is >499
	$( '.wcmp_shipment_options input.insured_amount' ).each( function( index ) {
		if ( $( this ).val() > 499 ) {
			insured_select = $( this ).closest('table').parent().find('select.insured_amount');
			$( insured_select ).val('');
		};
	});

	// hide insurance options if unsured not checked
	$('.wcmp_shipment_options .insured').change(function () {
		insured_select = $( this ).closest('table').parent().find('select.insured_amount');
		insured_input  = $( this ).closest('table').parent().find('input.insured_amount');
		if (this.checked) {
			$( insured_select ).prop('disabled', false);
			$( insured_select ).closest('tr').show();
			$('select.insured_amount').change();
		} else {
			$( insured_select ).prop('disabled', true);
			$( insured_select ).closest('tr').hide();
			$( insured_input ).closest('tr').hide();
		}
	}).change(); //ensure visible state matches initially

	// hide & disable insured amount input if not needed
	$('.wcmp_shipment_options select.insured_amount').change(function () {
		insured_check  = $( this ).closest('table').parent().find('.insured');
		insured_select = $( this ).closest('table').parent().find('select.insured_amount');
		insured_input  = $( this ).closest('table').find('input.insured_amount');
		if ( $( insured_select ).val() ) {
			$( insured_input ).val('');
			$( insured_input ).prop('disabled', true);
			$( insured_input ).closest('tr').hide();
		} else {
			$( insured_input ).prop('disabled', false);
			$( insured_input ).closest('tr').show();
		}
	}).change(); //ensure visible state matches initially

	// hide all options if not a parcel
	$('.wcmp_shipment_options select.shipment_type').change(function () {
		parcel_options  = $( this ).closest('table').parent().find('.parcel_options');
		if ( $( this ).val() == 'standard') {
			// parcel
			$( parcel_options ).find('input, textarea, button, select').prop('disabled', false);
			$( parcel_options ).show();
			$('.insured').change();
		} else {
			// not a parcel
			$( parcel_options ).find('input, textarea, button, select').prop('disabled', true);
			$( parcel_options ).hide();
			$('.insured').prop('checked', false);
			$('.insured').change();
		}
	}).change(); //ensure visible state matches initially







	var url
	
	$("#doaction, #doaction2").click(function (event) {
		var actionselected = $(this).attr("id").substr(2);
		if ( $('select[name="' + actionselected + '"]').val() == "wcmyparcel") {
			event.preventDefault();
			var checked = [];
			$('tbody th.check-column input[type="checkbox"]:checked').each(
				function() {
					checked.push($(this).val());
				}
			);
			
			var order_ids=checked.join('x');
			
			var H = $(window).height()-120;

			url = 'edit.php?&action=wcmyparcel&order_ids='+order_ids+'&TB_iframe=true&height='+H+'&width=720';

			// disable background scrolling
			$("body").css({ overflow: 'hidden' })
		
			tb_show('', url);
		}

		if ( $('select[name="' + actionselected + '"]').val() == "wcmyparcel-label") {
			event.preventDefault();
			var checked = [];
			$('tbody th.check-column input[type="checkbox"]:checked').each(
				function() {
					checked.push($(this).val());
				}
			);
			
			var order_ids=checked.join('x');
			url = 'edit.php?&action=wcmyparcel-label&order_ids='+order_ids;
			
			window.location.href = url;
		}
	});

	// click print button
	$('.one-myparcel').on('click', function(event) {
		event.preventDefault();
		var url = $(this).attr('href');

		// disable background scrolling
		$("body").css({ overflow: 'hidden' })

		var H = $(window).height()-120;
		tb_show('', url + '&TB_iframe=true&width=720&height='+H);
	});

	$(window).bind('tb_unload', function() {
		// re-enable scrolling after closing thickbox
		// (not really needed since page is reloaded in the next step, but applied anyway)
		$("body").css({ overflow: 'inherit' })

		// reload page
		window.location.reload()
	});
	
});