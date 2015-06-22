<?php

/**
* Declares functions to renderer product data for user to view.
*
* @link       http://affiget.com
* @since      1.0.0
*
* @package    AffiGet
* @subpackage AffiGet/review
*/

/**
*
*
* @since      1.0.0
* @package    AffiGet
* @subpackage AffiGet/review
* @author     Saru Tole <sarutole@affiget.com>
*/
class AffiGet_Review_Renderer {

	/**
	 * @var AffiGet_Review_Meta
	 */
	protected $meta;

	protected $metaboxes = array();

	protected $formats = array();

	//--------------------------------------------------------------------------

	public function __construct( AffiGet_Review_Meta $meta ){

		$this->meta = $meta;

		$this->formats = apply_filters('afg_review_renderer__formats', array(
				'post'      => array( __('Review',  'afg'),   __('This format defines what will be rendered when the full content of this post is requested.', 'afg')),
				'excerpt'   => array( __('Excerpt', 'afg'),   __('Your theme can utilize the_excerpt() to render this review with the elements selected here, on pages like Archive, Search, etc.', 'afg')),
				'tooltip'   => array( __('Tooltip', 'afg'),   __('Use a shortode to render a floating balloon with the review details enabled in this list.', 'afg')),
				'section'   => array( __('Section', 'afg'),   __('Use a shortode to render an inline section with the review details enabled in this list.', 'afg')),
				'widget'    => array( __('Widget',  'afg'),   __('Use an AffiGet Review widget to render the review details enabled in this list on a sidebar or other widgetized area.', 'afg')),
				//'dialog'    => array( __('Dialog', 'afg'),    __('', 'afg')),
				//'shortcode' => array( __('Shortcode', 'afg'), __('', 'afg')),
		));

		add_action( 'afg_review_controller__inherit_from_latest_in_category', array(&$this, 'inherit_from_latest_in_category'), 10, 5 );
	}

	public function register_metaboxes(){

		$post_type_name = $this->meta->post_type_name;

		$primary_metabox_id = "afg_{$post_type_name}_metabox";

		$fields = array();
		do_action_ref_array('afg_review_renderer__register_metabox_fields', array( &$fields ) );

		//echo '<pre>'.print_r( $fields, true ).'</pre>';

		foreach( $fields as $field_config ){

			if( isset( $field_config['metabox'] ) ){
				$metabox_id = $field_config['metabox']['id'];
				if( ! array_key_exists( $field_config['metabox']['id'], $this->metaboxes )){
					$this->metaboxes[ $field_config['metabox']['id'] ] = new_cmb2_box( $field_config['metabox'] );
				}
				$this->metaboxes[ $field_config['metabox']['id'] ]->add_field( $field_config, $field_config['position'] );
			} else {
				$metabox_id = $primary_metabox_id;
				if( ! isset( $this->metaboxes[ $primary_metabox_id ] )){
					$this->metaboxes[ $primary_metabox_id ] = new_cmb2_box( array(
							'id'            => $primary_metabox_id,
							'title'         => __( 'Review Details', 'afg' ),
							'object_types'  => array( $post_type_name ), // Post type
							'format'       => 'normal',
							'priority'      => 'high',
							'show_names'    => false, // Show field names on the left
							// 'cmb_styles' => false, // false to disable the CMB stylesheet
							// 'closed'     => true, // true to keep the metabox closed by default
					));
				}
				$this->metaboxes[ $primary_metabox_id ]->add_field( $field_config, $field_config['position'] );
			}
		}

		//echo '<pre>'.print_r( $this->metaboxes, true ).'</pre>';

		/*$this->metabox->add_field( array(
				'name'       => __( 'Product', 'afg' ),
				'desc'       => __( 'field description (optional)', 'afg' ),
				'id'         => 'afg_product_code',
				'type'       => 'text',
				'show_on_cb' => 'yourprefix_hide_if_no_cats', // function should return a bool value
				// 'sanitization_cb' => 'my_custom_sanitization', // custom sanitization callback parameter
				// 'escape_cb'       => 'my_custom_escaping',  // custom escaping callback parameter
				// 'on_front'        => false, // Optionally designate a field to wp-admin only
				// 'repeatable'      => true,
		));*/
	}

	public function the_content_feed( $content, $feed_type ){
		global $post;

		if( $post->post_type != $this->meta->post_type_name ){
			return $content;
		}

		$review = get_post_meta( $post->ID, AFG_META_PREFIX . 'review_content', true );

		if( $review ){
			return $review;
		}

		return $content;

	}

	public function get_the_excerpt( $excerpt ){

		global $post;

		if( $post->post_type != $this->meta->post_type_name ){
			return $excerpt;
		}

		$content_auto = '';

		$review_data = $this->meta->load_post( $post->ID );
		$elements    = $this->meta->get_elements_by_status( AffiGet_Abstract_Element::STATUS_ENABLED );

		$display_formats = get_post_meta( $post->ID, AFG_META_PREFIX . 'display_formats', true );
		if( false === $display_formats ){
			$display_formats = $this->get_default_rendering_settings();
		}

		$this->sort_elements_in_format( $display_formats, 'excerpt' );

		foreach( $display_formats as $element_name => $element_display ){
			if( $element_display['display']['excerpt'][0] ){
				if( array_key_exists( $element_name, $elements )){
					$content_auto .= $elements[ $element_name ]->front_html( $review_data );
				}
			}
		}
		return $content_auto;
	}

	public function the_content( $content ){

		global $post;

		if( $post->post_type != $this->meta->post_type_name ){
			return $content;
		}

		$content_auto = '';

		$review_data = $this->meta->load_post( $post->ID );
		$elements    = $this->meta->get_elements_by_status( AffiGet_Abstract_Element::STATUS_ENABLED );

		$display_formats = get_post_meta( $post->ID, AFG_META_PREFIX . 'display_formats', true );
		if( false === $display_formats ){
			$display_formats = $this->get_default_rendering_settings();
		}

		$this->sort_elements_in_format( $display_formats, 'post' );
		//echo '<pre>'.print_r( $display_formats, true ).'</pre>';

		foreach( $display_formats as $element_name => $element_display ){
			if( $element_display['display']['post'][0] ){
				if( array_key_exists( $element_name, $elements )){
					$content_auto .= $elements[ $element_name ]->front_html( $review_data );
				}
			}
		}

		return $content . $content_auto;
	}

	function sort_elements_in_format( &$display_formats, $format ){
		uasort( $display_formats, array(&$this, "compare_elements_in_{$format}_format"));
	}

	protected function compare_elements_in_post_format( $a, $b ){
		if( $a['display']['post'][2] == $b['display']['post'][2] )
			return 0;
		return ( $a['display']['post'][2] < $b['display']['post'][2] ) ? -1 : 1;
	}

	protected function compare_elements_in_excerpt_format( $a, $b ){
		if( $a['display']['excerpt'][2] == $b['display']['excerpt'][2] )
			return 0;
		return ( $a['display']['excerpt'][2] < $b['display']['excerpt'][2] ) ? -1 : 1;
	}

	protected function compare_elements_in_section_format( $a, $b ){
		if( $a['display']['section'][2] == $b['display']['section'][2] )
			return 0;
		return ( $a['display']['section'][2] < $b['display']['section'][2] ) ? -1 : 1;
	}

	protected function compare_elements_in_tooltip_format( $a, $b ){
		if( $a['display']['tooltip'][2] == $b['display']['tooltip'][2] )
			return 0;
		return ( $a['display']['tooltip'][2] < $b['display']['tooltip'][2] ) ? -1 : 1;
	}

	protected function compare_elements_in_widget_format( $a, $b ){
		if( $a['display']['widget'][2] == $b['display']['widget'][2] )
			return 0;
		return ( $a['display']['widget'][2] < $b['display']['widget'][2] ) ? -1 : 1;
	}


	public function add_display_formats_metabox(){

		add_meta_box( 'afg-display-formats-metabox', __( 'Display Formats', 'afg' ), array(&$this, 'render_display_formats_metabox'), $this->meta->post_type_name, 'normal', 'high');
	}

	function render_display_formats_metabox( $post ){

		//uncomment for debugging
		//delete_post_meta( $post->ID, AFG_META_PREFIX . 'display_formats' );

		$post_id = $post->ID;

		$display_formats = null;
		if( metadata_exists('post', $post_id, AFG_META_PREFIX . 'display_formats')){
			$display_formats = get_post_meta( $post_id, AFG_META_PREFIX . 'display_formats', true );
		} else {

			$display_formats = $this->get_default_rendering_settings();

			update_post_meta( $post_id, AFG_META_PREFIX . 'display_formats', $display_formats );

			//echo '<small><pre>'.print_r( $display_formats, true ).'</pre></small>';
		}

		echo '<input type="hidden" name="afg_display_formats" value=\''. json_encode( $display_formats ) .'\' />';

		wp_nonce_field( 'afg_display_formats_update', 'afg_display_formats_nonce' );

		if( ! empty( $display_formats )){

			foreach( $this->formats as $fmt => $fmt_details ){

				printf('<ul%s><li class="format" data-fmt="%s" title="%s">%s</li>',
						in_array( $fmt, array('widget', 'section', 'tooltip')) ? ' class="hidden"' : '',//disabled for now
						esc_attr( $fmt ),
						esc_html( $fmt_details[1] ),
						esc_html( $fmt_details[0] )
				);

				$this->sort_elements_in_format( $display_formats, $fmt );

				foreach( $display_formats as $el => $config ){//iterate over all elements in first format

					printf('<li class="element %s %s %s %s" data-elem="%s" data-title="%s" data-mode="%d">%s</li>',
							$el,
							$fmt,
							$config['display'][ $fmt ][0] ? '':'disabled',
							$config['display'][ $fmt ][1] ? '':'unavailable',
							$el,
							esc_attr( $config['title'] ),
							$config['display'][ $fmt ][1],
							esc_html( $config['label'] )
					);
				}
				echo '</ul>';
			}
			echo '<div class="afg-clearfix"></div>';

			//echo '<br />';
			//echo '<small><pre>'.print_r( $layout, true ).'</pre></small>';
		}
	}

	function get_default_rendering_settings(){

		$display_formats = array();

		$elements = $this->meta->get_elements();

		foreach( $elements as $name => $el ){
			$settings = $el->get_stored_settings();
			$display_formats[ $name ] = array(
					//'element'         => $name,
					//'enabled'         => 'enabled' == $settings['status'],
					'label'             => $settings['label'],
					'title'             => $settings['title'],
					'description'       => $settings['description'],
					'display'           => $settings['display'],
					'position'          => $settings['display_position']
			);
			foreach( $display_formats[ $name ]['display'] as $fmt => &$details ){
				//the same position in all formats
				//we split it here, so that later on it can be modified per-format
				$details[] = $settings['display_position'];
			}
		}

		//echo '<small><pre>'.print_r( $display_formats, true ).'</pre></small>';

		uasort( $display_formats, array(&$this, 'compare_by_position' ));

		foreach( $display_formats as $el => &$details ){
			unset( $details['position'] );
		}

		return $display_formats;
	}

	protected function compare_by_position( $a, $b ){

		if( $a["position"] == $b["position"] )
			return 0;

		return ( $a["position"] < $b["position"] ) ? -1 : 1;
	}

	function pass_script_params( $hook ) {
		global $post;

		$screen = get_current_screen();
		if( 'post' == $screen->base || 'edit' == $screen->base ){
			if( $screen->post_type != $this->meta->post_type_name ){
				return;
			}
		} else {
			return;
		}

		$params = array();
		//		'nonce'   => wp_create_nonce( $fieldname ),
		//);

		wp_localize_script( AFG_MINI.'-admin', 'dummy;
	window.affiget = window.affiget || {};
	affiget.params = affiget.params || {};
	affiget.params.msg = affiget.params.msg || {};

	affiget.params.msg["displayModeHint"]  = "'.esc_attr__( 'Check to have this element added to the display page.', 'afg' ).'";
	affiget.params.msg["dragItemHint"]     = "'.esc_attr__( 'Drag this item to its new position.', 'afg' ).'";
	affiget.params.msg["enabledItemHint"]  = "'.esc_attr__( 'Will show %1$s in a %2$s.', 'afg').'";
	affiget.params.msg["disabledItemHint"] = "'.esc_attr__( 'Will not show %1$s in a %2$s.', 'afg').'";

	affiget.params.msg["auto"] = "'.esc_attr__( 'Auto', 'afg').'";
	affiget.params.msg["now"]  = "'.esc_attr__( 'Now', 'afg').'";

	affiget.params.renderer', $params
		);

	} // end enqueue_scripts_and_styles

	function save_display_formats_meta( $post_id, $post ){

		if( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ){
			return $post_id;
		}
		if( defined('DOING_AJAX') && DOING_AJAX ){
			return $post_id;
		}

		$meta_key = AFG_META_PREFIX . 'display_formats';

		// Verify the nonce before proceeding.
		if ( ! isset( $_POST["afg_display_formats_nonce"] ) || ! wp_verify_nonce( $_POST["afg_display_formats_nonce"], 'afg_display_formats_update' ) )
			return $post_id;

		// Get the post type object
		$post_type = get_post_type_object( $post->post_type );

		//make sure this is a review post
		if( $post->post_type !== $this->meta->post_type_name )
			return $post_id;

		// Check if the current user has permission to edit the post.
		if( ! current_user_can( $post_type->cap->edit_post, $post_id ))
			return $post_id;

		// Get the posted data and sanitize it for use as an HTML class.
		$display_formats = ( isset( $_POST[ 'afg_display_formats' ] ) ? $_POST[ 'afg_display_formats' ] : '' );

		$display_formats = json_decode( stripslashes( $display_formats ), $as_array = true );

		update_post_meta( $post_id, AFG_META_PREFIX . 'display_formats', $display_formats );
	}

	public function get_formats(){

		return $this->formats;
	}

	function inherit_from_latest_in_category( $post_id, $product_data, $is_new, $cats, $prototype_post_id ){

		$preset = null;
		if( $prototype_post_id ){
			$preset = get_post_meta( $prototype_post_id, AFG_META_PREFIX . 'display_formats', true );
		}
		if( !$preset ){
			$preset = $this->get_default_rendering_settings();
		}
		update_post_meta( $post_id, AFG_META_PREFIX . 'display_formats', $preset );
	}
}

/* EOF */