<?php
/*
Plugin Name: Promys PSA Add-On
Plugin URI: http://www.promys.com
Description: Add-on to create leads at Promys PSA from Gravity Form
Version: 0.1
Author: Promys Inc.
Author URI: http://www.promys.com

------------------------------------------------------------------------
Copyright 2021 Promys Inc.
*/

define( 'GF_PROMYS_PSA_ADDON_VERSION', '2.0' );

add_action( 'gform_loaded', array( 'GF_Promys_PSA_AddOn_Bootstrap', 'load' ), 5 );

class GF_Promys_PSA_AddOn_Bootstrap {

	public static function load() {

		if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
			return;
		}

		require_once( 'class-gfpromyspsaaddon.php' );

		GFAddOn::register( 'GFPromysPSAAddOn' );
	}

}

function gf_promys_psa_addon() {
	return GFPromysPSAAddOn::get_instance();
}