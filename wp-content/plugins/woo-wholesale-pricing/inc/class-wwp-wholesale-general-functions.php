<?php
if ( !defined('ABSPATH') ) {
	exit; // Exit if accessed directly
}
if ( ! function_exists( 'shapeSpace_allowed_html' ) ) :
	function shapeSpace_allowed_html() {
	
		$allowed_atts = array(
		'align'      => array(),
		'class'      => array(),
		'type'       => array(),
		'id'         => array(),
		'dir'        => array(),
		'lang'       => array(),
		'style'      => array(),
		'xml:lang'   => array(),
		'src'        => array(),
		'alt'        => array(),
		'href'       => array(),
		'rel'        => array(),
		'rev'        => array(),
		'target'     => array(),
		'novalidate' => array(),
		'type'       => array(),
		'value'      => array(),
		'name'       => array(),
		'tabindex'   => array(),
		'action'     => array(),
		'method'     => array(),
		'for'        => array(),
		'width'      => array(),
		'height'     => array(),
		'data'       => array(),
		'title'      => array(),
		'value'      => array(),
		'selected'	=> array(),
		'enctype'	=> array(),
		'disable'	=> array(),
		'disabled'	=> array(),
		);
		$allowedposttags['form']	= $allowed_atts;
		$allowedposttags['label']	= $allowed_atts;
		$allowedposttags['select']	= $allowed_atts;
		$allowedposttags['option']	= $allowed_atts;
		$allowedposttags['input']	= $allowed_atts;
		$allowedposttags['textarea']	= $allowed_atts;
		$allowedposttags['iframe']	= $allowed_atts;
		$allowedposttags['script']	= $allowed_atts;
		$allowedposttags['style']	= $allowed_atts;
		$allowedposttags['strong']	= $allowed_atts;
		$allowedposttags['small']	= $allowed_atts;
		$allowedposttags['table']	= $allowed_atts;
		$allowedposttags['span']	= $allowed_atts;
		$allowedposttags['abbr']	= $allowed_atts;
		$allowedposttags['code']	= $allowed_atts;
		$allowedposttags['pre']	= $allowed_atts;
		$allowedposttags['div']	= $allowed_atts;
		$allowedposttags['img']	= $allowed_atts;
		$allowedposttags['h1']	= $allowed_atts;
		$allowedposttags['h2']	= $allowed_atts;
		$allowedposttags['h3']	= $allowed_atts;
		$allowedposttags['h4']	= $allowed_atts;
		$allowedposttags['h5']	= $allowed_atts;
		$allowedposttags['h6']	= $allowed_atts;
		$allowedposttags['ol']	= $allowed_atts;
		$allowedposttags['ul']	= $allowed_atts;
		$allowedposttags['li']	= $allowed_atts;
		$allowedposttags['em']	= $allowed_atts;
		$allowedposttags['hr']	= $allowed_atts;
		$allowedposttags['br']	= $allowed_atts;
		$allowedposttags['tr']	= $allowed_atts;
		$allowedposttags['td']	= $allowed_atts;
		$allowedposttags['p']	= $allowed_atts;
		$allowedposttags['a']	= $allowed_atts;
		$allowedposttags['b']	= $allowed_atts;
		$allowedposttags['i']	= $allowed_atts;
		return $allowedposttags;
	}
endif;

if ( ! function_exists( 'wwp_elements' ) ) : 
	function wwp_elements( $elements ) { 
		echo wp_kses_post( apply_filters( 'wwp_registration_form_elements', $elements ) );
	}
endif;

if ( ! function_exists( 'registration_form_class' ) ) : 
	function registration_form_class( $css ) { 
		return apply_filters( 'registration_form_class', $css );
	}
endif;

if ( ! function_exists( 'wholesale_tab_link' ) ) :
	function wholesale_tab_link( $tab = '' ) {
		
		if (!empty($tab)) {
			return admin_url( 'admin.php?page=wwp-registration-setting&tab=' ) . $tab;
		} else {
			return admin_url( 'admin.php?page=wwp-registration-setting' );
		}
	}
endif;

if ( ! function_exists( 'wholesale_tab_active' ) ) :
	function wholesale_tab_active( $active_tab = '' ) {
		$getdata = '';
		if (isset($_GET['tab'])) {
			$getdata = sanitize_text_field($_GET['tab']);
		}
		
		if ( $getdata == $active_tab ) {
			return 'nav-tab-active';
		} 
	}
endif;

if ( ! function_exists( 'wholesale_content_tab_active' ) ) :
	function wholesale_content_tab_active( $active_tab = '' ) {
		$getdata = '';		
		if (isset($_GET['tab'])) {
			$getdata = sanitize_text_field($_GET['tab']);
		}
		
		if ( $getdata == $active_tab ) {
			return 'bolck';
		} else {
			return 'none';
		}
	}
endif;

if ( ! function_exists( 'wholesale_load_form_builder' ) ) :
	function wholesale_load_form_builder( $active_tab = '' ) {
		$tab = '';
		if (isset($_GET['tab'])) {
			$tab = sanitize_text_field($_GET['tab']);
		}
		
		if ( 'extra-fields' != $tab ) { 
			return true;
		} else {
			return false;
		}
	}
endif;

