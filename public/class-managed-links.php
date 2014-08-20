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
 * 
 * @author Sheiry <sheiryng@gmail.com>
 */
class Managed_Links {
	
	/**
	 * @since   1.0.0
	 * @var     string
	 */
	const VERSION = '1.0.0';
	
	const DB_VERSION = '1.0';

	protected $plugin_slug = 'managed-links';
	
	protected static $instance = null;
	
	private function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_filter('query_vars', array( $this, 'query_vars' ) );
		add_filter('template_redirect', array( $this, 'display' ) );

		add_shortcode( 'managed_link', array( $this, 'shortcode' ) );
		add_shortcode( 'list_links', array( $this, 'list_links' ) );
	}

	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}

	public static function activate() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'managed_links';
		
		$charset_collate = '';
		
		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
		}
		
		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= " COLLATE {$wpdb->collate}";
		}
		
		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			url varchar(254) DEFAULT '' NOT NULL,
			downloads mediumint(9) DEFAULT 0 NOT NULL,
			category varchar(40) DEFAULT '' NOT NULL,
			number varchar(10) DEFAULT '' NOT NULL,
			type varchar(10) DEFAULT '' NOT NULL,
			title varchar(60) DEFAULT '' NOT NULL,
			UNIQUE KEY id (id)
		) $charset_collate;";
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		
		add_option( 'managed_links_db_version', self::DB_VERSION );
		
		add_option( 'managed_links_default_target', '_self' );
	}

	public static function deactivate() {
		delete_option( 'managed_links_default_target' );
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {
	
		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
	
		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );
	
	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'assets/css/public.css', __FILE__ ), array(), self::VERSION );
	}
	
	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'assets/js/public.js', __FILE__ ), array( 'jquery' ), self::VERSION );
	}

	public function query_vars($vars) {
		$vars[] = 'link_id';
		return $vars;
	}

	public function list_links( $atts ) {
		global $wpdb;

		$atts = shortcode_atts( array(
			'with_category' => null,
			'with_type'     => null,
		), $atts );
		
		$where_conditions = array('1');

		if( $atts['with_category'] != null ) {
			$where_conditions[] = 'ml.category = \'' . esc_sql($atts['with_category']) . '\'';
		}

		if( $atts['with_type'] != null ) {
			$where_conditions[] = 'ml.category = \'' . esc_sql($atts['with_category']) . '\'';
		}
		
		$where_condition = implode(' AND ', $where_conditions);

		$links = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}managed_links ml WHERE $where_condition ORDER BY ml.category asc, ml.number asc, ml.type asc", OBJECT );

		$results = array();
		$html = '';
		foreach($links as $link) {
			$results[$link->category][] = $link;
		}
		
		$html .= '<table>';
		$html .= '<table><thead><tr><th>Nom</th><th>Numéro</th><th>Type</th><th>Téléchargements</th></tr></thead>';
		foreach($results as $category) {
			foreach($category as $link) {
				$html .= '<tr>';
				$html .= '<td>' . $link->title . '</td>';
				$html .= '<td>' . $link->number . '</td>';
				$html .= '<td>' . $link->type . '</td>';
				$html .= '<td>' . $link->downloads . '</td>';
				$html .= '</tr>';
			}
		}
		$html .= '</table>';

		
		return $html;
	}

	public function shortcode( $atts, $content = null ) {
		global $wpdb;
	
		$atts = shortcode_atts( array(
			'category' => 'other',
			'number'   => '0',
			'type'     => '',
			'title'    => 'le fichier',
		), $atts );

		$target = get_option( 'managed_links_default_target' );
	
		$url = esc_sql( $content );
		$link = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}managed_links ml WHERE ml.url = '$url'");
	
		if( null === $link) {
			// link not in database, we insert it
			$wpdb->insert(
				$wpdb->prefix . 'managed_links',
				array(
					'time'     => date('Y-m-d'),
					'url'      => $content,
					'category' => $atts['category'],
					'number'   => $atts['number'],
					'type'     => $atts['type'],
					'title'    => $atts['title'],
				),
				array(
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
				)
			);
			$link_id = $wpdb->insert_id;
	
		} else {
			$link_id = $link->id;
		}
	
		$url = esc_url( home_url( '/?pagename=download_link&link_id=' . intval($link_id)) );
		return '<a class="button" href="' . $url . '" target="' . esc_attr($target) . '">Télécharger ' . $atts['title'] . '</a>';
	}

	public function display() {
		global $wpdb;
	
		$links_page = get_query_var('pagename');
		$link_id = get_query_var('link_id');

		if ('download_link' == $links_page && !empty($link_id)) {
			$link = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}managed_links WHERE id = " . intval($link_id));
			if( null == $link ) {
				wp_die("Link doesn't exists", "Download link error", array('response' => 404));
			} else {
				$wpdb->update(
					"{$wpdb->prefix}managed_links",
					array(
						'downloads' => $link->downloads + 1,
					),
					array( 'id' => $link->id ),
					array( '%d' ),
					array( '%d' )
				);
				wp_redirect( $link->url, 307 ); // 307 Temporary Redirect
				exit();
			}
		}
	}
}
