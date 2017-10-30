jQuery( function($) {
	
	// wc_cp_edit_product is required to continue, ensure the object exists
	if ( typeof wc_cp_admin_params === 'undefined' ) {
		return false;
	}
	
	$.blockUI.defaults.overlayCSS.cursor = 'default';
	
	var bindings = $('.js-cp-product-bind');
	
	function wc_cp_help_tips() {
		
		$( '.help_tip' ).tipTip({
			'attribute': 'tip',
			'fadeIn':    50,
			'fadeOut':   50,
			'delay':     200
		});
		
	}
	
	function wc_cp_get_select_format_string() {
		return {
			'language': {
				errorLoading: function() {
					// Workaround for https://github.com/select2/select2/issues/4355 instead of i18n_ajax_error.
					return wc_enhanced_select_params.i18n_searching;
				},
				inputTooLong: function( args ) {
					var overChars = args.input.length - args.maximum;

					if ( 1 === overChars ) {
						return wc_enhanced_select_params.i18n_input_too_long_1;
					}

					return wc_enhanced_select_params.i18n_input_too_long_n.replace( '%qty%', overChars );
				},
				inputTooShort: function( args ) {
					var remainingChars = args.minimum - args.input.length;

					if ( 1 === remainingChars ) {
						return wc_enhanced_select_params.i18n_input_too_short_1;
					}

					return wc_enhanced_select_params.i18n_input_too_short_n.replace( '%qty%', remainingChars );
				},
				loadingMore: function() {
					return wc_enhanced_select_params.i18n_load_more;
				},
				maximumSelected: function( args ) {
					if ( args.maximum === 1 ) {
						return wc_enhanced_select_params.i18n_selection_too_long_1;
					}

					return wc_enhanced_select_params.i18n_selection_too_long_n.replace( '%qty%', args.maximum );
				},
				noResults: function() {
					return wc_enhanced_select_params.i18n_no_matches;
				},
				searching: function() {
					return wc_enhanced_select_params.i18n_searching;
				}
			}
		};
	}
	
	function wc_cp_select2_products() {

		$( '#configuration_components' ).find( ':input.js-wc-product-search' ).each( function() {
			var select2_args = {
				allowClear:  $( this ).data( 'allow_clear' ) ? true : false,
				placeholder: $( this ).data( 'placeholder' ),
				minimumInputLength: 3,
				escapeMarkup: function( m ) {
					return m;
				},
				ajax: {
			        url:         wc_enhanced_select_params.ajax_url,
			        dataType:    'json',
			        quietMillis: 250,
			        data: function( params ) {
				        
			            return {
							term:     params.term,
							action:   $( this ).data( 'action' ) || 'woocommerce_cp_json_search_products',
							product_type: $( this ).attr( 'product_type' ).replace( '-product', '' ) || 'simple',
							security: wc_enhanced_select_params.search_products_nonce
			            };
			        },
			        processResults: function( data ) {
			        	var terms = [];
				        if ( data ) {
							$.each( data, function( id, object ) {
								terms.push( { id: id, text: object.text, title: object.title } );
							});
						}
			            return { results: terms };
			        },
			        cache: true
			    }
			};

			select2_args = $.extend( select2_args, wc_cp_get_select_format_string() );
			
			$( this ).select2( select2_args ).addClass( 'enhanced' ).on("select2:selecting", function(e) { 
				$(this).siblings('.js-wc-product-search').val(e.params.args.data.id).trigger('change');
				$(this).closest('.wc-metabox').find('.js-wc-product-search-title').eq(0).text(e.params.args.data.title);
			});
			
		} );
		
		$( '#configuration_scenarios' ).find( ':input.js-wc-product-select' ).each( function() {
			
			var select2_args = {
				allowClear:  $( this ).data( 'allow_clear' ) ? true : false,
				placeholder: $( this ).data( 'placeholder' )
			};

			select2_args = $.extend( select2_args, wc_cp_get_select_format_string() );
			
			$( this ).select2( select2_args ).addClass( 'enhanced' );
			
		} );
		
	};
	
	function wc_cp_editors() {
		
		$( '.js-wc-cp-editor' ).each(function() {
			
			if( $(this).attr('id') && $(this).attr('id') !== undefined && ! $(this).hasClass('editor-instantiated') ) {
				
				var init = tinyMCEPreInit.mceInit['content'];
			
				init.selector = '#' + $(this).attr('id');
				init.setup = function (editor) {
					$(editor.getElement()).addClass('editor-instantiated');
			        editor.on('change', function () {
			            editor.save();
			            $(editor.getElement()).trigger('change');
			        });
			    };
		
				tinymce.init( init );
				
			}
			
		});
		
	}
	
	function wc_cp_initialize() {
		
		wc_cp_editors();
		wc_cp_help_tips();
		wc_cp_select2_products();
		
				
		$('.js-sortable').sortable({
			exclude: '.js-component-list li',
			onDragStart:function(item){
				item.addClass('dragged');
			},
			onDrop:function(item){
				item.removeClass('dragged');
				item.closest('.js-sortable').find('>li').each(function(index, el) {
					$(this).data('positon', index).find('.js-item-position').val(index+1).trigger('change');
				});
			},
			tolerance: -10,
			isValidTarget: function(item, container) {
				return item.closest( '.js-sortable' )[0] === container.el[0];
			}
		});
		
	}
	
	/*
	 * Select/Upload image(s) event
	 */
	$('body').on('click', '.js-wc-cp-upload-image', function(e){
		
		e.preventDefault();

		var button = $(this),
		    custom_uploader = wp.media({
				title: 'Insert image',
				library : {
					// uncomment the next line if you want to attach image to the current post
					// uploadedTo : wp.media.view.settings.post.id, 
					type : 'image'
				},
				button: {
					text: 'Use this image' // button label text
				},
				multiple: false // for multiple image selection set to true
			}).on('select', function() { // it also has "open" and "close" events 
				var attachment = custom_uploader.state().get('selection').first().toJSON();
				$(button).removeClass('button').html('<img class="true_pre_image" src="' + attachment.url + '" style="max-width:95%;display:block;" />').next().val(attachment.id).next().show();
			}).open();
				
	});
	
	var Closable = Backbone.Model.extend({
		
		defaults: {
			closed: true,
			position: 1
		},
		
		open: function(e) {
			
			var closed = $(e.currentTarget).next().is(':visible');

			setTimeout(_.bind(function() {
				
				this.set('closed', closed);
				
			}, this), 100);
			
		}
		
	});
	
	var Nestable = Closable.extend({
		
		addItem: function(e, type, model) {
			
			e.preventDefault();
			
			var plural = type + 's';
			
			this.attributes[plural].models.push(model);
			
			this.attributes[plural].trigger('add');
			
			this.trigger( 'change' );
			this.trigger( 'change:' + plural );
			
			wc_cp_initialize();
			
		},
		
		removeItem: function(e, type) {
			
			e.preventDefault();
			
			var plural = type + 's',
				cid = $(e.currentTarget).attr(type + '_cid');
				
			this.get(plural).remove(cid);
			
			this.trigger( 'change' );
			this.trigger( 'change:' + plural );
			
		}
		
	});
	
	var HasOptions = Nestable.extend({
		
		addOption: function(e) {
			
			this.addItem(e, 'option', new Option({
				closed: false
			}));
			
		},
		
		removeOption: function(e) {
			
			this.removeItem(e, 'option');
			
		}
		
	});
	
	var HasComponents = HasOptions.extend({
		
		addComponent: function(e) {
		
			this.addItem(e, 'component', new Component({
				closed: false
			}));

		},
		
		removeComponent: function(e) {
			
			this.removeItem(e, 'component');
			
		}
		
	});
	
	var Option = HasOptions.extend({
		
		type: 'option',
		
		initialize: function() {
			this.on('change', wc_cp_initialize);
			this.set('options', new Options(this.get('options'), {silent: false}));
			this.on('change:label change:source', function() {
				this.set('title', this.get('source') == 'static' ? this.get('label') : this.get('product_title'));
			});
		},
		
		defaults : _.extend({}, HasOptions.prototype.defaults, {
			title: '',
			product_title: '',
			component_id: null,
			option_id: null,
			source: 'simple-product',
			product_id: null,
			value: '',
			label: '',
			affect_stock: 0,
			selected: 0,
			recommended: 0,
			sku: '',
			price: '',
			formula: '{p}',
			nested_options: 0,
			options: [],
		}),
		
		updateLabel: function() {
			
			this.trigger('change:label');
			
			this.trigger('change');
			
		},
		
		updateSource: function() {
			
			this.trigger('change:source');
			
			this.trigger('change');
			
		}
		
	});
	
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
			price_formula: ''
		}
		
	});
	
	var Positionable = Backbone.Collection.extend({
		initialize: function() {
			this.on('reset add update', function(){
				this.map(function(model) {
					model.set('position', this.indexOf(model)+1);
				}, this);
			});
		}
	});
	
	var Options = Positionable.extend({
		model: Option
	});

	var Component = HasComponents.extend({
		
		type: 'component',
		
		initialize: function() {
			this.on('change', wc_cp_initialize);
			this.set('components', new Components(this.get('components'), {silent: false}));
			this.set('options', new Options(this.get('options'), {silent: false}));
			this.set('field', new Field(this.get('field')));
		},
		
		defaults: _.extend({}, HasComponents.prototype.defaults, {
			tab: 'settings',
			title: '',
			source: 'default',
			style: 'dropdown',
			options: new Options(),
			field: new Field(),
			components: []
		}),
		
		tab: function(e) {
			
			this.set('tab', $(e.currentTarget).data('tab'));
			
		},
		
	});
	
	var Components = Positionable.extend({
		model: Component
	});
	
	var ScenarioComponent = Closable.extend({
		
		type: 'scenario_component',
		
		initialize: function() {
			this.on('change:allow_all', wc_cp_initialize);
		},
		
		defaults: _.extend({}, Closable.prototype.defaults, {
			component_cid: null,
			component_id: null,
			scenario_id: null,
			allow_all: true,
			allow_field: true,
			modifier: 'in',
			options: [],
		})
		
	});
	
	var ScenarioComponents = Positionable.extend({
		model: ScenarioComponent
	});
	
	var Scenario = Closable.extend({
		
		type: 'scenario',
		
		initialize: function() {
			this.on('change', wc_cp_initialize);
			this.set('components', new ScenarioComponents(this.get('components')));
		},
		
		addComponent: function(component, scenario_component) {
			
			var el = scenario_component || this;
			
			var scenario_component = el.get('components').add({
				component_cid: component.cid,
				component_id: component.get('id')
			});
			
			component.get('components').each(function(subcomponent) {
				
				this.addComponent(subcomponent, scenario_component);
				
			});
			
			this.trigger( 'change' );
			this.trigger( 'change:components' );
			
			return scenario_component;
			
		},
		
		removeComponent: function(component) {
			
			var component = this.get('components').findWhere({
				component_cid: component.cid
			});
			
			if( component ) {
				
				component.remove();
				
				this.trigger( 'change' );
				this.trigger( 'change:components' );
				
			}
			
		},
		
		defaults: _.extend({}, Closable.prototype.defaults, {
			title: '',
			description: '',
			active: true,
			components: [],
		})
		
	});
	
	var Scenarios = Positionable.extend({
		model: Scenario
	});
	
	var Product = HasComponents.extend({
		
		syncedScenarios: false,
		
		url: function() {
		
			return ajaxurl + '?action=wc_cp_save_configuration&security=' + wc_cp_admin_params.save_configuration_nonce;
			
		},
		
		initialize: function() {
			this.on('change', wc_cp_initialize);	
			this.on('sync', function() {
				
				this.set('scenarios', new Scenarios(this.get('scenarios'), {silent: false}));
				this.set('components', new Components(this.get('components'), {silent: false}));
				
			});
			this.listenTo(this.get('components'), 'remove', this.removeScenarioComponents);
			this.trigger('sync');
		},
		
		defaults: {
			style: 'stacked',
			build_sku: false,
			promise: false,
			components: new Components(),
			scenarios: new Scenarios()
		},
		
		addScenario: function(e) {
			
			var scenario = new Scenario({
				closed: false
			});
		
			this.addItem(e, 'scenario', scenario);

		},
		
		removeScenarioComponents: function(component) {
		
			this.get('scenarios').each(function(scenario) {
				
				scenario.removeComponnt(component);
				
			});
			
		},
		
		removeScenario: function(e) {
			
			this.removeItem(e, 'scenario');
			
		},
		
		saveConfiguration: function(e) {
			
			var container = $(e.currentTarget).closest('.js-cp-product-bind');
			
			container.block({
				message: null,
				overlayCSS: {
					opacity: 0.6
				}
			});
			
			this.save(null, {
				success: _.bind(function(model, response) {
					if(this.promise) {
						$('#publish').trigger('click');
					}
				}, this),
				error: function(model, xhr) {
					alert(JSON.parse(xhr.responseText).data.message);
				},
    		}).always(function() {
				container.unblock();
			});
    		
		}
		
	});
	
	var product = new Product( wc_cp_admin_params.product );
	
	$('#publish').click(function(e) {
		
		if( ! product.promise ) {
		
			e.preventDefault();
			
			$(this).addClass('button-primary-disabled').siblings('.spinner').addClass('is-active');
			
			product.promise = true;
			
			$('.js-save-configuration').trigger('click');
			
		}
		
	});
	
	rivets.formatters['>'] = function (value, arg) {
		return parseFloat(value) > parseFloat(arg);
	}
	
	rivets.formatters['='] = function (value, arg) {
		return value == arg;
	}
	
	rivets.formatters['length'] = function (value) {
		return value.length;
	}
	
	rivets.formatters['even'] = function (value) {
		return value % 2 === 0;
	}
	
	rivets.formatters['odd'] = function (value) {
		return value % 2 !== 0;
	}
	
	rivets.formatters['!='] = function (value, arg) {
		return value != arg;
	}
	
	rivets.formatters['append'] = function (value, arg) {
		return value + arg;
	}
	
	rivets.formatters['AND'] = function (value, arg) {
		return value && arg;
	}
	
	rivets.formatters['IN'] = function (value, arg) {
		return arg && arg.indexOf(value) > -1;
	}
	
	rivets.formatters['NOTIN'] = function (value, arg) {
		return ! arg || arg.indexOf(value) == -1;
	}
	
	rivets.formatters['has'] = function (value, arg) {
		return value instanceof Backbone.Collection && value.get(arg) ? true : false;
	}
	
	rivets.configure({
	  	handler: function(context, ev, binding) {
			this.call(binding.model, ev, context)
	  	}
	});
	
	rivets.components['component'] = {
		template: function() {
			return document.querySelector('#component').innerHTML;
		},
		initialize: function(el, data) {
			return data;
		}
	};
	
	rivets.components['selection'] = {
		template: function() {
			return document.querySelector('#selection').innerHTML;
		},
		initialize: function(el, data) {
			data.depth++;
			return data;
		}
	};
	
	rivets.components['scenario_component'] = {
		template: function() {
			return document.querySelector('#scenario_component').innerHTML;
		},
		initialize: function(el, data) {
			data.depth++;
			var scenario_component = data.scenario.get('components').findWhere({
				component_id: data.component.id
			});
			data.scenario_component = scenario_component ? scenario_component : data.scenario.get('components').findWhere({
				component_cid: data.component.cid
			});
			data.scenario_component = scenario_component ? scenario_component : data.scenario.addComponent(data.component);
			return data;
		}
	};
	
	rivets.binders['input-match'] = {
	    publishes: true,
	    priority: 2000,
	    bind: function(el) {
	        this.event = 'change';
	        rivets._.Util.bindEvent(el, this.event, this.publish);
	    },
	    unbind : function() {
	        rivets.binders.value.unbind.apply(this, arguments);
	    },
	    routine : function() {
	        rivets.binders.value.routine.apply(this, arguments);
	    }
	}
	
	// Subsubsub navigation

	$( 'body' ).on( 'click', '.js-component-list a', function(e) {

		$( this ).closest( '.js-component-list' ).find( 'a' ).removeClass( 'current' );
		$( this ).addClass( 'current' );

		$( this ).closest( '.js-component' ).find( '.js-component-page' ).addClass( 'hide' );

		var tab = $( this ).data( 'tab' );

		$( this ).closest( '.js-component' ).find( '.js-component-page-' + tab ).removeClass( 'hide' );

		e.preventDefault();

	} );
	
	setTimeout(wc_cp_initialize, 0);
	
	rivets.bind( bindings, {
    	product: product
	});

});
