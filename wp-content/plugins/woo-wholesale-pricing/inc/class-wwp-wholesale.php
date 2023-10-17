<?php
if (! defined('ABSPATH') ) {
	exit; // Exit if accessed directly
}
/**
 * Class To Add Wholesale Functionality with WooCommerce
 */
if (!class_exists('WWP_Easy_Wholesale')) {
	class WWP_Easy_Wholesale {
		public function __construct() {
			add_filter('woocommerce_get_price_html', array($this, 'wwp_change_product_price_display'));
			add_filter('woocommerce_cart_item_price', array($this, 'wwp_change_product_price_display'));
			add_action('woocommerce_before_calculate_totals', array($this, 'wwp_override_product_price_cart'), 99);
			add_action('wp_footer', array($this,'wwp_on_variation_change'));
			add_action('wp_ajax_wwp_variation', array($this, 'wwp_variation_change_callback'));
			add_action('wp_ajax_nopriv_wwp_variation', array($this, 'wwp_variation_change_callback'));
			add_filter('woocommerce_variable_sale_price_html', array($this, 'wwp_variable_price_format'), 10, 2);
			add_filter('woocommerce_variable_price_html', array($this, 'wwp_variable_price_format'), 10, 2);
			add_action('woocommerce_product_query', array($this, 'wwp_wwp_wholesaler_products_only'), 99, 1);
			add_action('init', array($this, 'wwp_default_settings'));
			add_filter('woocommerce_product_variation_get_price', array($this, 'wwp_variation_price_change') , 200, 2 );
			add_filter('woocommerce_product_variation_get_regular_price', array($this, 'wwp_variation_price_change'), 200, 2 );
			add_filter( 'woocommerce_available_variation', array( $this, 'filter_woocommerce_available_variation' ), 200, 3 );
		}
		public function filter_woocommerce_available_variation( $variation_get_max_purchase_quantity, $instance, $variation ) { 
			if ( !$this->is_wholesaler_user(get_current_user_id()) ) {
				return $variation_get_max_purchase_quantity;
			}
            $data = get_post_meta(wp_get_post_parent_id($variation_get_max_purchase_quantity['variation_id']), '_wwp_enable_wholesale_item', true);
			
			if ( 'yes' == $data ) {
				$min_quantity = get_post_meta($variation_get_max_purchase_quantity['variation_id'], '_wwp_wholesale_min_quantity', true);
				
				//	$variation_get_max_purchase_quantity['price_html'] = '<span class="price"><ins><span class="woocommerce-Price-amount amount">' . wc_price($variation_get_max_purchase_quantity['display_regular_price'] ) . ' </span></ins></span>';
				 
				if ( $min_quantity && 1 != $min_quantity ) {
					/* translators: %1$s is replaced with "string" */
					$variation_get_max_purchase_quantity['availability_html'] .= apply_filters( 'wwp_product_minimum_quantity_text', '<p style="font-size: 10px;">' . sprintf(esc_html__('Wholesale price will only be applied to a minimum quantity of %1$s products', 'woocommerce-wholesale-pricing'), $min_quantity) . '</p>' , $min_quantity );
					return $variation_get_max_purchase_quantity; 
				}
			 
			}
			$data=get_option('_wwp_enable_wholesale_item');
			if (  'yes' == $data  ) {
				
				$min_quantity = (int) get_option('_wwp_wholesale_min_quantity');
				//$variation_get_max_purchase_quantity['price_html'] = '<span class="price"><ins><span class="woocommerce-Price-amount amount"> ' . wc_price($variation_get_max_purchase_quantity['display_regular_price'] ) . ' </span></ins></span>';
				if ( $min_quantity && 1 != $min_quantity ) {
					 /* translators: %s: minimum quantity to apply wholesale */
					$variation_get_max_purchase_quantity['availability_html'] .= apply_filters( 'wwp_product_minimum_quantity_text', '<p style="font-size: 10px;">' . sprintf(esc_html__('Wholesale price will only be applied to a minimum quantity of %1$s products', 'woocommerce-wholesale-pricing'), $min_quantity ) . '</p>' , $min_quantity  );
					return $variation_get_max_purchase_quantity; 

				}
			}
			
			return $variation_get_max_purchase_quantity; 	
		}
		
		public function wwp_variation_price_change( $price, $variation ) {  
			global $woocommerce;
			$variation_id = $variation->get_id();
			$product_id = wp_get_post_parent_id($variation_id);
			$enable_wholesale = get_post_meta($product_id, '_wwp_enable_wholesale_item', true); 
			if ( 'yes' != get_option('_wwp_enable_wholesale_item')) {
				if ( empty($enable_wholesale) ) {
					return $price;
				}
			}
			$qty='';
			if ( is_cart() || is_checkout() ) {
				return $price;
			}
		
			if ($this->get_variable_wholesale_price ( $variation_id, $product_id )) {
				$price = $this->get_variable_wholesale_price ( $variation_id, $product_id );
			}	
			return $price;
		}
		public function wwp_change_product_price_display( $price ) { 
			global $post;
			$post_id = $post->ID;
			$product = wc_get_product($post_id);
			if ( is_cart()) {
				return $price;
			}
			if ( ( 'object' == gettype($product) ) && !$product->is_type('simple') ) {
				return $price;
			}
			if ( ( 'object' != gettype( $product ) )) {
				return $price;
			}
			if ( !$this->is_wholesaler_user(get_current_user_id()) ) {
				return $price;
			}
			 
			if ( !$this->is_wholesale($post->ID) ) {
				return $price;
			}
			$enable_wholesale = get_post_meta($post_id, '_wwp_enable_wholesale_item', true);
			if ( 'yes' != get_option('_wwp_enable_wholesale_item')) {
				if ( empty($enable_wholesale) ) {
					return $price;
				}
			}
			$r_price=$product->get_price();
			$wholesale_price = $this->get_wholesale_price($post_id);
			if ( !is_numeric($wholesale_price) || !is_numeric($r_price) ) {
				return $price;
			}
			$saving_amount = ( $r_price - $wholesale_price );
			$saving_percent = ( $r_price - $wholesale_price ) / $r_price * 100;
			$min_quantity = get_post_meta( $post_id, '_wwp_wholesale_min_quantity', true);
			if ( empty($enable_wholesale) && 'yes' == get_option('_wwp_enable_wholesale_item') ) {
				$enable_wholesale = 'yes';
				$min_quantity = get_option('_wwp_wholesale_min_quantity') ;
			}
			$html = '';
			$settings = get_option('wwp_wholesale_pricing_options', true);
			$actual = ( isset( $settings['retailer_label'] ) && !empty( $settings['retailer_label'] ) ) ? $settings['retailer_label'] : esc_html__('Actual', 'woocommerce-wholesale-pricing');
			$save = ( isset( $settings['save_label'] ) && !empty( $settings['save_label'] ) ) ? $settings['save_label'] : esc_html__('Save', 'woocommerce-wholesale-pricing');
			$new = ( isset( $settings['wholesaler_label'] ) && !empty( $settings['wholesaler_label']) ) ? $settings['wholesaler_label'] : esc_html__('New', 'woocommerce-wholesale-pricing');
			if ( !empty($wholesale_price) ) {
				$html = do_action('wwp_before_pricing');
				$html .= '<div class="wwp-wholesale-pricing-details">';
				if ( isset($settings['retailer_disabled']) &&  'yes' != $settings['retailer_disabled'] ) {
					$html .= '<p><span class="retailer-text">' . esc_html__($actual, 'woocommerce-wholesale-pricing') . '</span>: <s>' . $price . '</s></p>';
				}
				$html .= '<p><span class="price-text">' . esc_html__($new, 'woocommerce-wholesale-pricing') . '</span>: ' . wc_price( $wholesale_price ) . '</p>';
				if ( isset($settings['save_price_disabled']) &&  'yes' != $settings['save_price_disabled'] ) {
					$html .= '<p><b><span class="save-price-text">' . esc_html__($save, 'woocommerce-wholesale-pricing') . '</span>: ' . wc_price( $saving_amount ) . ' (' . round($saving_percent) . '%)</b></p>';
				}
				if ( $min_quantity > 1 ) {
					if ( $product->get_type() == 'simple') {
						/* translators: %s: minimum quantity to apply wholesale */
						$html .= '<p style="font-size: 10px;">' . sprintf(esc_html__('Wholesale price will only be applied to a minimum quantity of %1$s products', 'woocommerce-wholesale-pricing'), $min_quantity) . '</p>';
					}
				}
				$html .= '</div>';
				$html .= do_action('wwp_after_pricing');
			}
			return $html;
		}
		public function wwp_override_product_price_cart( $_cart ) {
			global $woocommerce;
			$items = $woocommerce->cart->get_cart();
			foreach ( $_cart->cart_contents as $item ) {
				if ( $this->is_wholesale($item['product_id']) ) {
					$variation_id = $item['variation_id'];
					if ( !empty($variation_id) ) {                     
						if ( 'yes' == get_post_meta( $item['product_id'], '_wwp_enable_wholesale_item', true ) ) {
							$min_quantity = get_post_meta( $variation_id, '_wwp_wholesale_min_quantity', true );
						} else {
							$min_quantity = $this->get_wholesale_qty($item['product_id']);
						}
					if ( $min_quantity <= $item['quantity'] ) {
							if ( !empty($this->get_variable_wholesale_price($variation_id, $item['product_id'])) ) {
								$item['data']->set_price($this->get_variable_wholesale_price($variation_id, $item['product_id']));
							}
						}
					} else {
						if ( 'yes' == get_post_meta( $item['product_id'], '_wwp_enable_wholesale_item', true ) ) {
							$min_quantity = get_post_meta( $item['product_id'], '_wwp_wholesale_min_quantity', true );
						} else {
							$min_quantity = $this->get_wholesale_qty($item['product_id']);
						}
						if ( $min_quantity <= $item['quantity'] ) {
							if ( !empty($this->get_wholesale_price($item['product_id'])) ) {
								$item['data']->set_price($this->get_wholesale_price($item['product_id']));
							}
						}
					}
				}
			}
		}
		public function get_wholesale_qty ( $product_id ) {
			$quantity = '';
			$enable_wholesale = get_post_meta($product_id, '_wwp_enable_wholesale_item', true);
			if ( !empty($enable_wholesale) ) {
				$quantity = get_post_meta($product_id, '_wwp_wholesale_min_quantity', true);
			}
			if ( '' == $quantity ) {
				if ( 'yes' == get_option('_wwp_enable_wholesale_item') ) {
					$quantity = get_option('_wwp_wholesale_min_quantity');
				}
			}
			return $quantity;
		}
		public function is_wholesale ( $post_id ) {
			$enable_wholesale = get_post_meta($post_id, '_wwp_enable_wholesale_item', true);
			if ( !empty($enable_wholesale) ) {
				return true;
			}
			if ( 'yes' == get_option('_wwp_enable_wholesale_item')) {
				return true;
			}
			return false;
		}
		public function get_wholesale_price_multi ( $discount, $wprice, $post_id ) {
			if ( 'fixed' == $discount ) {
				return $wprice;
			} else {
				$product_price = get_post_meta($post_id, '_price', true);
				$product_price = ( isset($product_price) && is_numeric($product_price) ) ? $product_price : 0;
				$wholesale_price = $product_price * $wprice / 100;
				return $wholesale_price;
			}
		}
		public function get_wholesale_price ( $post_id ) {
			$enable_wholesale = get_post_meta($post_id, '_wwp_enable_wholesale_item', true);
			$wholesale_price = get_post_meta($post_id, '_wwp_wholesale_amount', true);
			
			if ( empty( $enable_wholesale ) ) {
				if ( 'yes' == get_option('_wwp_enable_wholesale_item')) {
					$wholesale_price = get_option('_wwp_wholesale_amount');
				}
			}
			
			if ( $this->is_wholesale($post_id) ) {
				$wholesale_amount_type = get_post_meta($post_id, '_wwp_wholesale_type', true);
				
				if ( empty( $enable_wholesale ) ) {
					if ( 'yes' == get_option('_wwp_enable_wholesale_item')) {
						$wholesale_amount_type = get_option('_wwp_wholesale_type');
					}
				}
				
				if ( 'fixed' == $wholesale_amount_type ) {
					return $wholesale_price;
				} else {
					$product_price = get_post_meta($post_id, '_price', true);
					$product_price = ( isset($product_price) && is_numeric($product_price) ) ? $product_price : 0;
					$wholesale_price = $product_price * $wholesale_price / 100;
					return $wholesale_price;
				}
			}
		}
		public function get_variable_wholesale_price ( $variation_id, $product_id = '' ) { 
			if ( empty($product_id) ) {
				$product_id = get_the_ID();
			}
			
			$wholesale_item_enable = get_post_meta($product_id, '_wwp_enable_wholesale_item', true);
			$variable_price = get_post_meta($variation_id, '_wwp_wholesale_amount', true);
			$wholesale_amount_type = get_post_meta($product_id, '_wwp_wholesale_type', true);
		
			if ( 'yes' != $wholesale_item_enable ) { 
			
				if ( 'yes' == get_option('_wwp_enable_wholesale_item')) {
				
					$variable_price = get_option('_wwp_wholesale_amount');
					$wholesale_amount_type = get_option('_wwp_wholesale_type');
				
				}
			}
			
			if ( 'fixed' == $wholesale_amount_type ) {
				return $variable_price;
			} else {
				$product_price = get_post_meta($variation_id, '_price', true);
				$product_price= ( isset($product_price) && is_numeric($product_price) ) ? $product_price : 0;
				$variable_price= ( isset($variable_price) && is_numeric($variable_price) ) ? $variable_price : 0;
				$wholesale_price = $product_price * $variable_price / 100;
				return $wholesale_price;
			}
		}
		public function is_wholesaler_user ( $user_id) {
			if ( !empty($user_id) ) {
				$user_info = get_userdata($user_id);
				$user_role = (array) $user_info->roles;
				if (!empty($user_role) &&  in_array('wwp_wholesaler', $user_role) ) {
					return true;
				}
			}
			return false;
		}
		public function wwp_on_variation_change () {
			global $post;
			$user_info = get_userdata( get_current_user_id() );    
			if ( $this->is_wholesale($post->ID) ) { ?>
				<script type="text/javascript" >
					/* Make this document ready function to work on click where you want */
					jQuery(document).ready(function($) {
						/* In front end of WordPress we have to define ajaxurl */
						var ajaxurl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
						jQuery( "body").on( "found_variation" , ".variations_form", function( event, variation ) {
							var data = {
								'action': 'wwp_variation',
								'variation_id': variation['variation_id'],
								'variation_price': variation['price_html'],
								'wwp_variation_nonce' : <?php wp_create_nonce('wwp_variation_nonce'); ?>
								'product_id': <?php echo get_the_ID(); ?>
							};
							$.post(ajaxurl, data, function(response) {
								if ( '' != response)
									jQuery('.woocommerce-variation-price').html(response);
							});
						});
					});
				</script>
				<?php
			}
		}
		public function wwp_variation_change_callback () { 
			if ( !isset($_POST['wwp_variation_nonce']) || !wp_verify_nonce( wc_clean($_POST['wwp_variation_nonce']), 'wwp_variation_nonce') ) {
				return;
			}
			$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : '';
			$variation_price = isset( $_POST['variation_price'] ) ? wc_clean( $_POST['variation_price'] ) : '';
			$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : '';
			if ( !$this->is_wholesaler_user(get_current_user_id()) ) {
				echo '';
				die();
			}
			$wholesale_variable_price = get_post_meta($variation_id, '_wwp_wholesale_amount', true);
			$variable_wholesale_price = $this->get_variable_wholesale_price($variation_id, $product_id);
			$html = '<s>' . esc_html($variation_price) . '</s>';
			$html .= '<span class="price"><span class="woocommerce-Price-amount amount">' . wc_price($variable_wholesale_price) . '</span></span>';
			$min_quantity = get_post_meta($variation_id, '_wwp_wholesale_min_quantity', true);
			if ( $min_quantity > 1 ) {
				/* translators: %s: minimum quantity to apply wholesale */
				$html .= '<p style="font-size: 10px;">' . sprintf(esc_html__('Wholesale price will only be applied to a minimum quantity of %1$s products', 'woocommerce-wholesale-pricing'), $min_quantity) . ' </p>';
			}
			echo wp_kses_post($html);
			die(); // this is required to terminate immediately and return a proper response
		}
		public function wwp_variable_price_format( $price, $product ) { 
			$prod_id = $product->get_id();
			if ( !$this->is_wholesaler_user(get_current_user_id()) ) {
				return $price;
			}
			$enable_wholesale = get_post_meta($prod_id, '_wwp_enable_wholesale_item', true);
			if ( 'yes' != get_option('_wwp_enable_wholesale_item')) {
				if ( empty($enable_wholesale) ) {
					return $price;
				}
			}
			$product_variations = $product->get_children();
			$wholesale_product_variations = array();
			$original_variation_price = array();
			foreach ( $product_variations as $product_variation ) {
				$wholesale_product_variations[] = $this->get_variable_wholesale_price($product_variation, $prod_id);
				$original_variation_price[] = get_post_meta($product_variation, '_price', true);
			}
			sort($wholesale_product_variations);
			sort($original_variation_price);
			$min_wholesale_price = $wholesale_product_variations[0];
			$max_wholesale_price = $wholesale_product_variations[count($wholesale_product_variations) - 1];
			$min_original_variation_price = $original_variation_price[0];
			if ( empty($min_wholesale_price) ) {
				$min_wholesale_price = $min_original_variation_price;
			}
			$max_original_variation_price = $original_variation_price[count($original_variation_price) - 1];
			$min_saving_amount = ( $min_original_variation_price - $min_wholesale_price );
			if ( empty($max_wholesale_price) ) {
				$max_wholesale_price = $max_original_variation_price;
			}
			$min_saving_percent = ( $min_original_variation_price - $min_wholesale_price ) / $min_original_variation_price * 100;
			$max_saving_amount = ( $max_original_variation_price - $max_wholesale_price );
			$max_saving_percent = ( $max_original_variation_price - $max_wholesale_price ) / $max_original_variation_price * 100;
			$min_quantity = get_post_meta( $prod_id, '_wwp_wholesale_min_quantity', true);
			$settings = get_option('wwp_wholesale_pricing_options', true);
			$actual = ( isset( $settings['retailer_label'] ) && !empty( $settings['retailer_label']) ) ? esc_html( $settings['retailer_label'] ) : esc_html__('Actual', 'woocommerce-wholesale-pricing');
			$save= ( isset( $settings['save_label'] ) && !empty( $settings['save_label']) ) ? esc_html($settings['save_label']) : esc_html__('Save', 'woocommerce-wholesale-pricing');
			$new= ( isset( $settings['wholesaler_label'] ) && !empty( $settings['wholesaler_label']) ) ? esc_html($settings['wholesaler_label']) : esc_html__('New', 'woocommerce-wholesale-pricing');
			$html = '<div class="wwp-wholesale-pricing-details">';
			if ( isset($settings['retailer_disabled']) && 'yes' != $settings['retailer_disabled'] ) {
				$html .= '<p><span class="retailer-text">' . esc_html( $actual, 'woocommerce-wholesale-pricing' ) . '</span>: <s>' . $price . '</s></p>';
			}
			$html .= '<p><b><span class="price-text">' . esc_html( $new, 'woocommerce-wholesale-pricing' ) . '</span>: ' . wc_price( $wholesale_product_variations[0] ) . ' - ' . wc_price( $wholesale_product_variations[count($wholesale_product_variations) - 1] ) . '</b></p>';
			if ( isset($settings['save_price_disabled']) && 'yes' != $settings['save_price_disabled'] ) {
				$html .= '<p><b><span class="save-price-text">' . esc_html($save, 'woocommerce-wholesale-pricing') . '</span>:  (' . round( $min_saving_percent ) . '% - ' . round( $max_saving_percent ) . '%)</b></p>';
			}
			if ( $min_quantity > 1 ) {
				/* translators: %s: minimum quantity to apply wholesale */
				$html .= '<p style="font-size: 10px;">' . sprintf( esc_html__('Wholesale price will only be applied to a minimum quantity of %1$s products', 'woocommerce-wholesale-pricing'), $min_quantity) . '</p>';
			}
			$html .= '</div>';
			return $html;
		}
		public function wwp_wwp_wholesaler_products_only( $q ) {
			$settings=get_option('wwp_wholesale_pricing_options', true);
			$wholesaler_prod_only = ( isset($settings['wholesaler_prodcut_only']) && 'yes' == $settings['wholesaler_prodcut_only'] ) ? 'yes' : 'no';
			if ( 'yes' == $wholesaler_prod_only ) {
				$user_info = get_userdata(get_current_user_id());
				if ( in_array('wwp_wholesaler', $user_info->roles) ) {
					$meta_query = $q->get('meta_query');
					$meta_query[] = array(
						'key'       => '_wwp_enable_wholesale_item',
						'value'   => 'yes'
					);
					$q->set('meta_query', $meta_query);
				}
			}
		}
		public function wwp_default_settings () {
			if ( empty(get_option('wc_settings_tab_wholesale_retailer_label', true)) ) {
				update_option('wc_settings_tab_wholesale_retailer_label', esc_html__('RRP', 'woocommerce-wholesale-pricing'));
			}
			if ( empty(get_option('wc_settings_tab_wholesale_wholesaler_price_label', true)) ) {
				update_option('wc_settings_tab_wholesale_wholesaler_price_label', esc_html__('Your Price', 'woocommerce-wholesale-pricing'));
			}
			if ( empty(get_option('wc_settings_tab_wholesale_wholesaler_save_price_label', true)) ) {
				update_option('wc_settings_tab_wholesale_wholesaler_save_price_label', esc_html__('You Save', 'woocommerce-wholesale-pricing'));
			}
		}
	}
	new WWP_Easy_Wholesale();
}
