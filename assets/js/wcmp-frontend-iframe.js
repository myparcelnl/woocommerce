jQuery( function( $ ) {
	parent.MyPaiFrame = window; //parent now has a ref to the iframe's window
	window.initSettings = function( settings ) {
		// init vars
		if(window.mypa == null || window.mypa == undefined){
			window.mypa = {};
		}
		window.mypa.settings = settings;
		// Let's go!
		new MyParcel();

		// copy parent font & font size
		if (typeof parent.mypajQuery !== "undefined" && parent.mypajQuery !== null) {
			var selector = parent.mypajQuery('.woocommerce-billing-fields').length ? '.woocommerce-billing-fields' : 'body';
			var fontFamily = parent.mypajQuery(selector).css('font-family');
			var fontsSize = parent.mypajQuery(selector).css('font-size');
			$('#myparcel').css("font-family", fontFamily).css("font-size", fontsSize);
		}
	}

	window.updateMyPa = function() {
		$.when(
			updatePageRequest()
		).done(function () {
			// parent.$('#mypa-load').on('change', function () {
			// 	$('#mypa-input', parent.document).trigger('change');
			// });
			parent.MyPaSetHeight();
		});
	}

	updatePageRequest = function () {
		if ($.active > 0) {
			window.setTimeout(updatePageRequest, 100);
		}
		else {
			window.mypa.fn.updatePage()
		}
	};
});