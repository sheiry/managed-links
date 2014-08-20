<?php
/**
 * Sheiry's Managed Links
 * 
 * @package   Mangaged_Links
 * @author    Sheiry <sheiryng@gmail.com>
 * @license   Fansubware
 * @link	  http://twitter.com/fansubdev
 * @copyright 2014 Love from Sheiry
 * 
 * Plugin Name: Managed Links
 * Plugin URI: http://twitter.com/fansubdev
 * Description: Managed Links, made with love by Sheiry
 * Version: 0.1
 * Author: Sheiry
 * Author URI: http://twitter.com/fansubdev
 * License: Fansubware, you must support fansub to use this software
 */

if( ! defined('ABSPATH') ) {
	die;
}

require_once( plugin_dir_path( __FILE__ ) . 'public/class-managed-links.php' );

register_activation_hook( __FILE__, array( 'Managed_Links', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Managed_Links', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'Managed_Links', 'get_instance' ) );

if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-managed-links-admin.php' );
	add_action( 'plugins_loaded', array( 'Managed_Links_Admin', 'get_instance' ) );
}
