<?php
if( ! defined ( 'ABSPATH' ) ) { exit; }

if( ! class_exists('AffiGet_Element_Featured_Image', false)):

class AffiGet_Element_Featured_Image extends AffiGet_Abstract_Element
{
	function __construct( AffiGet_Abstract_Meta $meta, $name, array $params ){

		parent::__construct( $meta, $name, $params ); //calls resolve_settings(), which calls get_settings_config()

		if( ! $this->is_status( AffiGet_Abstract_Element::STATUS_ENABLED ) ) return;

		$element_name = $this->name;
		add_action("afg_front__html_{$element_name}", array(&$this, 'front_html'), 10, 1);

		//metavalue
		$meta_key = AFG_META_PREFIX . $this->name;
		add_filter( "sanitize_post_meta_{$meta_key}", array(&$this, 'sanitize_value' ), 10, 3);

		//assets
		//add_action( 'admin_enqueue_scripts', array(&$this, 'enqueue_scripts_and_styles'));
		//add_action( 'wp_enqueue_scripts', array(&$this, 'enqueue_scripts_and_styles'));
	}

	protected function get_settings_config(){

		//
		// Select size
		//

		global $_wp_additional_image_sizes;
		$options = array();
		foreach( get_intermediate_image_sizes() as $s ){
			//skip 'post-thumbnail', as it is the same as 'thumbnail'
			if ( $s == 'post-thumbnail' ) continue;

			if (isset($_wp_additional_image_sizes[$s])) {
				$width       = intval( $_wp_additional_image_sizes[$s]['width'] );
				$height      = intval( $_wp_additional_image_sizes[$s]['height'] );
				$options[$s] = array( $width, $height );
			} else {
				$width       = get_option($s.'_size_w');
				$height      = get_option($s.'_size_h');
				$options[$s] = array( $width, $height);
			}

			$label = str_replace( '-', ' ', $s );
			$label = str_replace( '_', ' ', $label );
			$label = ucwords( $label );

			$options[ $s ] = $label . ' ('.$width.'x'.$height.')';
		}

		$new_fields = array(
				'size' => array(
						'name'    => 'size',
						'atts'    => '',
						'type'    => 'dropdown',
						'default' => 'thumbnail',
						'options' => $options,
						'title'   => __('Size', 'afg'),
						'hint'    => __('', 'afg'),
						'help'    => __('Image Size Help', 'afg'),
				),
				'alignment' => array(
						'name'    => 'alignment',
						'atts'    => '',
						'type'    => 'dropdown',
						'default' => 'thumbnail',
						'options' => array(
								'left'   => __('Left', 'afg'),
								'right'  => __('Right', 'afg'),
								'center' => __('Center', 'afg'),
						),
						'title'   => __('Alignment', 'afg'),
						'hint'    => __('', 'afg'),
						'help'    => __('Image Size Help', 'afg'),
				),
				'clear' => array(
						'name'    => 'clear',
						'atts'    => '',
						'type'    => 'dropdown',
						'default' => 'yes',
						'options' => array(
								'none'         => __('No clearing', 'afg'),
								'before-left'  => __('Clear before', 'afg'),
								'after-right'  => __('Clear after', 'afg'),
								'after-both'   => __('Clear before & after', 'afg'),
						),
						'title'   => __('Clear', 'afg'),
						'hint'    => __('', 'afg'),
						'help'    => __('Image Size Help', 'afg'),
				)
		);

		return array_merge( parent::get_settings_config(), $new_fields );
	}

	function front_html( array $review_data ){

		$post_id = $review_data['post_fields']['ID'];

		ob_start();
		$this->render_html( $post_id, null, $this->name, $this->control_id, '' );
		$content = ob_get_clean();
		if( $content ){
			return $this->front_title() . $content;
		}
		return;
	}

	function render_html( $post_id, $items, $fieldname, $input_id, $nonce, $context = 'not-widget', $params = null ){

		//echo htmlspecialchars_decode( $items );
		//return;

		//$elements = get_post_meta( $post_id, AFG_META_PREFIX . 'review_elements', true );
		//echo '<pre>'.print_r( $elements, true ).'</pre>';

		if( has_post_thumbnail( $post_id )) {
			printf('<div class="afg-element %s %s %s">',
					$this->name,
					$context,
					$this->settings['size']
			);

			if( doing_filter('the_content')){
				echo get_the_post_thumbnail( $post_id );//'post-thumbnail' as specified by theme
			} else {
				echo get_the_post_thumbnail( $post_id, 'thumbnail' );//'thumbnail' as specified in Media settings
			}
			echo '</div>';
		}
	}

	//to be hooked into sanitize_meta(), but can also be called directly
	function sanitize_value( $meta_value, $meta_key, $meta_type ){

		if( false === strpos( $meta_key, AFG_META_PREFIX . $this->name )){
			//return $meta_value;
			throw new AffiGet_Exception('Unexpected meta_key: ' . $meta_key );
		}

		return wp_kses_post( $meta_value );
	}

	function enqueue_scripts_and_styles( $hook ) {
		global $post;

	} // end enqueue_scripts_and_styles
}

endif;

/* EOF */