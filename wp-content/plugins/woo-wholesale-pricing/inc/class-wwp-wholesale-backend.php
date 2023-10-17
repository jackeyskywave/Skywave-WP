<?php
if ( !defined('ABSPATH') ) {
	exit; // Exit if accessed directly
}
/**
 * Class to handle backend functionality
 */
if ( !class_exists('WWP_Wholesale_Pricing_Backend') ) {

	class WWP_Wholesale_Pricing_Backend {
		
		public function __construct() {
			add_action('admin_menu', array($this, 'wwp_register_custom_menu_page1'), 10);
			add_action('admin_menu', array($this, 'wwp_register_custom_menu_page'), 20);
			
			add_action('admin_init', array($this, 'wwp_request_options'));
			add_filter('woocommerce_product_data_tabs', array($this, 'wwp_add_wholesale_product_data_tab'), 99, 1);
			add_action('admin_enqueue_scripts', array($this, 'wwp_admin_script_style'));
			add_action('admin_head', array($this, 'wcpp_custom_style'));
			add_action('woocommerce_product_data_panels', array($this, 'wwp_add_wholesale_product_data_fields'));
			add_action('woocommerce_process_product_meta', array($this, 'wwp_woo_wholesale_fields_save'), 99);
			add_action('woocommerce_product_after_variable_attributes', array($this, 'wwp_variation_settings_fields'), 10, 3);
			add_action('woocommerce_save_product_variation', array($this, 'wwp_save_variation_settings_fields'), 10, 2);
			
		}
		public function wwp_request_options() {
			register_setting('wwp_wholesale_request_notifications', 'wwp_wholesale_user_registration_notification'); 
			register_setting('wwp_wholesale_request_notifications', 'wwp_wholesale_registration_notification_subject'); 
			register_setting('wwp_wholesale_request_notifications', 'wwp_wholesale_registration_notification_body');
		}
		
		public function wwp_admin_script_style() {
		
		
			wp_enqueue_style( 'jquery-ui-styles' );
			wp_enqueue_script( 'jquery-ui-core' );
			wp_enqueue_script( 'jquery-ui-autocomplete' );
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'wwp-script', WWP_PLUGIN_URL . 'assets/js/admin-script.js', array( 'jquery' ), '1.0' );
			wp_enqueue_style('wwp-style', WWP_PLUGIN_URL . 'assets/css/admin-style.css', array(), '1.0' );
		}
		
		public function wwp_register_custom_menu_page1() {
			add_menu_page(
				esc_html__('Wholesale Pricing', 'woocommerce-wholesale-pricing'),
				esc_html__('Wholesale', 'woocommerce-wholesale-pricing'),
				'manage_options',
				'wwp_wholesale',
				array($this, 'wwp_wholesale_page_callback'),
				'dashicons-store',
				58
			);
			add_submenu_page( 
				'wwp_wholesale', 
				esc_html__('Wholesale For WooCommerce', 'woocommerce-wholesale-pricing'), 
				esc_html__('Settings', 'woocommerce-wholesale-pricing'), 
				'manage_options', 
				'wwp_wholesale',
				array($this, 'wwp_wholesale_page_callback')
			);
		}
		
		public function wwp_register_custom_menu_page() {
		    global $submenu;

			add_submenu_page( 
				'wwp_wholesale', 
				esc_html__('Notifications', 'woocommerce-wholesale-pricing'), 
				esc_html__('Notifications', 'woocommerce-wholesale-pricing'), 
				'manage_wholesale_notifications', 
				'wwp_wholesale_notifcations',
				array($this, 'wwp_wholesale_notifications_callback')
			);
		 
			add_submenu_page(
				'wwp_wholesale', 
				esc_html__('Registration Page', 'woocommerce-wholesale-pricing'), 
				esc_html__('Registration Setting', 'woocommerce-wholesale-pricing'),
				'manage_options',
				'wwp-registration-setting', 
				array($this,'wwp_wholesale_registration_page_callback')
			);
			 
			
			$submenu['wwp_wholesale'][] = array( '<b style="color:#fff">Get Pro Version</b>', 'manage_options' , 'https://woocommerce.com/products/wholesale-for-woocommerce/?aff=2878' );  
		}
		
				public function wwp_wholesale_notifications_callback() {
			?>
			 
				<form method="post" action="options.php">
					<?php settings_errors(); ?>
					<?php settings_fields('wwp_wholesale_request_notifications'); ?>
					<?php do_settings_sections('wwp_wholesale_request_notifications'); ?>
					<table class="form-table wwp-main-settings">
						<tr>
							<td colspan="2"><h3><?php esc_html_e('New User Registration Notification', 'woocommerce-wholesale-pricing'); ?></h3><hr></td>	
						</tr>
						<tr>
							<th>
								<label for="wwp_wholesale_user_registration_notification"><?php esc_html_e('Registration Notification', 'woocommerce-wholesale-pricing'); ?></label>
							</th>
							<td>
								<?php $value=get_option('wwp_wholesale_user_registration_notification'); ?>
								<input type="checkbox" name="wwp_wholesale_user_registration_notification" value="yes" id="wwp_wholesale_user_registration_notification" <?php echo checked('yes', $value); ?>>
								<span><?php esc_html_e('When checked, an Email will be sent to user registration requested	.', 'woocommerce-wholesale-pricing'); ?></span>
							</td>
						</tr>
						<tr>
							<th>
								<label for="wwp_wholesale_registration_notification_subject"><?php esc_html_e('Email Subject', 'woocommerce-wholesale-pricing'); ?></label>
							</th>
							<td>
								<?php $value=get_option('wwp_wholesale_registration_notification_subject'); ?>
								<input type="text" name="wwp_wholesale_registration_notification_subject" id="wwp_wholesale_registration_notification_subject" value="<?php echo esc_attr($value); ?>" class="regular-text"/>
							</td>
						</tr>
						<tr>
							<th>
								<label for="wwp_wholesale_registration_notification_body"><?php esc_html_e('Message', 'woocommerce-wholesale-pricing'); ?></label>
							</th>
							<td>
								<?php
									$content = html_entity_decode(get_option('wwp_wholesale_registration_notification_body'));
									echo wp_kses_post(wp_editor(
										$content,
										'wwp_wholesale_registration_notification_body',
										array('textarea_rows' => 3)
									)); 
								?>
								<p><?php esc_html_e('Email body for the new registration user role. Use {first_name}, {last_name}, {username}, {email}, {date}, {time} tag in body to get user email.', 'woocommerce-wholesale-pricing'); ?></p>
							</td>
						</tr>
						
					</table>
					<?php submit_button(); ?>
				</form>
			 
			<?php
		}
		
		public function wwp_wholesale_registration_page_callback() {
			$registrations = get_option('wwp_wholesale_registration_options');
			if ( isset($_POST['save_wwp_registration_setting']) ) {
				if ( isset($_POST['wwp_wholesale_settings_nonce']) || wp_verify_nonce( wc_clean($_POST['wwp_wholesale_settings_nonce']), 'wwp_wholesale_settings_nonce') ) {
					$registrations = isset($_POST['registrations']) ? wc_clean($_POST['registrations']) : '';
					update_option('wwp_wholesale_registration_options', $registrations);
				}
			} 
			?><div id="screen_fix"></div>
			<div id="wwp-global-settings">
			
			<div class="tab" role="tabpanel">
					<!-- Nav tabs -->
				<div class="row">
					<div class="col-md-12">	
					<ul class="nav nav-tabs" role="tablist">
						<li role="presentation" class="section1">
							<a class="<?php echo esc_html_e(wholesale_tab_active('')); ?>" href="<?php echo esc_html_e(wholesale_tab_link('')); ?>" role="tab" data-toggle="tab">General Settings</a>
						</li>
						<li role="presentation" class="section2">
							<a class="<?php echo esc_html_e(wholesale_tab_active('default-fields')); ?>" href="<?php echo esc_html_e(wholesale_tab_link('default-fields')); ?>" role="tab" data-toggle="tab">Default Fields</a>
						</li>
						<li role="presentation" class="section4">
							<a class="<?php echo esc_html_e(wholesale_tab_active('extra-fields')); ?>" href="<?php echo esc_html_e(wholesale_tab_link('extra-fields')); ?>" role="tab" data-toggle="tab">
							<?php  _e('Form Builder <span class="bagpro">Pro</span>' , 'woocommerce-wholesale-pricing'); ?>
							</a>
						</li>
					</ul>
					</div>
				</div>			
			</div>
			 
			<?php if (wholesale_load_form_builder()) { ?>
			
				<form action="" method="post">
					<?php wp_nonce_field('wwp_wholesale_settings_nonce', 'wwp_wholesale_settings_nonce'); ?>
					<div class="container_data">	
					
					<table class="form-table" style="display: <?php echo esc_html_e(wholesale_content_tab_active('')); ?>">
						<tbody>
							<tr scope="row">
								<th><h4><label for=""><?php esc_html_e('Enable Billing Address form Default Fields', 'woocommerce-wholesale-pricing'); ?></label></h4></th>
								<td>
									<p>
										<label for="custommer_billing_address" class="switch">
										<?php
											$checked = '';
										if ( !isset($registrations) || empty($registrations) ) {
											$checked = 'checked';
										} else if ( isset( $registrations['custommer_billing_address'] ) && 'yes' == $registrations['custommer_billing_address'] ) {
											$checked = 'checked';
										} else {
											$checked = '';
										} 
										?>
											<input id="custommer_billing_address" type="checkbox"  value="yes" name="registrations[custommer_billing_address]" <?php echo esc_html($checked); ?> >
											<span class="slider round"></span>
										</label>
										<span data-tip="Enabling this option will allow default WooCommerce billing address field to appear on the front-end form." class="data-tip-top"><span class="woocommerce-help-tip"></span></span>
									</p>
								</td>
							</tr>
						</tbody>
					</table>
					<table class="form-table"  style="display: <?php echo esc_html_e(wholesale_content_tab_active('')); ?>">
						<tbody>
							<tr scope="row">
								<th><h4><label><?php esc_html_e('Enable Shipping Address form Default Fields', 'woocommerce-wholesale-pricing'); ?></label></h4></th>
								<td>
									<p>
										<?php
										$checked = '';
										if ( !isset($registrations) || empty($registrations) ) {
											$checked = 'checked';
										} elseif ( isset( $registrations['custommer_shipping_address'] ) && 'yes' == $registrations['custommer_shipping_address'] ) {
											$checked = 'checked';
										} else {
											$checked = '';
										} 
										?>
										<label for="custommer_shipping_address" class="switch">
											<input id="custommer_shipping_address" type="checkbox" value="yes" name="registrations[custommer_shipping_address]" <?php echo esc_html($checked); ?>>
											<span class="slider round"></span>
										</label>
										<span data-tip="Enabling this option will allow default WooCommerce shipping address field to appear on the front-end form." class="data-tip-top"><span class="woocommerce-help-tip"></span></span>
									</p>
								</td>
							</tr>
						</tbody>
					</table>
 
					 
					<div id="billing_address_fields" style="display:<?php echo esc_html_e(wholesale_content_tab_active('default-fields')); ?>">
					<h3><label for=""><?php esc_html_e('Billing Address form Fields', 'woocommerce-wholesale-pricing'); ?></label></h3>
					<table class="form-table">
						<tbody>
							<tr scope="row">
								<th><label for=""><?php esc_html_e( 'Billing First Name', 'woocommerce-wholesale-pricing' ); ?></label></th>
								<td>
									<p>
										<input type="text" id="billing_first_name" placeholder="Custom label" name="registrations[billing_first_name]" value="<?php echo isset( $registrations['billing_first_name'] ) ? esc_attr( $registrations['billing_first_name'] ) : ''; ?>" >
										<input class="inp-cbx" style="display: none" type="checkbox" id="enable_billing_first_name" name="registrations[enable_billing_first_name]" value="yes" <?php echo ( isset( $registrations['enable_billing_first_name'] ) && 'yes' == $registrations['enable_billing_first_name'] ) ? 'checked' : ''; ?>>
										<label class="cbx cbx-square" for="enable_billing_first_name">
											<span>
												<svg width="12px" height="9px" viewbox="0 0 12 9">
													<polyline points="1 5 4 8 11 1"></polyline>
												</svg>
											</span>
											<span><?php esc_html_e( 'Enable', 'woocommerce-wholesale-pricing' ); ?></span>
										</label>
										<div class="wwp-tags">
											<?php esc_html_e( ' [ default label : "First Name" ]', 'woocommerce-wholesale-pricing' ); ?>
										</div>													
										
									</p>
								</td>
							</tr>
							<tr scope="row">
								<th><label for=""><?php esc_html_e( 'Billing Last Name', 'woocommerce-wholesale-pricing' ); ?></label></th>
								<td>														
									<p>
										<input type="text" id="billing_last_name" placeholder="Custom label" name="registrations[billing_last_name]" value="<?php echo isset( $registrations['billing_last_name'] ) ? esc_attr( $registrations['billing_last_name'] ) : ''; ?>">
										<input class="inp-cbx" style="display: none" type="checkbox" id="enable_billing_last_name" name="registrations[enable_billing_last_name]" value="yes" <?php echo ( isset( $registrations['enable_billing_last_name'] ) && 'yes' == $registrations['enable_billing_last_name'] ) ? 'checked' : ''; ?>>
										<label class="cbx cbx-square" for="enable_billing_last_name">
											<span>
												<svg width="12px" height="9px" viewbox="0 0 12 9">
													<polyline points="1 5 4 8 11 1"></polyline>
												</svg>
											</span>
											<span><?php esc_html_e( 'Enable', 'woocommerce-wholesale-pricing' ); ?></span>
										</label>
										<div class="wwp-tags">
											<?php esc_html_e( ' [ default label : "Last Name" ] ', 'woocommerce-wholesale-pricing' ); ?>
										</div>													
										
									</p>
								</td>
							</tr>
							<tr scope="row" >
								<th><label for=""><?php esc_html_e( 'Company', 'woocommerce-wholesale-pricing' ); ?></label></th>
								<td>														
									<p>
										<input type="text" id="billing_company" placeholder="Custom label" name="registrations[billing_company]" value="<?php echo isset( $registrations['billing_company'] ) ? esc_attr( $registrations['billing_company'] ) : ''; ?>" >
										<input class="inp-cbx" style="display: none" type="checkbox" id="enable_billing_company" name="registrations[enable_billing_company]" value="yes" <?php echo ( isset( $registrations['enable_billing_company'] ) && 'yes' == $registrations['enable_billing_company'] ) ? 'checked' : ''; ?>>
										<label class="cbx cbx-square" for="enable_billing_company">
											<span>
												<svg width="12px" height="9px" viewbox="0 0 12 9">
													<polyline points="1 5 4 8 11 1"></polyline>
												</svg>
											</span>
											<span><?php esc_html_e( 'Enable', 'woocommerce-wholesale-pricing' ); ?></span>
										</label>
										<div class="wwp-tags">
											<?php esc_html_e( ' [ default label : "Company" ] ', 'woocommerce-wholesale-pricing' ); ?>
										</div>												
										
									</p>
								</td>
							</tr>
							<tr scope="row" >
								<th><label for=""><?php esc_html_e( 'Address line 1 ', 'woocommerce-wholesale-pricing' ); ?></label></th>
								<td>													
									<p>
										<input type="text" id="billing_address_1" placeholder="Custom label" name="registrations[billing_address_1]" value="<?php echo isset( $registrations['billing_address_1'] ) ? esc_attr( $registrations['billing_address_1'] ) : ''; ?>">
										<input class="inp-cbx" style="display: none" type="checkbox" id="enable_billing_address_1" name="registrations[enable_billing_address_1]" value="yes"  <?php echo ( isset( $registrations['enable_billing_address_1'] ) && 'yes' == $registrations['enable_billing_address_1'] ) ? 'checked' : ''; ?>>
										<label class="cbx cbx-square" for="enable_billing_address_1">
											<span>
												<svg width="12px" height="9px" viewbox="0 0 12 9">
													<polyline points="1 5 4 8 11 1"></polyline>
												</svg>
											</span>
											<span><?php esc_html_e( 'Enable', 'woocommerce-wholesale-pricing' ); ?></span>
										</label>
										<div class="wwp-tags">
											<?php esc_html_e( ' [ default label : "Address line 1" ] ', 'woocommerce-wholesale-pricing' ); ?>
										</div>														
										
									</p>
								</td>
							</tr>
							<tr scope="row" >
								<th><label for=""><?php esc_html_e( 'Address line 2 ', 'woocommerce-wholesale-pricing' ); ?></label></th>
								<td>
									<p>
										<input type="text" id="billing_address_2" placeholder="Custom label" name="registrations[billing_address_2]" value="<?php echo isset( $registrations['billing_address_2'] ) ? esc_attr( $registrations['billing_address_2'] ) : ''; ?>" >
										<input class="inp-cbx" style="display: none" type="checkbox" id="enable_billing_address_2" name="registrations[enable_billing_address_2]" value="yes" <?php echo ( isset( $registrations['enable_billing_address_2'] ) && 'yes' == $registrations['enable_billing_address_2'] ) ? 'checked' : ''; ?>>
										<label class="cbx cbx-square" for="enable_billing_address_2">
											<span>
												<svg width="12px" height="9px" viewbox="0 0 12 9">
													<polyline points="1 5 4 8 11 1"></polyline>
												</svg>
											</span>
											<span><?php esc_html_e( 'Enable', 'woocommerce-wholesale-pricing' ); ?></span>
										</label>
										<div class="wwp-tags">
											<?php esc_html_e( '  [ default label : "Address line 2" ]', 'woocommerce-wholesale-pricing' ); ?>
										</div>													
										
									</p>
								</td>
							</tr>
							<tr scope="row" >
								<th><label for=""><?php esc_html_e( 'City ', 'woocommerce-wholesale-pricing' ); ?></label></th>
								<td>														
									<p>
										<input type="text" id="billing_city" placeholder="Custom label" name="registrations[billing_city]" value="<?php echo isset( $registrations['billing_city'] ) ? esc_attr( $registrations['billing_city'] ) : ''; ?>"  >															
										<input class="inp-cbx" style="display: none" type="checkbox" id="enable_billing_city" name="registrations[enable_billing_city]" value="yes" <?php echo ( isset( $registrations['enable_billing_city'] ) && 'yes' == $registrations['enable_billing_city'] ) ? 'checked' : ''; ?>>
										<label class="cbx cbx-square" for="enable_billing_city">
											<span>
												<svg width="12px" height="9px" viewbox="0 0 12 9">
													<polyline points="1 5 4 8 11 1"></polyline>
												</svg>
											</span>
											<span><?php esc_html_e( 'Enable', 'woocommerce-wholesale-pricing' ); ?></span>
										</label>
										<div class="wwp-tags">
											<?php esc_html_e( '  [ default label : "City" ]', 'woocommerce-wholesale-pricing' ); ?>
										</div>
										
									</p>
								</td>
							</tr>
							<tr scope="row" >
								<th><label for=""><?php esc_html_e( 'Postcode / ZIP ', 'woocommerce-wholesale-pricing' ); ?></label></th>
								<td>
									<p>
										<input type="text" id="billing_post_code"  placeholder="Custom label" name="registrations[billing_post_code]" value="<?php echo isset( $registrations['billing_post_code'] ) ? esc_attr( $registrations['billing_post_code'] ) : ''; ?>"  >
										<input class="inp-cbx" style="display: none" type="checkbox" id="enable_billing_post_code" name="registrations[enable_billing_post_code]" value="yes" <?php echo ( isset( $registrations['enable_billing_post_code'] ) && 'yes' == $registrations['enable_billing_post_code'] ) ? 'checked' : ''; ?>>
										<label class="cbx cbx-square" for="enable_billing_post_code">
											<span>
												<svg width="12px" height="9px" viewbox="0 0 12 9">
													<polyline points="1 5 4 8 11 1"></polyline>
												</svg>
											</span>
											<span><?php esc_html_e( 'Enable', 'woocommerce-wholesale-pricing' ); ?></span>
										</label>
										<div class="wwp-tags">
											<?php esc_html_e( '  [ default label : "Postcode / ZIP" ]', 'woocommerce-wholesale-pricing' ); ?>
										</div>												
										
									</p>
								</td>
							</tr>
							<tr scope="row" >
								<th><label for=""><?php esc_html_e( 'Countries ', 'woocommerce-wholesale-pricing' ); ?></label></th>
								<td>
									<p>
										<input type="text" id="billing_countries" placeholder="Custom label" name="registrations[billing_countries]" value="<?php echo isset( $registrations['billing_countries'] ) ? esc_attr( $registrations['billing_countries'] ) : ''; ?>" >
										<input class="inp-cbx" style="display: none" type="checkbox" id="enable_billing_country" name="registrations[enable_billing_country]" value="yes" <?php echo ( isset( $registrations['enable_billing_country'] ) && 'yes' == $registrations['enable_billing_country'] ) ? 'checked' : ''; ?>>
										<label class="cbx cbx-square" for="enable_billing_country">
											<span>
												<svg width="12px" height="9px" viewbox="0 0 12 9">
													<polyline points="1 5 4 8 11 1"></polyline>
												</svg>
											</span>
											<span><?php esc_html_e( 'Enable', 'woocommerce-wholesale-pricing' ); ?></span>
										</label>
										<div class="wwp-tags">
											<?php esc_html_e( '  [ default label : "Countries" ]', 'woocommerce-wholesale-pricing' ); ?>
										</div>
									</p>
								</td>
							</tr>
							<tr scope="row" >
								<th><label for=""><?php esc_html_e( 'States ', 'woocommerce-wholesale-pricing' ); ?></label></th>
								<td>
									<p>
										<input type="text" id="billing_state"  placeholder="Custom label" name="registrations[billing_state]" value="<?php echo isset( $registrations['billing_state'] ) ? esc_attr( $registrations['billing_state'] ) : ''; ?>" >
										<input class="inp-cbx" style="display: none" type="checkbox" id="enable_billing_state" name="registrations[enable_billing_state]" value="yes" <?php echo ( isset( $registrations['enable_billing_state'] ) && 'yes' == $registrations['enable_billing_state'] ) ? 'checked' : ''; ?>>
										<label class="cbx cbx-square" for="enable_billing_state">
											<span>
												<svg width="12px" height="9px" viewbox="0 0 12 9">
													<polyline points="1 5 4 8 11 1"></polyline>
												</svg>
											</span>
											<span><?php esc_html_e( 'Enable', 'woocommerce-wholesale-pricing' ); ?></span>
										</label>																												
										<div class="wwp-tags">
											<?php esc_html_e( '  [ default label : "States" ]', 'woocommerce-wholesale-pricing' ); ?>
										</div>
									</p>
								</td>
							</tr>
							<tr scope="row" >
								<th><label><?php esc_html_e( 'Phone ', 'woocommerce-wholesale-pricing' ); ?></label></th>
								<td>
									<p>
										<input type="text" id="billing_phone" placeholder="Custom label" name="registrations[billing_phone]" value="<?php echo isset( $registrations['billing_phone'] ) ? esc_attr( $registrations['billing_phone'] ) : ''; ?>">
										<input class="inp-cbx" style="display: none" type="checkbox" id="enable_billing_phone" name="registrations[enable_billing_phone]" value="yes" <?php echo ( isset( $registrations['enable_billing_phone'] ) && 'yes' == $registrations['enable_billing_phone'] ) ? 'checked' : ''; ?>>
										<label class="cbx cbx-square" for="enable_billing_phone">
											<span>
												<svg width="12px" height="9px" viewbox="0 0 12 9">
													<polyline points="1 5 4 8 11 1"></polyline>
												</svg>
											</span>
											<span><?php esc_html_e( 'Enable', 'woocommerce-wholesale-pricing' ); ?></span>
										</label>												
										<div class="wwp-tags">
											<?php esc_html_e( '  [ default label : "Phone" ]', 'woocommerce-wholesale-pricing' ); ?>
										</div>
									</p>
								</td>
							</tr>
						</tbody>
					</table>
					</div>
					
					<div id="shipping_address_fields" style="display:<?php echo esc_html_e(wholesale_content_tab_active('default-fields')); ?>;">
					<h3><label><?php esc_html_e('Shipping Address form Fields', 'woocommerce-wholesale-pricing'); ?></label></h3>
					<table class="form-table">
						<tbody>
							<tr scope="row">
								<th><label for=""><?php esc_html_e( 'Shipping First Name', 'woocommerce-wholesale-pricing' ); ?></label></th>
								<td>														
									<p>
										<input type="text" id="shipping_first_name" placeholder="Custom label" name="registrations[shipping_first_name]" value="<?php echo isset( $registrations['shipping_first_name'] ) ? esc_attr( $registrations['shipping_first_name'] ) : ''; ?>">
										<input class="inp-cbx" style="display: none" type="checkbox" id="enable_shipping_first_name" name="registrations[enable_shipping_first_name]" value="yes" <?php echo ( isset( $registrations['enable_shipping_first_name'] ) && 'yes' == $registrations['enable_shipping_first_name'] ) ? 'checked' : ''; ?>>
										<label class="cbx cbx-square" for="enable_shipping_first_name">
											<span>
												<svg width="12px" height="9px" viewbox="0 0 12 9">
													<polyline points="1 5 4 8 11 1"></polyline>
												</svg>
											</span>
											<span><?php esc_html_e( 'Enable', 'woocommerce-wholesale-pricing' ); ?></span>
										</label>												
										<div class="wwp-tags">
											<?php esc_html_e( '  [ default label : "First Name" ]', 'woocommerce-wholesale-pricing' ); ?>
										</div>
									</p>
								</td>
							</tr>
							<tr scope="row">
								<th><label for=""><?php esc_html_e( 'Shipping Last Name', 'woocommerce-wholesale-pricing' ); ?></label></th>
								<td>
									<p>
										<input type="text" id="shipping_last_name" placeholder="Custom label" name="registrations[shipping_last_name]" value="<?php echo isset( $registrations['shipping_last_name'] ) ? esc_attr( $registrations['shipping_last_name'] ) : ''; ?>">
										<input class="inp-cbx" style="display: none" type="checkbox" id="enable_shipping_last_name" name="registrations[enable_shipping_last_name]" value="yes" <?php echo ( isset( $registrations['enable_shipping_last_name'] ) && 'yes' == $registrations['enable_shipping_last_name'] ) ? 'checked' : ''; ?>>
										<label class="cbx cbx-square" for="enable_shipping_last_name">
											<span>
												<svg width="12px" height="9px" viewbox="0 0 12 9">
													<polyline points="1 5 4 8 11 1"></polyline>
												</svg>
											</span>
											<span><?php esc_html_e( 'Enable', 'woocommerce-wholesale-pricing' ); ?></span>
										</label>												
										<div class="wwp-tags">
											<?php esc_html_e( '  [ default label : "Last Name" ]', 'woocommerce-wholesale-pricing' ); ?>
										</div>
									</p>
								</td>
							</tr>
							<tr scope="row" >
								<th><label for=""><?php esc_html_e( 'Company', 'woocommerce-wholesale-pricing' ); ?></label></th>
								<td>														
									<p>
										<input type="text" id="shipping_company" placeholder="Custom label" name="registrations[shipping_company]" value="<?php echo isset( $registrations['shipping_company'] ) ? esc_attr( $registrations['shipping_company'] ) : ''; ?>">
										<input class="inp-cbx" style="display: none" type="checkbox" id="enable_shipping_company" name="registrations[enable_shipping_company]" value="yes" <?php echo ( isset( $registrations['enable_shipping_company'] ) && 'yes' == $registrations['enable_shipping_company'] ) ? 'checked' : ''; ?>>
										<label class="cbx cbx-square" for="enable_shipping_company">
											<span>
												<svg width="12px" height="9px" viewbox="0 0 12 9">
													<polyline points="1 5 4 8 11 1"></polyline>
												</svg>
											</span>
											<span><?php esc_html_e( 'Enable', 'woocommerce-wholesale-pricing' ); ?></span>
										</label>												
										<div class="wwp-tags">
											<?php esc_html_e( '  [ default label : "Company" ] ', 'woocommerce-wholesale-pricing' ); ?>
										</div>
									</p>
								</td>
							</tr>
							<tr scope="row" >
								<th><label for=""><?php esc_html_e( 'Address line 1 ', 'woocommerce-wholesale-pricing' ); ?></label></th>
								<td>
									
									<p>
										<input type="text" id="shipping_address_1" placeholder="Custom label" name="registrations[shipping_address_1]" value="<?php echo isset( $registrations['shipping_address_1'] ) ? esc_attr( $registrations['shipping_address_1'] ) : ''; ?>">
										<input class="inp-cbx" style="display: none" type="checkbox" id="enable_shipping_address_1" name="registrations[enable_shipping_address_1]" value="yes" value="yes" <?php echo ( isset( $registrations['enable_shipping_address_1'] ) && 'yes' == $registrations['enable_shipping_address_1'] ) ? 'checked' : ''; ?>>
										<label class="cbx cbx-square" for="enable_shipping_address_1">
											<span>
												<svg width="12px" height="9px" viewbox="0 0 12 9">
													<polyline points="1 5 4 8 11 1"></polyline>
												</svg>
											</span>
											<span><?php esc_html_e( 'Enable', 'woocommerce-wholesale-pricing' ); ?></span>
										</label>												
										<div class="wwp-tags">
											<?php esc_html_e( '  [ default label : "Address line 1" ] ', 'woocommerce-wholesale-pricing' ); ?>
										</div>
									</p>
								</td>
							</tr>
							<tr scope="row" >
								<th><label for=""><?php esc_html_e( 'Address line 2 ', 'woocommerce-wholesale-pricing' ); ?></label></th>
								<td>
									
									<p>
										<input type="text" id="shipping_address_2" placeholder="Custom label" name="registrations[shipping_address_2]" value="<?php echo isset( $registrations['shipping_address_2'] ) ? esc_attr( $registrations['shipping_address_2'] ) : ''; ?>">
										<input class="inp-cbx" style="display: none" type="checkbox" id="enable_shipping_address_2" name="registrations[enable_shipping_address_2]" value="yes" <?php echo ( isset( $registrations['enable_shipping_address_2'] ) && 'yes' == $registrations['enable_shipping_address_2'] ) ? 'checked' : ''; ?>>
										<label class="cbx cbx-square" for="enable_shipping_address_2">
											<span>
												<svg width="12px" height="9px" viewbox="0 0 12 9">
													<polyline points="1 5 4 8 11 1"></polyline>
												</svg>
											</span>
											<span><?php esc_html_e( 'Enable', 'woocommerce-wholesale-pricing' ); ?></span>
										</label>												
										<div class="wwp-tags">
											<?php esc_html_e( '  [ default label : "Address line 2" ] ', 'woocommerce-wholesale-pricing' ); ?>
										</div>
									</p>
								</td>
							</tr>
							<tr scope="row" >
								<th><label for=""><?php esc_html_e( 'City ', 'woocommerce-wholesale-pricing' ); ?></label></th>
								<td>
									
									<p>
										<input type="text" id="shipping_city" placeholder="Custom label" name="registrations[shipping_city]" value="<?php echo isset( $registrations['shipping_city'] ) ? esc_attr( $registrations['shipping_city'] ) : ''; ?>">
										<input class="inp-cbx" style="display: none" type="checkbox" id="enable_shipping_city" name="registrations[enable_shipping_city]" value="yes" <?php echo ( isset( $registrations['enable_shipping_city'] ) && 'yes' == $registrations['enable_shipping_city'] ) ? 'checked' : ''; ?>>
										<label class="cbx cbx-square" for="enable_shipping_city">
											<span>
												<svg width="12px" height="9px" viewbox="0 0 12 9">
													<polyline points="1 5 4 8 11 1"></polyline>
												</svg>
											</span>
											<span><?php esc_html_e( 'Enable', 'woocommerce-wholesale-pricing' ); ?></span>
										</label>												
										<div class="wwp-tags">
											<?php esc_html_e( '  [ default label : "City" ] ', 'woocommerce-wholesale-pricing' ); ?>
										</div>
									</p>
								</td>
							</tr>
							<tr scope="row" >
								<th><label for=""><?php esc_html_e( 'Postcode / ZIP ', 'woocommerce-wholesale-pricing' ); ?></label></th>
								<td>
									
									<p>
										<input type="text" id="shipping_post_code" placeholder="Custom label" name="registrations[shipping_post_code]" value="<?php echo isset( $registrations['shipping_post_code'] ) ? esc_attr( $registrations['shipping_post_code'] ) : ''; ?>">
										<input class="inp-cbx" style="display: none" type="checkbox" id="enable_shipping_post_code" name="registrations[enable_shipping_post_code]" value="yes" <?php echo ( isset( $registrations['enable_shipping_post_code'] ) && 'yes' == $registrations['enable_shipping_post_code'] ) ? 'checked' : ''; ?>>
										<label class="cbx cbx-square" for="enable_shipping_post_code">
											<span>
												<svg width="12px" height="9px" viewbox="0 0 12 9">
													<polyline points="1 5 4 8 11 1"></polyline>
												</svg>
											</span>
											<span><?php esc_html_e( 'Enable', 'woocommerce-wholesale-pricing' ); ?></span>
										</label>												
										<div class="wwp-tags">
											<?php esc_html_e( '  [ default label : "Postcode / ZIP" ] ', 'woocommerce-wholesale-pricing' ); ?>
										</div>
									</p>
								</td>
							</tr>
							<tr scope="row" >
								<th><label for=""><?php esc_html_e( 'Countries ', 'woocommerce-wholesale-pricing' ); ?></label></th>
								<td>
									
									<p>
										<input type="text" id="shipping_countries" placeholder="Custom label" name="registrations[shipping_countries]" value="<?php echo isset( $registrations['shipping_countries'] ) ? esc_attr( $registrations['shipping_countries'] ) : ''; ?>">
										<input class="inp-cbx" style="display: none" type="checkbox" id="enable_shipping_country" name="registrations[enable_shipping_country]" value="yes" <?php echo ( isset( $registrations['enable_shipping_country'] ) && 'yes' == $registrations['enable_shipping_country'] ) ? 'checked' : ''; ?>>
										<label class="cbx cbx-square" for="enable_shipping_country">
											<span>
												<svg width="12px" height="9px" viewbox="0 0 12 9">
													<polyline points="1 5 4 8 11 1"></polyline>
												</svg>
											</span>
											<span><?php esc_html_e( 'Enable', 'woocommerce-wholesale-pricing' ); ?></span>
										</label>												
										<div class="wwp-tags">
											<?php esc_html_e( '  [ default label : "Countries" ] ', 'woocommerce-wholesale-pricing' ); ?>
										</div>
									</p>
								</td>
							</tr>
							<tr scope="row">
								<th><label for=""><?php esc_html_e( 'States ', 'woocommerce-wholesale-pricing' ); ?></label></th>
								<td>
									<p>
										<input type="text" id="shipping_state" placeholder="Custom label" name="registrations[shipping_state]" value="<?php echo isset( $registrations['shipping_state'] ) ? esc_attr( $registrations['shipping_state'] ) : ''; ?>">
										<input class="inp-cbx" style="display: none" type="checkbox" id="enable_shipping_state" name="registrations[enable_shipping_state]" value="yes" <?php echo ( isset( $registrations['enable_shipping_state'] ) && 'yes' == $registrations['enable_shipping_state'] ) ? 'checked' : ''; ?>>
										<label class="cbx cbx-square" for="enable_shipping_state">
											<span>
												<svg width="12px" height="9px" viewbox="0 0 12 9">
													<polyline points="1 5 4 8 11 1"></polyline>
												</svg>
											</span>
											<span><?php esc_html_e( 'Enable', 'woocommerce-wholesale-pricing' ); ?></span>
										</label>												
										<div class="wwp-tags">
											<?php esc_html_e( '  [ default label : "States" ] ', 'woocommerce-wholesale-pricing' ); ?>
										</div>
									</p>
								</td>
							</tr>
						</tbody>
					</table>
					</div>
					</div>
					
				<div class="save_button_lite_container"><button name="save_wwp_registration_setting" class="custom-button-save-changes" type="submit" value="Save changes"><?php esc_html_e('Save changes', 'woocommerce-wholesale-pricing'); ?></button></div>
						
				<div class="map_shortcode_callback">
					<h5>Shortcode</h5>
					<p> <?php esc_html_e('Copy following shortcode, and paste in page where you would like to display wholesaler registration form.', 'woocommerce-wholesale-pricing'); ?></p>
					<p> 
					<input type="text" onfocus="this.select();" value="[wwp_registration_form]" readonly="readonly" name="shortcode" class="large-text code">
					</p>
				</div>
				 
				</form>
				<?php 
			} else {
				?>
				<div class="container_data" style="width: 96%!important;">	
				<h1>GET IN PRO VERSION<h1> 
				<p class="propragraph">Upgrade to pro version of Wholesale for WooCommerce to access form builder In Pro Version you can add extra fields into your registration form which gives you more control over registration fields. Also, have the option to display Tax ID in billing address with Formbuilder and more features are available in <a target="_blank" href="https://woocommerce.com/products/wholesale-for-woocommerce/?aff=2878" class="btn " >Wholesale for WooCommerce Pro Version.</a></p>
				<a target="_blank" href="https://woocommerce.com/products/wholesale-for-woocommerce/?aff=2878">
				<img src="<?php echo WWP_PLUGIN_URL ?>assets/images/builder.png " style=" width: 98%;"></a> 
				<a target="_blank" href="https://woocommerce.com/products/wholesale-for-woocommerce/?aff=2878" class="btn wholesale_btn" >Get Wholesale Pro Version</a>
				<a target="_blank" href="https://docs.woocommerce.com/document/wholesale/wholesale-for-woocommerce/store-owners-guide-basic-concepts/#section-13" class="btn wholesale_btn" >FormBuilder Documentation</a>
				</div>
				<?php 
			}
			?>
			</div>
			<?php
		}
		
		public function wwp_wholesale_page_callback() {
			$settings=get_option('wwp_wholesale_pricing_options', true);
			if ( isset($_POST['save-wwp_wholesale']) ) {
				if ( isset($_POST['wwp_wholesale_register_nonce']) || wp_verify_nonce( wc_clean($_POST['wwp_wholesale_register_nonce']), 'wwp_wholesale_register_nonce') ) {
					$settings = isset($_POST['options']) ? wc_clean($_POST['options']) : '';
					$settings['enable_registration_page'] = isset($settings['enable_registration_page']) ? 'yes' : 'no';
					$settings['wholesaler_prodcut_only'] = isset($settings['wholesaler_prodcut_only']) ? 'yes' : 'no';
					$settings['enable_upgrade'] = isset($settings['enable_upgrade']) ? 'yes' : 'no';
					$settings['disable_auto_role'] = isset($settings['disable_auto_role']) ? 'yes' : 'no';
					$settings['retailer_disabled'] = isset($settings['retailer_disabled']) ? 'yes' : 'no';
					$settings['save_price_disabled'] = isset($settings['save_price_disabled']) ? 'yes' : 'no';
					update_option('wwp_wholesale_pricing_options', $settings);
					
					if ( isset($_POST['_wwp_enable_wholesale_item']) ) {
						update_option('_wwp_enable_wholesale_item', 'yes');
					} else {
						update_option('_wwp_enable_wholesale_item', 'no');
					}
					if ( isset($_POST['wwp_wholesale_disable_coupons']) ) {
						update_option('wwp_wholesale_disable_coupons', 'yes');
					} else {
						update_option('wwp_wholesale_disable_coupons', 'no');
					}
					if ( isset($_POST['_wwp_wholesale_amount']) ) {
						update_option('_wwp_wholesale_amount', wc_clean($_POST['_wwp_wholesale_amount']) );
					} else {
						update_option('_wwp_wholesale_amount', '');
					}
					if ( isset($_POST['_wwp_wholesale_type']) ) {
						update_option('_wwp_wholesale_type', wc_clean($_POST['_wwp_wholesale_type']) );
					} else {
						update_option('_wwp_wholesale_type', '');
					}
					if ( isset($_POST['_wwp_wholesale_min_quantity']) ) {
						update_option('_wwp_wholesale_min_quantity', wc_clean($_POST['_wwp_wholesale_min_quantity']) );
					} else {
						update_option('_wwp_wholesale_min_quantity', '');
					}
					
				}
			} 
			 
			$disable_coupons = get_option('wwp_wholesale_disable_coupons');
			$enable 		 = get_option('_wwp_enable_wholesale_item');
			$amount			 = get_option('_wwp_wholesale_amount');
			$type 			 = get_option('_wwp_wholesale_type');
			$qty             = get_option('_wwp_wholesale_min_quantity');	 
			?>
			<form action="" method="post" id="wwp-global-settings">
				<h2><?php esc_html_e('Wholesale For WooCommerce', 'woocommerce-wholesale-pricing'); ?></h2><hr>
				<?php wp_nonce_field('wwp_wholesale_register_nonce', 'wwp_wholesale_register_nonce'); ?>
				
				<div class="tab" role="tabpanel">
					<!-- Nav tabs -->
				<div class="row">
					<div class="col-md-3 col-sm-12">	
						<ul class="nav nav-tabs" role="tablist">
							<li role="presentation" class="section1">
								<a href="javascript:void(0)" role="tab" data-toggle="tab">General</a>
							</li>
							<li role="presentation" class="section2">
								<a href="javascript:void(0)" role="tab" data-toggle="tab">Wholesale Price Global</a>
							</li>
							<li role="presentation" class="section3">
								<a href="javascript:void(0)" role="tab" data-toggle="tab">Labels</a>
							</li>
							<li role="presentation" class="section4">
								<a href="javascript:void(0)" role="tab" data-toggle="tab">Pro Features</a>
							</li>
						</ul>
					</div>
					
					<div class="col-md-9 col-sm-12">	
						<div class="tab-content tabs">
							<div role="tabpanel" class="tab-pane fade" id="section1">
								<table class="form-table">
									<tr>
										<th>
										<label for="wwp_wholesale_disable_coupons"><?php esc_html_e('Disable Coupons', 'woocommerce-wholesale-pricing'); ?></label>
										</th>
										<td scope="row">
										<input class="inp-cbx" style="display: none" type="checkbox" name="wwp_wholesale_disable_coupons" id="wwp_wholesale_disable_coupons" value="yes" <?php echo ( 'yes' == $disable_coupons ) ? 'checked' : ''; ?>>
										<label class="cbx cbx-square" for="wwp_wholesale_disable_coupons">
											<span>
												<svg width="12px" height="9px" viewbox="0 0 12 9">
													<polyline points="1 5 4 8 11 1"></polyline>
												</svg>
											</span>
											<span><?php esc_html_e('Disable Coupons for wholesale user role.', 'woocommerce-wholesale-pricing'); ?></span>
										</label>
										</td>
									</tr>
								</table>
								 
								<table class="form-table">
										<tr scope="row">
											<th><label for=""><?php esc_html_e( 'Hide price', 'woocommerce-wholesale-pricing' ); ?></label></th>
											<td>
												<input class="inp-cbx" style="display: none" id="price_hide" type="checkbox" value="yes" name="options[price_hide]" <?php echo ( isset( $settings['price_hide'] ) && 'yes' == $settings['price_hide'] ) ? 'checked' : ''; ?>>
												<label class="cbx cbx-square" for="price_hide">
													<span>
														<svg width="12px" height="9px" viewbox="0 0 12 9">
															<polyline points="1 5 4 8 11 1"></polyline>
														</svg>
													</span>
													<span><?php esc_html_e( 'Hide retail prices until user gets logged in', 'woocommerce-wholesale-pricing' ); ?></span>												
												</label>
											</td>
										</tr>
										<tr scope="row">
											<th><label for="display_link_text"><?php esc_html_e( 'Label for login link', 'woocommerce-wholesale-pricing' ); ?></label></th>
											<td><input type="text" class="regular-text" name="options[display_link_text]" id="display_link_text" value="<?php echo isset( $settings['display_link_text'] ) ? esc_html( $settings['display_link_text'] ) : ''; ?>">
											<span data-tip="This login link will appear on every product if Hide price option is checked" class="data-tip-top"><span class="woocommerce-help-tip"></span></span>
											</td>
										</tr>
										<tr scope="row">
											<th><label for=""><?php esc_html_e('Enable Upgrade Tab', 'woocommerce-wholesale-pricing'); ?></label></th>
											<td>
											<input class="inp-cbx" style="display: none" id="enable_upgrade" type="checkbox" value="yes" name="options[enable_upgrade]" <?php echo ( isset($settings['enable_upgrade']) && 'yes' == $settings['enable_upgrade'] ) ? 'checked' : ''; ?>>
												<label  class="cbx cbx-square"  for="enable_upgrade">
													<span>
														<svg width="12px" height="9px" viewbox="0 0 12 9">
															<polyline points="1 5 4 8 11 1"></polyline>
														</svg>
													</span> 
													<span>
													<?php esc_html_e(' Enable wholesale upgrade tab on my account page for non wholesale users', 'woocommerce-wholesale-pricing'); ?>
													</span> 
												</label>
											</td>
										</tr>
										<tr scope="row">
											<th><label for=""><?php esc_html_e('Upgrade Tab Text', 'woocommerce-wholesale-pricing'); ?></label></th>
											<td>
												<label for="upgrade_tab_text">
													<input type="text" class="regular-text" name="options[upgrade_tab_text]" id="upgrade_tab_text" value="<?php echo isset($settings['upgrade_tab_text']) ? esc_html($settings['upgrade_tab_text']) : ''; ?>" Placeholder="Label for Upgrade to Wholesaler tab">
												</label>
												<span data-tip='Display any text you want on the "Upgrade to Wholesaler" tab.' class="data-tip-top"><span class="woocommerce-help-tip"></span></span>
											</td>
										</tr>
										
										<tr scope="row">
											<th>
												<label for=""><?php esc_html_e( 'Disable Auto Approval', 'woocommerce-wholesale-pricing' ); ?></label>
											</th>
											<td>
												<p>
													<input class="inp-cbx" style="display: none" id="disable_auto_role" type="checkbox" value="yes" name="options[disable_auto_role]" <?php echo ( isset( $settings['disable_auto_role'] ) && 'yes' == $settings['disable_auto_role'] ) ? 'checked' : ''; ?>>
													<label class="cbx cbx-square" for="disable_auto_role">
														<span>
															<svg width="12px" height="9px" viewbox="0 0 12 9">
																<polyline points="1 5 4 8 11 1"></polyline>
															</svg>
														</span>
														<span><?php esc_html_e( ' Check this option to disable auto approval for wholesale user role registration requests', 'woocommerce-wholesale-pricing' ); ?></span>
													</label>
												</p>
											</td>
										</tr>
								</table>
							</div>
							<div role="tabpanel" class="tab-pane fade" id="section2">	
								<table class="form-table">
									<tr>
										<th>
											<label for="_wwp_enable_wholesale_item">
												<?php esc_html_e('Enable Wholesale Prices', 'woocommerce-wholesale-pricing'); ?>
											</label>
										</th>
										<td scope="row">
											<input class="inp-cbx" style="display: none" type="checkbox" name="_wwp_enable_wholesale_item" id="_wwp_enable_wholesale_item" value="yes" <?php checked('yes', $enable); ?>>
											<label class="cbx cbx-square" for="_wwp_enable_wholesale_item">
												<span>
													<svg width="12px" height="9px" viewbox="0 0 12 9">
														<polyline points="1 5 4 8 11 1"></polyline>
													</svg>
												</span>
												<span><?php esc_html_e('Enable wholesale prices.', 'woocommerce-wholesale-pricing'); ?></span>
											</label>
										</td>
									</tr>
									<tr>
										<th>
											<label for="_wwp_wholesale_type">
												<?php esc_html_e('Wholesale Discount Type', 'woocommerce-wholesale-pricing'); ?>
											</label>
										</th>
										<td scope="row">
											<select name="_wwp_wholesale_type" id="_wwp_wholesale_type" class="regular-text">
												<option value="fixed" <?php selected('fixed', $type); ?>><?php esc_html_e('Fixed Amount', 'woocommerce-wholesale-pricing'); ?></option>
												<option value="percent" <?php selected('percent', $type); ?>><?php esc_html_e('Percentage', 'woocommerce-wholesale-pricing'); ?></option>
											</select>
											<p><?php esc_html_e('Price type for wholesale products.', 'woocommerce-wholesale-pricing'); ?></p>
										</td>
									</tr>
									<tr>
										<th>
											<label for="_wwp_wholesale_amount">
												<?php esc_html_e('Enter Wholesale Amount', 'woocommerce-wholesale-pricing'); ?>
											</label>
										</th>
										<td scope="row">
											<input type="text" name="_wwp_wholesale_amount" id="_wwp_wholesale_amount" value="<?php esc_attr_e($amount); ?>" class="regular-text">
											<p><?php esc_html_e('Enter wholesale amount.', 'woocommerce-wholesale-pricing'); ?></p>
										</td>
									</tr>
									<tr>
										<th>
											<label for="_wwp_wholesale_min_quantity">
												<?php esc_html_e('Minimum Quantity', 'woocommerce-wholesale-pricing'); ?>
											</label>
										</th>
										<td scope="row">
											<input type="number" name="_wwp_wholesale_min_quantity" id="_wwp_wholesale_min_quantity" value="<?php esc_attr_e($qty); ?>" class="regular-text">
											<p><?php esc_html_e('Enter wholesale minimum quantity to apply discount.', 'woocommerce-wholesale-pricing'); ?></p>
										</td>
									</tr>
								</table>
							</div>
							<div role="tabpanel" class="tab-pane fade" id="section3">
								<table class="form-table">
									<tbody>
										<tr scope="row">
											<th>
											<label for="retailer_label"><?php esc_html_e('Retailer Price Label', 'woocommerce-wholesale-pricing'); ?>
											</label>
											</th>
											<td>
											<input type="text" class="regular-text" name="options[retailer_label]" id="retailer_label" value="<?php echo isset($settings['retailer_label']) ? esc_html($settings['retailer_label']) : ''; ?>">
											<input class="inp-cbx" style="display: none" id="retailer_disabled" type="checkbox" value="yes" name="options[retailer_disabled]" <?php echo ( isset($settings['retailer_disabled']) && 'yes' == $settings['retailer_disabled'] ) ? 'checked' : ''; ?>>
											<label class="cbx cbx-square" for="retailer_disabled">
												<span>
													<svg width="12px" height="9px" viewbox="0 0 12 9">
														<polyline points="1 5 4 8 11 1"></polyline>
													</svg>
												</span>
												<span>
													<?php esc_html_e('Label Hide', 'woocommerce-wholesale-pricing'); ?>
												</span>
											</label>
											</td>
										</tr>
										<tr scope="row">
											<th><label for="wholesaler_price_label"><?php esc_html_e('Wholesaler Price Label', 'woocommerce-wholesale-pricing'); ?></label></th>
											<td><input type="text" class="regular-text" name="options[wholesaler_label]" id="wholesaler_price_label" value="<?php echo isset($settings['wholesaler_label']) ? esc_html($settings['wholesaler_label']) : ''; ?>"></td>
										</tr>
										<tr scope="row">
											<th><label for="save_price_label"><?php esc_html_e('Save Price Label', 'woocommerce-wholesale-pricing'); ?></label></th>
											<td><input type="text" class="regular-text" name="options[save_label]" id="save_price_label" value="<?php echo isset($settings['save_label']) ? esc_html($settings['save_label']) : ''; ?>">
											<input class="inp-cbx" style="display: none" id="save_price_disabled" type="checkbox" value="yes" name="options[save_price_disabled]" <?php echo ( isset($settings['save_price_disabled']) && 'yes' == $settings['save_price_disabled'] ) ? 'checked' : ''; ?>>
											<label class="cbx cbx-square" for="save_price_disabled">
												<span>
													<svg width="12px" height="9px" viewbox="0 0 12 9">
														<polyline points="1 5 4 8 11 1"></polyline>
													</svg>
												</span>
												<span><?php esc_html_e('Label Hide', 'woocommerce-wholesale-pricing'); ?></span>
											</label>
											</td>
										</tr>
									</tbody>            
								</table>
							</div>
							
							<div role="tabpanel" class="tab-pane fade" id="section4">
								<div id="wcwp" class="wrap" style="background: #FFF;">
									<div class="pro_container" style="background:url(<?php echo WWP_PLUGIN_URL.'assets/images/wholesale-banner.png'; ?>);">
									<h2>Pro Features</h2>
										<ol>		
											<li>User Roles</li>
											<li> Notifications </li>
											<li> Requests</li>
											<li> Registration Setting </li>
											<li> Bulk Pricing</li>
										</ol>
										
										<a href="https://woocommerce.com/products/wholesale-for-woocommerce/?aff=2878" class="get_pro_btn">Get Pro Now</a>
									</div>
								</div>
							</div>
							
						</div>
					</div>
				
				</div>
				
			</div>
				<p><button name="save-wwp_wholesale" class="button-primary custom-button-save-changes" type="submit" value="Save changes"><?php esc_html_e('Save changes', 'woocommerce-wholesale-pricing'); ?></button></p>
			</form>
			
			<style>
			.pro_container{
				padding: 10px 20px 20px;
				background-position: right!important;
				background-size: 356px!important;
				background-repeat: no-repeat!important;
				}
			a.get_pro_btn {
				margin-top: 20px;
				display: block;
				background: #96588a;
				padding: 10px 20px;
				color: #FFF;
				text-decoration: none;
				border-radius: 30px;
				font-weight: bold;
				font-size: 16px;
				width: 105px;
				text-align: center;
			}
			a.get_pro_btn:hover{
				color:#FFF;
				background:#000;
			}

			</style>
			<?php
		}
		/**
		 * Initialize product wholesale data tab
		 * 
		 * @since   1.0
		 * @version 1.0
		 */
		public function wwp_add_wholesale_product_data_tab( $product_data_tabs ) {
			$product_data_tabs['wwp-wholesale-tab'] = array(
				'label' => esc_html__('Wholesale', 'woocommerce-wholesale-pricing'),
				'target' => 'wwp_wholesale_product_data',
			);
			return $product_data_tabs;
		}
		/**
		 * Initialize product wholesale data tab
		 * 
		 * @since   1.0
		 * @version 1.0
		 */
		public function wcpp_custom_style() {
			?>
			<style>
				.wwp-wholesale-tab_tab a:before {
					font-family: Dashicons;
					content: "\f240" !important;
				}
			</style>
			<?php
		}
		/**
		 * Product wholesale data tab single user 
		 * 
		 * @since   1.0
		 * @version 1.0
		 */
		public function wwp_add_wholesale_product_data_fields() {
			global $woocommerce, $post, $product; 
			?>
			<!-- id below must match target registered in above wwp_add_wholesale_product_data_tab function -->
			<div id="wwp_wholesale_product_data" class="panel woocommerce_options_panel">
				<?php
				wp_nonce_field('wwp_product_wholesale_nonce', 'wwp_product_wholesale_nonce');
				woocommerce_wp_checkbox(
					array(
						'id'            => '_wwp_enable_wholesale_item',
						'wrapper_class' => 'wwp_enable_wholesale_item',
						'label'         => esc_html__('Enable Wholesale Item', 'woocommerce-wholesale-pricing'),
						'description'   => esc_html__('Add this item for wholesale customers', 'woocommerce-wholesale-pricing')
					)
				);
				woocommerce_wp_select(
					array(
						'id'      => '_wwp_wholesale_type',
						'label'   => esc_html__('Wholesale Type', 'woocommerce-wholesale-pricing'),
						'options' => array(
							'fixed'   => esc_html__('Fixed Amount', 'woocommerce-wholesale-pricing'),
							'percent' => esc_html__('Percent', 'woocommerce-wholesale-pricing'),
						)
					)
				);
				echo '<div class="hide_if_variable">';
					woocommerce_wp_text_input(
						array(
							'id'          => '_wwp_wholesale_amount',
							'label'       => esc_html__('Enter Wholesale Amount', 'woocommerce-wholesale-pricing'),
							'placeholder' => '15',
							'desc_tip'    => 'true',
							'description' => esc_html__('Enter Wholesale Price (e.g 15)', 'woocommerce-wholesale-pricing')
						)
					);
					woocommerce_wp_text_input(
						array(
							'id'          => '_wwp_wholesale_min_quantity',
							'label'       => esc_html__('Minimum Quantity', 'woocommerce-wholesale-pricing'),
							'placeholder' => '1',
							'desc_tip'    => 'true',
							'description' => esc_html__('Minimum quantity to apply wholesale price (default is 1)', 'woocommerce-wholesale-pricing'),
							'type'        => 'number',
							'custom_attributes' => array(
								'step'     => '1',
								'min'    => '1'
							)
						)
					);
				echo '</div>';
				echo '<div class="show_if_variable">';
				echo '<p>' . esc_html__('For Variable Product you can add wholesale price from variations tab', 'woocommerce-wholesale-pricing') . '</p>';
				echo '</div>';
				?>
			</div>
			<?php
		}
		/**
		 * Save product meta fields
		 * 
		 * @param   $post_id to save product meta
		 * @since   1.0
		 * @version 1.0
		 */
		public function wwp_woo_wholesale_fields_save( $post_id ) {
			if ( !isset($_POST['wwp_product_wholesale_nonce']) || !wp_verify_nonce( wc_clean($_POST['wwp_product_wholesale_nonce']), 'wwp_product_wholesale_nonce') ) {
				return;
			}
			// Wholesale Enable
			$woo_wholesale_enable = isset($_POST['_wwp_enable_wholesale_item']) ? wc_clean($_POST['_wwp_enable_wholesale_item']) : '';        
			update_post_meta($post_id, '_wwp_enable_wholesale_item', esc_attr($woo_wholesale_enable));
			// Wholesale Type
			$woo_wholesale_type = isset($_POST['_wwp_wholesale_type']) ? wc_clean($_POST['_wwp_wholesale_type']) : '';
			if ( !empty($woo_wholesale_type) ) {
				update_post_meta($post_id, '_wwp_wholesale_type', esc_attr($woo_wholesale_type));
			}
			// Wholesale Amount
			$woo_wholesale_amount = isset($_POST['_wwp_wholesale_amount']) ? wc_clean($_POST['_wwp_wholesale_amount']) : '';
			if ( !empty($woo_wholesale_amount) ) {
				update_post_meta($post_id, '_wwp_wholesale_amount', esc_attr($woo_wholesale_amount));
			}
			// Wholesale Minimum Quantity
			$wwp_wholesale_min_quantity = isset($_POST['_wwp_wholesale_min_quantity']) ? wc_clean($_POST['_wwp_wholesale_min_quantity']) : '';
			if ( !empty($wwp_wholesale_min_quantity) ) {
				update_post_meta($post_id, '_wwp_wholesale_min_quantity', esc_attr($wwp_wholesale_min_quantity));
			}
		}
		/**
		 * Product variations settings single user 
		 * 
		 * @since   1.0
		 * @version 1.0
		 */
		public function wwp_variation_settings_fields ( $loop, $variation_data, $variation ) {
			wp_nonce_field('wwp_variation_wholesale_nonce', 'wwp_variation_wholesale_nonce');
			woocommerce_wp_text_input(
				array(
					'id'          => '_wwp_wholesale_amount[' . esc_attr($variation->ID) . ']',
					'label'       => esc_html__('Enter Wholesale Price', 'woocommerce-wholesale-pricing'),
					'desc_tip'    => 'true',
					'description' => esc_html__('Enter Wholesale Price Here (e.g 15)', 'woocommerce-wholesale-pricing'),
					'value'       => get_post_meta($variation->ID, '_wwp_wholesale_amount', true),
					'custom_attributes' => array(
						'step'     => 'any',
						'min'    => '0'
					)
				)
			);
			woocommerce_wp_text_input(
				array(
					'id'          => '_wwp_wholesale_min_quantity[' . esc_attr($variation->ID) . ']',
					'label'       => esc_html__('Minimum Quantity', 'woocommerce-wholesale-pricing'),
					'placeholder' => '1',
					'value'       =>  get_post_meta($variation->ID, '_wwp_wholesale_min_quantity', true),
					'desc_tip'    => 'true',
					'description' => esc_html__('Minimum quantity to apply wholesale price (default is 1)', 'woocommerce-wholesale-pricing'),
					'type'              => 'number',
					'custom_attributes' => array(
						'step'     => '1',
						'min'    => '1'
					)
				)
			);
		}
		/**
		 * Save product variations settings single user 
		 * 
		 * @since   1.0
		 * @version 1.0
		 */
		public function wwp_save_variation_settings_fields ( $post_id ) {
			if ( !isset($_POST['wwp_variation_wholesale_nonce']) || !wp_verify_nonce( wc_clean($_POST['wwp_variation_wholesale_nonce']), 'wwp_variation_wholesale_nonce') ) {
				return;
			}
			$variable_wholesale = isset( $_POST['_wwp_wholesale_amount'][ $post_id ] ) ? wc_clean($_POST['_wwp_wholesale_amount'][ $post_id ]) : '';
			
			update_post_meta($post_id, '_wwp_wholesale_amount', esc_attr($variable_wholesale));
			
			$wholesale_min_quantity = isset($_POST['_wwp_wholesale_min_quantity'][ $post_id ]) ? wc_clean($_POST['_wwp_wholesale_min_quantity'][ $post_id ]) : '';
			
			update_post_meta($post_id, '_wwp_wholesale_min_quantity', esc_attr($wholesale_min_quantity));
			
		}
	}
	new WWP_Wholesale_Pricing_Backend();
}