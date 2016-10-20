// enhanced select for shipping method search, partially uses wc-enhanced-select!
jQuery( function( $ ) {
	function getEnhancedSelectFormatString() {
		var formatString = {
			formatMatches: function( matches ) {
				if ( 1 === matches ) {
					return wc_enhanced_select_params.i18n_matches_1;
				}

				return wc_enhanced_select_params.i18n_matches_n.replace( '%qty%', matches );
			},
			formatNoMatches: function() {
				return wc_enhanced_select_params.i18n_no_matches;
			},
			formatAjaxError: function() {
				return wc_enhanced_select_params.i18n_ajax_error;
			},
			formatInputTooShort: function( input, min ) {
				var number = min - input.length;

				if ( 1 === number ) {
					return wc_enhanced_select_params.i18n_input_too_short_1;
				}

				return wc_enhanced_select_params.i18n_input_too_short_n.replace( '%qty%', number );
			},
			formatInputTooLong: function( input, max ) {
				var number = input.length - max;

				if ( 1 === number ) {
					return wc_enhanced_select_params.i18n_input_too_long_1;
				}

				return wc_enhanced_select_params.i18n_input_too_long_n.replace( '%qty%', number );
			},
			formatSelectionTooBig: function( limit ) {
				if ( 1 === limit ) {
					return wc_enhanced_select_params.i18n_selection_too_long_1;
				}

				return wc_enhanced_select_params.i18n_selection_too_long_n.replace( '%qty%', limit );
			},
			formatLoadMore: function() {
				return wc_enhanced_select_params.i18n_load_more;
			},
			formatSearching: function() {
				return wc_enhanced_select_params.i18n_searching;
			}
		};

		return formatString;
	}

	$( document.body )

		.on( 'wc-myparcel-enhanced-select-init', function() {

			// Ajax shipping method search box
			$( ':input.wc-myparcel-shipping-method-search' ).filter( ':not(.enhanced)' ).each( function() {
				var select2_args = {
					allowClear:  $( this ).data( 'allow_clear' ) ? true : false,
					placeholder: $( this ).data( 'placeholder' ),
					minimumInputLength: $( this ).data( 'minimum_input_length' ) ? $( this ).data( 'minimum_input_length' ) : '3',
					escapeMarkup: function( m ) {
						return m;
					},
					ajax: {
				        url:         wc_myparcel.ajax_url,
				        dataType:    'json',
				        quietMillis: 250,
				        data: function( term ) {
				            return {
								term:     term,
								action:   'wc_myparcel_json_search_shipping_methods',
								security: wc_myparcel.nonce,
				            };
				        },
				        results: function( data ) {
				        	var terms = [];
					        if ( data ) {
								$.each( data, function( id, text ) {
									terms.push( { id: id, text: text } );
								});
							}
				            return {
				            	results: terms
			            	};
				        },
				        cache: true
				    }
				};

				if ( $( this ).data( 'multiple' ) === true ) {
					select2_args.multiple = true;
					select2_args.initSelection = function( element, callback ) {
						var data     = $.parseJSON( element.attr( 'data-selected' ) );
						var selected = [];

						$( element.val().split( ',' ) ).each( function( i, val ) {
							selected.push({
								id: val,
								text: data[ val ]
							});
						});
						return callback( selected );
					};
					select2_args.formatSelection = function( data ) {
						return '<div class="selected-option" data-id="' + data.id + '">' + data.text + '</div>';
					};
				} else {
					select2_args.multiple = false;
					select2_args.initSelection = function( element, callback ) {
						var data = {
							id: element.val(),
							text: element.attr( 'data-selected' )
						};
						return callback( data );
					};
				}

				select2_args = $.extend( select2_args, getEnhancedSelectFormatString() );

				$( this ).select2( select2_args ).addClass( 'enhanced' );
			});
		})

		.trigger( 'wc-myparcel-enhanced-select-init' );
	
});