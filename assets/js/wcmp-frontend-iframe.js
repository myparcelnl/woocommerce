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
	// trigger parent init functions
	// parent.MyPaLoaded();


	// setTimeout(function () {
	// 	new MyParcel();
	// 	window.mypa = {}
	// 	// window.mypa.fn.load();

	// 	// var fonts = parent.jQuery('label').css('font-family');
	// 	// var fontsSize = parent.jQuery('label').css('font-size');
	// 	// $('#myparcel').css("font-family", fonts).css("font-size", fontsSize);
	// }, 500);
});