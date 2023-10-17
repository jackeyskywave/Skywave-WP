<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}
/**
 * Class Woo_Wholesale_User_Roles
 */
if (!class_exists('WWP_Wholesale_User_Roles')) {

	class WWP_Wholesale_User_Roles {

		public function __construct () {
			add_role('wwp_wholesaler', esc_html__('Wholesaler - Wholesaler Role', 'woocommerce-wholesale-pricing'), array( 'read' => true, 'level_0' => true ));
		}
	}
	new WWP_Wholesale_User_Roles();
}
