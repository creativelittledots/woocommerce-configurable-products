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
 	
 	String.prototype.ucwords = function() {
	 	
		return this.replace(/^(.)|\s+(.)/g, function ($1) {
			return $1.toUpperCase()
		});
	 	
 	};
 	
 	var form = $('.js-configurable-product-form'),
 		bindings = $('.js-configurable-product-bind');
 		
 	var Field = Backbone.Model.extend({
		
		defaults : {
			component_id: null,
			label: '',
			placeholder: '',
			value: '',
			step: 1,
			min: 0,
			max: null,
			suffix: '',
			price_formula: '',
			scenarios: []
		},
		
		to_selection: function() {
			
			var title = this.get('value').toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") + this.get('suffix'),
				max = Math.max(this.get('value'), this.get('min')),
				value = this.get('max') ? Math.min(max, this.get('max')) : max,
				error = value != this.get('value');
				
			return {
				title: title,
				value: value,
				error: error,
				formula: this.get('price_formula')
			};
			
		}
		
	});
	
	var Option = Backbone.Model.extend({
		initialize: function() {
			this.on('change:' + this.get('display_price_field'), this.set_display);
			this.set('price_incl_tax', this.get('display_price_incl_tax'));
			this.set('price_excl_tax', this.get('display_price_excl_tax'));
			this.set('formula', this.get('display_formula'));
			this.set('starting_price_incl_tax', this.get('price_incl_tax'));
			this.set('starting_price_excl_tax', this.get('price_excl_tax'));
			this.set('options', new Options(this.get('options')));
			this.on('change:available', function() {
				this.set('selected', this.get('available') ? this.get('selected') : false);
			});
			this.listenTo(this.get('options'), 'change:selected', this.evaluate);
			this.on('change:subselected', function(option, selected_id) {
				this.get('options').each(function(option) {
					option.set('selected', option.id === selected_id);
				}, this);
			});
			this.set_display();
			this.evaluate();
		},
		defaults: {
			id: null,
			title: '',
			display: '',
			display_price_field: 'price_excl_tax',
			price_incl_tax: 0,
			price_excl_tax: 0,
			display_price_incl_tax: 0,
			display_price_excl_tax: 0,
			starting_price_incl_tax: 0,
			starting_price_excl_tax: 0,
			formatted_price: '',
			formula: '{p}',
			display_formula: '{p}',
			weight: 0,
			sku: '',
			recommended: false,
			available: false,
			scenarios: [],
			options: [],
			selections: [],
			selected: false,
			subselected: 0
		},
		evaluate: function() {
			var selections = this.get('options').where({selected: true}) || [];
			this.set('selections', new Options(selections));
		},
		set_display: function() {
			this.set('formatted_price', wc_cp_product_data.priced_per_product && this.get(this.get('display_price_field')) > 0 ? '+' + wc_cp_params.currency + parseFloat(this.get(this.get('display_price_field'))).formatMoney() : '');
    		this.set('display', this.get('title') + ( this.get('formatted_price') ? ' <strong>[' + this.get('formatted_price') + ']</strong>' : '' ) + ( this.get('recommended') ? ' <em>Recommended</em>' : '' ) );
		},
		calculate_price: function(components) {
			this.set('price_incl_tax', this.get_calculated_price(this.get('starting_price_incl_tax'), components));
			this.set('price_excl_tax', this.get_calculated_price(this.get('starting_price_excl_tax'), components));
		},
		get_calculated_price: function(price, components) {
			var formula = this.get('formula').replace('{p}', price);
			if(formula.indexOf('{n') > -1) {
				var i = 0;
				components.each(function(component) {
					var selections = component.get('selections');
					i++
					formula = formula.replace('{n'+i+'}', selections instanceof PriceFormulas ? selections.at(0).get('value') : 0);
					if(formula.indexOf('{n') == -1) {
						return false;	
					}
				});
			}
			return eval(formula);
		}
	});
	
	var Options = Backbone.Collection.extend({
		model: Option
	});
	
	var PriceFormula = Backbone.Model.extend({
		initialize: function() {
			this.calculate_price();
		},
		defaults: {
			title: '',
			value: 0,
			formula: '',
			error: false,
			price_incl_tax: 0,
			price_excl_tax: 0
		},
		calculate_price: function() {
			this.set('price_incl_tax', this.get_price(true));
			this.set('price_excl_tax', this.get_price());
		},
		get_price: function(incl_tax) {
			var price = eval(this.get('formula').replace('{n}', this.get('value'))),
				tax_rate = 1.2; // dynamically please

			return incl_tax ? price*tax_rate : price;
		}
	});
	
	var PriceFormulas = Backbone.Collection.extend({
		model: PriceFormula
	});
	
	var Component = Backbone.Model.extend({
		initialize: function(opts) {
			this.set('empty_text', this.get('empty_text') + ( this.get('optional') ? ' (optional)' : ' (required)'));
			this.set('options', new Options(this.get('options')));
			this.set('components', new Components(this.get('components')));
			this.set('field', new Field(this.get('field')));
			this.on('change:no_of_selections', function() {
				this.set('show_summary', this.get('no_of_selections') ? true : false);
				this.set('show_title', this.get('no_of_selections') > 1 || ( this.get('no_of_selections') && this.get('field').get('id') ) );
			});
			this.on('change:subselected', function(component, selected_id) {
				this.get('options').each(function(option) {
					option.set('selected', option.id === selected_id);
				}, this);
			});
			this.listenTo(this.get('options'), 'change:selected', this.evaluate);
			this.listenTo(this.get('options'), 'change:selections', function() {
				this.trigger('change:selections', this, this.get('selections'));
			}, this);
			this.listenTo(this.get('components'), 'change:selections', function() {
				var selections = this.get('components').filter(function(component) {
					return component.get('selections');
				});
				this.set('show_summary', selections.length ? true : false);
				this.set('show_title', selections.length ? true : false);
				this.trigger('change:selections', this, this.get('selections'));
			});
			switch(this.get('style')) {
				case 'number':
				case 'text':
					this.get('field').on('change:value', function(field) {
						
						var Collection = this.get('style') == 'number' ? PriceFormulas : Backbone.Collection;
					
						this.set('selections', new Collection( field.get('value') ? [ field.to_selection() ] : [] ) );
						this.set('no_of_selections', field.get('value') ? 1 : 0);
						
					}, this).trigger('change:value', this.get('field'), this.get('field').get('value'));
				break;
				default:
					switch(this.get('style')) {
						case 'checkboxes': break;
						default:
							var selected = this.get('options').findWhere({selected: true}) || [];
							this.set('subselected', selected['id'] || 0);
						break;
					}
					this.evaluate();
				break;
			}
		},
		defaults: {
			id: null,
			name : '',
			title: '',
			style: 'dropdown',
			field: null,
			options: [],
			selections: [],
			subselected: 0,
			components: [],
			no_of_selections: 0,
			optional: false,
			error: false,
			sku_order: 0,
			sku_default: '',
			affect_sku: false,
			empty_text: 'None',
            updating: false,
			sovereign: false,
			available: true,
			show_summary: false,
			show_title: false
		},
		evaluate: function() {
			var selections = this.get('options').where({selected: true}) || [];
			this.set('selections', new Options(selections));
			this.set('no_of_selections', selections.length);
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
	
	var Evaluator = Backbone.Model.extend({
		
		defaults: {
			product: null,
			price_incl_tax: 0,
			price_excl_tax: 0,
			weight: 0,
			no_of_errors: 0,
			no_of_selections: 0,
			sku: '',
			active_scenarios: [],
			selections: [],
			errors: []
		},
		
		evaluate_selection: function(selection, component, child) {
			
			var product = this.get('product');
			
			this.attributes.selections.push({
				title: component.get('title'),
				selected: selection.get('title').ucwords() === component.get('title').ucwords() ? '✓' : ( child ? '- ' : '' ) + selection.get('title'),
				product_id: selection.get('product_id'),
				affect_stock: selection.get('affect_stock')
			});
				
			if( selection instanceof Option ) {
				
				this.attributes.price_incl_tax += parseFloat(selection.get('price_incl_tax'));
				this.attributes.price_excl_tax += parseFloat(selection.get('price_excl_tax'));
				this.attributes.weight += parseFloat(selection.get('weight') || 0);
				
				if( product.get('build_sku') && component.get('affect_sku') ) {
					
					this.attributes.sku[component.get('sku_order')] = this.attributes.sku[component.get('sku_order')] || [];
					
					this.attributes.sku[component.get('sku_order')].push(selection.get('sku'));
					
				}
				
				if( this.attributes.active_scenarios.length ) {
					
					// this is not the first in the progression
					
					for(var i = 0; i < this.attributes.active_scenarios.length; i++) {
						if( 0 > $.inArray( this.attributes.active_scenarios[i], selection.get('scenarios') ) ) {
                            this.attributes.active_scenarios.splice(i, 1);
                            i--;
                        }
					};
					
				} else {
					
					// this is the first in the progression
					
					$.extend(this.attributes.active_scenarios, selection.get('scenarios'));
					
				}

				this.attributes.no_of_selections++;
				
				component.set('error', false);
				
				selection.get('selections').each(function(selection) {
				
					this.evaluate_selection(selection, component, true);
				
				}, this);
				
			} else if( selection instanceof PriceFormula ) {

				this.attributes.price_incl_tax += parseFloat(selection.get('price_incl_tax'));
				this.attributes.price_excl_tax += parseFloat(selection.get('price_excl_tax'));
				
				component.set('error', selection.get('error'));
				
				if( component.get('error') ) {
					
					this.attributes.no_of_errors++;
					
				} else {
					
					this.attributes.no_of_selections++;
					
				}
				
			}
			
		},
		
		evaluate_component: function(component) {
				
			var product = this.get('product'),
				options = component.get('options'),
				selections = component.get('selections');
			
			if( options.length ) {
				
				options.each(function(option) {
					
					option.calculate_price( product.get('components') );
					
				}, this);
				
			}
			
			if( selections.length ) {
			
				selections.each(function(selection) {
				
					this.evaluate_selection(selection, component);
				
				}, this);
				
			} else {
				
				if( product.get('build_sku') && component.get('affect_sku') ) {
				
				    this.attributes.sku[component.get('sku_order')] = component.get('sku_default');
				    
				}
						
				if( ! component.get('optional') && component.get('source') !== 'subcomponents' ) {
					
					this.attributes.no_of_errors++;
					
					component.set('error', true);
					
				}
				
			}
			
			component.get('components').each(this.evaluate_component, this);
			
		},
		
		evaluate_option: function(option, component) {
				
			option.set('available', component.get('sovereign') || ! this.get('product').get('scenarios').length || _.intersection( _.map(option.get('scenarios'), Number), _.map(this.attributes.active_scenarios, Number) ).length ? true : false);
			
			if( ! option.get('available') ) {
				
				option.set('selected', false);
				
				if( component.get('subselected') == option.id ) {
					
					component.set('subselected', 0);
					
				}
				
			}
                
			option.get('options').each(function(option) {
			
				this.evaluate_option(option, component);
				
			}, this);
			
		},
		
		evaluate_options: function(component) {
				
			component.get('options').each(function(option) {
			
				this.evaluate_option(option, component);
				
			}, this);
			
			component.set('available', ! component.get('field') || component.get('sovereign') || ! this.get('product').get('scenarios').length || _.intersection( _.map(component.get('field').get('scenarios'), Number), _.map(this.attributes.active_scenarios, Number) ).length ? true : false);
			
			component.get('components').each(this.evaluate_options, this);
			
		},
		
		evaluate_errors: function(component) {
			
			if( component.get('error') ) {
				
				this.attributes.errors.push({
					title: component.get('title')
				});				
			}
			
			component.get('components').each(this.evaluate_errors, this);
			
		},
	
		evaluate: function() {
			
			var product = this.get('product');
			
			this.attributes.price_incl_tax = parseFloat(product.get('base_price_incl_tax'));
			this.attributes.price_excl_tax = parseFloat(product.get('base_price_excl_tax'));
			this.attributes.weight = parseFloat(product.get('base_weight'));
			this.attributes.sku = product.get('build_sku') ? [product.get('base_sku')] : [product.get('sku')];
			this.attributes.no_of_errors = 0;
			this.attributes.no_of_selections = 0;
			this.attributes.active_scenarios = [];
			this.attributes.selections = [];
			this.attributes.errors = [];
			
			product.get('components').each(this.evaluate_component, this);
			
			this.attributes.active_scenarios = this.attributes.active_scenarios.length ? $.unique(this.attributes.active_scenarios) : product.get('scenarios').pluck('id');
			
			product.get('components').each(this.evaluate_options, this);
			
			this.set('price_incl_tax', Math.max(this.attributes.price_incl_tax, parseFloat(product.get('min_price_incl_tax'))).formatMoney());
			this.set('price_excl_tax', Math.max(this.attributes.price_excl_tax, parseFloat(product.get('min_price_excl_tax'))).formatMoney());
			this.set('weight', this.attributes.weight.toFixed(2).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","));
			
			this.set('sku', [].concat.apply([], this.attributes.sku).join(''));
			
			this.set('selections', new Backbone.Collection( this.get('selections') ) );
			
			product.get('components').each(this.evaluate_errors, this);
			
			this.set('errors', new Backbone.Collection( this.get('errors') ) );
			
		}
		
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
			min_price_incl_tax: 0,
			min_price_excl_tax: 0,
			base_price_incl_tax: 0,
			base_price_excl_tax: 0,
			base_weight: 0,
			base_sku: '',
			build_sku: false,
			price_incl_tax: 0,
			price_excl_tax: 0,
			weight: 0,
			quantity: 1,
			no_of_errors: 0,
			no_of_selections: 0,
			selections: [],
			errors: [],
			adding_to_cart: false
		},
		evaluate: function() {
			
			form.block({
				message: null,
				overlayCSS: {
					opacity: 0.6
				}
			});
			
			var evaluator = new Evaluator({
				product: this
			});
			
			evaluator.evaluate();
			
			this.set('price_incl_tax', evaluator.get('price_incl_tax'));
			this.set('price_excl_tax', evaluator.get('price_excl_tax'));
			this.set('weight', evaluator.get('weight'));
			this.set('no_of_selections', evaluator.get('no_of_selections'));
			this.set('no_of_errors', evaluator.get('no_of_errors'));
			this.set('errors', evaluator.get('errors'));
			this.set('selections', evaluator.get('selections'));
			this.set('sku', evaluator.get('sku'));
			
			form.unblock();
				
		},
		add_to_cart_click: function(e) {
			
			e.preventDefault();
			
			form.trigger('submit');
			
		},
		add_to_cart: function(e) {
    		
    		if( ! product.get( 'adding_to_cart' ) && ! product.get('no_of_errors') ) {
        		
        		product.set( 'adding_to_cart', true );
			
    			e.preventDefault();
    			
    			var url = wc_add_to_cart_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'add_to_cart' ),
    				data = {
    					'product_id': product.get('id'),
    					'product_sku': product.get('sku'),
    					'quantity': parseInt( product.get('quantity') ),
    					'price_incl_tax': parseFloat( product.get('price_incl_tax').replace(',', '') ),
    					'price_excl_tax': parseFloat( product.get('price_excl_tax').replace(',', '') ),
						'weight': parseFloat( product.get('weight') ),
    					'display_weight': product.get('weight') + wc_cp_product_data.weight_unit,
    					'selections': product.get('selections').toJSON()
    				},
    				button = $('.configurable_add_to_cart_button').eq(0);
    			
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
		return parseFloat(value) > parseFloat(arg);
	}
	
	rivets.formatters['>='] = function (value, arg) {
		return parseFloat(value) >= parseFloat(arg);
	}
	
	rivets.formatters['<'] = function (value, arg) {
		return parseFloat(value) < parseFloat(arg);
	}
	
	rivets.formatters['<='] = function (value, arg) {
		return parseFloat(value) <= parseFloat(arg);
	}
	
	rivets.formatters['&&'] = function(comparee, comparator) {
	    return comparee && comparator;
	};
	
	rivets.formatters['^'] = function(comparee, comparator) {
	    return comparee || comparator;
	};
	
	rivets.formatters['IN'] = function (value, arg) {
		return arg.indexOf(value) > -1;
	};
	
	rivets.formatters.append = function(comparee, comparator) {
	    return comparee + comparator;
	};
	
	rivets.configure({
	  	handler: function(context, ev, binding) {
			this.call(binding.model, ev, context)
	  	}
	});
	
	rivets.binders['attr-*'] = function(el, value) {
	    var attrToSet = this.type.substring(this.type.indexOf('-')+1)
	
	    value && el.setAttribute(attrToSet, attrToSet);
	}
	
	rivets.components['component'] = {
		template: function() {
			return document.querySelector('.js-component-component').innerHTML;
		},
		initialize: function(el, data) {
			return data;
		}
	};
	
	rivets.components['component_inner'] = {
		template: function() {
			return document.querySelector('.js-component-component_inner').innerHTML;
		},
		initialize: function(el, data) {
			return data;
		}
	};
	
	rivets.components['selection'] = {
		template: function() {
			return document.querySelector('.js-component-selection').innerHTML;
		},
		initialize: function(el, data) {
			data.depth++;
			return data;
		}
	};
	
	rivets.bind( bindings, {
    	product: product
	});
	
});