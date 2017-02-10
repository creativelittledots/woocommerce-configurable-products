jQuery( function($) {

	var Component = Backbone.Model.extend({});
	
	var Components = Backbone.Collection.extend({});
	
	var Option = Backbone.Model.extend({});
	
	var Options = Backbone.Collection.extend({});
	
	var Product = Backbone.Model.extend({});
	
	var product = new Product( wc_cp_edit_product );
	
	rivets.configure({
	  	handler: function(context, ev, binding) {
			this.call(binding.model, ev, context)
	  	}
	});
	
	var bindings = $('.js-cp-product-bind');
	
	rivets.bind( bindings, {
    	product: product
	});

});
