<?php
/**
 * Plugin Name: Wholesale For WooCommerce Lite
 * Plugin URI: https://wpexperts.io/
 * Description: Wholesale for WooCommerce Lite gives you an ability to display wholesale price on all products of your WooCommerce store. Add wholesale pricing on your existing products and display how much your customers are savings.<a href="https://woocommerce.com/products/wholesale-for-woocommerce/?aff=2878" target="_blank"> UPGRADE TO WHOLESALE FOR WOOCOMMERCE PRO </a>to get premium features - Assign and manage wholesale user roles, control product and price visibility and more. 
 * Version: 1.6.1
 * Author: wpexpertsio
 * Author URI: https://wholesaleplugin.com/
 * Developer: wpexpertsio
 * Developer URI: https://wpexperts.io/
 * Text Domain: woocommerce-wholesale-pricing
 * 
 * WC requires at least: 3.0
 * WC tested up to: 4.8.0
 * Tested up to: 6.0
 *
 * Copyright: © 2009-2015 WooCommerce.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */
if (!defined('ABSPATH')) {
	exit();
}
if (!defined('WWP_PLUGIN_URL')) {
	define('WWP_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('WWP_PLUGIN_PATH')) {
	define('WWP_PLUGIN_PATH', plugin_dir_path(__FILE__));
}
if (!defined('WWP_PLUGIN_DIRECTORY_NAME')) {
	define('WWP_PLUGIN_DIRECTORY_NAME', dirname(__FILE__));
}
if (!class_exists('Wwp_Wholesale_Pricing')) {

	class Wwp_Wholesale_Pricing {

		public function __construct() {
			if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
				self::init();
			} else {
				add_action('admin_notices', array(__Class__, 'wholesale_admin_notice_error'));
			}
		}
		public static function init() {
			if (function_exists('load_plugin_textdomain')) {
				load_plugin_textdomain('woocommerce-wholesale-pricing', false, dirname(plugin_basename(__FILE__)) . '/languages/');
			}
			include_once WWP_PLUGIN_PATH . 'inc/class-wwp-wholesale-general-functions.php';
			require_once WWP_PLUGIN_PATH.'/inc/class-wwp-wholesale-requests.php';
			if (is_admin()) {
			    require_once WWP_PLUGIN_PATH.'/inc/class-wwp-wholesale-user-roles.php';
				include_once WWP_PLUGIN_PATH . 'inc/class-wwp-wholesale-backend.php';
				
			} else {
				include_once WWP_PLUGIN_PATH . '/inc/class-wwp-wholesale-common.php';
				include_once WWP_PLUGIN_PATH . '/inc/class-wwp-wholesale-frontend.php';
				add_action('init', array(__Class__, 'include_wholesale_functionality'));
			}
		}
		public static function include_wholesale_functionality() {
			if (is_user_logged_in()) {
				$user_info = get_userdata(get_current_user_id());
				$user_role = (array) $user_info->roles;
				if ( !empty($user_role) && in_array('wwp_wholesaler', $user_role) ) {
				    include_once WWP_PLUGIN_PATH . 'inc/class-wwp-wholesale.php';
					include_once WWP_PLUGIN_PATH . 'inc/class-wwp-wholesale-functions.php';
				}
			}
			include_once WWP_PLUGIN_PATH . '/inc/class-wwp-wholesale-registration.php';
		}
		public static function wholesale_admin_notice_error() {
			$class = 'notice notice-error';
			$message = esc_html__('The plugin Wholesale For WooCommerce requires Woocommerce to be installed and activated, in order to work', 'woocommerce-wholesale-pricing');
			printf('<div class="%1$s"><p>%2$s</p></div>', esc_html($class), esc_html($message)); 
		}
	}   
	new Wwp_Wholesale_Pricing();
}

if ( ! function_exists( 'wwl_fs' ) ) {
    // Create a helper function for easy SDK access.
    function wwl_fs() {
        global $wwl_fs;

        if ( ! isset( $wwl_fs ) ) {
            // Include Freemius SDK.
            require_once dirname(__FILE__) . '/freemius/start.php';

            $wwl_fs = fs_dynamic_init( array(
                'id'                  => '8990',
                'slug'                => 'wc-wholesale-lite',
                'type'                => 'plugin',
                'public_key'          => 'pk_466c8d5547c4b56a548fb5ac668a5',
                'is_premium'          => false,
                'has_addons'          => false,
                'has_paid_plans'      => false,
                'menu'                => array(
                    'slug'           => 'wwp_wholesale',
                    'first-path'     => 'admin.php?page=wwp_wholesale',
                    'account'        => false,
                    'contact'        => false,
                    'support'        => false,
                ),
            ) );
        }

        return $wwl_fs;
    }

    // Init Freemius.
    wwl_fs();
    // Signal that SDK was initiated.
    do_action( 'wwl_fs_loaded' );
}
