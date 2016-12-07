jQuery( function($) {

	function wc_cp_getEnhancedSelectFormatString() {
		var formatString = {
			formatMatches: function( matches ) {
				if ( 1 === matches ) {
					return wc_composite_admin_params.i18n_matches_1;
				}

				return wc_composite_admin_params.i18n_matches_n.replace( '%qty%', matches );
			},
			formatNoMatches: function() {
				return wc_composite_admin_params.i18n_no_matches;
			},
			formatAjaxError: function( jqXHR, textStatus, errorThrown ) {
				return wc_composite_admin_params.i18n_ajax_error;
			},
			formatInputTooShort: function( input, min ) {
				var number = min - input.length;

				if ( 1 === number ) {
					return wc_composite_admin_params.i18n_input_too_short_1;
				}

				return wc_composite_admin_params.i18n_input_too_short_n.replace( '%qty%', number );
			},
			formatInputTooLong: function( input, max ) {
				var number = input.length - max;

				if ( 1 === number ) {
					return wc_composite_admin_params.i18n_input_too_long_1;
				}

				return wc_composite_admin_params.i18n_input_too_long_n.replace( '%qty%', number );
			},
			formatSelectionTooBig: function( limit ) {
				if ( 1 === limit ) {
					return wc_composite_admin_params.i18n_selection_too_long_1;
				}

				return wc_composite_admin_params.i18n_selection_too_long_n.replace( '%qty%', number );
			},
			formatLoadMore: function( pageNumber ) {
				return wc_composite_admin_params.i18n_load_more;
			},
			formatSearching: function() {
				return wc_composite_admin_params.i18n_searching;
			}
		};

		return formatString;
	}

	$.fn.wc_cp_select2 = function() {

		$( this ).find( ':input.wc-enhanced-select' ).filter( ':not(.enhanced)' ).each( function() {
			var select2_args = $.extend({
				minimumResultsForSearch: 10,
				allowClear:  $( this ).data( 'allow_clear' ) ? true : false,
				placeholder: $( this ).data( 'placeholder' )
			}, wc_cp_getEnhancedSelectFormatString() );

			$( this ).select2( select2_args ).addClass( 'enhanced' );
		} );
	};

	$.fn.wc_cp_select2_products = function() {

		$( this ).find( ':input.wc-product-search' ).filter( ':not(.enhanced)' ).each( function() {
			var select2_args = {
				allowClear:  $( this ).data( 'allow_clear' ) ? true : false,
				placeholder: $( this ).data( 'placeholder' ),
				minimumInputLength: $( this ).data( 'minimum_input_length' ) ? $( this ).data( 'minimum_input_length' ) : '3',
				escapeMarkup: function( m ) {
					return m;
				},
				ajax: {
			        url:         wc_enhanced_select_params.ajax_url,
			        dataType:    'json',
			        quietMillis: 250,
			        data: function( term, page ) {
			            return {
							term:     term,
							action:   $( this ).data( 'action' ) || 'woocommerce_json_search_products',
							security: wc_enhanced_select_params.search_products_nonce
			            };
			        },
			        results: function( data, page ) {
			        	var terms = [];
				        if ( data ) {
							$.each( data, function( id, text ) {
								terms.push( { id: id, text: text } );
							});
						}
			            return { results: terms };
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
						selected.push( { id: val, text: data[ val ] } );
					});
					return callback( selected );
				};
				select2_args.formatSelection = function( data ) {
					return '<div class="selected-option" data-id="' + data.id + '">' + data.text + '</div>';
				};
			} else {
				select2_args.multiple = false;
				select2_args.initSelection = function( element, callback ) {
					var data = {id: element.val(), text: element.attr( 'data-selected' )};
					return callback( data );
				};
			}

			select2_args = $.extend( select2_args, wc_cp_getEnhancedSelectFormatString() );

			$( this ).select2( select2_args ).addClass( 'enhanced' );
		} );
	};

	$.fn.wc_cp_select2_component_options = function() {

		$( this ).find( ':input.wc-component-options-search' ).filter( ':not(.enhanced)' ).each( function() {

			var component_id = $( this ).data( 'component_id' );
			var action       = $( this ).data( 'action' );
			var select       = $( this );

			if ( ! component_id ) {
				component_id = $( this ).closest( '.bto_group' ).find( 'input.group_id' ).val();
			}

			var select2_args = {
				allowClear:  $( this ).data( 'allow_clear' ) ? true : false,
				placeholder: $( this ).data( 'placeholder' ),
				minimumInputLength: $( this ).data( 'minimum_input_length' ) ? $( this ).data( 'minimum_input_length' ) : '3',
				escapeMarkup: function( m ) {
					return m;
				},
				ajax: {
			        url:         wc_enhanced_select_params.ajax_url,
			        dataType:    'json',
			        quietMillis: 250,
			        data: function( term, page ) {
			            return {
							term:           term,
							action:         action,
							composite_id:   woocommerce_admin_meta_boxes.post_id,
							component_id:   component_id,
							security:       woocommerce_admin_meta_boxes.search_products_nonce
			            };
			        },
			        results: function( data, page ) {
			        	var terms = [];

			        	if ( action === 'woocommerce_json_search_component_options_in_scenario' ) {

							if ( select.data( 'component_optional' ) === 'yes' ) {
								if ( select.find( 'option[value="-1"]' ).length === 0 ) {
									terms.push( { id: '-1', text: wc_composite_admin_params.i18n_none } );
								}
							}

							if ( select.find( 'option[value="0"]' ).length === 0 ) {
								terms.push( { id: '0', text: wc_composite_admin_params.i18n_all } );
							}
						}

				        if ( data ) {
							$.each( data, function( id, text ) {
								terms.push( { id: id, text: text } );
							} );
						}
			            return { results: terms };
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
						selected.push( { id: val, text: data[ val ] } );
					});
					return callback( selected );
				};
				select2_args.formatSelection = function( data ) {
					return '<div class="selected-option" data-id="' + data.id + '">' + data.text + '</div>';
				};
			} else {
				select2_args.multiple = false;
				select2_args.initSelection = function( element, callback ) {
					var data = {id: element.val(), text: element.attr( 'data-selected' )};
					return callback( data );
				};
			}

			select2_args = $.extend( select2_args, wc_cp_getEnhancedSelectFormatString() );

			$( this ).select2( select2_args ).addClass( 'enhanced' );
		} );

	};

	var wc_cp_block_params = {};

	wc_cp_block_params = {
		message:    null,
		overlayCSS: {
			background: '#fff',
			opacity:    0.6
		}
	};

	// composite type move stock msg up
	$( '.composite_stock_msg' ).insertBefore( '._manage_stock_field' );

	if ( typeof woocommerce_admin_meta_boxes === 'undefined' ) {
		woocommerce_admin_meta_boxes = woocommerce_writepanel_params;
	}

	// Composite type specific options
	$( 'body' ).on( 'woocommerce-product-type-change', function( event, select_val, select ) {

		if ( select_val == 'composite' ) {

			$( 'input#_downloadable' ).prop( 'checked', false );
			$( 'input#_virtual' ).removeAttr( 'checked' );

			$( '.show_if_simple' ).show();
			$( '.show_if_external' ).hide();
			$( '.show_if_composite' ).show();

			$( 'input#_downloadable' ).closest( '.show_if_simple' ).hide();
			$( 'input#_virtual' ).closest( '.show_if_simple' ).hide();

			$( 'input#_manage_stock' ).change();
			$( 'input#_per_product_pricing_bto' ).change();
			$( 'input#_per_product_shipping_bto' ).change();

			$( '#_nyp' ).change();
		} else {

			$( '.show_if_composite' ).hide();
		}

	} );

	$( 'select#product-type' ).change();

	// Non-bundled shipping
	$( 'input#_per_product_shipping_bto' ).change( function() {

		if ( $( 'select#product-type' ).val() == 'composite' ) {

			if ( $( 'input#_per_product_shipping_bto' ).is( ':checked' ) ) {

				$( '.show_if_virtual' ).show();
				$( '.hide_if_virtual' ).hide();

				if ( $( '.shipping_tab' ).hasClass( 'active' ) )
					$( 'ul.product_data_tabs li:visible' ).eq(0).find('a').click();

			} else {

				$( '.show_if_virtual' ).hide();
				$( '.hide_if_virtual' ).show();
			}
		}
	} ).change();

	// Show options if pricing is static
	$( 'input#_per_product_pricing_bto' ).change( function() {

		if ( $( 'select#product-type' ).val() == 'composite' ) {

			if ( $( this ).is( ':checked' ) ) {

		        $( '#_regular_price' ).val('');
		        $( '#_sale_price' ).val('');

		        $( '._tax_class_field' ).closest( '.options_group' ).show();
				$( '.pricing' ).hide();
				$( '.bto_base_pricing' ).show();

				$( '#bto_product_data .config_group .bto_groups .bto_group' ).each( function() {
					$( this ).find( '.bto_group_data .group_discount input.group_discount' ).attr( 'disabled', false );
					$( this ).find( '.bto_group_data .group_discount' ).show();
				} );

			} else {

		        $( '._tax_class_field' ).closest( '.options_group' ).show();

		        if ( ! $( '#_nyp' ).is( ':checked' ) ) {
					$( '.pricing' ).show();
				}

				$( '.bto_base_pricing' ).hide();

				$( '#bto_product_data .config_group .bto_groups .bto_group' ).each( function() {
					$( this ).find( '.bto_group_data .group_discount input.group_discount' ).attr( 'disabled', 'disabled' );
					$( this ).find( '.bto_group_data .group_discount' ).hide();
				} );
			}
		}

	} ).change();
	
	
	/* ------------------------------------*/
	/* SKU
	/* ------------------------------------*/
	
	$('#bto_build_sku').on('click', function() {
		
		if( $(this).is(':checked') ) {
			
			jQuery('.group_affect_sku').slideDown(300);
			
            jQuery('.group_affect_sku').each(function() {
    			
    			if( $(this).find('input:checkbox').is(':checked') ) {
    			
        			$(this).siblings('.group_affected_by_sku').slideDown(300);
        			
    			} 
    			 
            });
			
		} else {
    		
    		jQuery(".group_affect_sku").slideUp(300).siblings('.group_affected_by_sku').slideUp(300);
    		
		}
		
	});
	
	$('.affect_sku').on('click', function() {
    	
    	if( $(this).is(':checked') ) {
		
		    jQuery(this).parents('.group_affect_sku').siblings('.group_affected_by_sku').slideDown(300);
		            
        } else {
            
            jQuery(this).parents('.group_affect_sku').siblings('.group_affected_by_sku').slideUp(300);
            
        }
		
	});
	
	/* ------------------------------------*/
	/* Option Change
	/* ------------------------------------*/
	
	function showAppropriateOptionsForComponent(e, option) {
		
		var thisOption = $(this).val(),
			group = $(this).closest('.bto_group');
			
		option = option ? option : thisOption;
			
		group.find('[class*="show-if-option-"]').hide();
		group.find('[class*="hide-if-option-"]').show();
		group.find('.show-if-option-' + option).show();
		group.find('.hide-if-option-' + option).hide();
		
	}
	
	function showAppropriateOptions(e) {
		
		var option = $(this).val();
		
		$('select.option_style_select[val="0"]').each(function() {
			
			showAppropriateOptionsForComponent(e, option);
			
		});
		
	}
	
	$(document).on('change', 'select[name="bto_selection_mode"]', showAppropriateOptions);
	
	$(document).on('change', 'select.option_style_select', showAppropriateOptionsForComponent);
	
	$('select[name="bto_selection_mode"]').change();
	
	$('select.option_style_select').change();


	/* ------------------------------------*/
	/* Components
	/* ------------------------------------*/

	// Subsubsub navigation

	$( '#bto_product_data .config_group' ).on( 'click', '.subsubsub a', function() {

		$( this ).closest( '.subsubsub' ).find( 'a' ).removeClass( 'current' );
		$( this ).addClass( 'current' );

		$( this ).closest( '.bto_group_data' ).find( '.options_group' ).addClass( 'options_group_hidden' );

		var tab = $( this ).data( 'tab' );

		$( this ).closest( '.bto_group_data' ).find( '.options_group_' + tab ).removeClass( 'options_group_hidden' );

		return false;

	} );

	// Component Remove
	$( '#bto_product_data .config_group' ).on( 'click', 'button.remove_row', function() {

		var $parent = $( this ).parent().parent();

		$parent.find('*').off();
		$parent.remove();
		group_row_indexes();

	} );

	// Component Keyup
	$( '#bto_product_data .config_group' ).on( 'keyup', 'input.group_title', function() {
		$( this ).closest( '.bto_group' ).find( 'h3 .group_name' ).html( $( this ).val() );
	} );

	// Component Expand
	$( '#bto_product_data .config_group' ).on( 'click', '.expand_all', function() {
		$( this ).closest( '.wc-metaboxes-wrapper' ).find( '.wc-metabox > .bto_group_data' ).show();
		return false;
	} );

	// Component Close
	$( '#bto_product_data .config_group' ).on( 'click', '.close_all', function() {
		$( this ).closest( '.wc-metaboxes-wrapper' ).find( '.wc-metabox > .bto_group_data' ).hide();
		return false;
	} );

	// Query type
	$( '#bto_product_data .config_group' ).on( 'change', 'select.bto_query_type', function() {

		var query_type = $( this ).val();

		$( this ).closest( '.bto_group' ).find( '.bto_query_type_selector' ).hide();
		$( this ).closest( '.bto_group' ).find( '.bto_query_type_' + query_type ).show();

	} );

	$( '#bto_product_data .config_group select.bto_query_type' ).change();

	// Filters
	$( '#bto_product_data .config_group' ).on( 'change', '.group_show_filters input', function() {

		if ( $( this ).is( ':checked' ) ) {
			$( this ).closest( '.bto_group' ).find( '.group_filters' ).show();
		} else {
			$( this ).closest( '.bto_group' ).find( '.group_filters' ).hide();
		}

	} );

	$( '#bto_product_data .config_group .group_show_filters input' ).change();

	// Ajax Chosen Single Product Selector
	
	$( '#bto_product_data .config_group, #bto_scenario_data .bto_scenarios' ).wc_cp_select2_component_options();

	// Component Add
	var bto_groups_metabox_count = $( '.bto_groups .bto_group' ).size();

	$( '#bto_product_data' ).on( 'click', 'button.add_bto_group', function() {

		$( '#bto_product_data' ).block( wc_cp_block_params );

		bto_groups_metabox_count++;

		var data = {
			action: 	'woocommerce_add_composite_component',
			post_id: 	woocommerce_admin_meta_boxes.post_id,
			id: 		bto_groups_metabox_count,
			security: 	wc_composite_admin_params.add_component_nonce
		};

		$.post( woocommerce_admin_meta_boxes_variations.ajax_url, data, function ( response ) {

			$( '#bto_config_group_inner .bto_groups' ).append( response );

			var added = $( '#bto_config_group_inner .bto_groups .bto_group' ).last();

			added.wc_cp_select2();
			added.wc_cp_select2_products();

			$( 'input#_per_product_pricing_bto' ).change();

			added.find( 'select.bto_query_type' ).change();

			added.find( '.group_show_filters input' ).change();

			added.find( '.help_tip' ).tipTip( {
				'attribute' : 'data-tip',
				'fadeIn' : 50,
				'fadeOut' : 50,
				'delay' : 200
			} );

			$( '#bto_product_data' ).unblock();
			added.trigger( 'woocommerce_bto_component_added' );

		} );

		return false;

	} );


	/* ------------------------------------*/
	/* Scenarios
	/* ------------------------------------*/

	// Scenario Remove
	$( '#bto_scenario_data #bto_scenarios_inner' ).on( 'click', 'button.remove_row', function() {

		var $parent = $( this ).parent().parent();

		$parent.find('*').off();
		$parent.remove();
		scenario_row_indexes();

	} );

	// Scenario Keyup
	$( '#bto_scenario_data #bto_scenarios_inner' ).on( 'keyup', 'input.scenario_title', function() {
		$( this ).closest( '.bto_scenario' ).find( 'h3 .scenario_name' ).html( $( this ).val() );
	} );

	// Scenario Expand
	$( '#bto_scenario_data #bto_scenarios_inner' ).on( 'click', '.expand_all', function() {
		$( this ).closest( '.wc-metaboxes-wrapper' ).find( '.wc-metabox > .bto_scenario_data' ).show();
		return false;
	} );

	// Scenario Close
	$( '#bto_scenario_data #bto_scenarios_inner' ).on( 'click', '.close_all', function() {
		$( this ).closest( '.wc-metaboxes-wrapper' ).find( '.wc-metabox > .bto_scenario_data' ).hide();
		return false;
	} );

	// exclude option modifier
	$( '#bto_scenario_data #bto_scenarios_inner' ).on( 'change', 'select.bto_scenario_exclude', function() {

		if ( $( this ).val() === 'masked' ) {
			$( this ).closest( '.bto_scenario_selector' ).find( '.bto_scenario_selector_inner' ).slideUp( 200 );
		} else {
			$( this ).closest( '.bto_scenario_selector' ).find( '.bto_scenario_selector_inner' ).slideDown( 200 );
		}

	} );

	// Scenario Add
	var bto_scenarios_metabox_count = $( '.bto_scenarios .bto_scenario' ).size();

	$( '#bto_scenario_data' ).on( 'click', 'button.add_bto_scenario', function () {

		$( '#bto_scenario_data' ).block( wc_cp_block_params );

		bto_scenarios_metabox_count++;

		var data = {
			action: 	'woocommerce_add_composite_scenario',
			post_id: 	woocommerce_admin_meta_boxes.post_id,
			id: 		bto_scenarios_metabox_count,
			security: 	wc_composite_admin_params.add_scenario_nonce
		};

		$.post( woocommerce_admin_meta_boxes.ajax_url, data, function ( response ) {

			$( '#bto_scenarios_inner .bto_scenarios' ).append( response );

			var added = $( '.bto_scenario_' + bto_scenarios_metabox_count );

			added.wc_cp_select2();
			added.wc_cp_select2_component_options();

			added.find( '.tips, .help_tip' ).tipTip( {
				'attribute' : 'data-tip',
				'fadeIn' : 50,
				'fadeOut' : 50,
				'delay' : 200
			} );

			$( '#bto_scenario_data' ).unblock();
			added.trigger( 'woocommerce_bto_scenario_added' );

		} );

		return false;
	} );

	// Init metaboxes
	init_bto_composition_metaboxes();
	init_bto_scenario_metaboxes();

	function group_row_indexes() {
		$( '.bto_groups .bto_group' ).each( function( index, el ){
			$( '.group_position', el ).val( parseInt( $(el).index( '.bto_groups .bto_group' ) ) );
		} );
	}

	function init_bto_composition_metaboxes() {

		// Initial order
		var bto_groups = $( '.bto_groups' ).find( '.bto_group' ).get();

		bto_groups.sort( function(a, b) {
		   var compA = parseInt($(a).attr('rel'));
		   var compB = parseInt($(b).attr('rel'));
		   return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;
		} );

		$(bto_groups).each( function( idx, itm ) {
			$( '.bto_groups' ).append(itm);
		} );

		// Component ordering
		$( '.bto_groups' ).sortable( {
			items:'.bto_group',
			cursor:'move',
			axis:'y',
			handle: 'h3',
			scrollSensitivity:40,
			forcePlaceholderSize: true,
			helper: 'clone',
			opacity: 0.65,
			placeholder: 'wc-metabox-sortable-placeholder',
			start:function(event,ui){
				ui.item.css( 'background-color','#f6f6f6' );
			},
			stop:function(event,ui){
				ui.item.removeAttr( 'style' );
				group_row_indexes();
			}
		} );
	}

	function scenario_row_indexes() {
		$( '.bto_scenarios .bto_scenario' ).each( function( index, el ) {
			$( '.scenario_position', el ).val( parseInt( $(el).index( '.bto_scenarios .bto_scenario' ) ) );
		} );
	}

	function init_bto_scenario_metaboxes() {

		// Initial order
		var bto_scenarios = $( '.bto_scenarios' ).find( '.bto_scenario' ).get();

		bto_scenarios.sort( function( a, b ) {
		   var compA = parseInt( $(a).attr('rel') );
		   var compB = parseInt( $(b).attr('rel') );
		   return ( compA < compB ) ? -1 : ( compA > compB ) ? 1 : 0;
		} );

		$(bto_scenarios).each( function( idx, itm ) {
			$( '.bto_scenarios' ).append( itm );
		} );

		// Scenario ordering
		$( '.bto_scenarios' ).sortable( {
			items:'.bto_scenario',
			cursor:'move',
			axis:'y',
			handle: 'h3',
			scrollSensitivity:40,
			forcePlaceholderSize: true,
			helper: 'clone',
			opacity: 0.65,
			placeholder: 'wc-metabox-sortable-placeholder',
			start:function(event,ui){
				ui.item.css( 'background-color','#f6f6f6' );
			},
			stop:function(event,ui){
				ui.item.removeAttr( 'style' );
				scenario_row_indexes();
			}
		} );

	}

	// Save bundle data and update configuration options via ajax
	$( '.save_composition' ).on( 'click', function() {

		$( '#bto_product_data, #bto_scenario_data' ).block( wc_cp_block_params );

		$( '.bto_groups .bto_group' ).find('*').off();

		var data = {
			post_id: 		woocommerce_admin_meta_boxes.post_id,
			data:			$( '#bto_product_data, #bto_scenario_data' ).find( 'input, select, textarea' ).serialize(),
			action: 		'woocommerce_bto_composite_save',
			security: 		wc_composite_admin_params.save_composite_nonce
		};

		$.post( woocommerce_admin_meta_boxes.ajax_url, data, function( post_response ) {

			var this_page = window.location.toString();

			this_page = this_page.replace( 'post-new.php?', 'post.php?post=' + woocommerce_admin_meta_boxes.post_id + '&action=edit&' );

			$.get( this_page, function( response ) {

				$( '#bto_product_data #bto_config_group_inner' ).html( $(response).find( '#bto_config_group_inner' ).html() );
				$( '#bto_scenario_data #bto_scenarios_inner' ).html( $(response).find( '#bto_scenarios_inner' ).html() );

				init_bto_composition_metaboxes();
				init_bto_scenario_metaboxes();

				$( '#bto_product_data .help_tip, #bto_scenario_data .help_tip, #bto_scenario_data .tips' ).tipTip( {
					'attribute' : 'data-tip',
					'fadeIn' : 50,
					'fadeOut' : 50,
					'delay' : 200
				} );

				$( '#bto_config_group_inner .bto_groups, #bto_scenarios_inner .bto_scenarios' ).find( '.wc-metabox-content' ).hide();

				$( 'input#_per_product_pricing_bto' ).change();

				$( '#bto_config_group_inner select.bto_query_type' ).change();

				$( '#bto_config_group_inner .group_show_filters input' ).change();

				$( '#bto_config_group_inner, #bto_scenarios_inner' ).wc_cp_select2();
				$( '#bto_config_group_inner' ).wc_cp_select2_products();
				$( '#bto_config_group_inner, #bto_scenarios_inner' ).wc_cp_select2_component_options();
				
				$('select[name="bto_selection_mode"]').change();
				$('select.option_style_select').change();

				if ( post_response.length > 0 ) {
					alert( post_response.join( '\n\n' ) );
				}

				$( '#bto_product_data, #bto_scenario_data' ).unblock();
			} );

		}, 'json' );

	} );

});
