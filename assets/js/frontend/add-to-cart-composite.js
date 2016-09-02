jQuery(document).ready(function($) {
	
	// wc_checkout_params is required to continue, ensure the object exists
	if ( typeof wc_cp_product_data === 'undefined' ) {
		return false;
	}
	
	$.blockUI.defaults.overlayCSS.cursor = 'default';
	
	Number.prototype.formatMoney = function(c, d, t) {
		
		var n = this, 
		    c = isNaN(c = Math.abs(c)) ? 2 : c, 
		    d = d == undefined ? "." : d, 
		    t = t == undefined ? "," : t, 
		    s = n < 0 ? "-" : "", 
		    i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", 
		    j = (j = i.length) > 3 ? j % 3 : 0;
		    
		return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
		
 	};
 	
 	var form = $('.js-composite-product-form'),
 		bindings = $('.js-composite-product-bind');
	
	var Option = Backbone.Model.extend({
		initialize: function() {
			this.on('change:is_recommended', this.display);
			this.on('change:available', this.deselect);
			this.set('formatted_price', this.get('price') > 0 ? wc_cp_params.currency + parseFloat(this.get('price')).formatMoney() : '');
			this.display();
		},
		defaults: {
			id: null,
			title: '',
			display: '',
			price: 0,
			price_incl_tax: 0,
			formatted_price: '',
			weight: 0,
			sku: '',
			is_recommended: 0,
			available: false,
			scenarios: [],
			selected: 0
		},
		display: function() {
    		this.set('display', this.get('title') + ( this.get('formatted_price') ? ' <strong>[+' + this.get('formatted_price') + ']</strong>' : '' ) + ( this.get('is_recommended') ? ' <em>Recommended</em>' : '' ) );
		},
		select: function() {
			this.set('selected', this.get('id'));
		},
		deselect: function() {
    		if(!this.get('available')) {
			    this.set('selected', 0);
            }
		}
	});
	
	var Options = Backbone.Collection.extend({
		model: Option
	});
	
	var Component = Backbone.Model.extend({
		initialize: function(opts) {
			this.set('empty_text', this.get('empty_text') + ( this.get('optional') ? ' (optional)' : ' (required)'));
			this.set('options', new Options(this.get('options')));
			this.set('selections', new Options(this.get('selections')));
			this.listenTo(this.get('options'), 'change:selected', this.update_selections);
			this.on('change:selections', this.maybe_show_tag_number_field);
			this.on('change:selections', this.update_selected);
			if(this.get('default_id') > 0) {
    			var option = this.get('options').get( this.get('default_id') );
                if( option instanceof Option ) {
    				option.set('selected', option.get('id'));
    			}
			}
			if(this.get('recommended_id') > 0) {
    			var option = this.get('options').get( this.get('recommended_id') );
                if( option instanceof Option ) {
    				option.set('is_recommended', 1); 
    			}
			}
			this.update_selected();
			this.maybe_show_tag_number_field();
		},
		defaults: {
			id: null,
			name : '',
			title: '',
			style: 'dropdown',
			options: [],
			selections: [],
			no_of_selections: 0,
			selected: 0,
			first: 0,
			optional: true,
			error: false,
			sku_order: 0,
			sku_default: '',
			affect_sku: false,
			empty_text: 'None',
			use_tag_numbers: false,
			show_tag_number_field: false,
			tag_number: '',
			recommended_id: 0,
			default_id: 0,
<<<<<<< HEAD
			updating: false,
			sovereign: false,
=======
			updating: false
>>>>>>> origin/NewProd
		},
		is_style: function(style) {
			return this.get('style') == style;	
		},
		select: function(e) {
			
			// dropdown only
			
			if( $(e.target).val() ) {
			
				var option = this.get('options').get( $(e.target).val() );
				
				if( option ) { 
				
					option.set('selected', option.get('id'));
					this.set('first', option.get('id'));
					
				} else {
					
					this.deselect();
					
				}
				
			} else {
				
				this.deselect();
				
			}
			
		},
		deselect: function() {
			
			this.get('options').each(function(option) {
				
				option.set('selected', 0);
				
			});
			
		},
		update_selections: function(model) {
			
			if( ! this.get('updating') ) {
			
				this.set('updating', true);
				
				var component = this,
					selections = new Options();
			
				component.get('options').each(function(option) {
					
					if( option.get('selected') ) {
					
						if( ! component.is_style( 'checkboxes' ) ) {
							
							if( model instanceof Option ) {
							
								if( option.get('id') != model.get('id') ) {
							
									option.set('selected', 0);
									
								} else {
    								
    								selections.push(option);
    								
								}
								
							}
							
						} else {
							
							if( model instanceof Option ) {
								
								if( option.get('id') != model.get('id') ) {
								
									selections.push(option);
								
								}
								
							}
							
						}
						
					}
					
				});	
				
				if( component.get('options').at(0) ) {
				
				    this.set('first', component.get('options').at(0).get('id')); // for dropdowns
				    
				}
				
				if( model instanceof Option ) {
					
					if( model.get('selected') ) {
				
						selections.push(model);
						
					}
					
				}
				
				this.set('selections', selections);
				this.set('no_of_selections', selections.length);
				
				this.set('updating', false);
				
			}
			
		},
		update_selected: function() {
			
			var selections = this.get('selections');
		
			if( selections.length ) {
				
				this.set('selected', selections.at(0).get('id'));
				
			} else {
				
				this.set('selected', 0);
				
			}
			
		},
		maybe_show_tag_number_field: function() {
			
			var selections = this.get('selections');
			
			if( this.get('use_tag_numbers') && selections.length ) {
				
				this.set('show_tag_number_field', true);
				
			}
			
		},
		close: function(e, el) {
			
			e.preventDefault();
			
			$(el).closest('.component').toggleClass('closed').find('.component_inner').slideToggle();
			
		}
	});
	
	var Components = Backbone.Collection.extend({
		model: Component
	});
	
	var Scenario = Backbone.Model.extend({
		defaults: {
			id: null,
			actions: [],
			masked_components: []
		}
	});
	
	var Scenarios = Backbone.Collection.extend({
		model: Scenario
	});
	
	var Product = Backbone.Model.extend({
		initialize: function(){
			this.set('components', new Components(this.get('components')));
			this.set('scenarios', new Scenarios(this.get('scenarios')));
			this.listenTo(this.get('components'), 'change:selections', this.evaluate);
			this.evaluate();
		},
		defaults: {
			id: null,
			sku: '',
			weight: 0,
			components: [],
			scenarios: [], 
			min_price: 0,
			min_price_incl_tax: 0,
			base_price: 0,
			base_weight: 0,
			base_sku: '',
			build_sku: false,
			price: 0,
			price_incl_tax: 0,
			weight: 0,
			quantity: 1,
			errors: 0,
			selections: 0,
			adding_to_cart: false
		},
		evaluate: function() {
			
			form.block({
				message: null,
				overlayCSS: {
					opacity: 0.6
				}
			});
			
			var product = this;
			    min_price = parseFloat(this.get('min_price')),
			    min_price_incl_tax = parseFloat(this.get('min_price_incl_tax')),
				price = parseFloat(this.get('base_price')),
				price_incl_tax = parseFloat(this.get('base_price_incl_tax')),
				weight = parseFloat(this.get('base_weight')),
				sku = [this.get('base_sku')],
				errors = 0,
				no_of_selections = 0,
				active_scenarios = [];
			
			product.get('components').each(function(component) {
				
				var selections = component.get('selections');
				
				if( selections.length ) {
				
					selections.each(function(selection) {
					
						if( selection instanceof Option ) {
							
							price += parseFloat(selection.get('price'));
							price_incl_tax += parseFloat(selection.get('price_incl_tax'));
							weight += parseFloat(selection.get('weight'));
							
							if( product.get('build_sku') && component.get('affect_sku') ) {
								
								sku[component.get('sku_order')] = selection.get('sku');
								
							}
							
							if( active_scenarios.length ) {
    							
    							// this is not the first in the progression
    							
    							$.each(active_scenarios, function(index, scenario) {
        							
                                    if( 0 > $.inArray( scenario, selection.get('scenarios') ) ) {
                                        
                                        active_scenarios.splice(index, 1);
                                        
                                    }
        							
    							});
    							
							} else {
    							
    							// this is the first in the progression
    							
    							$.extend(active_scenarios, selection.get('scenarios'));
    							
							}
		
							no_of_selections++;
							
							component.set('error', false);
							
						}
						
					});
					
				} else {
    				
    				sku[component.get('sku_order')] = component.get('sku_default');
							
					if( ! component.get('optional') ) {
						
						errors++;
						
						component.set('error', true);
						
					}
					
				}
				
			});
			
			active_scenarios = active_scenarios.length ? $.unique(active_scenarios) : product.get('scenarios').pluck('id');
			
			product.get('components').each(function(component) {
				
				component.get('options').each(function(option) {
					
<<<<<<< HEAD
					if( active_scenarios.length && ! component.get('sovereign') ) {
=======
					if( active_scenarios.length ) {
>>>>>>> origin/NewProd
							
						var available = false;
						
						if( option.get('scenarios').length ) {
					
							$.each(option.get('scenarios'), function(index, scenario_id) {
								
								if( $.inArray( scenario_id, active_scenarios ) > -1 )  {
									
									available = true;
									
									return true;
									
								}
								
							});
							
						}
						
						if( available ) {
							
							option.set('available', true);
							
						} else {
							
							option.set('available', false);
							
						}
						
					} else {
						
						option.set('available', true);
						
					}
					
				});
				
			});
			
			this.set('price', Math.max(price, min_price).formatMoney());
			this.set('price_incl_tax', Math.max(price_incl_tax, min_price_incl_tax).formatMoney());
			this.set('weight', weight.toFixed(1));
			this.set('errors', errors);
			this.set('selections', no_of_selections);
			this.set('sku', sku.join(''));
			
			form.unblock();
				
		},
		get_selections: function() {
		
			var selected = {};
			
			this.get('components').each(function(component) {
				
				var selections = component.get('selections');
				
				selected[component.get('id')] = [];
				
				if( selections ) {
					
					selections.each(function(selection) {
						
						selected[component.get('id')].push({
							title: component.get('title'),
							selected: selection.get('title') + ( component.get('tag_number') ? ' <em>Tag Number: ' + component.get('tag_number') + '</em>' : '' )
						});
						
					});
					
				} else {
					
					selected[component.get('id')].push({
						title: component.get('title'),
						selected: component.get('empty_text')
					});
					
				}
				
			});
			
			return selected;	
			
		},
		add_to_cart_click: function(e) {
			
			e.preventDefault();
			
			form.trigger('submit');
			
		},
		add_to_cart: function(e) {
    		
    		if( ! product.get( 'adding_to_cart' ) ) {
        		
        		product.set( 'adding_to_cart', true );
			
    			e.preventDefault();
    			
    			var url = wc_add_to_cart_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'add_to_cart' ),
    				data = {
    					'product_id': product.get('id'),
    					'product_sku': product.get('sku'),
    					'quantity': product.get('quantity'),
    					'price': product.get('price'),
    					'weight': product.get('weight'),
    					'selections': product.get_selections()
    				},
    				button = $('.composite_add_to_cart_button').eq(0);
    			
    			form.block({
    				message: null,
    				overlayCSS: {
    					opacity: 0.6
    				}
    			});
    			
    			// Trigger event
    			$( document.body ).trigger( 'adding_to_cart', [ button, data ] );
    			
    			$.post(url, data, function(response) {
    				
    				if ( ! response ) {
    					return;
    				}
    
    				var this_page = window.location.toString();
    
    				this_page = this_page.replace( 'add-to-cart', 'added-to-cart' );
    
    				if ( response.error && response.product_url ) {
    					window.location = response.product_url;
    					return;
    				}
    
    				// Redirect to cart option
    				if ( wc_add_to_cart_params.cart_redirect_after_add === 'yes' ) {
    
    					window.location = wc_add_to_cart_params.cart_url;
    					return;
    
    				} else {
    
    					var fragments = response.fragments;
    					var cart_hash = response.cart_hash;
    
    					// Block fragments class
    					if ( fragments ) {
    						$.each( fragments, function( key ) {
    							$( key ).addClass( 'updating' );
    						});
    					}
    
    					// Replace fragments
    					if ( fragments ) {
    						$.each( fragments, function( key, value ) {
    							$( key ).replaceWith( value );
    						});
    					}
    
    				}
    				
    				// Trigger event so themes can refresh other areas
    				$( document.body ).trigger( 'added_to_cart', [ fragments, cart_hash, button ] );
    				
    			}, 'json').always(function(response) {
    				
    				console.log(response);
    				
    				form.unblock();
    				
    				product.set( 'adding_to_cart', false );
    				
    			});
    			
            }
			
		}
	});
	
	var product = new Product(wc_cp_product_data);
	
	form.on('valid.fndtn.abide', product.add_to_cart);
	
	rivets.formatters['='] = function (value, arg) {
		return value == arg;
	}
	
	rivets.formatters['!='] = function (value, arg) {
		return value != arg;
	}
	
	rivets.formatters['!'] = function (value) {
		return !value;
	}
	
	rivets.formatters['>'] = function (value, arg) {
		return value > arg;
	}
	
	rivets.formatters['>='] = function (value, arg) {
		return value >= arg;
	}
	
	rivets.formatters['<'] = function (value, arg) {
		return value < arg;
	}
	
	rivets.formatters['<='] = function (value, arg) {
		return value <= arg;
	}
	
	rivets.formatters.and = function(comparee, comparator) {
	    return comparee && comparator;
	};
	
	rivets.formatters.or = function(comparee, comparator) {
	    return comparee || comparator;
	};
	
	rivets.formatters.append = function(comparee, comparator) {
	    return comparee + comparator;
	};
	
	rivets.configure({
	  	handler: function(context, ev, binding) {
			this.call(binding.model, ev, context)
	  	}
	});
	
	rivets.bind( bindings, {
    	product: product
	});
	
});