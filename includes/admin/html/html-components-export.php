<p>
	
	<label>
	
		<input type="radio" name="content" value="configurable-products" /> 
		
		Configurable Products
		
	</label>
	
</p>

<ul id="configurable-products-filters" class="export-filters">
	
	<li>
	
		<label>
			
			<span class="label-responsive"><?php _e( 'Brands:' ); ?></span>
			
			<?php wp_dropdown_categories( array( 'orderby' => 'name', 'hierarchical' => true, 'name' => 'product_brand', 'taxonomy' => 'product_brand', 'value_field' => 'slug', 'show_option_all' => __('All') ) ); ?>
			
			
		</label>
		
	</li>
	
	<li>
	
		
		<label>
		
			<span class="label-responsive"><?php _e( 'Categories:' ); ?></span>
		
			<?php wp_dropdown_categories( array( 'orderby' => 'name', 'hierarchical' => true, 'name' => 'product_cat', 'taxonomy' => 'product_cat', 'value_field' => 'slug', 'show_option_all' => __('All') ) ); ?>
		
		</label>
		
	</li>
	
	<li>
	
		<label>
		
			<span class="label-responsive"><?php _e( 'Tags:' ); ?></span>
			
			<?php wp_dropdown_categories( array( 'orderby' => 'name', 'hierarchical' => true, 'name' => 'product_tag', 'taxonomy' => 'product_tag', 'value_field' => 'slug', 'show_option_all' => __('All') ) ); ?>
			
		</label>
		
	</li>
	
</ul>

<script type="text/javascript">
	jQuery(document).ready(function($){
 		var form = $('#export-filters'),
 			filters = form.find('.export-filters');
 		filters.hide();
 		form.find('input:radio').change(function() {
			filters.slideUp('fast');
			switch ( $(this).val() ) {
				case 'configurable-products': $('#configurable-products-filters').slideDown(); break;
			}
 		});
	});
</script>