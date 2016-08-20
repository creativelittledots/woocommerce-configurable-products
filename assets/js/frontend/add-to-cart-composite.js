/* global wc_checkout_params */
jQuery(document).ready(function($){
	
	$.blockUI.defaults.overlayCSS.cursor = 'default';
	
	function Product(opts) {
		
		this.valid = false;
		this.errors = [
			{
				text: 'Come on'
			}
		];
		this.selections = [
			{
				title: 'You got this'
			}
		];
		this.regular_price = 25;
		
	}
	
	var product = new Product();
	
	rivets.configure({
	  	handler: function(context, ev, binding) {
			this.call(binding.model, ev, context)
	  	}
	});
	
	rivets.bind( $('.js-composite-product-bind'), {
    	product: product
	});
	
});