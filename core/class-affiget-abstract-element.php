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

	protected $supported_formats;

	const STATUS_DISABLED  = 0;
	const STATUS_ENABLED   = 1;

	const DISPLAY_DISABLED = 0;
	const DISPLAY_ENABLED  = 1;

	const DISPLAY_MODE_ALWAYS_DISABLED = 0;
	const DISPLAY_MODE_ALWAYS_ENABLED  = 1;
	const DISPLAY_MODE_MODIFIABLE      = 2;

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

		/*  Params should contain the following fields:

			title: text
			label: text
			description: text
			status: enabled | disabled
			display: array

		*/
		$required_fields = array(
			'title', 'label', 'description', 'status', 'display',
		);

		$missing_fields = array_diff( $required_fields , array_keys( $params ));
		if( ! empty( $missing_fields ) ){
			throw new AffiGet_Exception('Missing fields for $param: '.join(',', $missing_fields).'!');
		}

		$this->id         = "afg_{$name}";

		$this->control_id = "afg_{$name}";

		$this->supported_formats = array('post', 'tooltip', 'widget', 'excerpt', 'shortcode', 'dialog');

		$this->settings = $this->resolve_settings( $params );

//		echo '<pre>';
//		print_r( $this->settings );
//		echo '</pre>';

		add_shortcode( "afg_{$name}", array(&$this, 'do_shortcode'));

		if( isset( $params['declare_meta_fields'] ) && ! empty( $params['declare_meta_fields'] )){
			add_filter('afg_review_meta__declare_meta_fields', array(&$this, 'declare_meta_fields'));
		}
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

		//$stored = get_option( "{$this->id}_settings" );
		//if( false !== $stored ){
		//	return $stored;
		//}

		$defaults = array('display' => array());

		//Collect the hardcoded settings.
		$configs = $this->get_settings_config( $params );
		foreach( $configs as $field => $config ){
			if( strpos( $field, 'display_in_' ) !== FALSE ){
				$defaults['display'][ str_replace('display_in_', '', $field) ] = array( $config['default'], self::DISPLAY_MODE_MODIFIABLE );
			} else {
				$defaults[ $field ] = $config['default'];
			}
		}

		$settings = array();

		//Hardcoded/stored settings extend the params coming from constructor params.
		//In other words, constuctor params override hardcoded/stored settings.
		$settings = wp_parse_args( $params, $defaults );

		//afg_log(__METHOD__.','.__LINE__, compact('params', 'configs', 'defaults', 'settings') );

		//$this->_log( 'Storing option', array('element'=>$this->id, 'params'=>$params, 'defaults'=>$defaults, 'settings'=>$settings ));

		add_option( "{$this->id}_settings", $settings );

		return $settings;
	}


	protected function get_settings_config(){

		$config = array(
				'status' => array(
						'name'    => 'status',
						'atts'    => '',
						'type'    => 'toggle',
						'default' => self::STATUS_ENABLED,
						'label'   => __('Element status', 'afg'),
						'hint'    => __('Whether the element is enabled.', 'afg'),
						'help'    => __('Status Help', 'afg'),
						'config'  => array(
								'on-label'  => __('Enabled', 'afg'),
								'on-value'  => self::STATUS_ENABLED,
								'off-label' => __('Disabled', 'afg'),
								'off-value' => self::STATUS_DISABLED,
						)
				),
				'label' => array(
						'name'    => 'label',
						'atts'    => '',
						'type'    => 'text',
						'default' => '',
						'label'   => __('Short label', 'afg'),
						'hint'    => __('Short label to represent element in Dashboard.', 'afg'),
						'help'    => __('Help', 'afg'),
				),
				'description' => array(
						'name'    => 'label',
						'atts'    => 'size="55"',
						'type'    => 'text',
						'default' => '',
						'label'   => __('Description', 'afg'),
						'hint'    => __('Description to show on <em>Afg+ Dialog</em>', 'afg'),
						'help'    => __('Description Help', 'afg'),
						'render'  => 'hidden',
				),
				'title' => array(
						'name'    => 'title',
						'atts'    => 'size="55"',
						'type'    => 'text',
						'default' => '',
						'label'   => __('Heading', 'afg'),
						'hint'    => __('Title on page.', 'afg'),
						'help'    => __('Help', 'afg'),
				),
				'display_in_metabox' => array(
						'name'    => 'display_in_metabox',
						'atts'    => '',
						'type'    => 'toggle',
						'default' => self::DISPLAY_ENABLED,
						'label'   => __('Display a metabox', 'afg'),
						'hint'    => __('Display a metabox when editing post', 'afg'),
						'help'    => __('Help', 'afg'),
						'config'  => array(
								'on-label'  => __('Yes', 'afg'),
								'on-value'  => self::DISPLAY_ENABLED,
								'off-label' => __('No', 'afg'),
								'off-value' => self::DISPLAY_DISABLED,
						)
				),
				'display_in_post' => array(
						'name'    => 'display_in_post',
						'atts'    => '',
						'type'    => 'toggle',
						'default' => self::DISPLAY_ENABLED,
						'label'   => __('Display on page', 'afg'),
						'hint'    => __('Automatically include in a full-content format', 'afg'),
						'help'    => __('Help', 'afg'),
						'config'  => array(
								'on-label'  => __('Yes', 'afg'),
								'on-value'  => self::DISPLAY_ENABLED,
								'off-label' => __('No', 'afg'),
								'off-value' => self::DISPLAY_DISABLED,
						)
				),
				'display_in_section' => array(
						'name'    => 'display_in_section',
						'atts'    => '',
						'type'    => 'toggle',
						'default' => self::DISPLAY_ENABLED,
						'label'   => __('Display in a section', 'afg'),
						'hint'    => __('Automatically include in a section format', 'afg'),
						'help'    => __('Help', 'afg'),
						'config'  => array(
								'on-label'  => __('Yes', 'afg'),
								'on-value'  => self::DISPLAY_ENABLED,
								'off-label' => __('No', 'afg'),
								'off-value' => self::DISPLAY_DISABLED,
						)
				),
				'display_in_tooltip' => array(
						'name'    => 'display_in_tooltip',
						'atts'    => '',
						'type'    => 'toggle',
						'default' => self::DISPLAY_ENABLED,
						'label'   => __('Display in tooltip', 'afg'),
						'hint'    => __('Automatically include in a tooltip format.', 'afg'),
						'help'    => __('Help', 'afg'),
						'config'  => array(
								'on-label'  => __('Yes', 'afg'),
								'on-value'  => self::DISPLAY_ENABLED,
								'off-label' => __('No', 'afg'),
								'off-value' => self::DISPLAY_DISABLED,
						)
				),
				'display_in_widget' => array(
						'name'    => 'display_in_widget',
						'atts'    => '',
						'type'    => 'toggle',
						'default' => self::DISPLAY_ENABLED,
						'label'   => __('Display in widget', 'afg'),
						'hint'    => __('Automatically include in a widget format.', 'afg'),
						'help'    => __('Help', 'afg'),
						'config'  => array(
								'on-label'  => __('Yes', 'afg'),
								'on-value'  => self::DISPLAY_ENABLED,
								'off-label' => __('No', 'afg'),
								'off-value' => self::DISPLAY_DISABLED,
						)
				),
				'display_in_excerpt' => array(
						'name'    => 'display_in_excerpt',
						'atts'    => '',
						'type'    => 'toggle',
						'default' => self::DISPLAY_ENABLED,
						'label'   => __('Show in excerpt', 'afg'),
						'hint'    => __('Automatically include in an excerpt format.', 'afg'),
						'help'    => __('Help', 'afg'),
						'config'  => array(
								'on-label'  => __('Yes', 'afg'),
								'on-value'  => self::DISPLAY_ENABLED,
								'off-label' => __('No', 'afg'),
								'off-value' => self::DISPLAY_DISABLED,
						)
				),
				'display_in_dialog' => array(
						'name'    => 'display_in_dialog',
						'atts'    => '',
						'type'    => 'toggle',
						'default' => self::DISPLAY_ENABLED,
						'label'   => __('Show on Afg+ Dialog', 'afg'),
						'hint'    => __('Automatically display on Afg+ dialog.', 'afg'),
						'help'    => __('Help', 'afg'),
						'config'  => array(
								'on-label'  => __('Yes', 'afg'),
								'on-value'  => self::DISPLAY_ENABLED,
								'off-label' => __('No', 'afg'),
								'off-value' => self::DISPLAY_DISABLED,
						)
				),
				'display_in_shortcode' => array(
						'name'    => 'display_in_shortcode',
						'atts'    => '',
						'type'    => 'toggle',
						'default' => self::DISPLAY_ENABLED,
						'label'   => __('Shortcode status', 'afg'),
						'hint'    => '',
						'help'    => __('Shortcode Help', 'afg'),
						'render'  => 'hidden',
						'config'  => array(
								'on-label'  => __('Enabled', 'afg'),
								'on-value'  => self::DISPLAY_ENABLED,
								'off-label' => __('Disabled', 'afg'),
								'off-value' => self::DISPLAY_DISABLED,
						)
				),
				'display_position' => array(
						'name'    => 'display_position',
						'atts'    => '',
						'type'    => 'spinner',
						'default' => '0',
						'label'   => __('Display position', 'afg'),
						'hint'    => __('Position among other elements', 'afg'),
						'help'    => __('Help', 'afg'),
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
		);
		return $config;
	}

	//element status: enabled|disabled
	public function is_status( $status ){

		if( $status != self::STATUS_ENABLED && $status != self::STATUS_DISABLED ){
			throw new AffiGet_Exception('Unsupported element status: ' . $status);
		}

		if( is_array( $status )){
			return in_array( $this->settings['status'], $status );
		} else {
			return $this->settings['status'] === $status;
		}
	}

	//display status: 0|1
	//format: post, tooltip, widget, excerpt, shortcode, dialog
	public function check_display_status_for_format( $format, $status ){

		if( ! in_array( $format, $this->supported_formats )){
			throw new AffiGet_Exception('Unsupported display format: ' . $format );
		}
		if( $status != self::DISPLAY_ENABLED && $status != self::DISPLAY_DISABLED ){
			throw new AffiGet_Exception('Unsupported display status: ' . $status );
		}

		if( is_array( $status )){
			return in_array( $this->settings['display'][ $format ][0], $status );
		} else {
			return $this->settings['display'][ $format ][0] == $status;
		}
	}

	//display_mode: 0|1|2
	//format: post, tooltip, widget, excerpt, shortcode, dialog
	public function check_display_mode_for_format( $format, $mode ){

		if( ! in_array( $format, $this->supported_formats )){
			throw new AffiGet_Exception('Unsupported display format: ' . $format );
		}
		if( $mode != self::DISPLAY_MODE_ALWAYS_DISABLED && $mode != self::DISPLAY_MODE_ALWAYS_ENABLED && $mode != self::DISPLAY_MODE_MODIFIABLED){
			throw new AffiGet_Exception('Unsupported display mode: ' . $mode );
		}

		if( is_array( $mode )){
			return in_array( $this->settings['display'][ $format ][1], $mode );
		} else {
			return $this->settings['display'][ $format ][1] == $mode;
		}
	}

	public function get_id(){

		return $this->id;
	}

	public function get_name(){

		return $this->name;
	}

	public function get_title(){

		return $this->settings['title'];
	}

	public function get_meta(){

		return $this->meta;
	}

	public function get_stored_settings(){

		return get_option( "{$this->id}_settings" );
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

		if( self::DISPLAY_ENABLED != $this->settings['display']['shortcode'][0] ) return '';

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
	 * Format and escape widget title for display on the front-end.
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

} /* AffiGet_Abstract_Element */

endif;

/* EOF */