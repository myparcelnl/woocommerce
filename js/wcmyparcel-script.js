jQuery(document).ready(function($) {
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
			url = 'edit.php?&action=wcmyparcel&order_ids='+order_ids+'&TB_iframe=true&height=460&width=720';

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

		tb_show('', url + '&TB_iframe=true&width=720&height=460');
	});

	$(window).bind('tb_unload', function() {
		// re-enable scrolling after closing thickbox
		// (not really needed since page is reloaded in the next step, but applied anyway)
		$("body").css({ overflow: 'inherit' })

		// reload page
		window.location.reload()
	});
	
});