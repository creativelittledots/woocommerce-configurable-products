jQuery(document).ready(function($) {
						
	$(this).on('click', '#bto_build_sku', function() {
		
		jQuery(".group_affect_sku").slideToggle(300);
		
		if(!$(this).is(':checked')) {
			
			jQuery(".group_affect_sku_order").slideUp(300);
			
		}
		
		if($(this).is(':checked') && jQuery(".group_affect_sku").find('input:checkbox').is(':checked')) {
			
			jQuery(".group_affect_sku_order").slideDown(300);
			
		}
		
	});
	
	$(this).on('click', '.affect_sku', function() {
		
		jQuery(this).parents('.group_affect_sku').siblings('.group_affected_by_sku').slideToggle(300);
		
	});

});