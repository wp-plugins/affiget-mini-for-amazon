<?php
if( ! defined ( 'ABSPATH' ) ) { exit; }

if( ! class_exists('AffiGet_Element_Review_Part', false)):

class AffiGet_Element_Review_Part extends AffiGet_Abstract_Element
{
	function __construct( AffiGet_Abstract_Meta $meta, $name, array $params ){

		parent::__construct( $meta, $name, $params ); //calls resolve_settings(), which calls get_settings_config()

		if( ! $this->is_status( AffiGet_Abstract_Element::STATUS_ENABLED ) ) return;

		$element_name = $this->name;
		add_action("afg_front__html_{$element_name}", array(&$this, 'front_html'), 10, 1);

		//metavalue
		$meta_key = AFG_META_PREFIX . $this->name;
		add_filter( "sanitize_post_meta_{$meta_key}", array(&$this, 'sanitize_value' ), 10, 3);

		if( 'review_intro' === $element_name ){
			add_action('updated_postmeta', array(&$this, 'update_post_excerpt_maybe'), 10, 4);
		}

		//metabox
		add_action( 'afg_review_renderer__register_metabox_fields', array(&$this, 'register_cmb2_fields'));
		//add_action( 'cmb2_render_afg_pricing_details', array(&$this, 'render_cmb2_field'), 10, 5 );

		//assets
		//add_action( 'admin_enqueue_scripts', array(&$this, 'enqueue_scripts_and_styles'));
		//add_action( 'wp_enqueue_scripts', array(&$this, 'enqueue_scripts_and_styles'));
	}

	protected function get_settings_config(){

		//
		// Select size
		//

		$lines = __('%d lines', 'afg');

		$new_fields = array(
				'textarea_rows' => array(
						'name'    => 'textarea_rows',
						'atts'    => '',
						'type'    => 'dropdown',
						'default' => '5',
						'options' => array(
							 '1' => sprintf(__('%d line', 'afg'), 1 ),
							 '2' => sprintf( $lines, 2 ),
							 '3' => sprintf( $lines, 3 ),
							 '4' => sprintf( $lines, 4 ),
							 '5' => sprintf( $lines, 5 ),
							 '6' => sprintf( $lines, 6 ),
							 '7' => sprintf( $lines, 7 ),
							 '8' => sprintf( $lines, 8 ),
							 '9' => sprintf( $lines, 9 ),
							'10' => sprintf( $lines, 10 ),
							'15' => sprintf( $lines, 15 ),

						),
						'label'   => __('Size', 'afg'),
						'hint'    => __('', 'afg'),
						'help'    => __('Size Help', 'afg'),

				)
		);

		return array_merge( parent::get_settings_config(), $new_fields );
	}

	function register_cmb2_fields( &$fields ){

		$fields[] = array(
				'name'    => 'value',
				'desc'    => $this->settings['description'],
				'id'      => AFG_META_PREFIX . $this->name,
				'type'    => 'wysiwyg',
				'options' => array( 'textarea_rows' => $this->settings[ 'textarea_rows' ] ),
				'position'=> $this->settings[ 'metabox_position' ],
				'metabox' => array(
						'id'            => $this->control_id. '_metabox',
						'title'         => $this->settings['label'],
						'object_types'  => array( $this->meta->post_type_name ), // Post type
						'context'       => 'normal', //side/normal/advanced
						'priority'      => 'high',   //high/core/default/low
						'show_names'    => false,    // Show field names on the left
						'cmb_styles'    => false,    // false to disable the CMB stylesheet
						// 'closed'     => true,     // true to keep the metabox closed by default
				)
		);

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

		$value = get_post_meta( $post_id, AFG_META_PREFIX . $fieldname, true );

		if( $value ){
			printf('<div class="afg-element %s %s %s" data-post="%d" data-field="%s" data-nonce="%s" data-input="%s" data-wid="%s">',
					'afg-review-part '.$this->name,
					'contains-html',
					$context,
					$post_id,
					$fieldname,
					$nonce,
					$input_id,
					isset( $params['wid'] ) ? $params['wid'] : ''
			);

			echo $value;

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

	function update_post_excerpt_maybe( $meta_id, $object_id, $meta_key, $meta_value ){

		if( AFG_META_PREFIX . 'review_intro' === $meta_key ){

			$postarr = array(
				'ID'           => $object_id,
				'post_excerpt' => $meta_value
			);
			wp_update_post( $postarr );
		}
	}


	function direct_update( $post_id, $fieldname, $new_value, $part_id = 0 ){

		return false;
	}

	function get_default_value( $params = null ){

		if( isset( $params['post_id']) && 0 < absint( $params['post_id'] )){
			$post_id = absint( $params['post_id'] );
		} else {
			$post_id = get_the_ID();
		}

		$items = array(
				'post_id'   => $post_id,
				'fieldname' => $this->name
		);
		return $items;
	}


	function enqueue_scripts_and_styles( $hook ) {
		global $post;

	} // end enqueue_scripts_and_styles
}

endif;

/* EOF */