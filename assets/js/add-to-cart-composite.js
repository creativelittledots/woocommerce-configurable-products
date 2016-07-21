/* jshint -W069 */
/* jshint -W041 */
/* jshint -W018 */

/**
 * Composite scripts, accessible to the outside world
 */
var wc_cp_composite_scripts = {};

jQuery( document ).ready( function($) {

	$( 'body' ).on( 'quick-view-displayed', function() {

		$( '.composite_form .composite_data' ).each( function() {
			$(this).wc_composite_form();
		} );
	} );


	/**
	 * Responsive form CSS (we can't rely on media queries since we must work with the .composite_form width, not screen width)
	 */
	$( window ).resize( function() {

		$( '.composite_form' ).each( function() {

			if ( $(this).width() <= wc_composite_params.small_width_threshold ) {
				$(this).addClass( 'small_width' );
			} else {
				$(this).removeClass( 'small_width' );
			}

			if ( $(this).width() > wc_composite_params.full_width_threshold ) {
				$(this).addClass( 'full_width' );
			} else {
				$(this).removeClass( 'full_width' );
			}

			if ( wc_composite_params.legacy_width_threshold ) {
				if ( $(this).width() <= wc_composite_params.legacy_width_threshold ) {
					$(this).addClass( 'legacy_width' );
				} else {
					$(this).removeClass( 'legacy_width' );
				}
			}
		} );

	} ).trigger( 'resize' );


	/**
	 * On clicking a composite summary link (widget)
	 */
	$( '.widget_composite_summary' )

		.on( 'click', '.summary_element_link', function( event ) {

			var composite_summary = $(this).closest( '.composite_summary' );
			var container_id      = composite_summary.find( '.widget_composite_summary_content' ).data( 'container_id' );
			var form              = $( '#composite_data_' + container_id ).closest( '.composite_form' );

			if ( $(this).hasClass( 'disabled' ) ) {
				return false;
			}

			var composite = wc_cp_composite_scripts[ container_id ];

			if ( composite.has_transition_lock ) {
				return false;
			}

			if ( typeof( composite ) === 'undefined' ) {
				return false;
			}

			var step_id = $(this).closest( '.summary_element' ).data( 'item_id' );

			if ( typeof( step_id ) === 'undefined' ) {
				var element_index     = composite_summary.find( '.summary_element' ).index( $(this).closest( '.summary_element' ) );
				step_id               = form.find( '.multistep.component:eq(' + element_index + ')' ).data( 'item_id' );
			}

			var step = composite.get_step( step_id );

			if ( step === false ) {
				return false;
			}

			if ( step.get_markup().hasClass( 'progressive' ) ) {
				step.block_next_steps();
			}

			if ( ! step.is_current() ) {
				step.show_step();
			}

			return false;
		} )

		.on( 'click', 'a.summary_element_tap', function( event ) {
			$(this).closest( '.summary_element_link' ).trigger( 'click' );
			return false;
		} );


	/**
	 * BlockUI background params
	 */
	var wc_cp_block_params = {};

	if ( wc_composite_params.is_wc_version_gte_2_3 === 'yes' ) {

		wc_cp_block_params = {
			message:    null,
			overlayCSS: {
				background: 'rgba( 255, 255, 255, 0 )',
				opacity:    0.6
			}
		};
	} else {

		wc_cp_block_params = {
			message:    null,
			overlayCSS: {
				background: 'rgba( 255, 255, 255, 0 ) url(' + woocommerce_params.ajax_loader_url + ') no-repeat center',
				backgroundSize: '20px 20px',
				opacity:    0.6
			}
		};
	}

	/**
 	* Populate composite scripts
 	*/
	$.fn.wc_composite_form = function() {

		if ( ! $(this).hasClass( 'composite_data' ) ) {
			return true;
		}

		var composite_data = $(this);
		var container_id   = $(this).data( 'container_id' );

		if ( typeof( wc_cp_composite_scripts[ container_id ] ) !== 'undefined' ) {
			return true;
		}

		var composite_form = composite_data.closest( '.composite_form' );

		wc_cp_composite_scripts[ container_id ] = {

			$composite_data:             composite_data,
			$composite_form:             composite_form,
			$add_to_cart_button:         composite_form.find( '.composite_add_to_cart_button' ),
			$composite_navigation:       composite_form.find( '.composite_navigation' ),
			$composite_pagination:       composite_form.find( '.composite_pagination' ),
			$composite_summary:          composite_form.find( '.composite_summary' ),
			$composite_summary_widget:   $( '.widget_composite_summary' ),
			$components:                 composite_form.find( '.component' ),

			composite_id:                container_id,
			composite_layout:            composite_data.data( 'bto_style' ),
			composite_layout_variation:  composite_data.data( 'bto_style_variation' ),
			composite_selection_mode:    composite_data.data( 'bto_selection_mode' ),
			composite_stock_status:      false,
			actions_nesting:             0,

			composite_components:        {},

			composite_steps:             {},

			composite_initialized:       false,
			has_update_lock:             false,
			has_ui_update_lock:          false,
			has_transition_lock:         false,
			has_update_nav_delay:        false,
			triggered_by_step:           false,
			active_scenarios:            {},

			/**
			 * Insertion point
			 */

			init: function() {

				/**
				 * Bind composite event handlers
				 */

				this.bind_event_handlers();

				/**
				 * Init components
				 */

				this.init_components();

				/**
				 * Init steps
				 */

				this.init_steps();

				/**
				 * Initial states and loading
				 */

				var composite      = this;
				var composite_data = composite.$composite_data;

				// Save composite stock status
				if ( composite_data.find( '.composite_wrap p.stock' ).length > 0 ) {
					composite.composite_stock_status = composite_data.find( '.composite_wrap p.stock' ).clone().wrap( '<div>' ).parent().html();
				}

				// Add-ons support - move totals container
				var addons_totals = composite_data.find( '#product-addons-total' );
				composite_data.find( '.composite_price' ).after( addons_totals );

				// NYP support
				composite_data.find( '.nyp' ).trigger( 'woocommerce-nyp-updated-item' );

				var style = composite.composite_layout;
				var form  = composite.$composite_form;

				// Init toggle boxes
				if ( composite.composite_layout === 'progressive' ) {
					composite.$composite_form.find( '.toggled:not(.active) .component_title' ).addClass( 'inactive' );
				}

				// Trigger pre-init event
				composite_data.trigger( 'wc-composite-initializing' );


				// Initialize component selection states and quantities for all modes
				$.each( composite.composite_components, function( index, component ) {

					// Load main component scripts
					component.init_scripts();

					// Load 3rd party scripts
					component.$self.trigger( 'wc-composite-component-loaded' );

				} );

				// Make the form visible
				form.css( 'visibility', 'visible' );

				// Activate initial step

				var current_step = composite.get_current_step();

				if ( style === 'paged' || style === 'progressive') {

					current_step.show_step();

				} else {

					current_step.set_active();

					current_step.fire_scenario_actions();
				}

				// Set the form as initialized and validate/update it
				composite.composite_initialized = true;

				composite.update_composite();

				// Let 3rd party scripts know that all component options are loaded
				this.$components.each( function() {
					$(this).trigger( 'wc-composite-component-options-loaded' );
					
				} );

				// Trigger post-init event
				composite_data.trigger( 'wc-composite-initialized' );

			},

			/**
			 * Attach composite-level event handlers
			 */

			bind_event_handlers: function() {

				var composite = this;

				/**
				 * Change the post names of variation/attribute fields to make them unique (for debugging purposes)
				 * Data from these fields is copied and posted in new unique vars - see below
				 * To maintain variations script compatibility with WC 2.2, we can't use our own unique field names in the variable-product.php template
				 */
				this.$add_to_cart_button

					.on( 'click', function() {

						$.each( composite.composite_components, function( index, component ) {

							var component_id = component.component_id;

							component.$self.find( '.variations_form .variations .attribute-options select, .variations_form .component_wrap input[name="variation_id"]' ).each( function() {

								$(this).attr( 'name', $(this).attr( 'name' ) + '_' + component_id );
							} );

							component.$self.find( 'select, input' ).each( function() {

								$(this).prop( 'disabled', false );
							} );
						} );
					} );


				/**
				 * Update composite totals when a new NYP price is entered at composite level
				 */
				this.$composite_data

					.on( 'woocommerce-nyp-updated-item', function() {

						var nyp = $(this).find( '.nyp' );

						if ( nyp.length > 0 ) {

							var price_data = $(this).data( 'price_data' );

							price_data[ 'base_price' ]      = nyp.data( 'price' );
							price_data[ 'price_undefined' ] = false;

							composite.update_composite( true );
						}
					} );


				/**
				 * On clicking the Next / Previous navigation buttons
				 */
				this.$composite_navigation

					.on( 'click', '.page_button', function() {

						if ( composite.has_transition_lock ) {
							return false;
						}

						if ( $(this).hasClass( 'next' ) ) {

							if ( composite.get_next_step() ) {

								composite.show_next_step();

							} else {

								var scroll_to = composite.$composite_form.find( '.scroll_final_step' );

								if ( scroll_to.length > 0 ) {

									var window_offset = scroll_to.hasClass( 'scroll_bottom' ) ? $(window).height() - 80 : 80;

									// avoid scrolling both html and body
									var pos            = $( 'html' ).scrollTop();
									var animate_target = 'body';

									$( 'html' ).scrollTop( $( 'html' ).scrollTop() - 1 );
									if ( pos != $( 'html' ).scrollTop() ) {
										animate_target = 'html';
									}

									$( animate_target ).animate( { scrollTop: scroll_to.offset().top - window_offset }, 200 );
								}
							}

						} else {

							composite.show_previous_step();
						}

						return false;

					} );


				/**
				 * On clicking a composite pagination link
				 */
				this.$composite_pagination

					.on( 'click', '.pagination_element a', function() {

						if ( composite.has_transition_lock ) {
							return false;
						}

						if ( $(this).hasClass( 'inactive' ) ) {
							return false;
						}

						var step_id = $(this).closest( '.pagination_element' ).data( 'item_id' );

						composite.show_step( step_id );

						return false;

					} );


				/**
				 * On clicking a composite summary link (review section)
				 */
				this.$composite_summary

					.on( 'click', '.summary_element_link', function() {

						if ( composite.has_transition_lock ) {
							return false;
						}

						var form = composite.$composite_form;

						if ( $(this).hasClass( 'disabled' ) ) {
							return false;
						}

						var step_id = $(this).closest( '.summary_element' ).data( 'item_id' );

						if ( typeof( step_id ) === 'undefined' ) {
							var composite_summary = composite.$composite_summary;
							var element_index     = composite_summary.find( '.summary_element' ).index( $(this).closest( '.summary_element' ) );
							step_id               = form.find( '.multistep.component:eq(' + element_index + ')' ).data( 'item_id' );
						}

						var step = composite.get_step( step_id );

						if ( step === false ) {
							return false;
						}

						if ( step.get_markup().hasClass( 'progressive' ) ) {
							step.block_next_steps();
						}

						if ( ! step.is_current() ) {
							step.show_step();
						}

						return false;

					} )

					.on( 'click', 'a.summary_element_tap', function() {
						$(this).closest( '.summary_element_link' ).trigger( 'click' );
						return false;
					} );

			},

			/**
			 * Initialize component objects
			 */

			init_components: function() {

				var composite = this;

				composite.$components.each( function( index ) {

					var self = $(this);
					var id   = self.attr( 'data-item_id' );

					composite.composite_components[ index ] = {

						component_id:             id,
						component_index:          index,
						component_title:          self.data( 'nav_title' ),
						_is_optional:             false,
						$self:                    self,
						$component_summary:       self.find( '.component_summary' ),
						$component_selections:    self.find( '.component_selections' ),
						$component_content:       self.find( '.component_content' ),
						$component_options:       self.find( '.component_options' ),
						$component_options_inner: self.find( '.component_options_inner' ),
						$component_inner:         self.find( '.component_inner' ),
						$component_pagination:    self.find( '.component_pagination' ),

						$component_data:          $(),

						/**
						 * Get the product type of the selected product
						 */

						get_selected_product_type: function(i) {
							
							i = typeof i === 'undefined' ? 0 : i;

							return this.$component_data.eq(i).data( 'product_type' );

						},

						/**
						 * Get the product id of the selected product (non casted)
						 */

						get_selected_product_ids: function() {
							
							var product_ids = this.$component_options.find( '#component_options_' + this.component_id ).val();

							return $.isArray(product_ids) ? product_ids : [product_ids];

						},

						/**
						 * True if the component has an out-of-stock availability class
						 */

						is_in_stock: function() {

							if ( this.$component_summary.find( '.component_wrap .out-of-stock' ).length > 0 ) {
								return false;
							}

							return true;

						},

						/**
						 * Initialize component scripts dependent on product type - called when selecting a new Component Option
						 * When called with init = false, no type-dependent scripts will be initialized
						 */

						init_scripts: function( init ) {

							if ( typeof( init ) === 'undefined' ) {
								init = true;
							}

							this.$component_data = this.$self.find( '.component_data' );
							
							var price = 0;

							if ( init ) {
								
								var products = this.get_selected_product_ids();
								var summary_content = this.$self.find( '.component_summary > .content' );
								
								for(var i in products) {
									
									var product = products[i];
									
									var product_type = this.get_selected_product_type(i);
	
									if ( product_type === 'variable' ) {
	
										if ( ! summary_content.hasClass( 'cart' ) ) {
											summary_content.addClass( 'cart' );
										}
	
										if ( ! summary_content.hasClass( 'variations_form' ) ) {
											summary_content.addClass( 'variations_form' );
										}
	
										// Selections must be updated before firing script in order to load variation_data
										this.get_step().fire_scenario_actions();
	
										// Initialize variations script
										summary_content.wc_variation_form();
	
										// Fire change in order to save 'variation_id' input
										summary_content.find( '.variations select' ).change();
	
									} else if ( product_type === 'bundle' ) {
	
										if ( ! summary_content.hasClass( 'bundle_form' ) ) {
											summary_content.addClass( 'bundle_form' );
										}
	
										// Initialize bundles script now
										summary_content.find( '.bundle_data' ).wc_pb_bundle_form();
	
									} else {
	
										if ( ! summary_content.hasClass( 'cart' ) ) {
											summary_content.addClass( 'cart' );
										}
									}
									
									if(products.length > 1) {
										
										this.$component_data.find('.price').remove();
										
										price += this.$component_data.eq(i).data( 'price' );
									
										summary_content.find('> .price .amount').replaceWith(wc_cp_woocommerce_number_format( wc_cp_number_format( price ) ));
										
									}
									
									
									
								}
								
							}

						},

						/**
						 * Get the step that corresponds to this component
						 */

						get_step: function() {

							return composite.get_step( this.component_id );

						},

						/**
						 * Get the title of this component
						 */

						get_title: function() {

							return this.component_title;

						},

						/**
						 * Add active/filtered classes to the component filters markup, can be used for styling purposes
						 */

						update_filters_ui: function() {

							var component_filters = this.$self.find( '.component_filters' );
							var filters           = component_filters.find( '.component_filter' );
							var all_empty         = true;

							if ( filters.length == 0 ) {
								return false;
							}

							filters.each( function() {

								if ( $(this).find( '.component_filter_option.selected' ).length == 0 ) {
									$(this).removeClass( 'active' );
								} else {
									$(this).addClass( 'active' );
									all_empty = false;
								}

							} );

							if ( all_empty ) {
								component_filters.removeClass( 'filtered' );
							} else {
								component_filters.addClass( 'filtered' );
							}
						},

						/**
						 * Collect active component filters and options and build an object for posting
						 */

						get_active_filters: function() {

							var component_filters = this.$self.find( '.component_filters' );
							var filters           = {};

							if ( component_filters.length == 0 ) {
								return filters;
							}

							component_filters.find( '.component_filter_option.selected' ).each( function() {

								var filter_type = $(this).closest( '.component_filter' ).data( 'filter_type' );
								var filter_id   = $(this).closest( '.component_filter' ).data( 'filter_id' );
								var option_id   = $(this).data( 'option_id' );

								if ( filter_type in filters ) {

									if ( filter_id in filters[ filter_type ] ) {

										filters[ filter_type ][ filter_id ].push( option_id );

									} else {

										filters[ filter_type ][ filter_id ] = [];
										filters[ filter_type ][ filter_id ].push( option_id );
									}

								} else {

									filters[ filter_type ]              = {};
									filters[ filter_type ][ filter_id ] = [];
									filters[ filter_type ][ filter_id ].push( option_id );
								}

							} );

							return filters;
						},

						/**
						 * Update the available component options via ajax - called upon sorting, updating filters, or viewing a new page
						 */

						reload_component_options: function( data ) {

							var component               = this;
							var item                    = this.$self;
							var component_selections    = this.$component_selections;
							var component_options       = this.$component_options;
							var component_options_inner = this.$component_options_inner;
							var component_pagination    = this.$component_pagination;
							var load_height             = component_options.outerHeight();
							var new_height              = 0;
							var animate_height          = false;

							// Do nothing if the component is disabled
							if ( item.hasClass( 'disabled' ) ) {
								return false;
							}

							var animate_component_options = function() {

								// animate component options container
								window.setTimeout( function() {

									if ( animate_height ) {

										component_options.animate( { 'height' : new_height }, { duration: 200, queue: false, always: function() {
											component_options.css( { 'height' : 'auto' } );
											component_selections.unblock().removeClass( 'blocked_content refresh_component_options' );
										} } );

									} else {
										component_selections.unblock().removeClass( 'blocked_content refresh_component_options' );
									}

								}, 250 );
							};

							// block container
							component_selections.addClass( 'blocked_content' ).block( wc_cp_block_params );

							window.setTimeout( function() {

								// get product info via ajax
								$.post( woocommerce_params.ajax_url, data, function( response ) {

									try {

										if ( response.result === 'success' ) {

											// fade thumbnails
											component_selections.addClass( 'refresh_component_options' );

											// lock height
											component_options.css( 'height', load_height );

											// store initial selection
											var initial_selection = component_options.find( '.component_options_select' ).val();

											// put content in place
											component_options_inner.html( $( response.options_markup ).find( '.component_options_inner' ).html() );

											// preload images before proceeding
											var thumbnails = component_options_inner.find( 'img' );

											var preload_images_then_show_component_options = function() {

												if ( thumbnails.length > 0 ) {

													var retry = false;

													thumbnails.each( function() {

														var thumbnail = $(this);

														if ( thumbnail.height() === 0 ) {
															retry = true;
															return false;
														}

													} );

													if ( retry ) {
														window.setTimeout( function() {
															preload_images_then_show_component_options();
														}, 100 );
													} else {
														show_component_options();
													}
												} else {
													show_component_options();
												}
											};

											var show_component_options = function() {

												// update pagination
												if ( response.pagination_markup ) {

													component_pagination.html( $( response.pagination_markup ).html() );
													component_pagination.slideDown( 200 );

												} else {
													component_pagination.slideUp( 200 );
												}

												// update component scenarios with new data
												var scenario_data = composite.$composite_data.data( 'scenario_data' );

												scenario_data.scenario_data[ data.component_id ] = response.component_scenario_data;

												// if the initial selection is not part of the result set, reset
												// note - in thumbnails mode, the initial selection is always appended to the (hidden) dropdown
												var current_selection = component_options.find( '.component_options_select' ).val();

												if ( initial_selection > 0 && ( current_selection === '' || typeof( current_selection ) === 'undefined' ) ) {

													component_options.find( '.component_options_select' ).change();

												} else {

													// disable newly loaded products and variations
													component.get_step().fire_scenario_actions();

													// update component state
													component.get_step().trigger_ui_update();
												}

												item.trigger( 'wc-composite-component-options-loaded' );

												// measure height
												component_options.css( 'height', 'auto' );

												new_height = component_options_inner.outerHeight();

												if ( Math.abs( new_height - load_height ) > 1 ) {

													animate_height = true;

													// lock height
													component_options.css( 'height', load_height );
												}

												animate_component_options();
											};

											preload_images_then_show_component_options();

										} else {

											// lock height
											component_options.css( 'height', load_height );

											// show failure message
											component_options_inner.html( response.options_markup );
										}

									} catch ( err ) {

										// show failure message
										console.log( err );
										animate_component_options();
									}

								}, 'json' );

							}, 100 );

						},

						/**
						 * True if a Component is set as optional
						 */

						is_optional: function() {

							return this._is_optional;

						},

						/**
						 * Set Component as optional
						 */

						set_optional: function( optional ) {

							if ( optional && ! this._is_optional ) {
								this.$component_selections.find( 'option.none' ).html( wc_composite_params.i18n_none );
							} else if ( ! optional && this._is_optional ) {
								this.$component_selections.find( 'option.none' ).html( wc_composite_params.i18n_select_an_option );
							}

							this._is_optional = optional;

						},

						/**
						 * Initialize quantity input
						 */

						init_qty_input: function() {

							// Quantity buttons
							if ( wc_composite_params.is_wc_version_gte_2_3 === 'no' || wc_composite_params.show_quantity_buttons === 'yes' ) {
								this.$self.find( 'div.quantity:not(.buttons_added), td.quantity:not(.buttons_added)' ).addClass( 'buttons_added' ).append( '<input type="button" value="+" class="plus" />' ).prepend( '<input type="button" value="-" class="minus" />' );
							}

							// Target quantity inputs on product pages
							this.$self.find( '.component_wrap input.qty' ).each( function() {

								var min = parseFloat( $(this).attr( 'min' ) );

								if ( min >= 0 && parseFloat( $(this).val() ) < min ) {
									$(this).val( min );
								}

							} );

						}

					};

					composite.bind_component_event_handlers( composite.composite_components[ index ] );

				} );

			},

			/**
			 * Attach component-levle event handlers
			 */

			bind_component_event_handlers: function( component ) {

				var composite = this;

				component.$self

					.on( 'wc-composite-component-loaded', function() {

						if ( $.isFunction( $.fn.prettyPhoto ) ) {

							$(this).find( 'a[data-rel^="prettyPhoto"]' ).prettyPhoto( {
								hook: 'data-rel',
								social_tools: false,
								theme: 'pp_woocommerce',
								horizontal_padding: 20,
								opacity: 0.8,
								deeplinking: false
							} );
						}
					} )

					/**
					 * Update composite totals when a new Add-on is selected
					 */
					.on( 'woocommerce-product-addons-update', function() {

						var addons = $(this).find( '.addon' );

						if ( addons.length == 0 ) {
							return false;
						}

						composite.update_composite( true );
					} )

					/**
					 * Update composite totals when a new NYP price is entered
					 */
					.on( 'woocommerce-nyp-updated-item', function() {

						var nyp = $(this).find( '.cart .nyp' );

						if ( nyp.length > 0 && component.get_selected_product_type() !== 'variable' ) {

							component.$component_data.data( 'price', nyp.data( 'price' ) );
							component.$component_data.data( 'regular_price', nyp.data( 'price' ) );

							composite.update_composite( true );
						}
					} )

					/**
					 * Reset composite totals and form inputs when a new variation selection is initiated
					 */
					.on( 'woocommerce_variation_select_change', function( event ) {

						var summary = component.$component_summary;

						// Reset submit form data - TODO: get rid of this by making the variations script usable regardless of the variations form input names (https://github.com/woothemes/woocommerce/pull/6531)
						composite.$composite_data.find( '.composite_wrap .composite_button .form_data_' + component.component_id + ' .variation_input' ).remove();
						composite.$composite_data.find( '.composite_wrap .composite_button .form_data_' + component.component_id + ' .attribute_input' ).remove();

						// Mark component as not set
						component.$component_data.data( 'component_set', false );

						// Add images class to composited_product_images div ( required by the variations script to flip images )
						summary.find( '.composited_product_images' ).addClass( 'images' );

						$(this).find( '.variations .attribute-options select' ).each( function() {

							if ( $(this).val() === '' ) {
								summary.find( '.component_wrap .single_variation input[name="variation_id"]' ).val( '' );

								var step = component.get_step();

								if ( composite.triggered_by_step ) {
									step = composite.get_step( composite.triggered_by_step );
									composite.triggered_by_step = false;
								} else {
									step.fire_scenario_actions();
								}

								step.trigger_ui_update();
								composite.update_composite();
								return false;
							}
						} );
					} )

					.on( 'woocommerce_variation_select_focusin', function( event ) {

						component.get_step().fire_scenario_actions( true );
					} )

					.on( 'reset_image', function( event ) {

						var summary = component.$component_summary;

						// Remove images class from composited_product_images div in order to avoid styling issues
						summary.find( '.composited_product_images' ).removeClass( 'images' );
					} )

					/**
					 * Update composite totals and form inputs when a new variation is selected
					 */
					.on( 'found_variation', function( event, variation ) {

						var summary   = component.$component_summary;
						var form_data = composite.$composite_data.find( '.composite_wrap .composite_button .form_data_' + component.component_id );

						// Start copying submit form data - TODO: get rid of this by making the variations script usable regardless of the variations form input names (https://github.com/woothemes/woocommerce/pull/6531)
						var variation_data 	= '<input type="hidden" name="wccp_variation_id[' + component.component_id + '][' + variation.product_id + ']" class="variation_input" value="' + variation.variation_id + '"/>';
						form_data.append( variation_data );

						for ( var attribute in variation.attributes ) {
							var attribute_data 	= '<input type="hidden" name="wccp_' + attribute + '[' + component.component_id + '][' + variation.product_id + ']" class="attribute_input" value="' + $(this).find( '.variations .attribute-options select[name="' + attribute + '"]' ).val() + '"/>';
							form_data.append( attribute_data );
						}
						// End copying form data
						
						// Copy variation price data
						var price_data = composite.$composite_data.data( 'price_data' );
						
						var products = component.get_selected_product_ids();
						
						
						for(var i in products) {
							
							if(variation.product_id == products[i]) {
								
								break;
								
							}
							
						}

						if ( price_data[ 'per_product_pricing' ] == true ) {
							component.$component_data.eq(i).data( 'price', variation.price );
							component.$component_data.eq(i).data( 'regular_price', variation.regular_price );
							
						}
						
						var custom_data = component.$component_data.eq(i).data( 'custom' );
						
						custom_data['all_prices'] = variation.all_prices;
						
						component.$component_data.eq(i).data( 'custom', custom_data );

						// Mark component as set
						component.$component_data.eq(i).data( 'component_set', true );

						// Remove images class from composited_product_images div in order to avoid styling issues
						summary.find( '.composited_product_images' ).removeClass( 'images' );

						// Handle sold_individually variations qty
						if ( variation.is_sold_individually === 'yes' ) {
							$(this).find( '.component_wrap input.qty' ).val( '1' ).change();
						}

						component.get_step().fire_scenario_actions();
						component.get_step().trigger_ui_update();
						composite.update_composite();
					} )

					/**
					 * Event triggered by custom product types to indicate that the state of the component selection has changed
					 */
					.on ( 'woocommerce-composited-product-update', function( event ) {

						var price_data = composite.$composite_data.data( 'price_data' );

						component.get_step().trigger_ui_update();

						if ( price_data[ 'per_product_pricing' ] == true ) {

							var bundle_price         = component.$component_data.data( 'price' );
							var bundle_regular_price = component.$component_data.data( 'regular_price' );

							component.$component_data.data( 'price', bundle_price );
							component.$component_data.data( 'regular_price', bundle_regular_price );
						}

						composite.update_composite();
					} )

					/**
					 * On clicking the clear options button
					 */
					.on( 'click', '.clear_component_options', function( event ) {

						if ( $(this).hasClass( 'reset_component_options' ) ) {
							return false;
						}

						var selection = component.$component_options.find( 'select.component_options_select' );

						component.get_step().unblock_step_inputs();

						component.$self.find( '.component_option_thumbnails .selected' ).removeClass( 'selected' );

						selection.val( '' ).change();

						return false;
					} )

					/**
					 * On clicking the reset options button
					 */
					.on( 'click', '.reset_component_options', function( event ) {

						var step      = component.get_step();
						var selection = component.$component_options.find( 'select.component_options_select' );

						step.unblock_step_inputs();

						component.$self.find( '.component_option_thumbnails .selected' ).removeClass( 'selected' );

						step.set_active();

						selection.val( '' ).change();

						step.block_next_steps();

						return false;
					} )

					/**
					 * On clicking the blocked area in progressive mode
					 */
					.on( 'click', '.block_component_selections_inner', function( event ) {

						var step = component.get_step();

						step.block_next_steps();
						step.show_step();

						return false;
					} )

					/**
					 * On clicking a thumbnail
					 */
					.on( 'click', '.component_option_thumbnail', function( event ) {

						var item = component.$self;

						if ( item.hasClass( 'disabled' ) || $(this).hasClass( 'disabled' ) ) {
							return true;
						}

						$(this).blur();

						if ( ! $(this).hasClass( 'selected' ) ) {
							var value = $(this).data( 'val' );
							component.$component_options.find( 'select.component_options_select' ).val( value ).change();
						}

					} )

					.on( 'click', 'a.component_option_thumbnail_tap', function( event ) {
						$(this).closest( '.component_option_thumbnail' ).trigger( 'click' );
						return false;
					} )

					.on( 'focusin touchstart', '.component_options select.component_options_select', function( event ) {

						component.get_step().fire_scenario_actions( true );

					} )

					/**
					 * On changing a component option
					 */
					.on( 'change', '.component_options select.component_options_select', function( event ) {

						var item_id              = component.component_id;
						var item                 = component.$self;
						var step                 = component.get_step();
						var component_selections = component.$component_selections;
						var component_content    = component.$component_content;
						var component_summary    = component.$component_summary;
						var summary_content      = item.find( '.component_summary > .content' );
						var form                 = composite.$composite_form;
						var container_id         = composite.composite_id;
						var form_data            = composite.$composite_data.find( '.composite_wrap .composite_button .form_data_' + item_id );
						var style                = composite.composite_layout;
						var scroll_to            = '';

						if ( style === 'paged' ) {
							scroll_to = form.find( '.scroll_select_component_option' );
						} else {
							scroll_to = item.next( '.component' );
						}

						var load_height    = component_summary.outerHeight();
						var new_height     = 0;
						var animate_height = false;

						$(this).blur();

						// Reset submit data
						form_data.find( '.variation_input' ).remove();
						form_data.find( '.attribute_input' ).remove();

						// Select thumbnail
						item.find( '.component_option_thumbnails .selected' ).removeClass( 'selected disabled' );
						item.find( '#component_option_thumbnail_' + $(this).val() ).addClass( 'selected' );
						
						var product_ids = component.get_selected_product_ids();

						var data = {
							action: 		'woocommerce_show_composited_product',
							product_id: 	product_ids,
							component_id: 	item_id,
							composite_id: 	container_id,
							security: 		wc_composite_params.show_product_nonce
						};

						// Remove all event listeners
						summary_content.removeClass( 'variations_form bundle_form cart' );
						component_content.off().find( '*' ).off();

						if ( data.product_id !== '' ) {

							// block component selections
							component_selections.addClass( 'blocked_content' ).block( wc_cp_block_params );

							// block composite transitions
							composite.has_transition_lock = true;

							// get product info via ajax
							$.post( woocommerce_params.ajax_url, data, function( response ) {

								try {

									if ( response.product_data.purchasable === 'yes' ) {

										// lock height
										component_content.css( 'height', load_height );

										// put content in place
										summary_content.html( response.markup );

										component.init_qty_input();

										component.init_scripts();

										step.fire_scenario_actions();

										step.trigger_ui_update();

										composite.update_composite();

										item.trigger( 'wc-composite-component-loaded' );

										// measure height
										component_content.css( 'height', 'auto' );

										new_height = component_summary.outerHeight();

										if ( Math.abs( new_height - load_height ) > 1 ) {

											animate_height = true;

											// lock height
											component_content.css( 'height', load_height );
										}

									} else {

										// lock height
										component_content.css( 'height', load_height );

										summary_content.html( response.markup );

										component.init_scripts( false );

										step.fire_scenario_actions();

										step.trigger_ui_update();

										composite.update_summary();

										composite.disable_add_to_cart();
									}

								} catch ( err ) {

									// show failure message
									console.log( err );

									// lock height
									component_content.css( 'height', load_height );

									// reset content
									summary_content.html( '<div class="component_data" data-component_set="true" data-price="0" data-regular_price="0" data-product_type="none" style="display:none;"></div>' );

									component.init_scripts( false );

									step.fire_scenario_actions();

									step.trigger_ui_update();

									composite.update_summary();

									composite.disable_add_to_cart();
								}

								// animate component content height and scroll to selected product details
								window.setTimeout( function() {

									if ( animate_height ) {

										// re-measure height to account for animations in loaded markup
										component_content.css( 'height', 'auto' );

										new_height = component_summary.outerHeight();

										if ( Math.abs( new_height - load_height ) > 1 ) {

											animate_height = true;

											// lock height
											component_content.css( 'height', load_height );
										}

										// animate component content height
										component_content.animate( { 'height' : new_height }, { duration: 200, queue: false, always: function() {

											// scroll
											if ( scroll_to.length > 0 && ! scroll_to.is_in_viewport( true ) ) {

												var window_offset = 0;

												if ( style === 'paged' ) {
													window_offset = scroll_to.hasClass( 'scroll_bottom' ) ? $(window).height() - 80 : 50;
												} else {
													window_offset = $(window).height() - 70;
												}

												// avoid scrolling both html and body
												var pos            = $( 'html' ).scrollTop();
												var animate_target = 'body';

												$( 'html' ).scrollTop( $( 'html' ).scrollTop() - 1 );
												if ( pos != $( 'html' ).scrollTop() ) {
													animate_target = 'html';
												}

												$( animate_target ).animate( { scrollTop: scroll_to.offset().top - window_offset }, { duration: 200, queue: false, always: function() {

													// reset height
													component_content.css( { 'height' : 'auto' } );

													// unblock component
													component_selections.unblock().removeClass( 'blocked_content' );
													composite.has_transition_lock = false;

												} } );

											} else {

												// reset height
												component_content.css( { 'height' : 'auto' } );

												// unblock component
												component_selections.unblock().removeClass( 'blocked_content' );
												composite.has_transition_lock = false;
											}

										} } );

									} else {

										// scroll
										if ( scroll_to.length > 0 && ! scroll_to.is_in_viewport( true ) ) {

											var window_offset = 0;

											if ( style === 'paged' ) {
												window_offset = scroll_to.hasClass( 'scroll_bottom' ) ? $(window).height() - 80 : 50;
											} else {
												window_offset = $(window).height() - 70;
											}

											// avoid scrolling both html and body
											var pos            = $( 'html' ).scrollTop();
											var animate_target = 'body';

											$( 'html' ).scrollTop( $( 'html' ).scrollTop() - 1 );
											if ( pos != $( 'html' ).scrollTop() ) {
												animate_target = 'html';
											}

											$( animate_target ).animate( { scrollTop: scroll_to.offset().top - window_offset }, { duration: 200, queue: false, always: function() {

												// unblock component
												component_selections.unblock().removeClass( 'blocked_content' );
												composite.has_transition_lock = false;

											} } );

										} else {

											// unblock component
											component_selections.unblock().removeClass( 'blocked_content' );
											composite.has_transition_lock = false;
										}
									}

								}, 400 );

							}, 'json' );

						} else {

							// lock height
							component_content.css( 'height', load_height );
							
							// reset content
							summary_content.html( '<div class="component_data" data-component_set="true" data-price="0" data-regular_price="0" data-product_type="none" style="display:none;"></div>' );

							component.init_scripts( false );

							step.fire_scenario_actions();

							step.trigger_ui_update();

							composite.update_composite();

							// animate component content height
							component_content.animate( { 'height': component_summary.outerHeight() }, { duration: 200, queue: false, always: function() {
								component_content.css( { 'height': 'auto' } );
							} } );
						}

					} )

					/**
					 * Refresh component options upon clicking on a component options page
					 */
					.on( 'click', '.component_pagination a.component_pagination_element', function( event ) {

						var item                    = component.$self;
						var item_id                 = component.component_id;
						var component_ordering      = item.find( '.component_ordering select' );

						// Variables to post
						var page                    = parseInt( $(this).data( 'page_num' ) );
						var selected_option         = component.get_selected_product_ids();
						var container_id            = composite.composite_id;
						var filters                 = component.get_active_filters();

						var data = {
							action: 			'woocommerce_show_component_options',
							load_page: 			page,
							component_id: 		item_id,
							composite_id: 		container_id,
							selected_option: 	selected_option,
							filters:            filters,
							security: 			wc_composite_params.show_product_nonce
						};

						// Current 'orderby' setting
						if ( component_ordering.length > 0 ) {
							data.orderby = component_ordering.val();
						}

						// Update component options
						if ( data.load_page > 0 ) {
							$(this).blur();
							component.reload_component_options( data );
						}

						// Finito
						return false;

					} )

					/**
					 * Refresh component options upon reordering
					 */
					.on( 'change', '.component_ordering select', function( event ) {

						var item_id                 = component.component_id;

						// Variables to post
						var selected_option         = component.get_selected_product_ids();
						var container_id            = composite.composite_id;
						var orderby                 = $(this).val();
						var filters                 = component.get_active_filters();

						var data = {
							action: 			'woocommerce_show_component_options',
							load_page: 			1,
							component_id: 		item_id,
							composite_id: 		container_id,
							selected_option: 	selected_option,
							orderby: 			orderby,
							filters:            filters,
							security: 			wc_composite_params.show_product_nonce
						};

						$(this).blur();

						// Update component options
						component.reload_component_options( data );

						// Finito
						return false;

					} )

					/**
					 * Refresh component options upon activating a filter
					 */
					.on( 'click', '.component_filter_option a', function( event ) {

						var item                    = component.$self;
						var item_id                 = component.component_id;
						var component_ordering      = item.find( '.component_ordering select' );

						var component_filter_option = $(this).closest( '.component_filter_option' );

						// Variables to post
						var selected_option         = component.get_selected_product_ids();
						var container_id            = composite.composite_id;
						var filters                 = {};

						if ( ! component_filter_option.hasClass( 'selected' ) ) {
							component_filter_option.addClass( 'selected' );
						} else {
							component_filter_option.removeClass( 'selected' );
						}

						// add / remove 'active' classes
						component.update_filters_ui();

						// get active filters
						filters = component.get_active_filters();

						var data = {
							action: 			'woocommerce_show_component_options',
							load_page: 			1,
							component_id: 		item_id,
							composite_id: 		container_id,
							selected_option: 	selected_option,
							filters: 			filters,
							security: 			wc_composite_params.show_product_nonce
						};

						// Current 'orderby' setting
						if ( component_ordering.length > 0 ) {
							data.orderby = component_ordering.val();
						}

						$(this).blur();

						// Update component options
						component.reload_component_options( data );

						// Finito
						return false;

					} )

					/**
					 * Refresh component options upon resetting all filters
					 */
					.on( 'click', '.component_filters a.reset_component_filters', function( event ) {

						var item                    = component.$self;
						var item_id                 = component.component_id;
						var component_ordering      = item.find( '.component_ordering select' );

						// Get active filters
						var component_filter_options = item.find( '.component_filters .component_filter_option.selected' );

						if ( component_filter_options.length == 0 ) {
							return false;
						}

						// Variables to post
						var selected_option         = component.get_selected_product_ids();
						var container_id            = composite.composite_id;
						var filters                 = {};

						component_filter_options.removeClass( 'selected' );

						// add / remove 'active' classes
						component.update_filters_ui();

						var data = {
							action: 			'woocommerce_show_component_options',
							load_page: 			1,
							component_id: 		item_id,
							composite_id: 		container_id,
							selected_option: 	selected_option,
							filters: 			filters,
							security: 			wc_composite_params.show_product_nonce
						};

						// Current 'orderby' setting
						if ( component_ordering.length > 0 ) {
							data.orderby = component_ordering.val();
						}

						$(this).blur();

						// Update component options
						component.reload_component_options( data );

						// Finito
						return false;

					} )

					/**
					 * Refresh component options upon resetting a filter
					 */
					.on( 'click', '.component_filters a.reset_component_filter', function( event ) {

						var item                     = component.$self;
						var item_id                  = component.component_id;
						var component_ordering       = item.find( '.component_ordering select' );

						// Get active filters
						var component_filter_options = $(this).closest( '.component_filter' ).find( '.component_filter_option.selected' );

						if ( component_filter_options.length == 0 ) {
							return false;
						}

						// Variables to post
						var selected_option         = component.get_selected_product_ids();
						var container_id            = composite.composite_id;
						var filters                 = {};

						component_filter_options.removeClass( 'selected' );

						// add / remove 'active' classes
						component.update_filters_ui();

						// get active filters
						filters = component.get_active_filters();

						var data = {
							action: 			'woocommerce_show_component_options',
							load_page: 			1,
							component_id: 		item_id,
							composite_id: 		container_id,
							selected_option: 	selected_option,
							filters: 			filters,
							security: 			wc_composite_params.show_product_nonce
						};

						// Current 'orderby' setting
						if ( component_ordering.length > 0 ) {
							data.orderby = component_ordering.val();
						}

						$(this).blur();

						// Update component options
						component.reload_component_options( data );

						// Finito
						return false;

					} )

					/**
					 * Expand / Collapse filters
					 */
					.on( 'click', '.component_filter_title label', function( event ) {

						var component_filter         = $(this).closest( '.component_filter' );
						var component_filter_content = component_filter.find( '.component_filter_content' );
						wc_cp_toggle_element( component_filter, component_filter_content );

						$(this).blur();

						// Finito
						return false;

					} )

					/**
					 * Expand / Collapse components
					 */
					.on( 'click', '.component_title', function( event ) {

						var item = component.$self;
						var form = composite.$composite_form;

						if ( ! item.hasClass( 'toggled' ) || $(this).hasClass( 'inactive' ) ) {
							return false;
						}

						if ( item.hasClass( 'progressive' ) && item.hasClass( 'active' ) ) {
							return false;
						}

						var component_inner = component.$component_inner;
						if ( wc_cp_toggle_element( item, component_inner ) ) {
							if ( item.hasClass( 'progressive' ) && item.hasClass( 'blocked' ) ) {
								window.setTimeout( function() {
									form.find( '.page_button.next' ).click();
								}, 200 );
							}
						}

						$(this).blur();

						// Finito
						return false;

					} )

					/**
					 * Update composite totals upon changing quantities
					 */
					.on( 'change', '.component_wrap input.qty', function( event ) {

						var min = parseFloat( $(this).attr( 'min' ) );
						var max = parseFloat( $(this).attr( 'max' ) );

						if ( min >= 0 && parseFloat( $(this).val() ) < min ) {
							$(this).val( min );
						}

						if ( max > 0 && parseFloat( $(this).val() ) > max ) {
							$(this).val( max );
						}

						composite.update_composite();
					} );

			},

			/**
			 * Initialize composite step objects
			 */

			init_steps: function() {

				var composite = this;

				/*
				 * Prepare markup for "Review" step, if needed
				 */

				if ( composite.composite_layout === 'paged' ) {

					// Componentized layout: replace the step-based process with a summary-based process
					if ( composite.composite_layout_variation === 'componentized' ) {

						composite.$composite_form.find( '.multistep.active' ).removeClass( 'active' );
						composite.$composite_data.addClass( 'multistep active' );

						// No review step in the pagination template
						composite.$composite_pagination.find( '.pagination_element_review' ).remove();

						// No summary widget
						composite.$composite_summary_widget.hide();

					// If the composite-add-to-cart.php template is added right after the component divs, it will be used as the final step of the step-based configuration process
					} else if ( composite.$composite_data.prev().hasClass( 'multistep' ) ) {

						composite.$composite_data.addClass( 'multistep' );
						composite.$composite_data.hide();

					} else {

						composite.$composite_pagination.find( '.pagination_element_review' ).remove();
					}
				}

				/*
				 * Initialize steps
				 */

				composite.$composite_form.children( '.component, .multistep' ).each( function( index ) {

					var step              = $(this);
					var step_id           = $(this).data( 'item_id' );
					var step_is_component = $(this).hasClass( 'component' );
					var step_is_review    = $(this).hasClass( 'cart' );

					composite.composite_steps[ index ] = {

						step_id:        step_id,
						step_index:     index,
						step_title:     step.data( 'nav_title' ),
						_is_component:  step_is_component,
						_is_review:     step_is_review,

						$markup:        step,

						get_title: function() {

							return this.step_title;

						},

						get_markup: function() {

							return this.$markup;

						},

						is_review: function() {

							return this._is_review;

						},

						is_component: function() {

							return this._is_component;

						},

						get_component: function() {

							if ( this._is_component ) {
								return composite.composite_components[ this.step_index ];
							} else {
								return false;
							}
						},

						is_current: function() {

							return this.$markup.hasClass( 'active' );

						},

						is_next: function() {

							return this.$markup.hasClass( 'next' );

						},

						is_previous: function() {

							return this.$markup.hasClass( 'prev' );

						},

						/**
	 					 * Brings a new step into view - called when clicking on a navigation element
						 */

						show_step: function() {

							var step            = this;
							var form            = composite.$composite_form;
							var style           = composite.composite_layout;
							var style_variation = composite.composite_layout_variation;
							var item            = this.$markup;
							var do_scroll       = composite.composite_initialized === false ? false : true;

							// scroll to the desired section
							if ( style === 'paged' && do_scroll ) {

								var scroll_to = form.find( '.scroll_show_component' );

								if ( scroll_to.length > 0 && ! scroll_to.is_in_viewport( true ) ) {

									var window_offset = scroll_to.hasClass( 'scroll_bottom' ) ? $(window).height() - 80 : 50;

									// avoid scrolling both html and body
									var pos            = $( 'html' ).scrollTop();
									var animate_target = 'body';

									$( 'html' ).scrollTop( $( 'html' ).scrollTop() - 1 );
									if ( pos != $( 'html' ).scrollTop() ) {
										animate_target = 'html';
									}

									$( animate_target ).delay(20).animate( { scrollTop: scroll_to.offset().top - window_offset }, { duration: 250, queue: false } );
								}

								setTimeout( function() {

									// fade out or show summary widget
									if ( composite.$composite_summary_widget.length > 0 ) {
										if ( step.is_review() ) {
											composite.$composite_summary_widget.animate( { opacity: 0 }, { duration: 250, queue: false } );
										} else {
											composite.$composite_summary_widget.slideDown( 250 );
											composite.$composite_summary_widget.animate( { opacity: 1 }, { duration: 250, queue: false } );
										}
									}

									// move summary widget out of the way if needed
									if ( step.is_review() ) {
										composite.$composite_summary_widget.slideUp( 250 );
									}

								}, 20 );

								if ( style_variation === 'componentized' ) {
									if ( step.is_review() ) {
										composite.$composite_navigation.css( { visibility: 'hidden' } );
									} else {
										composite.$composite_navigation.css( { visibility: 'visible' } );
									}
								}

							}

							// move active component
							step.set_active();

							// update blocks
							step.update_block_state();

							// update selections
							step.fire_scenario_actions();

							// update ui
							step.trigger_ui_update( true );

							// scroll to the desired section (progressive)
							if ( style === 'progressive' && do_scroll && item.hasClass( 'autoscrolled' ) ) {
								setTimeout( function() {

									var scroll_to = item;

									if ( scroll_to.length > 0 && ! item.is_in_viewport( false ) ) {

										// avoid scrolling both html and body
										var pos            = $( 'html' ).scrollTop();
										var animate_target = 'body';

										$( 'html' ).scrollTop( $( 'html' ).scrollTop() - 1 );
										if ( pos != $( 'html' ).scrollTop() ) {
											animate_target = 'html';
										}

										$( animate_target ).animate( { scrollTop: scroll_to.offset().top }, { duration: 250, queue: false } );
									}

								}, 300 );
							}

							item.trigger( 'wc-composite-show-component' );

						},

						/**
						 * Sets a step as active by hiding the previous one and updating the steps' markup
						 */

						set_active: function() {

							var step            = this;
							var form            = composite.$composite_form;
							var style           = composite.composite_layout;
							var style_variation = composite.composite_layout_variation;
							var active_step     = composite.get_current_step();
							var is_active       = false;

							if ( active_step.step_id == step.step_id ) {
								is_active = true;
							}

							form.children( '.multistep.active, .multistep.next, .multistep.prev' ).removeClass( 'active next prev' );

							this.get_markup().addClass( 'active' );

							var next_item = this.get_markup().next();
							var prev_item = this.get_markup().prev();

							if ( style === 'paged' && style_variation === 'componentized' ) {
								next_item = form.find( '.multistep.cart' );
								prev_item = form.find( '.multistep.cart' );
							}

							if ( next_item.hasClass( 'multistep' ) ) {
								next_item.addClass( 'next' );
							}

							if ( prev_item.hasClass( 'multistep' ) ) {
								prev_item.addClass( 'prev' );
							}

							if ( style !== 'progressive' ) {

								if ( ! is_active ) {

									composite.has_transition_lock = true;

									if ( wc_composite_params.transition_type === 'slide' ) {

										setTimeout( function() {

											var active_step_height = active_step.get_markup().height();
											var step_height        = step.get_markup().height();
											var max_height         = Math.max( active_step_height, step_height );
											var ref_duration       = 150 + Math.min( 450, parseInt( max_height / 5 ) );

											// hide with a sliding effect
											active_step.get_markup().addClass( 'faded' ).slideUp( { duration: ref_duration, always: function() {
												active_step.get_markup().removeClass( 'faded' );
											} } );

											// show with a sliding effect
											if ( active_step.step_index < step.step_index ) {
												step.get_markup().after( '<div style="display:none" class="active_placeholder"></div>' );
												step.get_markup().insertBefore( active_step.get_markup() );
											}

											step.get_markup().slideDown( { duration: ref_duration, always: function() {

												if ( active_step.step_index < step.step_index ) {
													composite.$composite_form.find( '.active_placeholder' ).replaceWith( step.get_markup() );
												}

												composite.has_transition_lock = false;

											} } );

										}, 150 );

									} else if ( wc_composite_params.transition_type === 'fade' ) {

										// fadeout
										active_step.get_markup().addClass( 'faded' );

										step.get_markup().addClass( 'faded' );

										setTimeout( function() {

											// show new
											step.get_markup().show();

											setTimeout( function() {
												// hide old
												active_step.get_markup().hide();
												// fade in new
												step.get_markup().removeClass( 'faded' );
											}, 10 );

											composite.has_transition_lock = false;

										}, 300 );
									}

								} else {
									step.get_markup().show();
								}
							}

							this.$markup.trigger( 'wc-composite-set-active-component' );

						},

						/**
						 * Updates the block state of a progressive step that's brought into view
						 */

						update_block_state: function() {

							var style = composite.composite_layout;

							if ( style !== 'progressive' ) {
								return false;
							}

							var prev_step = composite.get_previous_step();

							if ( prev_step !== false ) {
								prev_step.block_step_inputs();
							}	

							this.unblock_step_inputs();
							this.unblock_step();

							if ( prev_step !== false ) {
								var reset_options = prev_step.get_markup().find( '.clear_component_options' );
								reset_options.html( wc_composite_params.i18n_reset_selection ).addClass( 'reset_component_options' );
							}

						},

						/**
						 * Unblocks access to step in progressive mode
						 */

						unblock_step: function() {

							if ( this.$markup.hasClass( 'toggled' ) ) {

								if ( this.$markup.hasClass( 'closed' ) ) {
									wc_cp_toggle_element( this.$markup, this.$markup.find( '.component_inner' ) );
								}

								this.$markup.find( '.component_title' ).removeClass( 'inactive' );
							}

							this.$markup.removeClass( 'blocked' );
							
							this.unblock_step_inputs();

						},

						/**
						 * Blocks access to all later steps in progressive mode
						 */

						block_next_steps: function() {

							var min_block_index = this.step_index;

							$.each( composite.composite_steps, function( index, step ) {

								if ( index > min_block_index ) {

									if ( step.get_markup().hasClass( 'disabled' ) ) {
										step.unblock_step_inputs();
									}

									step.block_step();
								}
							} );

						},

						/**
						 * Blocks access to step in progressive mode
						 */

						block_step: function() {
							
							if(! wc_composite_params.block_components ) {
								
								return;
								
							}

							this.$markup.addClass( 'blocked' );

							if ( this.$markup.hasClass( 'toggled' ) ) {

								if ( this.$markup.hasClass( 'open' ) ) {
									wc_cp_toggle_element( this.$markup, this.$markup.find( '.component_inner' ) );
								}

								this.$markup.find( '.component_title' ).addClass( 'inactive' );
							}
							
							this.$markup.trigger('after_blocked_step');

						},

						/**
						 * Unblocks step inputs
						 */

						unblock_step_inputs: function() {

							this.$markup.find( 'select.disabled_input, input.disabled_input' ).removeClass( 'disabled_input' ).prop( 'disabled', false );

							this.$markup.removeClass( 'disabled' ).trigger( 'wc-composite-enable-component-options' );

							var reset_options = this.$markup.find( '.clear_component_options' );

							reset_options.html( wc_composite_params.i18n_clear_selection ).removeClass( 'reset_component_options' );

						},

						/**
						 * Blocks step inputs
						 */

						block_step_inputs: function() {

							this.$markup.find( 'select, input' ).addClass( 'disabled_input' ).prop( 'disabled', 'disabled' );

							this.$markup.addClass( 'disabled' ).trigger( 'wc-composite-disable-component-options' );

							var reset_options = this.$markup.find( '.clear_component_options' );

							reset_options.html( wc_composite_params.i18n_reset_selection ).addClass( 'reset_component_options' ).trigger('after_blocked_step_inputs');

						},

						/**
						 * True if access to the step is blocked (progressive mode)
						 */

						is_blocked: function() {

							return this.$markup.hasClass( 'blocked' );

						},

						/**
						 * Fire state actions based on scenarios
						 */

						fire_scenario_actions: function( excl_firing ) {

							if ( typeof( excl_firing ) === 'undefined' ) {
								excl_firing = false;
							}

							composite.actions_nesting++;

							// Update active scenarios
							composite.update_active_scenarios( this.step_id );

							// Update selections - 'compat_group' scenario action
							composite.update_selections( this.step_id, excl_firing );

							if ( composite.actions_nesting === 1 ) {

								// Signal 3rd party scripts to fire their own actions
								this.get_markup().trigger( 'wc-composite-fire-scenario-actions' );
							}

							composite.actions_nesting--;

						},

						/**
						 * Trigger UI update
						 */

						trigger_ui_update: function( update_nav_delay ) {

							if ( typeof( update_nav_delay ) === 'undefined' ) {
								update_nav_delay = false;
							}

						 	composite.update_ui( this.step_id, update_nav_delay );

						},

						/**
						 * Validate step inputs
						 * True if a component has been fully configured - i.e. product/variation selected and in stock
						 */

						validate_inputs: function() {

							if ( this.is_component() ) {

								var component = this.get_component();

								var product_ids   = component.get_selected_product_ids();
								
								var response = true;
								
								for(var i in product_ids) {
									
									var product_id = product_ids[i];
									
									var product_type = component.get_selected_product_type(i);

									if ( product_id > 0 && component.is_in_stock() ) {
	
										if ( product_type === 'variable' ) {
	
											if ( component.$component_summary.find( '.variations_button input[name="variation_id"]' ).val() == '' ) {
												response = false;
											} else {
											}
	
										}  else if ( product_type === 'simple' || product_type === 'none' ) {
	
											// ok
	
										} else {
	
											if ( component.$component_data.data( 'component_set' ) == true ) {
												// ok
											} else {
												response = false;
											}
										}
	
									} else if ( product_id === '' && component.is_optional() ) {
	
										// ok
	
									} else {
										response = false;
									}
									
								}
								
								return response;
								
							}

							return false;

						}

					};

				} );

			},

			/**
			 * Shows a step when its id is known
			 */

			show_step: function( step_id ) {

				var composite = this;

				$.each( composite.composite_steps, function( step_index, step ) {

					if ( step.step_id == step_id ) {
						step.show_step();
						return false;
					}

				} );

			},

			/**
			 * Shows the step marked as previous from the current one
			 */

			show_previous_step: function() {

				var composite = this;

				$.each( composite.composite_steps, function( step_index, step ) {

					if ( step.is_previous() ) {
						step.show_step();
						return false;
					}

				} );

			},

			/**
			 * Shows the step marked as next from the current one
			 */

			show_next_step: function() {

				var composite = this;

				$.each( composite.composite_steps, function( step_index, step ) {

					if ( step.is_next() ) {
						step.show_step();
						return false;
					}

				} );

			},

			/**
			 * Returns a step object by id
			 */

			get_step: function( step_id ) {

				var composite = this;
				var found     = false;

				$.each( composite.composite_steps, function( step_index, step ) {

					if ( step.step_id == step_id ) {
						found = step;
						return false;
					}

				} );

				return found;

			},

			/**
			 * Returns the current step object
			 */

			get_current_step: function() {

				var composite = this;
				var current   = false;

				$.each( composite.composite_steps, function( step_index, step ) {

					if ( step.is_current() ) {
						current = step;
						return false;
					}

				} );

				return current;

			},

			/**
			 * Returns the previous step object
			 */

			get_previous_step: function() {

				var composite  = this;
				var previous   = false;

				$.each( composite.composite_steps, function( step_index, step ) {

					if ( step.is_previous() ) {
						previous = step;
						return false;
					}

				} );

				return previous;

			},

			/**
			 * Returns the next step object
			 */

			get_next_step: function() {

				var composite = this;
				var next      = false;

				$.each( composite.composite_steps, function( step_index, step ) {

					if ( step.is_next() ) {
						next = step;
						return false;
					}

				} );

				return next;

			},

			/**
			 * Return stored scenario data
			 */

			get_scenario_data: function() {

				var composite = this;
				var scenarios = composite.$composite_data.data( 'scenario_data' );

				return scenarios;

			},

			/**
			 * Extract active scenarios from current selections
			 */

			update_active_scenarios: function( firing_step_id ) {

				var composite         = this;

				var fired_by_step     = composite.get_step( firing_step_id );
				var style             = composite.composite_layout;
				var firing_step_index = fired_by_step.step_index;
				var tabs              = '';

				for ( var i = composite.actions_nesting-1; i > 0; i--) {
					tabs = tabs + '	';
				}

				if ( fired_by_step.is_review() ) {
					firing_step_index = 1000;
				}

				// Initialize by getting all scenarios
				var scenarios = composite.get_scenario_data().scenarios;

				var compat_group_scenarios = composite.get_scenarios_by_type( scenarios, 'compat_group' );

				if ( compat_group_scenarios.length === 0 ) {
					scenarios.push( '0' );
				}

				// Active scenarios including current component
				var active_scenarios_incl_current            = scenarios;

				// Active scenarios excluding current component
				var active_scenarios_excl_current            = scenarios;

				var scenario_shaping_components_incl_current = [];
				var scenario_shaping_components_excl_current = [];

				if ( wc_composite_params.script_debug === 'yes' ) {
					console.log( '\n' + tabs + 'Selections update triggered by ' + fired_by_step.get_title() + ' at ' + new Date().getTime().toString() + '...' );
					console.log( '\n' + tabs + 'Calculating active scenarios...' );
				}

				$.each( composite.composite_components, function( index, component ) {

					var component_id = component.component_id;

					// In single-page/multi-page progressive modes, when configuring a Component, Scenario restrictions must be evaluated based on previous Component selections only.
					// Any incompatible subsequent Component selections will be reset when the affected Component becomes active.

					if ( style === 'progressive' || style === 'paged' ) {
						if ( index > firing_step_index ) {
							return false;
						}
					}

					active_scenarios_excl_current            = active_scenarios_incl_current.slice();
					scenario_shaping_components_excl_current = scenario_shaping_components_incl_current.slice();

					var product_ids = composite.composite_steps[ index ].get_component().get_selected_product_ids();
					
					var response = true;
					
					for(var i in product_ids) {
						
						product_id = product_ids[i];
						
						var product_type = component.get_selected_product_type(i);

						if ( product_id !== null && product_id >= 0 ) {
	
							var scenario_data      = composite.get_scenario_data().scenario_data;
							var item_scenario_data = scenario_data[ component_id ];	
	
							// Treat '' optional component selections as 'None' if the component is optional
							if ( product_id === '' ) {
								if ( 0 in item_scenario_data ) {
									product_id = '0';
								} else {
									break;
								}
							}
	
							var product_in_scenarios = item_scenario_data[ product_id ];
	
							if ( wc_composite_params.script_debug === 'yes' ) {
								console.log( tabs + 'Selection #' + product_id + ' of ' + component.get_title() + ' in scenarios: ' + product_in_scenarios.toString() );
							}
	
							scenario_shaping_components_incl_current.push( component_id );
	
							var product_intersection    = wc_cp_intersect_safe( active_scenarios_incl_current, product_in_scenarios );
							var product_is_compatible   = product_intersection.length > 0;
	
							var variation_is_compatible = true;
	
							if ( product_is_compatible ) {
	
								if ( product_type === 'variable' ) {
	
									var variation_id = component.$self.find( '.single_variation_wrap .variations_button input[name="variation_id"]' ).val();
	
									// The selections of the current item must not shape the constraints in non-proressive modes, in order to be able to switch the selection.
									// Variations are only selected with dropdowns, so we don't need to check for that.
									if ( variation_id > 0 ) {
	
										var variation_in_scenarios = item_scenario_data[ variation_id ];
	
										if ( wc_composite_params.script_debug === 'yes' ) {
											console.log( tabs + 'Variation selection #' + variation_id + ' of ' + component_id + ' in scenarios: ' + product_in_scenarios.toString() );
										}
	
										product_intersection    = wc_cp_intersect_safe( product_intersection, variation_in_scenarios );
										variation_is_compatible = product_intersection.length > 0;
									}
								}
	
							}
	
							var is_compatible = product_is_compatible && variation_is_compatible;
	
							if ( is_compatible ) {
	
								active_scenarios_incl_current = product_intersection;
	
								if ( wc_composite_params.script_debug === 'yes' ) {
									console.log( tabs + '	Active scenarios: ' + active_scenarios_incl_current.toString() );
								}
	
							} else {
	
								// The chosen product was found incompatible
								if ( ! product_is_compatible ) {
	
									if ( product_id !== '0' ) {
	
										if ( wc_composite_params.script_debug === 'yes' ) {
											console.log( tabs + '	Product selection not found in any scenario - breaking out and resetting...' );
										}
	
										component.$self.addClass( 'reset' );
	
									} else {
	
										if ( wc_composite_params.script_debug === 'yes' ) {
											console.log( tabs + '	Selection not found in any scenario - breaking out...' );
										}
									}
	
								} else {
	
									if ( wc_composite_params.script_debug === 'yes' ) {
										console.log( tabs + '	Variation selection not found in any scenario - breaking out and resetting...' );
									}
	
									component.$self.addClass( 'reset_variation' );
								}
	
								response = false;
							}
						
						}
						
					}
					
					return response;

				} );

				if ( wc_composite_params.script_debug === 'yes' ) {
					console.log( tabs + 'Removing active scenarios where all scenario shaping components are masked...' );
				}

				if ( wc_composite_params.script_debug === 'yes' ) {
					console.log( tabs + '	Scenario shaping components: ' + scenario_shaping_components_incl_current.toString() );
				}

				composite.active_scenarios = {
					incl_current: composite.get_binding_scenarios( active_scenarios_incl_current, scenario_shaping_components_incl_current ),
					excl_current: composite.get_binding_scenarios( active_scenarios_excl_current, scenario_shaping_components_excl_current )
				};

				composite.$composite_data.data( 'active_scenarios', composite.active_scenarios.incl_current );

				if ( wc_composite_params.script_debug === 'yes' ) {
					console.log( '\n' + tabs + 'Final active scenarios incl.: ' + composite.active_scenarios.incl_current.toString() + '\n' + tabs + 'Final active scenarios excl.: ' + composite.active_scenarios.excl_current.toString() );
				}

			},

			/**
			 * Filters out unbinding scenarios
			 */

			get_binding_scenarios: function( scenarios, scenario_shaping_components ) {

				var composite = this;

				var masked    = composite.get_scenario_data().scenario_settings.masked_components;
				var clean     = [];

				if ( scenario_shaping_components.length > 0 ) {

					if ( scenarios.length > 0 ) {
						$.each( scenarios, function( i, scenario_id ) {

							// If all scenario shaping components are masked, filter out the scenario
							var all_components_masked_in_scenario = true;

							$.each( scenario_shaping_components, function( i, component_id ) {

								if ( $.inArray( component_id.toString(), masked[ scenario_id ] ) == -1 ) {
									all_components_masked_in_scenario = false;
									return false;
								}
							} );

							if ( ! all_components_masked_in_scenario ) {
								clean.push( scenario_id );
							}
						} );
					}

				} else {
					clean = scenarios;
				}

				if ( clean.length === 0 && scenarios.length > 0 ) {
					clean = scenarios;
				}

				return clean;

			},

			/**
			 * Filters active scenarios by type
			 */

			get_active_scenarios_by_type: function( type ) {

				var composite   = this;

				var incl = composite.get_scenarios_by_type( composite.active_scenarios.incl_current, type );
				var excl = composite.get_scenarios_by_type( composite.active_scenarios.excl_current, type );

				return {
					incl_current: incl,
					excl_current: excl
				};

			},

			/**
			 * Filters scenarios by type
			 */

			get_scenarios_by_type: function( scenarios, type ) {

				var composite   = this;

				var filtered    = [];
				var scenario_id = '';

				if ( scenarios.length > 0 ) {
					for ( var i in scenarios ) {

						scenario_id = scenarios[ i ];

						if ( $.inArray( type, composite.get_scenario_data().scenario_settings.scenario_actions[ scenario_id ] ) > -1 ) {
							filtered.push( scenario_id );
						}
					}
				}

				return filtered;

			},

			/**
			 * Filters out masked scenarios in 'update_selections'
			 */

			get_binding_scenarios_for: function( scenarios, component_id ) {

				var composite = this;

				var masked    = composite.get_scenario_data().scenario_settings.masked_components;
				var clean     = [];

				if ( scenarios.length > 0 ) {
					$.each( scenarios, function( i, scenario_id ) {

						if ( $.inArray( component_id.toString(), masked[ scenario_id ] ) == -1 ) {
							clean.push( scenario_id );
						}

					} );
				}

				return clean;

			},

			/**
			 * Activates or deactivates products and variations based on scenarios
			 */

			update_selections: function( firing_step_id, excl_firing ) {

				var composite        = this;

				var style            = composite.composite_layout;
				var fired_by_step    = composite.get_step( firing_step_id );
				var active_scenarios = [];
				var reset            = false;
				var tabs             = '';

				for ( var i = composite.actions_nesting-1; i > 0; i--) {
					tabs = tabs + '	';
				}

				if ( typeof( excl_firing ) === 'undefined' ) {
					excl_firing = false;
				}

				if ( style === 'progressive' || style === 'paged' ) {
					excl_firing = true;
				}

				if ( wc_composite_params.script_debug === 'yes' ) {
					console.log( '\n' + tabs + 'Updating selections...' );
				}

				/**
				 * 	1. Clear resets
				 */

				if ( wc_composite_params.script_debug === 'yes' ) {
					console.log( '\n' + tabs + 'Clearing incompatible selections...' );
				}

				$.each( composite.composite_components, function( index, component ) {

					var component_id             = component.component_id;
					var component_options_select = component.$component_options.find( 'select.component_options_select' );
					
					if ( firing_step_id != component_id && style !== 'single' ) {
						return true;
					}

					var product_ids  = component.get_selected_product_ids();
					
					for(var i in product_ids) {
						
						var product_id = product_ids[i];
						
						var product_type = component.get_selected_product_type(i);
						
						// If a disabled option is still selected, val will be null - use this fact to reset options before moving on
						if ( product_id === null ) {
							component.$self.addClass( 'reset' );
	
							if ( wc_composite_params.script_debug === 'yes' ) {
								console.log( tabs + component.get_title() + ' selection was found disabled.' );
							}
						}
						
						// Same with variations in WC 2.3
						if ( wc_composite_params.is_wc_version_gte_2_3 === 'yes' ) {
	
							if ( product_type === 'variable' && component.$component_content.find( '.single_variation_wrap .variations_button input[name="variation_id"]' ).val() > 0 ) {
										
								var attribute_options        = component.$component_summary.find( '.attribute-options' );
								var attribute_options_length = attribute_options.length;
	
								if ( attribute_options_length > 0 ) {
	
									attribute_options.each( function() {
	
										var selected = $(this).find( 'select option:selected' );
	
										if ( ! selected.hasClass( 'enabled' ) ) {
	
											if ( wc_composite_params.script_debug === 'yes' ) {
												console.log( tabs + component.get_title() + ' variation selections were found disabled.' );
											}
	
											component.$self.addClass( 'reset_variation' );
											reset = true;
											return false;
										}
									} );
								}
							}
	
						// ...and WC 2.2
						} else {
	
							if ( product_type === 'variable' ) {
	
								var current_item_summary_content = component.$self.find( '.component_summary > .content' );
	
								// If the selected attributes are not valid, reset options before initializing to prevent attribute match error
								if ( ! wc_cp_has_valid_default_attributes( current_item_summary_content ) ) {
									component.$self.addClass( 'reset_variation' );
									reset = true;
									return false;
								}
							}
						}
						
					}

					// Verify and reset active product selections that were found incompatible
					if ( component.$self.hasClass( 'reset' ) ) {

						if ( ! component.$self.hasClass( 'resetting' ) ) {

							if ( wc_composite_params.script_debug === 'yes' ) {
								console.log( tabs + 'Resetting ' + component.get_title() + '...\n' );
							}

							component.$self.addClass( 'resetting' );
							component_options_select.val( '' ).change(); // this is the culprit

							reset = true;

							return false;

						} else {
							component.$self.removeClass( 'reset' );
							component.$self.removeClass( 'resetting' );

							if ( wc_composite_params.script_debug === 'yes' ) {
								console.log( tabs + 'Reset ' + component.get_title() + ' complete...\n' );
							}
						}
					}

					// Verify and reset active variation selections that were found incompatible
					if ( component.$self.hasClass( 'reset_variation' ) ) {

						if ( ! component.$self.hasClass( 'resetting' ) ) {

							if ( wc_composite_params.script_debug === 'yes' ) {
								console.log( tabs + 'Resetting variation selections of ' + component.get_title() + '...\n' );
							}

							component.$self.addClass( 'resetting' );
							component.$component_summary.find( '.reset_variations' ).trigger( 'click' );

							reset = true;

							return false;

						} else {

							component.$self.removeClass( 'reset_variation' );
							component.$self.removeClass( 'resetting' );

							if ( wc_composite_params.script_debug === 'yes' ) {
								console.log( tabs + 'Reset ' + component.get_title() + ' complete...\n' );
							}
						}
					}

				} );

				/**
				 * 	2. Disable or enable product and variation selections
				 */

				if ( ! reset ) {

					var firing_step_index = fired_by_step.step_index;

					if ( fired_by_step.is_review() ) {
						firing_step_index = 1000;
					}

					// Get active scenarios filtered by action = 'compat_group'
					var scenarios = composite.get_active_scenarios_by_type( 'compat_group' );

					// Do the work
					$.each( composite.composite_components, function( index, component ) {
						
						if(style == 'progressive') {
							
							if(fired_by_step.step_index > index) {
								
								if ( wc_composite_params.script_debug === 'yes' ) {
								
									console.log('breaking out of ' + component.get_title() + ' because firing step ' + fired_by_step.step_index + ' is bigger than index ' + index);
									
								}
								
								return true;
								
							}
							
						}

						var component_id    = component.component_id;
						var summary_content = component.$self.find( '.component_summary > .content' );

						// The constraints of the firing item must not be taken into account when the update action is triggered by a dropdown, in order to be able to switch the selection
						if ( excl_firing && firing_step_index == index ) {
							active_scenarios = scenarios.excl_current;
						} else {
							active_scenarios = scenarios.incl_current;
						}

						if ( wc_composite_params.script_debug === 'yes' ) {
							console.log( tabs + 'Updating selections of ' + component.get_title() + '...' );
							console.log( tabs + '	Reference scenarios: ' + active_scenarios.toString() );
							console.log( tabs + '	Removing any scenarios where the current component is masked...' );
						}

						active_scenarios = composite.get_binding_scenarios_for( active_scenarios, component_id );

						// Enable all if all active scenarios ignore this component
						if ( active_scenarios.length === 0 ) {
							active_scenarios.push( '0' );
						}

						if ( wc_composite_params.script_debug === 'yes' ) {
							console.log( tabs + '	Active scenarios: ' + active_scenarios.slice().toString() );
						}

						var scenario_data      = composite.get_scenario_data().scenario_data;
						var item_scenario_data = scenario_data[ component_id ];

						// Set optional status

						var is_optional = false;

						if ( 0 in item_scenario_data ) {

							var optional_in_scenarios = item_scenario_data[ 0 ];

							for ( var s in optional_in_scenarios ) {

								var optional_in_scenario_id = optional_in_scenarios[ s ];

								if ( $.inArray( optional_in_scenario_id, active_scenarios ) > -1 ) {
									is_optional = true;
									break;
								}
							}

						} else {
							is_optional = false;
						}

						if ( is_optional ) {
							if ( wc_composite_params.script_debug === 'yes' ) {
								console.log( tabs + '	Component set as optional.' );
							}
							component.set_optional( true );
						} else {
							component.set_optional( false );
						}

						// Disable incompatible products

						component.$component_options.find( 'select.component_options_select option' ).each( function() {
							
							var product_id = $(this).val();

							// The '' option cannot be disabled - if an option must be selected the add to cart button will be hidden and a message will be shown
							if ( product_id >= 0 && product_id !== '' ) {

								if ( wc_composite_params.script_debug === 'yes' ) {
									console.log( tabs + '	Updating selection #' + product_id + ':' );
								}

								var product_in_scenarios = item_scenario_data[ product_id ];
								var is_compatible        = false;

								if ( wc_composite_params.script_debug === 'yes' ) {
									console.log( tabs + '		Selection in scenarios: ' + product_in_scenarios.toString() );
								}

								for ( var i in product_in_scenarios ) {

									var scenario_id = product_in_scenarios[ i ];

									if ( $.inArray( scenario_id, active_scenarios ) > -1 ) {
										is_compatible = true;
										break;
									}
								}

								if ( ! is_compatible ) {

									if ( wc_composite_params.script_debug === 'yes' ) {
										console.log( tabs + '		Selection disabled.' );
									}
									
									if($(this).is(':selected')) {
										
										var selected_option = $(this);
										
										if($(this).parent().find('option:selected').length > 1) {
											
											$(this).parent().find('option:selected').each(function(index) {
											
												if(selected_option.val() == $(this).val()) {
													
													component.$component_data.eq(index).remove();
													
													$(this).prop('selected', false).parent().trigger('change');
													
												}
												
											});
											
										}
										
										else if($(this).parent().find('option:selected[value=""]').length) {
											
											component.$component_data.remove();
											
											$(this).parent().find('option:selected[value=""]').prop('selected', true).parent().trigger('change');
											
										}
										
										$(this).prop('selected', false);
										
									}

									$(this).prop( 'disabled', 'disabled' ).trigger( 'wc-composite-selection-incompatible' );
									component.$self.find( '#component_option_thumbnail_' + $(this).val() ).addClass( 'disabled' );

								} else {

									if ( wc_composite_params.script_debug === 'yes' ) {
										console.log( tabs + '		Selection enabled.' );
									}

									$(this).prop( 'disabled', false ).trigger( 'wc-composite-selection-compatible' );
									component.$self.find( '#component_option_thumbnail_' + $(this).val() ).removeClass( 'disabled' );
								}
							}
						});
						
						var product_ids = component.get_selected_product_ids();
						
						for(var i in product_ids) {
							
							var product_id = product_ids[i];
							
							var product_type = component.get_selected_product_type(i);
							
							if ( product_type === 'variable' ) {

								// Note the variation id
								var variation_input    = summary_content.find( '.single_variation_wrap .variations_button input[name="variation_id"]' );
								var variation_input_id = variation_input.val();
								var variation_valid    = variation_input_id > 0 ? false : true;
	
								if ( wc_composite_params.script_debug === 'yes' ) {
									console.log( tabs + '		Checking variations...' );
								}
	
								if ( variation_input_id > 0 ) {
									if ( wc_composite_params.script_debug === 'yes' ) {
										console.log( tabs + '			--- Stored variation is #' + variation_input_id );
									}
								}
	
								// Get all variations
								var product_variations = component.$component_data.eq(i).data( 'product_variations' );
	
								var product_variations_in_scenario = [];
	
								for ( var i in product_variations ) {
	
									var variation_id           = product_variations[ i ].variation_id;
									var variation_in_scenarios = item_scenario_data[ variation_id ];
									var is_compatible          = false;
	
									if ( wc_composite_params.script_debug === 'yes' ) {
										console.log( tabs + '			Checking variation #' + variation_id + ':' );
										console.log( tabs + '			Selection in scenarios: ' + variation_in_scenarios.toString() );
									}
	
									for ( var k in variation_in_scenarios ) {
	
										var scenario_id = variation_in_scenarios[ k ];
	
										if ( $.inArray( scenario_id, active_scenarios ) > -1 ) {
											is_compatible = true;
											break;
										}
									}
	
									// In WC 2.3, copy all variation objects but set the variation_is_active property to false in order to disable the attributes of incompatible variations
									if ( wc_composite_params.is_wc_version_gte_2_3 === 'yes' ) {
	
										var variation = $.extend( true, {}, product_variations[ i ] );
	
										var variation_has_empty_attributes = false;
	
										if ( ! is_compatible ) {
	
											variation.variation_is_active = false;
	
											// do not include incompatible variations with empty attributes - they can break stuff when prioritized
											for ( var attr_name in variation.attributes ) {
												if ( variation.attributes[ attr_name ] === '' ) {
													variation_has_empty_attributes = true;
													break;
												}
											}
	
											if ( wc_composite_params.script_debug === 'yes' ) {
												console.log( tabs + '			Variation disabled.' );
											}
										} else {
	
											if ( wc_composite_params.script_debug === 'yes' ) {
												console.log( tabs + '			Variation enabled.' );
											}
	
											if ( parseInt( variation_id ) === parseInt( variation_input_id ) ) {
												variation_valid = true;
												if ( wc_composite_params.script_debug === 'yes' ) {
													console.log( tabs + '			--- Stored variation is valid.' );
												}
											}
										}
	
										if ( ! variation_has_empty_attributes ) {
	
											product_variations_in_scenario.push( variation );
										}
	
									// In WC 2.2/2.1, copy only compatible variations
									} else {
										if ( is_compatible ) {
	
											product_variations_in_scenario.push( product_variations[i] );
	
											if ( wc_composite_params.script_debug === 'yes' ) {
												console.log( tabs + '			Variation enabled.' );
											}
	
											if ( parseInt( variation_id ) === parseInt( variation_input_id ) ) {
												variation_valid = true;
												if ( wc_composite_params.script_debug === 'yes' ) {
													console.log( tabs + '			--- Stored variation is valid.' );
												}
											}
	
										} else {
											if ( wc_composite_params.script_debug === 'yes' ) {
												console.log( tabs + '			Variation disabled.' );
											}
										}
									}
								}
	
								// Put filtered variations in place
								summary_content.data( 'product_variations', product_variations_in_scenario );
	
								if ( firing_step_id != component_id ) {
									summary_content.trigger( 'update_variation_values', [ product_variations_in_scenario ] );
								}
	
								if ( ! variation_valid ) {
									if ( wc_composite_params.script_debug === 'yes' ) {
										console.log( tabs + '			--- Stored variation was NOT found.' );
									}
									composite.triggered_by_step = firing_step_id;
									summary_content.find( '.reset_variations' ).trigger( 'click' );
								}
							}
							
						}

					} );

					if ( wc_composite_params.script_debug === 'yes' ) {
						console.log( tabs + 'Finished updating component selections.\n\n' );
					}

				}

			},

			update_ui: function( firing_step_id, update_nav_delay ) {

				var composite    = this;

				var current_step = composite.get_step( firing_step_id );
				var style        = composite.composite_layout;

				if ( style === 'single' ) {
					return false;
				}

				if ( ! current_step.is_current() ) {
					return false;
				}

				// Update nav previous/next button immediately before init
				if ( composite.composite_initialized === false ) {
					update_nav_delay = false;
				}

				if ( update_nav_delay ) {
					composite.has_update_nav_delay = true;
				}

				// Dumb task scheduler
				if ( composite.has_ui_update_lock === true ) {
					return false;
				}

				composite.has_ui_update_lock = true;

				setTimeout( function() {

					composite.update_ui_task( firing_step_id, composite.has_update_nav_delay );
					composite.has_ui_update_lock   = false;
					composite.has_update_nav_delay = false;

					current_step.get_markup().trigger( 'wc-composite-ui-updated' );

				}, 10 );

			},

			/**
			 * Updates all pagination/navigation ui elements
			 */

			update_ui_task: function( firing_step_id, update_nav_delay ) {

				var composite       = this;

				var current_step    = composite.get_step( firing_step_id );
				var item            = current_step.get_markup();

				/**
				 * Update navigation (next/previous buttons)
				 */

				var form            = composite.$composite_form;
				var navigation      = composite.$composite_navigation;
				var style           = composite.composite_layout;
				var style_variation = composite.composite_layout_variation;
				var show_next       = false;

				var next_step       = composite.get_next_step();
				var prev_step       = composite.get_previous_step();

				var update_nav_duration = update_nav_delay ? 300 : 10;

				setTimeout( function() {

					// paged previous / next
					if ( current_step.validate_inputs() || ( style_variation === 'componentized' && current_step.is_component() ) ) {

						// hide navigation
						navigation.find( '.next' ).addClass( 'invisible' );
						navigation.find( '.prev' ).addClass( 'invisible' );

						// selectively show next/previous navigation buttons
						if ( next_step && style_variation !== 'componentized' ) {

							navigation.find( '.next' ).html( wc_composite_params.i18n_next_step.replace( '%s', next_step.get_title() ) );
							navigation.find( '.next' ).removeClass( 'invisible' );

							if ( next_step.get_markup().hasClass( 'toggled' ) ) {
								next_step.get_markup().find( '.component_title' ).removeClass( 'inactive' );
							}

						} else {

							form.find( '.composite_navigation.paged .next' ).html( wc_composite_params.i18n_final_step );
							form.find( '.composite_navigation.paged .next' ).removeClass( 'invisible' );
						}

						if ( prev_step && prev_step.is_component() ) {

							form.find( '.composite_navigation.paged .prev' ).html( wc_composite_params.i18n_previous_step.replace( '%s', prev_step.get_title() ) );
							form.find( '.composite_navigation.paged .prev' ).removeClass( 'invisible' );
						}

						navigation.find( '.prompt' ).html( '' );
						navigation.find( '.prompt' ).addClass( 'invisible' );

						show_next = true;

					} else {

						navigation.find( '.prev' ).addClass( 'invisible' );
						navigation.find( '.next' ).addClass( 'invisible' );

						if ( next_step && next_step.get_markup().hasClass( 'toggled' ) ) {
							next_step.block_step();
						}

						if ( prev_step && prev_step.is_component() ) {

							var product_ids = prev_step.get_component().get_selected_product_ids();
							
							for(i in product_ids) {
								
								var product_id = product_ids[i];
								
								if ( product_id > 0 || product_id === '0' || product_id === '' && prev_step.get_component().is_optional() ) {

									form.find( '.composite_navigation.paged .prev' ).html( wc_composite_params.i18n_previous_step.replace( '%s', prev_step.get_title() ) );
									form.find( '.composite_navigation.paged .prev' ).removeClass( 'invisible' );
								}
								
							}
							
						}

						if ( current_step.is_component() ) {

							// don't show the prompt if it's the last component of the progressive layout
							if ( ! item.hasClass( 'last' ) || ! item.hasClass( 'progressive' ) ) {

								navigation.find( '.prompt' ).html( wc_composite_params.i18n_select_component_options.replace( '%s', current_step.get_title() ) );
								navigation.find( '.prompt' ).removeClass( 'invisible' );
							}
						}

					}

					// move navigation into the next component when using the progressive layout without toggles
					if ( style === 'progressive' ) {

						var navi      = form.find( '.composite_navigation.progressive' );
						var item_navi = item.find( '.composite_navigation.progressive' );
						var next_item = form.find( '.component.next' );

						if ( item_navi.length == 0 ) {
							navi.css( { visibility: 'hidden' } );
							navi.slideUp( { duration: 200, queue: false, always: function() {
								navi.appendTo( item.find( '.component_inner' ) ).css( { visibility: 'visible' } );
								if ( ! item.hasClass( 'last' ) && ( ! next_item.hasClass( 'toggled' ) || ! show_next ) ) {
									navi.slideDown( 200 );
								}
							} } );
						} else {
							if ( ! item.hasClass( 'last' ) && ( ! next_item.hasClass( 'toggled' ) || ! show_next ) ) {
								navi.css( { visibility: 'visible' } ).slideDown( 200 );
							} else {
								navi.css( { visibility: 'hidden' } ).slideUp( 200 );
							}
						}
					}

				}, update_nav_duration );

				/**
				 * Update pagination (step pagination + summary sections)
				 */

				var pagination = composite.$composite_pagination;
				var summary    = $.merge( form.find( '.composite_summary' ), $( '.widget_composite_summary') );

				if ( pagination.length == 0 && summary.length == 0 ) {
					return false;
				}

				var deactivate_step_links = false;

				$.each( composite.composite_steps, function( step_index, step ) {

					if ( step_index > 0 ) {

						var prev_step = composite.composite_steps[ step_index - 1 ];

						if ( ! prev_step.is_review() ) {

							if ( false === prev_step.validate_inputs() ) {
								deactivate_step_links = true;
							} else if ( prev_step.is_blocked() ) {
								deactivate_step_links = true;
							}
						}
					}

					// Update simple pagination
					if ( pagination.length > 0 ) {

						var pagination_element      = pagination.find( '.pagination_element_' + step.step_id );
						var pagination_element_link = pagination_element.find( '.element_link' );

						if ( step.is_current() ) {

							pagination_element_link.addClass( 'inactive' );
							pagination_element.addClass( 'pagination_element_current' );

						} else {

							if ( deactivate_step_links ) {

								pagination_element_link.addClass( 'inactive' );
								pagination_element.removeClass( 'pagination_element_current' );

							} else {

								pagination_element_link.removeClass( 'inactive' );
								pagination_element.removeClass( 'pagination_element_current' );

							}
						}
					}

					// Update summary links
					if ( summary.length > 0 ) {

						var summary_element      = summary.find( '.summary_element_' + step.step_id );
						var summary_element_link = summary_element.find( '.summary_element_link' );

						if ( step.is_current() ) {

							summary_element_link.removeClass( 'disabled' );
							summary_element_link.addClass( 'selected' );

							summary_element.find( '.summary_element_selection_prompt' ).slideUp( 200 );

						} else {

							summary_element.find( '.summary_element_selection_prompt' ).slideDown( 200 );

							if ( deactivate_step_links ) {

								summary_element_link.removeClass( 'selected' );
								summary_element_link.addClass( 'disabled' );

							} else {

								summary_element_link.removeClass( 'disabled' );
								summary_element_link.removeClass( 'selected' );

							}
						}
					}

				} );
	
			},

			/**
			 * Updates the state of the Review/Summary template
			 */

			update_summary: function() {

				var composite         = this;

				var composite_summary = composite.$composite_summary;
				var price_data        = composite.$composite_data.data( 'price_data' );

				if ( composite_summary.length == 0 ) {
					return false;
				}

				$.each( composite.composite_components, function( index, component ) {

					var component_id       = component.component_id;
					var item               = component.$self;
					var item_id            = component_id;

					var item_summary       = composite_summary.find( '.summary_element_' + item_id );
					var item_summary_outer = item_summary.find( '.summary_element_wrapper' );
					var item_summary_inner = item_summary.find( '.summary_element_wrapper_inner' );
					
					var product_ids         = component.get_selected_product_ids();
					
					// lock height if animating
					if ( composite_summary.is( ':visible' ) ) {
						var load_height = item_summary_inner.outerHeight();
						item_summary_outer.css( 'height', load_height );
					}
					
					for(var i in product_ids) {
						
						var product_id = product_ids[i];
						
						var product_type       = component.get_selected_product_type();
					
						var qty                = parseInt( item.find( '.component_wrap input.qty' ).val() );
	
						var title              = '';
						var select             = '';
						var image              = '';
	
						var product_title      = '';
						var product_quantity   = '';
						var product_meta       = '';
	
						// Get title and image
						if ( product_type === 'none' ) {
	
							if ( component.is_optional() ) {
								title = $( '#component_options_' + item_id + ' option.none' ).data( 'title' );
							}
	
						} else if ( product_type === 'variable' ) {
	
							if ( product_id > 0 && ( qty > 0 || qty === 0 ) ) {
	
								product_title    = $( '#component_options_' + item_id + ' option:selected' ).data( 'title' );
								product_quantity = ' <strong>&times; ' + qty + '</strong>';
								product_meta     = wc_cp_get_variable_product_attributes_description( item.find( '.variations' ) );
	
								if ( product_meta ) {
									product_meta = ' (' + product_meta + ')';
								}
	
								title = product_title + product_meta + product_quantity;
	
								image = item.find( '.composited_product_image img' ).attr( 'src' );
	
								if ( typeof( image ) === 'undefined' ) {
									image = item.find( '#component_options_' + item_id + ' option:selected' ).data( 'image_src' );
								}
							}
	
						} else if ( product_type === 'bundle' ) {
	
							if ( product_id > 0 && ( qty > 0 || qty === 0 ) ) {
	
								var selected_bundled_products = '';
								var bundled_products_num      = 0;
	
								item.find( '.bundled_product .cart' ).each( function() {
	
									if ( $(this).data( 'quantity' ) > 0 )
										bundled_products_num++;
								} );
	
								if ( bundled_products_num == 0 ) {
	
									title = wc_composite_params.i18n_none;
	
								} else {
	
									item.find( '.bundled_product .cart' ).each( function() {
	
										if ( $(this).data( 'quantity' ) > 0 ) {
	
											var item_meta = wc_cp_get_variable_product_attributes_description( $(this).find( '.variations' ) );
	
											if ( item_meta ) {
												item_meta = ' (' + item_meta + ')';
											}
	
											selected_bundled_products = selected_bundled_products + $(this).data( 'title' ) + item_meta + ' <strong>&times; ' + parseInt( $(this).data( 'quantity' ) * qty ) + '</strong></br>';
										}
									} );
	
									title = selected_bundled_products;
								}
	
								image = item.find( '#component_options_' + item_id + ' option:selected' ).data( 'image_src' );
							}
	
						} else {
	
							if ( product_id > 0 ) {
	
								product_title    = $( '#component_options_' + item_id + ' option:selected' ).data( 'title' );
								product_quantity = isNaN( qty ) ? '' : '<strong>&times; ' + qty + '</strong>';
	
								title = product_title + ' ' + product_quantity;
	
								image = item.find( '#component_options_' + item_id + ' option:selected' ).data( 'image_src' );
							}
						}
	
						// Selection text
						if ( title ) {
	
							if ( item.hasClass( 'static') ) {
								select = '<a href="">' + wc_composite_params.i18n_summary_static_component + '</a>';
							} else {
								select = '<a href="">' + wc_composite_params.i18n_summary_filled_component + '</a>';
							}
	
						} else {
							select = '<a href="">' + wc_composite_params.i18n_summary_empty_component + '</a>';
						}
	
	
						// Update title
						if ( title ) {
							item_summary.find( '.summary_element_selection' ).html( '<span class="summary_element_content">' + title + '</span><span class="summary_element_content summary_element_selection_prompt">' + select + '</span>' );
						} else {
							item_summary.find( '.summary_element_selection' ).html( '<span class="summary_element_content summary_element_selection_prompt">' + select + '</span>' );
						}
	
						if ( $(this).hasClass( 'active' ) ) {
							item_summary.find( '.summary_element_selection_prompt' ).hide();
						}
	
						// Update image
						wc_cp_update_summary_element_image( item_summary, image );
	
	
						// Update price
						if ( price_data[ 'per_product_pricing' ] === true && product_id > 0 && qty > 0 && component.get_step().validate_inputs() ) {
	
							var price         = ( parseFloat( price_data[ 'prices' ][ item_id ] ) + parseFloat( price_data[ 'addons_prices' ][ item_id ] ) ) * qty;
							var regular_price = ( parseFloat( price_data[ 'regular_prices' ][ item_id ] ) + parseFloat( price_data[ 'addons_prices' ][ item_id ] ) ) * qty;
	
							var price_format         = wc_cp_woocommerce_number_format( wc_cp_number_format( price ) );
							var regular_price_format = wc_cp_woocommerce_number_format( wc_cp_number_format( regular_price ) );
	
							if ( regular_price > price ) {
								item_summary.find( '.summary_element_price' ).html( '<span class="price summary_element_content"><del>' + regular_price_format + '</del> <ins>' + price_format + '</ins></span>' );
							} else {
								item_summary.find( '.summary_element_price' ).html( '<span class="price summary_element_content">' + price_format + '</span>' );
							}
	
						} else {
	
							item_summary.find( '.summary_element_price' ).html( '' );
						}
						
					}
					
					// Send an event to allow 3rd party code to add data to the summary
					item.trigger( 'wc-composite-component-update-summary-content' );

					// Animate
					if ( composite_summary.is( ':visible' ) ) {
						item_summary_outer.animate( { 'height': item_summary_inner.outerHeight() }, { duration: 200, queue: false, always: function() {
							item_summary_outer.css( { 'height': 'auto' } );
						} } );
					}

				} );

				// Update Summary Widget

				var widget = $( '.widget_composite_summary_content' );

				if ( widget.length > 0 ) {

					var clone = composite_summary.find( '.summary_elements' ).clone();

					clone.find( '.summary_element_wrapper' ).css( { 'height': 'auto' } );
					clone.find( '.summary_element' ).css( { 'width': '100%' } );
					clone.find( '.summary_element_selection_prompt' ).remove();

					widget.html( clone );
				}

			},


			/**
			 * Schedules an update of the composite totals and review/summary section
			 * Uses a dumb scheduler to avoid queueing multiple calls of update_composite_task() - the "scheduler" simply introduces a 50msec execution delay during which all update requests are dropped
			 */

			update_composite: function( update_only ) {

				var composite = this;

				// Break out if the initialization is not finished yet (function call triggered by a 'wc-composite-component-loaded' event listener)
				if ( composite.composite_initialized !== true ) {
					return false;
				}

				// Dumb task scheduler
				if ( composite.has_update_lock === true ) {
					return false;
				}

				composite.has_update_lock = true;

				window.setTimeout( function() {

					composite.update_composite_task( update_only );
					composite.has_update_lock = false;

				}, 50 );

			},
			
			/* validates components for use in tasks */
			
			validate_components: function() {
				
				var composite      = this;
				var form           = composite.$composite_form;
				var composite_data = composite.$composite_data;
				var all_set = true;
				
				// Validate components
				$.each( composite.composite_components, function( index, component ) {

					var component_id    = component.component_id;
					var item            = component.$self;
					var item_id         = component_id;
					var form_data       = composite_data.find( '.composite_wrap .composite_button .form_data_' + item_id);
					
					component.$component_data.each(function( index, component_data ) {
						
						var products = component.get_selected_product_ids();
						
						var quantity  = $(component_data).find( '.component_wrap input.qty' ).val();
						
						for(var i in products) {
							
							product_id = products[i];
							
							var variation_id = form_data.find( 'input.variation_input[name="wccp_variation_id[' + component_id + '][' + product_id + ']"]' ).val();
							
							var product_type = component.get_selected_product_type(i);
	
							if ( typeof( product_type ) === 'undefined' || product_type == '' ) {
								all_set = false;
							} else if ( ! ( product_id > 0 ) && ! component.is_optional() ) {
								all_set = false;
							} else if ( product_type !== 'none' && quantity === '' ) {
								all_set = false;
							} else if ( product_type === 'variable' && ( typeof( variation_id ) === 'undefined' || component.$component_data.eq(i).data( 'component_set' ) == false ) ) {
								all_set = false;
							} else if ( product_type !== 'variable' && product_type !== 'simple' && product_type !== 'none' && component.$component_data.eq(i).data( 'component_set' ) == false ) {
								all_set = false;
							} else if ( ! component.is_in_stock() ) {
								out_of_stock.push( wc_composite_params.i18n_insufficient_item_stock.replace( '%s', $( '#component_options_' + item_id + ' option:selected' ).data( 'title' ) ).replace( '%v', component.get_title() ) );
								all_set = false;
							}
							
						}
					
					});

				} );
				
				return all_set;
				
			},
			
			/* get component quantity for use in tasks */
				
			get_components_quantity: function() {
				
				var composite      = this;
				var form           = composite.$composite_form;
				var composite_data = composite.$composite_data;
				var component_quantity = {};
				
				// Validate components
				$.each( composite.composite_components, function( index, component ) {

					var component_id    = component.component_id;
					var item            = component.$self;
					var item_id         = component_id;
					var form_data       = composite_data.find( '.composite_wrap .composite_button .form_data_' + item_id);
					
					component.$component_data.each(function( index, component_data ) {
						
						var products = component.get_selected_product_ids();
						
						var quantity  = $(component_data).find( '.component_wrap input.qty' ).val();
						
						for(var i in products) {
							
							component.$component_data.eq(i).data( 'component_set', true );
							
							if ( quantity > 0 ) {
								
								component_quantity[ item_id ] = parseInt( quantity );
								
							} else {
								
								component_quantity[ item_id ] = 0;
								
							}
							
						}
					
					});

				} );
				
				return component_quantity;
				
			},
			
			/* get prices for use in tasks */
			
			get_prices: function() {
				
				var composite      = this;
				var form           = composite.$composite_form;
				var composite_data = composite.$composite_data;
				var price_data = composite_data.data( 'price_data' );
				
				price_data[ 'prices_excl_tax' ] = [];
				price_data[ 'prices_incl_tax' ] = [];
				price_data[ 'addons_prices_incl_tax' ] = [];
				price_data[ 'addons_prices_excl_tax' ] = [];
				price_data[ 'regular_prices_incl_tax' ] = [];
				price_data[ 'regular_prices_excl_tax' ] = [];
				
				// Validate components
				$.each( composite.composite_components, function( index, component ) {

					var component_id    = component.component_id;
					var item            = component.$self;
					var item_id         = component_id;
					var form_data       = composite_data.find( '.composite_wrap .composite_button .form_data_' + item_id);

					// Copy prices
					
					price_data[ 'prices' ][ item_id ] = 0;
					price_data[ 'prices_incl_tax' ][ item_id ] = 0;
					price_data[ 'prices_excl_tax' ][ item_id ] = 0;
					price_data[ 'addons_prices' ][ item_id ] = 0;
					price_data[ 'addons_prices_incl_tax' ][ item_id ] = 0;
					price_data[ 'addons_prices_excl_tax' ][ item_id ] = 0;
					price_data[ 'regular_prices' ][ item_id ] = 0;
					price_data[ 'regular_prices_incl_tax' ][ item_id ] = 0;
					price_data[ 'regular_prices_excl_tax' ][ item_id ] = 0;
					
					item.find( '.addon' ).each( function() {
	
						var addon_cost = 0;
						var addon_cost_incl_tax = 0;
						var addon_cost_excl_tax = 0;

						if ( $(this).is('.addon-custom-price') ) {
							addon_cost = addon_cost_incl_tax = addon_cost_excl_tax = $(this).val();
						} else if ( $(this).is('.addon-input_multiplier') ) {
							if( isNaN( $(this).val() ) || $(this).val() == '' ) { // Number inputs return blank when invalid
								$(this).val( '' );
								$(this).closest('p').find('.addon-alert').show();
							} else {
								if( $(this).val() != '' ) {
									$(this).val( Math.ceil( $(this).val() ) );
								}
								$(this).closest('p').find('.addon-alert').hide();
							}
							addon_cost = $(this).data('price') * $(this).val();
							addon_cost_incl_tax = $(this).data('price_incl_tax') * $(this).val();
							addon_cost_excl_tax = $(this).data('price_excl_tax') * $(this).val();
						} else if ( $(this).is('.addon-checkbox, .addon-radio') ) {
							if ( $(this).is(':checked') ) {
								addon_cost = $(this).data('price');
								addon_cost_incl_tax = $(this).data('price_incl_tax');
								addon_cost_excl_tax = $(this).data('price_excl_tax');
							}
						} else if ( $(this).is('.addon-select') ) {
							if ( $(this).val() ) {
								addon_cost = $(this).find('option:selected').data('price');
								addon_cost_incl_tax = $(this).find('option:selected').data('price_incl_tax');
								addon_cost_excl_tax = $(this).find('option:selected').data('price_excl_tax');
							}
						} else {
							if ( $(this).val() ) {
								addon_cost = $(this).data('price');
								addon_cost_incl_tax = $(this).data('price_incl_tax');
								addon_cost_excl_tax = $(this).data('price_excl_tax');
							}	
						}

						if ( ! addon_cost ) {
							addon_cost = 0;
							addon_cost_incl_tax = 0;
							addon_cost_excl_tax = 0;
						}

						price_data[ 'addons_prices' ][ item_id ] = parseFloat( price_data[ 'addons_prices' ][ item_id ] ) + parseFloat( addon_cost );
						price_data[ 'addons_prices_incl_tax' ][ item_id ] = parseFloat( price_data[ 'addons_prices_incl_tax' ][ item_id ] ) + parseFloat( addon_cost_incl_tax );
						price_data[ 'addons_prices_excl_tax' ][ item_id ] = parseFloat( price_data[ 'addons_prices_excl_tax' ][ item_id ] ) + parseFloat( addon_cost_excl_tax );

					} );
					
					component.$component_data.each(function( index, component_data ) {
						
						price_data[ 'prices' ][ item_id ]         += parseFloat( $(this).data( 'price' ) );
						price_data[ 'regular_prices' ][ item_id ] += parseFloat( $(this).data( 'regular_price' ) );
						
						var custom_data = $.isArray($(this).data('custom')) ? $.extend({}, $(this).data('custom')) : $(this).data('custom');
						
						if(typeof custom_data !== 'undefined' && !$.isEmptyObject(custom_data)) {
							
							price_data[ 'prices_incl_tax' ][ item_id ] += parseFloat( custom_data.all_prices.price.incl );
							price_data[ 'prices_excl_tax' ][ item_id ] += parseFloat( custom_data.all_prices.price.excl );
							
							price_data[ 'regular_prices_incl_tax' ][ item_id ] += parseFloat( custom_data.all_prices.regular_price.incl );
							price_data[ 'regular_prices_excl_tax' ][ item_id ] += parseFloat( custom_data.all_prices.regular_price.excl );
							
						}
					
					});

				} );
				
				return price_data;
				
			},

			/**
			 * Updates the composite totals and review/summary section + enables/disables the add-to-cart button
			 * When update_only is true, the composite is updated wihout changing the add-to-cart button state
			 */

			update_composite_task: function( update_only ) {

				var composite      = this;
				var form           = composite.$composite_form;
				var composite_data = composite.$composite_data;

				if ( typeof( update_only ) === 'undefined' ) {
					update_only = false;
				}
				
				var all_set = this.validate_components();
				var component_quantity = this.get_components_quantity();
				var price_data = this.get_prices();
				var out_of_stock       = [];
				var progression_style  = composite_data.data( 'progression_style' );
				var style              = composite.composite_layout;

				var composite_stock = composite_data.find( '.composite_wrap p.stock' );

				// Reset composite stock status
				if ( composite.composite_stock_status !== false ) {
					composite_stock.replaceWith( $( composite.composite_stock_status ) );
				} else {
					composite_stock.remove();
				}

				// In progressive/paged mode, when the progression style is 'strict' the active component must be the last to continue
				if ( ( style === 'progressive' || style === 'paged' ) && progression_style === 'strict' && ! form.find( '.multistep' ).last().hasClass( 'active' ) ) {
					composite.disable_add_to_cart();
					return false;
				}

				// Update paged layout summary state
				composite.update_summary();

				// Add to cart button state and price
				if ( all_set ) {

					if ( ( price_data[ 'per_product_pricing' ] == false ) && ( price_data[ 'price_undefined' ] == true ) ) {
						composite.disable_add_to_cart( wc_composite_params.i18n_unavailable_text );
						return false;
					}

					if ( price_data[ 'per_product_pricing' ] == true ) {

						price_data[ 'total' ]         	= 0;
						price_data[ 'total_incl_tax' ]  = 0;
						price_data[ 'total_excl_tax' ]  = 0;
						price_data[ 'regular_total' ] 	= 0;
						price_data[ 'regular_total_incl_tax' ] 	= 0;
						price_data[ 'regular_total_excl_tax' ] 	= 0;

						for ( var item_id_ppp in price_data[ 'prices' ] ) {

							price_data[ 'total' ]         += ( parseFloat( price_data[ 'prices' ][ item_id_ppp ] ) + parseFloat( price_data[ 'addons_prices' ][ item_id_ppp ] ) ) * component_quantity[ item_id_ppp ];
							price_data[ 'total_incl_tax' ]         += ( parseFloat( price_data[ 'prices_incl_tax' ][ item_id_ppp ] ) + parseFloat( price_data[ 'addons_prices_incl_tax' ][ item_id_ppp ] ) ) * component_quantity[ item_id_ppp ];
							price_data[ 'total_excl_tax' ]         += ( parseFloat( price_data[ 'prices_excl_tax' ][ item_id_ppp ] ) + parseFloat( price_data[ 'addons_prices_excl_tax' ][ item_id_ppp ] ) ) * component_quantity[ item_id_ppp ];
							
							price_data[ 'regular_total' ] += ( parseFloat( price_data[ 'regular_prices' ][ item_id_ppp ] ) + parseFloat( price_data[ 'addons_prices' ][ item_id_ppp ] ) ) * component_quantity[ item_id_ppp ];
							price_data[ 'regular_total_incl_tax' ] += ( parseFloat( price_data[ 'regular_prices_incl_tax' ][ item_id_ppp ] ) + parseFloat( price_data[ 'addons_prices_incl_tax' ][ item_id_ppp ] ) ) * component_quantity[ item_id_ppp ];
							price_data[ 'regular_total_excl_tax' ] += ( parseFloat( price_data[ 'regular_prices_excl_tax' ][ item_id_ppp ] ) + parseFloat( price_data[ 'addons_prices_excl_tax' ][ item_id_ppp ] ) ) * component_quantity[ item_id_ppp ];
							
						}

						price_data[ 'total' ]         += parseFloat( price_data[ 'base_price' ] );
						price_data[ 'total_incl_tax' ]         += parseFloat( price_data[ 'base_price_incl_tax' ] );
						price_data[ 'total_excl_tax' ]         += parseFloat( price_data[ 'base_price_excl_tax' ] );
						
						price_data[ 'regular_total' ] += parseFloat( price_data[ 'base_regular_price' ] );
						price_data[ 'regular_total_incl_tax' ]         += parseFloat( price_data[ 'base_regular_price_incl_tax' ] );
						price_data[ 'regular_total_excl_tax' ]         += parseFloat( price_data[ 'base_regular_price_excl_tax' ] );

					} else {

						price_data[ 'total' ]         = parseFloat( price_data[ 'base_price' ] );
						price_data[ 'total_incl_tax' ]         = parseFloat( price_data[ 'base_price_incl_tax' ] );
						price_data[ 'total_excl_tax' ]         = parseFloat( price_data[ 'base_price_excl_tax' ] );
						
						price_data[ 'regular_total' ] = parseFloat( price_data[ 'base_regular_price' ] );
						price_data[ 'regular_total_incl_tax' ] = parseFloat( price_data[ 'base_regular_price_incl_tax' ] );
						price_data[ 'regular_total_excl_tax' ] = parseFloat( price_data[ 'base_regular_price_excl_tax' ] );

						for ( var item_id_sp in price_data[ 'addons_prices' ] ) {

							price_data[ 'total' ]         += parseFloat( price_data[ 'addons_prices' ][ item_id_sp ] ) * component_quantity[ item_id_sp ];
							price_data[ 'total_incl_tax' ]         = parseFloat( price_data[ 'addons_prices_incl_tax' ][ item_id_sp ] ) * component_quantity[ item_id_sp ];
							price_data[ 'total_excl_tax' ]         = parseFloat( price_data[ 'addons_prices_excl_tax' ][ item_id_sp ] ) * component_quantity[ item_id_sp ];
							
							price_data[ 'regular_total' ] += parseFloat( price_data[ 'addons_prices' ][ item_id_sp ] ) * component_quantity[ item_id_sp ];
							price_data[ 'regular_total_incl_tax' ] += parseFloat( price_data[ 'addons_prices_incl_tax' ][ item_id_sp ] ) * component_quantity[ item_id_sp ];
							price_data[ 'regular_total_excl_tax' ] += parseFloat( price_data[ 'addons_prices_excl_tax' ][ item_id_sp ] ) * component_quantity[ item_id_sp ];
						
						}
					}

					var composite_addon = composite_data.find( '#product-addons-total' );

					if ( composite_addon.length > 0 ) {
						composite_addon.data( 'price', price_data[ 'total' ] );
						composite_data.trigger( 'woocommerce-product-addons-update' );
					}

					if ( price_data[ 'total' ] == 0 && price_data[ 'show_free_string' ] == true ) {
						composite_data.find( '.composite_price' ).html( '<p class="price"><span class="total">' + wc_composite_params.i18n_total + '</span>'+ wc_composite_params.i18n_free +'</p>' );
					} else {

						var sales_price_format   = wc_cp_woocommerce_number_format( wc_cp_number_format( price_data[ 'total' ] ) );
						var regular_price_format = wc_cp_woocommerce_number_format( wc_cp_number_format( price_data[ 'regular_total' ] ) );

						if ( price_data[ 'regular_total' ] > price_data[ 'total' ] ) {
							composite_data.find( '.composite_price' ).html( '<p class="price"><span class="total">' + wc_composite_params.i18n_total + '</span><del>' + regular_price_format + '</del> <ins>' + sales_price_format + '</ins></p>' );
						} else {
							composite_data.find( '.composite_price' ).html( '<p class="price"><span class="total">' + wc_composite_params.i18n_total + '</span>' + sales_price_format + '</p>' );
						}
					}

					var button_behaviour = composite_data.data( 'button_behaviour' );

					if ( button_behaviour !== 'new' ) {
						composite_data.find( '.composite_wrap' ).slideDown( 200 );
					} else {
						composite_data.find( '.composite_button button' ).prop( 'disabled', false ).removeClass( 'disabled' );
					}

					composite_data.find( '.composite_wrap' ).trigger( 'wc-composite-show-add-to-cart' );

				} else {

					// List out-of-stock selections
					if ( out_of_stock.length > 0 ) {

						var composite_out_of_stock_string = '<p class="stock out-of-stock">' + wc_composite_params.i18n_insufficient_stock + '</p>';

						var loop = 0;
						var out_of_stock_string = '';

						for ( var i in out_of_stock ) {

							loop++;

							if ( out_of_stock.length == 1 || loop == 1 ) {
								out_of_stock_string = out_of_stock[i];
							} else {
								out_of_stock_string = wc_composite_params.i18n_insufficient_item_stock_comma_sep.replace( '%s', out_of_stock_string ).replace( '%v', out_of_stock[i] );
							}
						}

						if ( composite_data.find( '.composite_wrap p.stock' ).length > 0 ) {
							composite_data.find( '.composite_wrap p.stock' ).replaceWith( $( composite_out_of_stock_string.replace( '%s', out_of_stock_string ) ) );
						} else {
							composite_data.find( '.composite_wrap .composite_price' ).after( $( composite_out_of_stock_string.replace( '%s', out_of_stock_string ) ) );
						}
					}

					if ( ! update_only ) {
						composite.disable_add_to_cart();
					}
					
				}

				// Update summary widget
				var widget = $( '.widget_composite_summary_content' );

				if ( widget.length > 0 ) {

					var price_clone = composite_data.find( '.composite_wrap .composite_price' ).clone();
					widget.append( price_clone.addClass( 'cp_clearfix' ) );
				}


			},

			/**
			 * Called when the Composite can't be added-to-cart - disables the add-to-cart button and builds a string with a human-friendly reason
			 */

			disable_add_to_cart: function( hide_message ) {

				var composite        = this;
				var composite_data   = composite.$composite_data;
				var button_behaviour = composite_data.data( 'button_behaviour' );

				if ( button_behaviour === 'new' ) {

					if ( typeof( hide_message ) === 'undefined' ) {
						var pending = composite.get_pending_components_string();
						if ( pending ) {
							hide_message = wc_composite_params.i18n_select_options.replace( '%s', pending );
						} else {
							hide_message = '';
						}
					}

					composite_data.find( '.composite_price' ).html( hide_message );
					composite_data.find( '.composite_button button' ).prop( 'disabled', true ).addClass( 'disabled' );

				} else {

					composite_data.find( '.composite_price' ).html( '' );
					composite_data.find( '.composite_wrap' ).slideUp( 200 );
				}

				composite_data.find( '.composite_wrap' ).trigger( 'wc-composite-hide-add-to-cart' );

			},
			
			/**
			 * Builds an array with all Components that require further user input
			 */
 
			get_pending_components: function() {
				
				var composite               = this;
				var composite_data 			= composite.$composite_data;
				var pending_components      = [];
				var progression_style  		= composite_data.data( 'progression_style' );
			
				$.each( composite.composite_components, function( index, component ) {
			
					var products = component.get_selected_product_ids();
					
					for(i in products) {
						
						var product_id = parseInt(products[i]);
						var item_set  = component.$component_data.eq(i).data( 'component_set' );
					
						if ( ( ! ( product_id > 0 ) && ! component.is_optional() ) || ( progression_style === 'strict' && component.$self.hasClass( 'blocked' ) ) || item_set == false || typeof( item_set ) === 'undefined' ) {
				
							pending_components.push( component.get_title() );
							
						}
						
					}
			
				} );
			
				return pending_components;
				
			},
			
			/**
			 * Builds an array with all Components that are active
			 */
 
			get_active_components: function() {
				
				var composite               = this;
				var composite_data 			= composite.$composite_data;
				var active_components      = [];
				var progression_style  		= composite_data.data( 'progression_style' );
			
				$.each( composite.composite_components, function( index, component ) {
			
					var products = component.get_selected_product_ids();
					
					for(i in products) {
						
						var product_id = parseInt(products[i]);
						var item_set  = component.$component_data.eq(i).data( 'component_set' );
					
						if ( ( ! ( product_id > 0 ) && ! component.is_optional() ) || ( progression_style === 'strict' && component.$self.hasClass( 'blocked' ) ) || item_set == false || typeof( item_set ) === 'undefined' ) {
				
							// pending
							
						}
						
						else {
							
							active_components.push( component.get_title() );
							
						}
						
					}
			
				} );
			
				return active_components;
				
			},
			
			/**
			 * Builds an object with all active Components Products
			 */
			
			get_active_components_products: function() {
				
				var composite               		= this;
				var composite_data 					= composite.$composite_data;
				var active_components_products      = {};
				var progression_style  				= composite_data.data( 'progression_style' );
			
				$.each( composite.composite_components, function( index, component ) {
			
					var products = component.get_selected_product_ids();
					
					active_components_products[component.get_title()] = [];
					
					for(i in products) {
						
						var product_id = parseInt(products[i]);
						
						if(product_id !== null && product_id > 0) {
							
							var item_set  = component.$component_data.eq(i).data( 'component_set' );
					
							if ( item_set == true && typeof( item_set ) !== 'undefined' ) {
					
								var product_title = $.trim(component.$self.find( '.component_options_select option:selected[value="' + product_id + '"]' ).data('title'));
								
								active_components_products[component.get_title()].push({id: product_id, title: product_title  });

								
							}
							
						}
						
					}
			
				} );
			
				return active_components_products;
				
			},

			/**
			 * Builds a string with all Components that require user input
			 */

			get_pending_components_string: function() {

				var pending_components = this.get_pending_components();
				var count = pending_components.length;
				var pending_components_string = '';

				if ( count > 0 ) {

					var loop = 0;

					for ( var i in pending_components ) {

						loop++;

						if ( count == 1 || loop == 1 ) {
							pending_components_string = '&quot;' + pending_components[ i ] + '&quot;';
						} else if ( loop == count ) {
							pending_components_string = wc_composite_params.i18n_select_options_and_sep.replace( '%s', pending_components_string ).replace( '%v', pending_components[ i ] );
						} else {
							pending_components_string = wc_composite_params.i18n_select_options_comma_sep.replace( '%s', pending_components_string ).replace( '%v', pending_components[ i ] );
						}
					}
				}

				return pending_components_string;
			}

		};

		wc_cp_composite_scripts[ container_id ].init();
		
		$(this).trigger('wc-composite-after-initialized');
		
	};

    $.fn.is_in_viewport = function( partial, hidden, direction ) {

    	var $w = $( window );

        if ( this.length < 1 ) {
            return;
        }

        var $t         = this.length > 1 ? this.eq(0) : this,
			t          = $t.get(0),
			vpWidth    = $w.width(),
			vpHeight   = $w.height(),
			direction  = (direction) ? direction : 'both',
			clientSize = hidden === true ? t.offsetWidth * t.offsetHeight : true;

        if (typeof t.getBoundingClientRect === 'function'){

            // Use this native browser method, if available.
            var rec = t.getBoundingClientRect(),
                tViz = rec.top    >= 0 && rec.top    <  vpHeight,
                bViz = rec.bottom >  0 && rec.bottom <= vpHeight,
                lViz = rec.left   >= 0 && rec.left   <  vpWidth,
                rViz = rec.right  >  0 && rec.right  <= vpWidth,
                vVisible   = partial ? tViz || bViz : tViz && bViz,
                hVisible   = partial ? lViz || rViz : lViz && rViz;

            if ( direction === 'both' ) {
                return clientSize && vVisible && hVisible;
            } else if ( direction === 'vertical' ) {
                return clientSize && vVisible;
            } else if ( direction === 'horizontal' ) {
                return clientSize && hVisible;
            }

        } else {

            var viewTop         = $w.scrollTop(),
                viewBottom      = viewTop + vpHeight,
                viewLeft        = $w.scrollLeft(),
                viewRight       = viewLeft + vpWidth,
                offset          = $t.offset(),
                _top            = offset.top,
                _bottom         = _top + $t.height(),
                _left           = offset.left,
                _right          = _left + $t.width(),
                compareTop      = partial === true ? _bottom : _top,
                compareBottom   = partial === true ? _top : _bottom,
                compareLeft     = partial === true ? _right : _left,
                compareRight    = partial === true ? _left : _right;

            if ( direction === 'both' ) {
                return !!clientSize && ( ( compareBottom <= viewBottom ) && ( compareTop >= viewTop ) ) && ( ( compareRight <= viewRight ) && ( compareLeft >= viewLeft ) );
            } else if ( direction === 'vertical' ) {
                return !!clientSize && ( ( compareBottom <= viewBottom ) && ( compareTop >= viewTop ) );
            } else if ( direction === 'horizontal' ) {
                return !!clientSize && ( ( compareRight <= viewRight ) && ( compareLeft >= viewLeft ) );
            }
        }
    };

	$( '.composite_form .composite_data' ).each( function() {
		$(this).wc_composite_form();
	} );

} );

/**
 * Construct a variable product selected attributes short description
 */

function wc_cp_get_variable_product_attributes_description( variations ) {

	var attribute_options        = variations.find( '.attribute-options' );
	var attribute_options_length = attribute_options.length;
	var meta                     = '';

	if ( attribute_options_length == 0 ) {
		return '';
	}

	attribute_options.each( function( index ) {

		var selected = $(this).find( 'select' ).val();

		if ( selected === '' ) {
			meta = '';
			return false;
		}

		meta = meta + $(this).data( 'attribute_label' ) + ': ' + $(this).find( 'select option:selected' ).text();

		if ( index !== attribute_options_length - 1 ) {
			meta = meta + ', ';
		}

	} );

	return meta;
}

/**
 * Updates images in the Review/Summary template
 */

function wc_cp_update_summary_element_image( element, img_src ) {

	var element_image = element.find( '.summary_element_image img' );

	if ( element_image.length == 0 || element_image.hasClass( 'norefresh' ) ) {
		return false;
	}

	var o_src = element_image.attr( 'data-o_src' );

	if ( ! img_src ) {

		if ( typeof( o_src ) !== 'undefined' ) {
			element_image.attr( 'src', o_src );
		}

	} else {

		if ( typeof( o_src ) === 'undefined' ) {
			o_src = ( ! element_image.attr( 'src' ) ) ? '' : element_image.attr( 'src' );
			element_image.attr( 'data-o_src', o_src );
		}

		element_image.attr( 'src', img_src );
	}

}

/**
 * Toggle-box handling
 */

function wc_cp_toggle_element( container, content ) {

	if ( container.hasClass( 'closed' ) ) {
		content.slideDown( 300, function() {
			container.removeClass( 'animating' );
		} );
		container.removeClass( 'closed' ).addClass( 'open animating' );
	} else {
		content.slideUp();
		container.removeClass( 'open' ).addClass( 'closed' );
	}

	return true;
}

/**
 * Checks if the default attributes of a variable product are valid based on the active Scenarios
 */

function wc_cp_has_valid_default_attributes( variation_form ) {

	var current_settings = {};

	variation_form.find( '.variations select' ).each( function() {

    	// Encode entities
    	var value = $(this).val();

		// Add to settings array
		current_settings[ $(this).attr( 'name' ) ] = value;

	} );

	var all_variations      = variation_form.data( 'product_variations' );
	var matching_variations = wc_cp_find_matching_variations( all_variations, current_settings );
	var variation           = matching_variations.shift();

	if ( variation ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Used by wc_cp_has_valid_default_attributes
 */

function wc_cp_find_matching_variations( product_variations, settings ) {

    var matching = [];

    for ( var i = 0; i < product_variations.length; i++ ) {

    	var variation = product_variations[i];

		if ( wc_cp_variations_match( variation.attributes, settings ) ) {
            matching.push( variation );
        }
    }

    return matching;
}

/**
 * Used by wc_cp_has_valid_default_attributes
 */

function wc_cp_variations_match( attrs1, attrs2 ) {

    var match = true;

    for ( var attr_name in attrs1 ) {

        var val1 = attrs1[ attr_name ];
        var val2 = attrs2[ attr_name ];

        if ( val1 !== undefined && val2 !== undefined && val1.length != 0 && val2.length != 0 && val1 != val2 ) {
            match = false;
        }
    }

    return match;
}

/**
 * Various helper functions
 */

function wc_cp_woocommerce_number_format( price ) {

	var remove     = wc_composite_params.currency_format_decimal_sep;
	var position   = wc_composite_params.currency_position;
	var symbol     = wc_composite_params.currency_symbol;
	var trim_zeros = wc_composite_params.currency_format_trim_zeros;
	var decimals   = wc_composite_params.currency_format_num_decimals;

	if ( trim_zeros == 'yes' && decimals > 0 ) {
		for (var i = 0; i < decimals; i++) { remove = remove + '0'; }
		price = price.replace( remove, '' );
	}

	var price_format = '';

	if ( position == 'left' ) {
		price_format = '<span class="amount">' + symbol + price + '</span>';
	} else if ( position == 'right' ) {
		price_format = '<span class="amount">' + price + symbol +  '</span>';
	} else if ( position == 'left_space' ) {
		price_format = '<span class="amount">' + symbol + ' ' + price + '</span>';
	} else if ( position == 'right_space' ) {
		price_format = '<span class="amount">' + price + ' ' + symbol +  '</span>';
	}

	return price_format;
}

function wc_cp_number_format( number ) {

	var decimals      = wc_composite_params.currency_format_num_decimals;
	var decimal_sep   = wc_composite_params.currency_format_decimal_sep;
	var thousands_sep = wc_composite_params.currency_format_thousand_sep;

    var n = number, c = isNaN( decimals = Math.abs( decimals ) ) ? 2 : decimals;
    var d = typeof( decimal_sep ) === 'undefined' ? ',' : decimal_sep;
    var t = typeof( thousands_sep ) === 'undefined' ? '.' : thousands_sep, s = n < 0 ? '-' : '';
    var i = parseInt( n = Math.abs( +n || 0 ).toFixed(c) ) + '', j = ( j = i.length ) > 3 ? j % 3 : 0;

    return s + ( j ? i.substr( 0, j ) + t : '' ) + i.substr(j).replace( /(\d{3})(?=\d)/g, '$1' + t ) + ( c ? d + Math.abs( n - i ).toFixed(c).slice(2) : '' );
}

function wc_cp_intersect_safe( a, b ) {

	var ai     = 0, bi = 0;
	var result = [];

	a.sort();
	b.sort();

	while ( ai < a.length && bi < b.length ) {

		if ( a[ai] < b[bi] ) {
			ai++;
		} else if ( a[ai] > b[bi] ) {
			bi++;
		/* they're equal */
		} else {
			result.push( a[ai] );
			ai++;
			bi++;
		}
	}

	return result;
}

jQuery( document ).ready( function($) {
	
	var wc_cp_ext_blockUI_opts = {
		message: null,
		overlayCSS:  {
			backgroundColor:	'#fff',
			opacity:			0.6,
			cursor:				'not-allowed'
		},
	};
	
	for(var container_id in wc_cp_composite_scripts) {
		
		var composite = wc_cp_composite_scripts[container_id];
		
		composite.$composite_data.data('sku', wc_composite_ext_product_data.sku);
		composite.$composite_data.data('weight', wc_composite_ext_product_data.weight);
		
		composite.$composite_form
	
		.bind('wc-composite-hide-add-to-cart', function() {
			
			composite.$composite_data.find('.composite_sku').text('');
			
		})
		
		.bind('wc-composite-show-add-to-cart', function() {
			
			var sku = wc_cp_ext_get_sku(composite.$composite_form);
			
			composite.$composite_data.data('sku', sku);
			
		})
		
		.bind('wc-composite-calculate-weight', function() {
			
			var weight = wc_cp_ext_get_weight(composite.$composite_form);
				
			composite.$composite_data.data('weight', weight);
			
		})
		
		.trigger('wc-composite-calculate-weight');
		
		composite.$components
		
			/**
			 * On changing a radio
			 */
			.on( 'change', '.component_options_radio', function( event ) {
		
				var item = $(this).closest( '.component' );
		
				if ( item.hasClass( 'disabled' ) || $(this).hasClass( 'disabled' ) )
					return true;
		
				$(this).blur();
				
				var value = $(this).val();
				
				var select = $(this).closest( '.component_options' ).find( 'select.component_options_select' );
		
				if ( select.val() != value ) {
					
					select.val( value ).trigger('change').trigger('focusin');
					
					$( '.component' ).trigger('woocommerce_variation_select_focusin');
					
				}
		
			} )
			
			/**
			 * On changing a checkbox
			 */
			.on( 'change', '.component_options_checkbox', function( event ) {
		
				var item = $(this).closest( '.component' );
		
				if ( item.hasClass( 'disabled' ) || $(this).hasClass( 'disabled' ) )
					return true;
		
				$(this).blur();
				
				var values = [];
				
				$(this).parents('.component_options').find('input.component_options_checkbox:checked').each(function() {
					
					values.push($(this).val());
					
				});
				
				if(values.length === 0) {
					
					values.push("");
					
				}
		
				var select = $(this).closest( '.component_options' ).find( 'select.component_options_select' );
		
				if ( select.val() != values ) {
					
					select.val( values ).trigger('change').trigger('focusin');
					
					$( '.component' ).trigger('woocommerce_variation_select_focusin');
					
				}
		
			} )
			
			/**
			 * On changing a select box
			 */
			.on( 'change', '.component_options_select', function( event ) {
				
				var values = $.isArray($(this).val()) ? $(this).val() : [$(this).val()];
				
				if(values) {
					
					var list = $(this).siblings('ul.component_options_radios, ul.component_options_checkboxes');
					
					var atLeastOne = false;
					
					for (var i = 0; i < values.length; i++) {
						
						value = values[i];
					
						if(value) {
							
							atLeastOne = true;
						
							var input = list.find('input[type="radio"][value="' + value + '"], input[type="checkbox"][value="' + value + '"]');
							
							if(!input.is(':checked')) {
								
								$(input).prop('checked', true);
								
							}	
							
						}	
						
					}
					
					if(!atLeastOne) {
						
						list.find( 'input[type="radio"]:not(.none), input[type="checkbox"]' ).prop('checked', false);
						
						$(this).find('option').eq(0).prop('selected', true);
						
					}	
					
				}
				
				composite.$composite_form.trigger('wc-composite-calculate-weight');
				
			} )
			
						
			/**
			 * Trigger hide radios if in scenario
			 */
			.trigger('woocommerce_variation_select_focusin')
			
			.find('.component_options_select option')
		
				/*
				 * Hide radios / checkboxes if incompatible to scenario
				*/
				.bind('wc-composite-selection-incompatible', function() {
					
					var value = $(this).val();
					
					var select = $(this).parent('select');	
					
					var list = $(select).siblings('ul.component_options_radios, ul.component_options_checkboxes');
					
					var radio = list.find('input[type="radio"][value="' + value + '"], input[type="checkbox"][value="' + value + '"]');
					
					radio.prop('checked', false).parents('li').block(wc_cp_ext_blockUI_opts).find('.blockOverlay').removeClass('blockOverlay');
					
				})
				
				/*
				 * Show radios / checkboxes if compatible to scenario
				*/
				.bind('wc-composite-selection-compatible', function() {
					
					var value = $(this).val();
					
					var select = $(this).parent('select');
					
					var list = $(select).siblings('ul.component_options_radios, ul.component_options_checkbox');
					
					var radio = list.find('input[type="radio"][value="' + value + '"], input[type="checkbox"][value="' + value + '"]');
					
					radio.parents('li').unblock();
					
				});
		
		
	}
	
});

function wc_cp_ext_get_sku(composite) {
	
	var composite_data =composite.find('.composite_data');
	var container_id   = composite_data.data( 'container_id' );
	var skus = [wc_composite_ext_product_data.sku];
	
	wc_cp_composite_scripts[ container_id ].$components.each(function() {
		
		jQuery(this).find('.component_options_select').each(function() {
			
			var sku_order = $(this).data('sku-order');
		
			if( sku_order ) {
				
				skus[sku_order] = $(this).find('option:selected').data('sku-build') ? $(this).find('option:selected').data('sku-build') : $(this).data('sku-default');
				
			}
			
		});
		
	});
	
	var	sku = skus.join('');
	
	return sku;
	
}

function wc_cp_ext_get_weight(composite) {
	
	var composite_data = composite.find('.composite_data');
	var container_id   = composite_data.data( 'container_id' );
	var weight = parseFloat(typeof wc_composite_ext_product_data.weight !== 'undefined' ? wc_composite_ext_product_data.weight : 0);
	
	wc_cp_composite_scripts[ container_id ].$components.each(function() {
		
		jQuery(this).find('.component_options_select option:selected').each(function() {
			
			if($(this).data('product-weight') && typeof $(this).data('product-weight') !== 'undefined' && $(this).data('product-weight') > 0)
				weight += parseFloat($(this).data('product-weight'));
			
		});
		
	});
	
	return weight.toFixed(1);
	
}