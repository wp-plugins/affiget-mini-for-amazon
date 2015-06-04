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

	//--------------------------------------------------------------------------

	public function __construct( AffiGet_Review_Meta $meta ){

		$this->meta = $meta;
	}

	public function register_metaboxes(){

		$post_type_name = $this->meta->post_type_name;

		$primary_metabox_id = "afg_{$post_type_name}_metabox";

		$fields = array();
		do_action_ref_array('afg_review_renderer__register_metabox_fields', array( &$fields ) );

		//echo '<pre>'.print_r( $fields, true ).'</pre>';

		foreach( $fields as $field_config ){

			if( isset( $field_config['metabox'] ) ){
				if( ! array_key_exists( $field_config['metabox']['id'], $this->metaboxes )){
					$this->metaboxes[ $field_config['metabox']['id'] ] = new_cmb2_box( $field_config['metabox'] );
				}
				$this->metaboxes[ $field_config['metabox']['id'] ]->add_field( $field_config, $field_config['position'] );
			} else {
				if(!isset( $this->metaboxes[ $primary_metabox_id ])){
					$this->metaboxes[ $primary_metabox_id ] = new_cmb2_box( array(
							'id'            => $primary_metabox_id,
							'title'         => __( 'Review Details', 'afg' ),
							'object_types'  => array( $post_type_name ), // Post type
							'context'       => 'normal',
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

	public function the_content( $content ){

		global $post;

		if( $post->post_type != $this->meta->post_type_name ){
			return $content;
		}

		$content = '';

		$review_data = $this->meta->load_post( $post->ID );

		$elements = $this->meta->get_presentation_elements();

		foreach( $elements as $name => $el ){
			$content .= $el->front_html( $review_data );
		}

		return $content;
	}

	function ajax_get_front_styles(){

		ob_start();

		do_action('afg_front__style');

		$css = ob_get_clean();

		$this->output_cacheable_css( $css );
		die();
	}

	public function ajax_get_admin_post_js(){

		$post_id = isset( $_GET['post'] ) ? $_GET['post'] : '';

		ob_start();

		do_action('afg_get_admin_post_js', $post_id );

		$js = ob_get_clean();

		$this->output_cacheable_js( $js );

		die();
	}

	function ajax_get_admin_post_css(){

		$post_id = isset( $_GET['post'] ) ? $_GET['post'] : '';

		ob_start();

		do_action('afg_get_admin_post_css', $post_id );

		$css = ob_get_clean();

		$this->output_cacheable_css( $css );
		die();
	}

	function output_cacheable_css( $css, $expiration_in_minutes = 5 ){

		echo $css;
		$expires = 60 * $expiration_in_minutes; //DAY_IN_S; // 60 * 60 * 24 ... defined elsewhere
		header("Content-type: text/css");
		header('Content-Length: ' . strlen( $css ));
		header('Cache-Control: max-age='.$expires.', must-revalidate');
		header('Pragma: public');
		header('Expires: '. gmdate('D, d M Y H:i:s', time()+$expires).' GMT');
	}

	function output_cacheable_js( $js, $expiration_in_minutes = 5 ){

		//ob_start();
		echo $js;
		$expires = 60 * $expiration_in_minutes; //DAY_IN_S; // 60 * 60 * 24 ... defined elsewhere
		header("Content-type: application/javascript");
		header('Content-Length: ' . strlen( $js ));
		header('Cache-Control: max-age='.$expires.', must-revalidate');
		header('Pragma: public');
		header('Expires: '. gmdate('D, d M Y H:i:s', time()+$expires).' GMT');
		//ob_end_flush();
		return;
	}
}

/* EOF */