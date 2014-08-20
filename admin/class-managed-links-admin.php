<?php
/**
 * Managed Links
 *
 * @package   Mangaged_Links
 * @author    Sheiry <sheiryng@gmail.com>
 * @license   Fansubware
 * @link	  http://twitter.com/fansubdev
 * @copyright 2014 Love from Sheiry
 */

/**
 * @author Sheiry <sheiryng@gmail.com>
 */
class Managed_Links_Admin {
	protected $plugin_screen_hook_suffix = null;
	
	protected static $instance = null;
	
	private function __construct() {
		$plugin = Managed_Links::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();
		
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
		
		add_action( 'admin_init', array( $this, 'add_plugin_settings' ) );
		
		$plugin_basename = plugin_basename( plugin_dir_path( realpath( dirname( __FILE__ ) ) ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );
	}

	public function add_plugin_settings() {
		add_settings_section(
			'managed_links_setting_section',
			'Managed Links settings section',
			array( $this, 'section_callback' ),
			$this->plugin_slug
		);
		 
		add_settings_field(
			'managed_links_default_target',
			'Default managed link target',
			array( $this, 'default_target_callback' ),
			$this->plugin_slug,
			'managed_links_setting_section'
		);
		 
		register_setting( $this->plugin_slug, 'managed_links_default_target' );
	}

	public function add_plugin_admin_menu() {
		$this->plugin_screen_hook_suffix = add_options_page(
			__( 'Managed Links', $this->plugin_slug ),
			__( 'Managed Links', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);
	}

	public function section_callback() {
		echo '<p>In this section you can define default values for Managed Links\' shortcodes</p>';
	}

	public function default_target_callback() {
		$default_target = get_option( 'managed_links_default_target' );
		echo '<input name="managed_links_default_target" type="textfield" value="' . esc_attr($default_target) . '" /> "_self" is same page and "_blank" is a new page';
	}

	public function display_plugin_admin_page() {
		include_once( 'views/admin.php' );
	}

	public function add_action_links( $links ) {
		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
			),
			$links
		);
	}

	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}

}
