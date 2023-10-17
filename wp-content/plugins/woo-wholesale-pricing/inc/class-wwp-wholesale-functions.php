<?php
if ( !defined('ABSPATH') ) {
	exit; // Exit if accessed directly
}
/**
 * Class To Add Wholesale Functionality with WooCommerce
 */
if ( !class_exists('Wwp_Wholesale_Functions') ) {

	class Wwp_Wholesale_Functions {

		public function __construct () {
			add_filter('woocommerce_package_rates', array($this, 'wwp_apply_free_shipping_if_valid_coupon'), 100);
		}
		public function wwp_apply_free_shipping_if_valid_coupon ( $rates ) {
			global $woocommerce;
			$free = array();
			foreach ( $woocommerce->cart->applied_coupons as $coupon ) {
				$page = get_page_by_title($coupon, '', 'shop_coupon');
				$coupon = new WC_Coupon( $page->ID );
				if ( $coupon->get_free_shipping() ) {
					foreach ( $rates as $rate_id => $rate ) {
						if ( 'flat_rate' === $rate->method_id ) {
							$rate->label = 'Free Shipping';
							$rate->cost = 0.00;
							$free[ $rate_id ] = $rate;
							break;
						}
					}
				}
			}
			return !empty($free) ? $free : $rates;
		}
	}
	new Wwp_Wholesale_Functions();
}
