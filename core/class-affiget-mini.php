<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the dashboard.
 *
 * @link       http://affiget.com
 * @since      1.0.0
 *
 * @package    AffiGet
 * @subpackage AffiGet/core
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, dashboard-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    AffiGet
 * @subpackage AffiGet/core
 * @author     Saru Tole <sarutole@affiget.com>
 */
class AffiGet_Mini {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      AffiGet_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Handle the admin side.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $admin    Handle the admin side of the plugin.
	 */
	protected $admin;

	/**
	 * Handle CPT functionality for Product post type.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      AffiGet_Review_Meta $review_meta
	 */
	protected $review_meta;

	protected $dialog;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the Dashboard and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name = AFG_MINI;
		$this->version     = AFG_VER;

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

		add_image_size('afg-thumb', 50, 50, false);
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - AffiGet_Loader. Orchestrates the hooks of the plugin.
	 * - AffiGet_i18n. Defines internationalization functionality.
	 * - AffiGet_Admin. Defines all hooks for the dashboard.
	 * - AffiGet_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		$dir = plugin_dir_path( dirname( __FILE__ ) );

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once $dir . 'core/class-affiget-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once $dir . 'core/class-affiget-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the Dashboard.
		 */
		require_once $dir . 'admin/class-affiget-admin.php';
		require_once $dir . 'admin/amazon/class-affiget-admin-amazon.php';
		require_once $dir . 'admin/bookmarklet/class-affiget-admin-bookmarklet.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once $dir . 'public/class-affiget-public.php';

		require_once $dir . 'core/class-affiget-abstract-meta.php';
		require_once $dir . 'core/class-affiget-abstract-element.php';
		require_once $dir . 'review/class-affiget-review-meta.php';

		$this->review_meta = AffiGet_Review_Meta::get_instance( $this, $post_type_name = 'review' );

		require_once $dir . 'dialog/class-affiget-dialog.php';
		$this->dialog = new AffiGet_Dialog();
		$this->dialog->add_supported_meta( $this->review_meta );

		if( ! class_exists('AmazonProductAPI')){
			require_once AFG_LIBS_DIR . 'Amazon-API/AmazonProductAPI.php';
			spl_autoload_register( array( 'AmazonProductAPI', 'autoload' ) );
		}

		if ( file_exists( AFG_LIBS_DIR . 'cmb2/init.php' ) ) {
			require_once AFG_LIBS_DIR . 'cmb2/init.php';
		}

		$this->loader = new AffiGet_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the AffiGet_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new AffiGet_i18n();
		//$plugin_i18n->set_domain( $this->get_plugin_name() );
		$plugin_i18n->set_domain( 'afg' );

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the dashboard functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$this->admin = new AffiGet_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_filter( 'admin_body_class', $this, 'admin_body_classes', 10, 1 );

		$this->loader->add_action( 'admin_enqueue_scripts', $this->admin, 'enqueue_scripts_and_styles' );

		//declare settings pages
		$this->loader->add_action( 'admin_menu', $this->admin, 'admin_menu' );

		$this->loader->add_filter( 'plugin_action_links', $this->admin, 'plugin_action_links', 10, 4 );

		$amazon            = $this->admin->get_amazon();
		$bookmarklet       = $this->admin->get_bookmarklet();

		$review_controller = $this->review_meta->controller;
		$review_admin      = $this->review_meta->admin;

		//include posts of Review custom post type alongside regular posts
		$this->loader->add_action( 'pre_get_posts', $this->review_meta, 'filter_pre_get_posts' );

		//declare AJAX methods
		$this->loader->add_action( 'wp_ajax_afg_update_amazon_settings',    $amazon,       'ajax_update_settings' );
		$this->loader->add_action( 'wp_ajax_afg_get_current_config',        $this->dialog, 'ajax_get_current_config' );
		$this->loader->add_action( 'wp_ajax_afg_get_dialog_script',         $this->dialog, 'ajax_get_dialog_script' );


		$this->loader->add_action( 'wp_ajax_afg_prepare_review',	            $review_controller, 'ajax_prepare_review' );
		$this->loader->add_action( 'wp_ajax_afg_fetch_amazon_product',          $review_controller, 'ajax_fetch_amazon_product' );

		//$this->loader->add_action( 'wp_ajax_nopriv_afg_prepare_review',		$review_controller, 'ajax_prepare_review' );
		$this->loader->add_action( 'wp_ajax_nopriv_afg_fetch_amazon_product',	$review_controller, 'ajax_fetch_amazon_product' );

		$this->loader->add_action( 'wp_ajax_afg_get_product_data',              $review_controller, 'ajax_get_product_data' );
		$this->loader->add_action( 'wp_ajax_afg_update_review',                 $review_controller, 'ajax_update_review' );
		$this->loader->add_action( 'wp_ajax_afg_delete_review',                 $review_controller, 'ajax_delete_review' );
		$this->loader->add_action( 'wp_ajax_afg_update_review_field',           $review_controller, 'ajax_update_review_field' );
		$this->loader->add_action( 'wp_ajax_afg_retrieve_review_field',         $review_controller, 'ajax_retrieve_review_field' );
		$this->loader->add_action( 'wp_ajax_nopriv_afg_retrieve_review_field',  $review_controller, 'ajax_retrieve_review_field' );

		$this->loader->add_action( 'wp_ajax_afg_autoschedule' ,   $review_admin, 'ajax_autoschedule' );
		$this->loader->add_filter( 'query_vars',                  $review_admin, 'add_update_product_var' );
		$this->loader->add_filter( 'manage_posts_columns',        $review_admin, 'admin_list__inject_image_column', 10, 2);
		$this->loader->add_action( 'manage_posts_custom_column',  $review_admin, 'admin_list__post_data_row', 10, 2);
		$this->loader->add_action( 'post_row_actions',            $review_admin, 'admin_list__product_link', 10, 2);
		$this->loader->add_action( 'post_submitbox_misc_actions', $review_admin, 'admin_post_edit_product_sync');

		//afg itself is initialized in "init" action -- product update should be performed later!
		if( is_admin() ){
			if( isset( $_REQUEST['post'] )){
				$this->loader->add_action( 'wp_loaded', $review_admin, 'update_product_maybe');
			}
		} else {
			$this->loader->add_action( 'wp', $review_admin, 'update_product_for_post_maybe');
		}

		//declare option sanitization methods
		$this->loader->add_action( 'sanitize_option_afg_amazon_settings', $amazon, 'sanitize_settings' );

		//declare admin notices
		$this->loader->add_action( 'admin_notices', $amazon, 'admin_notice_invalid_settings' );

		//declare metaboxes
		//$this->loader->add_action( 'add_meta_boxes',  $review_admin, 'add_product_entry_metabox' );
		//$this->loader->add_action( 'save_post',       $review_admin, 'save_metabox_data' );
		//$this->loader->add_action( 'pre_post_update', $review_admin, 'build_post_content' );

		$this->loader->add_action( 'cmb2_init',   $this->review_meta->renderer, 'register_metaboxes');

		$this->loader->add_action( 'add_meta_boxes',  		$this->review_meta->renderer, 'add_display_formats_metabox' );
		$this->loader->add_action( 'save_post',             $this->review_meta->renderer, 'save_display_formats_meta', 10, 2 );
		$this->loader->add_action( 'admin_enqueue_scripts', $this->review_meta->renderer, 'pass_script_params' );

	}

	/**
	 * @since    1.0.0
	 * @return AffiGet_Admin
	 */
	public function get_admin(){

		return $this->admin;
	}

	function admin_body_classes( $classes ){
		global $post;

		$ver = explode('.', AFG_VER);
		$cnt = count( $ver );

		if( is_object( $post )){
			if( $post->post_type === $this->review_meta->post_type_name ){
				$classes .= ' afg-post-type-review';
			}
		}

		$classes .= ' afg-mini';
		$classes .= ' afg-mini-ver-' . str_replace('.', '_', AFG_VER);

		if( $cnt > 0 ) $classes .= ' afg-mini-major-' . $ver[0];
		if( $cnt > 1 ) $classes .= ' afg-mini-minor-' . $ver[1];

		return $classes;
	}

	function public_body_classes( $classes, $class ){
		global $post;

		if( is_object( $post )){
			if( $post->post_type === $this->review_meta->post_type_name ){
				$classes[] = ' afg-post-type-review';
			}
		}

		$ver = explode('.', AFG_VER);
		$cnt = count( $ver );

		$classes[] = ' afg-mini';
		$classes[] = ' afg-mini-ver-' . str_replace('.', '_', AFG_VER);

		if( $cnt > 0 ) $classes[] = ' afg-mini-major-' . $ver[0];
		if( $cnt > 1 ) $classes[] = ' afg-mini-minor-' . $ver[1];

		return $classes;
	}


	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$this->loader->add_action( 'init', $this, 'check_environment' );
		$this->loader->add_action( 'init', $this->review_meta, 'register_custom_post_type' );

		$this->loader->add_filter( 'body_class', $this, 'public_body_classes', 10, 2 );

		$plugin_public = new AffiGet_Public( $this->get_plugin_name(), $this->version );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		$this->loader->add_action( 'the_content',      $this->review_meta->renderer, 'the_content');
		$this->loader->add_action( 'get_the_excerpt',  $this->review_meta->renderer, 'get_the_excerpt');
		$this->loader->add_action( 'the_excerpt_rss',  $this->review_meta->renderer, 'get_the_excerpt');
		$this->loader->add_action( 'the_content_feed', $this->review_meta->renderer, 'the_content_feed', 10, 2);

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    AffiGet_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of WordPress.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * @since    1.0.0
	 */
	function check_environment(){

		if ( version_compare( phpversion(), '5.2', '<' )) {
			add_action('admin_notices', array(&$this, 'admin_notice_php_version'));
			return;
		}

		/*if ( defined('WP_DEBUG_LOG') && WP_DEBUG_LOG && ! is_writable( WP_CONTENT_DIR ) ) {
			add_action('admin_notices', array(&$this, 'admin_notice_content_dir_not_writable'));
		}

		if ( defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY ) {
			add_action('admin_notices', array(&$this, 'admin_notice_debug_display'));
		}*/

		$uploads = wp_upload_dir( time() );
		$uploads = $uploads['basedir'];
		if ( ! is_writable( $uploads ) ) {
			add_action('admin_notices', array(&$this, 'admin_notice_uploads_dir_not_writable'));
		}
	}

	/**
	 * @since    1.0.0
	 */
	function admin_notice_php_version(){
		echo '<div class="afg-error error"><p>'.__('AffiGet requires PHP version 5.2 or later!', 'afg').'</strong></p></div>';
	}

	/**
	 * @since    1.0.0
	 */
	function admin_notice_content_dir_not_writable(){
		echo '<div class="afg-warning updated"><p>'.__('AffiGet will attempt to write debug logs to your content dir, so please make it writable:', 'afg').'<br/><strong>'.WP_CONTENT_DIR.'</strong></p></div>';
	}

	/**
	 * @since    1.0.0
	 */
	function admin_notice_uploads_dir_not_writable(){
		$uploads = wp_upload_dir( time() );
		$uploads = $uploads['basedir'];
		echo '<div class="afg-warning updated"><p>'.__('AffiGet will attempt to upload product images to your uploads dir, so please make it recursively writable:', 'afg').'<br/><strong>'.$uploads.'</strong></p></div>';
	}

	/**
	 * @since    1.0.0
	 */
	function admin_notice_debug_display(){
		echo '<div class="afg-warning error"><p>'.__('AffiGet will not function properly, unless you disable <em>display of errors and warnings</em>.<br/><small>In your wp-config.php set <code>WP_DEBUG_DISPLAY</code> to <code>false</code> and <code>display_errors</code> to <code>0</code>.</small>','afg').'</p></div>';
	}
}