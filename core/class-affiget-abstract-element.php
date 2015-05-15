<?php
/**
 * Base class for all elements. Elements provide controls to view/edit known fields for post type.
 *
 * @link       http://affiget.com
 * @since      1.0.0
 * @package    AffiGet
 * @subpackage AffiGet/core
 * @author     Saru Tole <sarutole@affiget.com>
 */

if( ! class_exists('AffiGet_Abstract_Element', false)):

abstract class AffiGet_Abstract_Element
{
	/**
	 * @var AffiGet_Review_Meta $meta
	 */
	protected $meta;

	protected $name;

	/**
	 * Initialization parameters
	 *
	 * @var array
	 */
	protected $init_params;

	protected $id;

	protected $control_id;

	protected $settings;

	// ------------------------------------------------------------------------------------

	function __construct( AffiGet_Abstract_Meta $meta, $name, array $params ){

		if( is_null( $meta )){
			throw new AffiGet_Exception('Parameter $meta should not be null!');
		}
		$this->meta = $meta;

		if( ! trim( $name )){
			throw new AffiGet_Exception('Unsupported value for $name: "'.$name.'"!');
		}
		$name = trim( $name );
		$this->name = $name;

		if( empty( $params )){
			throw new AffiGet_Exception('Parameter $params should not be empty!');
		}

		$this->init_params = $params;

		/*params should contain the following fields
			title: text
			label: text
			description: text

			status: enabled|disabled

			dialog: enabled|disabled
			dialog_mode: modifiable|always_enabled|always_disabled

			presentation: auto|manual
			presentation_mode: modifiable|always_auto|always_manual

			shortcode: enabled|disabled
			shortcode_mode: modifiable|always_enabled|always_disabled
		*/

		$required_fields = array(
			'title', 'label', 'description',
			'status',
			'dialog',       'dialog_mode',
			'presentation', 'presentation_mode',
			'shortcode',    'shortcode_mode',
		);

		$missing_fields = array_diff( $required_fields , array_keys( $params ));
		if( ! empty( $missing_fields ) ){
			throw new AffiGet_Exception('Missing fields for $param: '.join(',', $missing_fields).'!');
		}
		if( 'enabled' != $params['status'] && 'disabled' != $params['status']){
			throw new AffiGet_Exception('Unsupported value for $param[status]: "'.$params['status'].'"!');
		}
		if( 'enabled' != $params['dialog'] && 'disabled' != $params['dialog']){
			throw new AffiGet_Exception('Unsupported value for $param[dialog]: "'.$params['dialog'].'"!');
		}
		if( ! in_array( $params['dialog_mode'], array('modifiable', 'always_enabled', 'always_disabled'))){
			throw new AffiGet_Exception('Unsupported value for $param[dialog_mode]: "'.$params['dialog_mode'].'"!');
		}
		if( 'auto' != $params['presentation'] && 'manual' != $params['presentation']){
			throw new AffiGet_Exception('Unsupported value for $param[presentation]: "'.$params['presentation'].'"!');
		}
		if( ! in_array( $params['presentation_mode'], array('modifiable', 'always_auto', 'always_manual'))){
			throw new AffiGet_Exception('Unsupported value for $param[presentation_mode]: "'.$params['presentation_mode'].'"!');
		}
		if( 'enabled' != $params['shortcode'] && 'disabled' != $params['shortcode']){
			throw new AffiGet_Exception('Unsupported value for $param[shortcode]: "'.$params['shortcode'].'"!');
		}
		if( ! in_array( $params['shortcode_mode'], array('modifiable', 'always_enabled', 'always_disabled'))){
			throw new AffiGet_Exception('Unsupported value for $param[shortcode_mode]: "'.$params['shortcode_mode'].'"!');
		}

		$this->id         = "afg_{$name}";

		$this->control_id = "afg_{$name}";

		$this->settings = $this->resolve_settings( $params );

//		echo '<pre>';
//		print_r( $this->settings );
//		echo '</pre>';

		add_shortcode( "afg_{$name}", array(&$this, 'do_shortcode'));

		if( isset( $params['declare_meta_fields'] ) && ! empty( $params['declare_meta_fields'] )){
			add_filter('afg_review_meta__declare_meta_fields', array(&$this, 'declare_meta_fields'));
		}

		//if( has_action("afg_admin_add_meta_box_{$name}" )){
			//add_action('pre_post_update', array( &$this, 'metabox_save' ), 1, 1);
			//add_action( 'save_post', array( $element, 'metabox_save' ), 2, 2);
		//}
	}

	// SETTINGS ------------------------------------------------------------------------------------


	final function declare_meta_fields( array $fields ){

		$more_fields = $this->init_params['declare_meta_fields'];

		foreach( $more_fields as $field => $config ){
			if( is_numeric( $field )){
				$field = $config;
				$config = '';
			}
			$fields[ $field ] = $config; //non-array value means de/serialization won't be necessary
		}

		return $fields;
	}

	protected function resolve_settings( array $params ){

		//uncomment for debugging
		delete_option( $this->id . '_settings' );

		$stored = get_option( $this->id . '_settings' );
		if( false !== $stored ){
			return $stored;
		}

		$defaults = array();

		$configs = $this->get_settings_config( $params );
		foreach( $configs as $field => $config ){
			$defaults[ $field ] = $config['default'];
		}

		$settings = array();
		$settings = wp_parse_args( $params, $defaults );

		//$this->_log( 'Storing option', array('element'=>$this->id, 'params'=>$params, 'defaults'=>$defaults, 'settings'=>$settings ));

		add_option( $this->id . '_settings', $settings );

		return $settings;
	}


	protected function get_settings_config(){

		$config = array(
				'status' => array(
						'name'    => 'status',
						'atts'    => '',
						'type'    => 'toggle',
						'default' => 'enabled',
						'label'   => __('Show on Afg+ Dialog', 'afg'),
						'hint'    => __('', 'afg'),
						'help'    => __('Status Help', 'afg'),
						'config'  => array(
								'on-label'  => __('Yes', 'afg'),
								'on-value'  => 'enabled',
								'off-label' => __('No', 'afg'),
								'off-value' => 'disabled',
						)
				),
				'title' => array(
						'name'    => 'title',
						'atts'    => 'size="55"',
						'type'    => 'text',
						'default' => '',
						'label'   => __('Title on page', 'afg'),
						'hint'    => __('', 'afg'),
						'help'    => __('Help', 'afg'),
				),
				'label' => array(
						'name'    => 'label',
						'atts'    => '',
						'type'    => 'text',
						'default' => '',
						'label'   => __('Short label', 'afg'),
						'hint'    => __('', 'afg'),
						'help'    => __('Help', 'afg'),
				),
				'description' => array(
						'name'    => 'description',
						'atts'    => 'size="55"',
						'type'    => 'text',
						'default' => '',
						'label'   => __('Description on <em>Afg+ Dialog</em>', 'afg'),
						'hint'    => __('', 'afg'),
						'help'    => __('Description Help', 'afg'),
						'render'  => 'hidden',
				),
				'dialog' => array(
						'name'    => 'dialog',
						'atts'    => '',
						'type'    => 'toggle',
						'default' => 'enabled',
						'label'   => __('Show on Afg+ Dialog', 'afg'),
						'hint'    => __('', 'afg'),
						'help'    => __('Status Help', 'afg'),
						'config'  => array(
								'on-label'  => __('Yes', 'afg'),
								'on-value'  => 'enabled',
								'off-label' => __('No', 'afg'),
								'off-value' => 'disabled',
						)
				),
				'presentation' => array(
						'name'    => 'presentation',
						'atts'    => '',
						'type'    => 'toggle',
						'default' => 'auto',
						'label'   => __('Show on page automatically', 'afg'),
						'hint'    => __('', 'afg'),
						'help'    => __('Presentation Help', 'afg'),
						/*
						 'options' => array(
						 		'auto'  => __('On', 'afg'),
						 		'manual'=> __('Off', 'afg'),
						 )*/
						'config'  => array(
								'on-label'  => __('Yes', 'afg'),
								'on-value'  => 'auto',
								'off-label' => __('No', 'afg'),
								'off-value' => 'manual',
						)
				),
				'shortcode' => array(
						'name'    => 'shortcode',
						'atts'    => '',
						'type'    => 'toggle',
						'default' => 'enabled',
						'label'   => '<div class="two-lines">'.__('Enable shortcode', 'afg') . '<br/><code>['.$this->name.']</code></div>',
						'hint'    => '',
						'help'    => __('Shortcode Help', 'afg'),
						'render'  => 'hidden',
						'config'  => array(
								'on-label'  => __('Yes', 'afg'),
								'on-value'  => 'enabled',
								'off-label' => __('No', 'afg'),
								'off-value' => 'disabled',
						)
				),
				'metabox_position' => array(
						'name'    => 'metabox_position',
						'atts'    => '',
						'type'    => 'spinner',
						'default' => '0',
						'label'   => __('Metabox position', 'afg'),
						'hint'    => __('Placement in a review details metabox', 'afg'),
						'help'    => __('Help', 'afg'),
				),
				/*
				'role_view_frontend' => array(
						'name'    => 'role_view_frontend',
						'atts'    => '',
						'type'    => 'Afg_Element_Settings_Role_Field',
						'default' => 'unregistered',
						'label'   => __('Permissions', 'afg'),
						'hint'    => __('<br/>Minimum role needed to view this on page.', 'afg'),
						'help'    => __('Status Help', 'afg'),
				),
				'role_edit_bookmarklet' => array(
						'name'    => 'role_edit_bookmarklet',
						'atts'    => '',
						'type'    => 'Afg_Element_Settings_Role_Field',
						'default' => 'author',
						'label'   => __('', 'afg'),
						'hint'    => __('<br/>Minimum role to edit this in <em>Afg+ Bookmarklet</em>.', 'afg'),
						'help'    => __('Status Help', 'afg'),
				),
				'role_view_backend' => array(
						'name'    => 'role_view_backend',
						'atts'    => '',
						'type'    => 'Afg_Element_Settings_Role_Field',
						'default' => 'editor',
						'label'   => __(''),
						'hint'    => __('Minimum role to <strong>view on backend</strong>.'),
						'help'    => __('Status Help'),
				),
				'role_edit_backend' => array(
						'name'    => 'role_edit_backend',
						'atts'    => '',
						'type'    => 'Afg_Element_Settings_Role_Field',
						'default' => 'editor',
						'label'   => __(''),
						'hint'    => __('Minimum role to <strong>edit on backend</strong>.'),
						'help'    => __('Status Help'),
				), */
		);
		return $config;
	}

	public function is_enabled(){

		return 'enabled' == $this->settings['status'];
	}

	public function is_enabled_dialog(){

		return 'enabled' == $this->settings['dialog'];
	}

	public function is_auto_presentation(){

		return 'auto' == $this->settings['presentation'];
	}

	public function is_enabled_shortcode(){

		return 'enabled' == $this->settings['shortcode'];
	}

	public function get_title(){

		return $this->settings['title'];
	}

	public function get_name(){

		return $this->name;
	}

	public function get_meta(){

		return $this->meta;
	}

	public function get_settings_value( $field ){

		if( array_key_exists( $field, $this->settings )){
			return $this->settings[ $field ];
		}
		throw new AffiGet_Exception('Unknown settings field requested: ' . $field );
	}

	protected function get_default_value( $params = null ){
		return null;
	}

	// FRONTEND ------------------------------------------------------------------------------------

	function do_shortcode( $atts ){

		if( 'enabled' != $this->settings['shortcode'] ) return '';

		//if( $review ) {
			ob_start();
			//do_action("afg_front__html_{$this->name}", $review);
			//do_action( "afg_front__html_{$this->name}" );
			do_action( "afg_front__html_{$this->name}" );
			return ob_get_clean();
		//} else {
		//	return '';
		//}
	}

	//default implementation of how the element will be represented on the front-end
	function front_html( array $review_data ){

		if( empty( $review_data )) return;

		$element_name = $this->name;

		$value = $this->meta->pick_value( $review_data, $element_name );
		if( ! $value ){
			$value = $this->get_default_value();
			if( is_null( $value )){
				return; //show nothing
			}
		}

		$result = '';
		if( is_array( $value ) ){
			$result .= '<pre>'.print_r( $value, true ).'</pre>';
		} else {
			$result .= apply_filters("afg_front__html_{$element_name}_content", $value);
		}

		if( $result ){
			$result = '<div class="afg_element simple ' . $element_name . '">'. $result .'</div>';
			$result .= $this->front_title() . $result;
		}

		return apply_filters("afg_front__html_{$element_name}_result", $result );
	}

	/**
	 * Format and escape widget title for presentation on the front-end.
	 *
	 * The heading is <h3> by default, but can be overriden,
	 * by adding a different number of '#' before the title.
	 *
	 * @param array $instance
	 * @param bool $echo
	 * @return string
	 */
	public function front_title( $echo = false){

		$result = '';
		if( isset( $this->settings['title'] ) && trim( $this->settings['title'] )){

			$title = trim( $this->settings['title'] );

			if( '-' == $title[0] ){ //starts with -
				return ''; //will display no title
			}

			$class  = ' class="'.$this->name.'"';

			$before = "<h3 $class>";
			$head   = trim( $title );
			$after  = '</h3>';

			if( '#' == $title[0] ){ //starts with # ?
				$result = preg_match('/^(#+)\s(.*)\s(#*)$/', $head, $parts ); //trailing #s will be dropped/ignored
				if( $result ){
					$cnt    = min( strlen( $parts[1] ), 6);
					$before = "<h$cnt$class>";
					$head   = trim( $parts[2] );
					$after  = "</h$cnt>";
				}
			}
			$result = $before . esc_html( $head ) . $after;

			if( $echo )
				echo $result;
		}
		return $result;
	}

	function front_script(){
		return;
	}

} /* AffiGet_Abstract_Element */

endif;

/* EOF */