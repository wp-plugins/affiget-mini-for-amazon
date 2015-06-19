<?php
/**
 * The dashboard-specific functionality of the plugin.
 *
 * @link       http://affiget.com
 * @since      1.0.0
 *
 * @package    AffiGet
 * @subpackage AffiGet/admin
 */

/**
 * The dashboard-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @package    AffiGet
 * @subpackage AffiGet/admin
 * @author     Saru Tole <sarutole@affiget.com>
 */
class AffiGet_Admin {

	const OPTION_USER_KEY = 'afg_user_key';

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Bookmarklet is responsible for maintaining and registering all hooks that power
	 * the bookmarklet.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      AffiGet_Admin_Bookmarklet $bookmarklet Maintains and registers all hooks for the Afg+ bookmarklet.
	 */
	protected $bookmarklet;

	/**
	 * Admin_Amazon is responsible for maintaining and registering all hooks that power
	 * the Amazon API.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      AffiGet_Admin_Amazon $amazon Makes calls to Amazon Product API.
	 */
	protected $amazon;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name       The name of this plugin.
	 * @var      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->bookmarklet = new AffiGet_Admin_Bookmarklet();
		$this->amazon      = new AffiGet_Admin_Amazon();

		add_action( 'admin_enqueue_scripts', array(&$this, 'enqueue_admin_scripts_for_pointers' ));
	}

	/**
	 *
	 * @return AffiGet_Admin_Amazon
	 */
	public function get_amazon(){
		return $this->amazon;
	}

	public function get_bookmarklet(){
		return $this->bookmarklet;
	}

	public function admin_menu() {

		add_options_page(
				__('AffiGet Settings', 'afg'),
				apply_filters('afg_admin_settings_menu_title', 'AffiGet <span style="color:#58c898">Mini</span>'),
				'manage_options',
				'affiget-settings-page',
				array( $this, 'settings_page' )
		);
	}

	public function settings_page(){?>

	<div class="wrap">
		<h2><?php _e('AffiGet Settings', 'afg');?></h2>
	<?php
		do_action('afg_admin__settings_page_top');

		$this->amazon->settings_section();
		echo '<br /><hr />';

		do_action('afg_admin__page_after_amazon');

		$this->bookmarklet->settings_section();
		echo '<br /><hr />';

		do_action('afg_admin__settings_page_bottom');
	?>
	</div><?php
	}

	/**
	 * Register the stylesheets for the Dashboard.
	 *
	 * @since    1.0.5
	 */
	public function enqueue_scripts_and_styles( $hook ) {

		//load styles on Reviews List, Review Edit and Settings page
		$screen = get_current_screen();
		if( 'post' == $screen->base || 'edit' == $screen->base ){
			if( $screen->post_type != AffiGet_Review_Meta::get_instance()->post_type_name ){
				return;
			}
		} elseif( 'settings_page_affiget-settings-page' !== $screen->id ){
			return;
		}

		wp_enqueue_style(  AFG_MINI . '-admin', plugin_dir_url( __FILE__ ) . 'css/affiget-admin.css', array(), AFG_VER, 'all' );
		wp_enqueue_script( AFG_MINI . '-admin', plugin_dir_url( __FILE__ ) . 'js/affiget-admin.js', array( 'jquery' ), AFG_VER, false );
	}

	static function output_cacheable_css( $css, $expiration_in_minutes = 5 ){

		ob_start();
		echo $css;
		header("Content-type: text/css");
		header('Content-Length: ' . ob_get_length());

		if( ! $expiration_in_minutes ){
			header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
			header("Cache-Control: post-check=0, pre-check=0", false);
			header("Pragma: no-cache");
		} else {
			$expires = 60 * $expiration_in_minutes; //DAY_IN_S; // 60 * 60 * 24 ... defined elsewhere
			header('Cache-Control: max-age='.$expires.', must-revalidate');
			header('Pragma: public');
			header('Expires: '. gmdate('D, d M Y H:i:s', time()+$expires).' GMT');
		}
		ob_end_flush();
		return;
	}

	static function output_cacheable_js( $js, $expiration_in_minutes = 5 ){

		ob_start();
		echo $js;

		header("Content-type: application/javascript");
		header('Content-Length: ' . ob_get_length());

		if( ! $expiration_in_minutes ){
			header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
			header("Cache-Control: post-check=0, pre-check=0", false );
			header("Pragma: no-cache");
		} else {
			$expires = 60 * $expiration_in_minutes; //DAY_IN_S; // 60 * 60 * 24 ... defined elsewhere
			header('Cache-Control: max-age='.$expires.', must-revalidate');
			header('Expires: '. gmdate('D, d M Y H:i:s', time()+$expires).' GMT');
			header('Pragma: public');
		}

		ob_end_flush();
		return;
	}

	static function output_cacheable_json( $data, $expiration_in_minutes = 5 ){

		ob_start();
		echo json_encode( $data );

		header("Content-type: application/json");
		header('Content-Length: ' . ob_get_length());

		if( ! $expiration_in_minutes ){
			header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
			header("Cache-Control: post-check=0, pre-check=0", false);
			header("Pragma: no-cache");
		} else {
			$expires = 60 * $expiration_in_minutes; //DAY_IN_S; // 60 * 60 * 24 ... defined elsewhere
			header('Cache-Control: max-age='.$expires.', must-revalidate');
			header('Pragma: public');
			header('Expires: '. gmdate('D, d M Y H:i:s', time()+$expires).' GMT');
		}
		ob_end_flush();
		return;
	}

	function enqueue_admin_scripts_for_pointers() {

		$seen_it = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );

		$do_add_script = false;

		if( ! in_array( 'afg00', $seen_it ) ) {
			$do_add_script = true;

			// Each pointer has its own function responsible for putting appropriate JavaScript into footer
			add_action( 'admin_print_footer_scripts', array( &$this, 'pointer_afg00_footer_script' ) );
		} elseif( ! in_array( 'afg01', $seen_it ) ) {

			$do_add_script = true;
			add_action( 'admin_print_footer_scripts', array( &$this, 'pointer_afg01_footer_script' ) );
		}

		if( $do_add_script ) {
			// add JavaScript for WP Pointers
			wp_enqueue_script( 'wp-pointer' );
			// add CSS for WP Pointers
			wp_enqueue_style( 'wp-pointer' );
		}
	}

	function pointer_afg00_footer_script() {

		$ptn  = AffiGet_Review_Meta::get_instance()->post_type_name;
		$link = admin_url('edit.php?post_type=' . $ptn );

		// Build the main content of your pointer balloon in a variable
		$pointer_content  = '<h3>'.__('AffiGet Reviews','afg').'</h3>'; // Title should be <h3> for proper formatting.
		$pointer_content .= '<p>';
		$pointer_content .= sprintf(
				__('AffiGet enables a new post type, called %s.<br/>The only way to create new reviews is by means of the <em>AffiGet Bookmarklet</em>', 'afg'),
				'<a href="'.$link.'">' . esc_html__('Reviews', 'afg') . '</a>'
		);
		$pointer_content .= '</p>';
		?>
			<script type="text/javascript">// <![CDATA[
			jQuery(document).ready(function($) {
				/* make sure pointers will actually work and have content */
				if( typeof( jQuery().pointer ) != 'undefined') {
					$('#menu-posts-review').pointer({
						content: '<?php echo $pointer_content; ?>',
						position: {
							edge:  'top',
							align: 'bottom'
						},
						close: function() {
							$.post( ajaxurl, {
								pointer: 'afg00',
								action:  'dismiss-wp-pointer'
							});
						}
					}).pointer('open');
				}
			});
			// ]]></script>
			<?php
	}

	// Each pointer has its own function responsible for putting appropriate JavaScript into footer
	function pointer_afg01_footer_script() {

		$link = admin_url('options-general.php?page=affiget-settings-page');

		// Build the main content of your pointer balloon in a variable
		$pointer_content  = '<h3>'.__('AffiGet Setup','afg').'</h3>'; // Title should be <h3> for proper formatting.
		$pointer_content .= '<p>';
		$pointer_content .= sprintf(
				__('To start posting your reviews,<br/>please complete %s.', 'afg'),
				'<a href="'.$link.'">' . esc_html__('AffiGet Settings','afg') . '</a>'
		);
		$pointer_content .= '</p>';

		//to provide your license key, and your details for Amazon Associates account
		?>
		<script type="text/javascript">// <![CDATA[
		jQuery( document ).ready( function($){
			/* make sure pointers will actually work and have content */
			if( typeof( jQuery().pointer ) != 'undefined' ){
				$('[href="options-general.php?page=affiget-settings-page"]').pointer({
					content: '<?php echo $pointer_content; ?>',
					position: {
						edge:  'top',
						align: 'bottom'
					},
					close: function() {
						$.post( ajaxurl, {
							pointer: 'afg01',
							action:  'dismiss-wp-pointer'
						});
					}
				}).pointer('open');
			}
		});
		// ]]></script>
		<?php
	}

	/**
	 * Add a Settings link on the admin/plugins page.
	 *
	 * @since    1.0.0
	 */
	function plugin_action_links( $links, $plugin_file, $plugin_data, $context ) {

		//echo '<pre>';
		//print_r(compact('links', 'plugin_file', 'plugin_data', 'context'));
		//echo '</pre>';
		if ( 'affiget-mini-for-amazon/affiget-mini.php' == $plugin_file || 'affiget-pro-for-amazon/affiget-pro.php' == $plugin_file ) {
			$new_link = sprintf('<a href="%s">%s</a>',
					admin_url('options-general.php?page=affiget-settings-page'),
					esc_html__('Settings', 'afg')
			);
			// make the 'Settings' link appear first
			$first_link = array_shift( $links );
			array_unshift( $links, $new_link );
			array_unshift( $links, $first_link );
		}

		return $links;
	}
}

/* EOF */