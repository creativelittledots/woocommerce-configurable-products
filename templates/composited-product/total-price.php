<p class="price">
	<?php
		
		if ( $total_price > 0 ) {
			
			$display_price = $product->get_display_price($total_price);
			$display_regular_price = $product->get_display_price( $total_regular_price );
	
			if ( $total_regular_price> $total_price ) {
			
				$price = $product->get_price_html_from_to( $display_regular_price, $display_price ) . $this->get_price_suffix();
				
				$price = apply_filters( 'woocommerce_sale_price_html', $price, $product );
			
			} else {
			
				$price = wc_price( $display_price ) . $product->get_price_suffix();
			
				$price = apply_filters( 'woocommerce_price_html', $price, $product );
			
			}
			
		} elseif ( $total_price === '' ) {
			 
			$price = apply_filters( 'woocommerce_empty_price_html', '', $price );
			 
		} elseif ( $total_price == 0 ) {
			
			if ( $total_regular_price> $total_price ) {
			
				$price = $this->get_price_html_from_to( $display_regular_price, __( 'Free!', 'woocommerce' ) );
				
				$price = apply_filters( 'woocommerce_free_sale_price_html', $price, $product );
				
			} else {
				
				$price = __( 'Free!', 'woocommerce' );
				
				$price = apply_filters( 'woocommerce_free_price_html', $price, $product );
				
			}
		
		}
		
		echo apply_filters( 'woocommerce_get_price_html', $price, $product );
		
	?>
</p>