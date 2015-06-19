<?php
if( ! defined ( 'ABSPATH' ) ) { exit; }

if ( ! class_exists('AffiGet_Review_Element_Call_to_Action', false)):

/**
 *
 * @since      1.0.0
 * @package    AffiGet
 * @subpackage AffiGet/review
 * @author     Saru Tole <sarutole@affiget.com>
 *
 */

class AffiGet_Review_Element_Call_to_Action extends AffiGet_Abstract_Element
{

	static protected $link_attributes;
	static protected $link_captions;
	static protected $link_hints;

	function __construct( AffiGet_Abstract_Meta $meta, $name, array $params ){

		parent::__construct( $meta, $name, $params ); //calls resolve_settings(), which calls get_settings_config()

		if( is_null( self::$link_attributes )){
			self::$link_attributes = array(
					'DetailPageURL'        => _x('Product Details', 'link to product page', 'afg'),
					'TechnicalDetails'     => _x('Technical Details', 'link to product page', 'afg'),
					'AddToBabyRegistry'    => _x('Add To Baby Registry', 'link to product page', 'afg'),
					'AddToWeddingRegistry' => _x('Add To Wedding Registry', 'link to product page', 'afg'),
					'AddToWishlist'        => _x('Add To Wishlist', 'link to product page', 'afg'),
					'TellAFriend'          => _x('Tell A Friend', 'link to product page', 'afg'),
					'AllCustomerReviews'   => _x('All Customer Reviews', 'link to product page', 'afg'),
					'AllOffers'            => _x('All Offers', 'link to product page', 'afg'),
			);
			self::$link_captions = array(
					'DetailPageURL'        => _x('Buy now on Amazon.com', 'link to product page', 'afg'),
					'TechnicalDetails'     => _x('More details on Amazon.com', 'link to product page', 'afg'),
					'AddToBabyRegistry'    => _x('Add To Baby Registry', 'link to product page', 'afg'),
					'AddToWeddingRegistry' => _x('Add To Wedding Registry', 'link to product page', 'afg'),
					'AddToWishlist'        => _x('Add To Wishlist on Amazon.com', 'link to product page', 'afg'),
					'TellAFriend'          => _x('Tell a friend via Amazon.com', 'link to product page', 'afg'),
					'AllCustomerReviews'   => _x('All customer reviews on Amazon.com', 'link to product page', 'afg'),
					'AllOffers'            => _x('All Offers on Amazon.com', 'link to product page', 'afg'),
			);
			self::$link_hints = array(
					'DetailPageURL'        => _x('Buy %s now on Amazon.com', 'link to product page', 'afg'),
					'TechnicalDetails'     => _x('See details about %s on Amazon.com', 'link to product page', 'afg'),
					'AddToBabyRegistry'    => _x('Add %s to Baby Registry on Amazon.com', 'link to product page', 'afg'),
					'AddToWeddingRegistry' => _x('Add %s to Wedding Registry on Amazon.com', 'link to product page', 'afg'),
					'AddToWishlist'        => _x('Add %s to Wishlist on Amazon.com', 'link to product page', 'afg'),
					'TellAFriend'          => _x('Tell a friend about %s via Amazon.com', 'link to product page', 'afg'),
					'AllCustomerReviews'   => _x('See all Customer Reviews about %s on Amazon.com', 'link to product page', 'afg'),
					'AllOffers'            => _x('See all Offers related to %s on Amazon.com', 'link to product page', 'afg'),
			);
		}

		if( ! $this->is_status( AffiGet_Abstract_Element::STATUS_ENABLED ) ) return;

		$element_name = $this->name;
		add_action("afg_front__html_{$element_name}", array(&$this, 'front_html'), 10, 1);

		//metavalue
		$meta_key = AFG_META_PREFIX . $this->name;
		add_filter( "sanitize_post_meta_{$meta_key}", array(&$this, 'sanitize_value' ), 10, 3);

		//metabox
		add_action( 'afg_review_renderer__register_metabox_fields', array(&$this, 'register_cmb2_fields'));
		add_action( 'cmb2_render_afg_call_to_action', array(&$this, 'render_cmb2_field'), 10, 5 );

		//assets
		add_action( 'admin_enqueue_scripts', array(&$this, 'enqueue_scripts_and_styles'));
		add_action( 'wp_enqueue_scripts', array(&$this, 'enqueue_scripts_and_styles'));
	}

	protected function get_settings_config(){

		return parent::get_settings_config();

		$new_fields = array(
				'display_format' => array(
						'name'    => 'display_format',
						'atts'    => '',
						'type'    => 'dropdown',
						'options' => array(),
						'default' => 'list',
						'label'   => __('', 'afg'),
						'hint'    => __('', 'afg'),
						'help'    => __('Help'),
				),
		);

		return array_merge( parent::get_settings_config(), $new_fields );
	}

	function register_cmb2_fields( &$fields ){

		/*$fields[] = array(
		 'name'    => $this->settings['title'],
				//'desc'    => __('Title to show on the front end.', 'afg'),
				'id'      => AFG_META_PREFIX . $this->name .'_heading',
				'type'    => 'title',
				'position'=> $this->settings[ 'metabox_position' ],
				//'default' => $this->settings['title'],
		);*/

		$post_type_name = $this->meta->post_type_name;

		$fields[] = array(
				'name'    => 'value',
				'desc'    => $this->settings['description'],
				'id'      => AFG_META_PREFIX . $this->name,
				'type'    => 'afg_call_to_action',
				//'options' => array( 'textarea_rows' => $this->settings[ 'textarea_rows' ] ),
				'position'=> $this->settings[ 'metabox_position' ],
				'metabox' => array(
						'id'            => $this->control_id. '_metabox',
						'title'         => __( 'Call to Action', 'afg' ),
						'object_types'  => array( $post_type_name ), // Post type
						'context'       => 'side',//side/normal/advanced
						'priority'      => 'low', //high/core/default/low
						'show_names'    => false, // Show field names on the left
						'cmb_styles'    => false, // false to disable the CMB stylesheet
						// 'closed'     => true, // true to keep the metabox closed by default
				)
		);
	}

	function front_html( array $review_data ){

		$post_id = $review_data['post_fields']['ID'];

		/*$nonce = '';
		if ( current_user_can('edit_post', $post_id )){
			$nonce = wp_create_nonce("afg-update-{$post_id}");
		}*/

		ob_start();
		$this->render_html( $post_id, null, $this->name, $this->control_id, '' );
		$content = ob_get_clean();
		if( $content ){
			return $this->front_title() . $content;
		}
		return $content;
	}

	function get_preview( $post_id, $options ){

		$items = $this->get_default_value_for_post( $post_id, array('widget_data' => $options ));

		list( $width, $height ) = $this->get_image_attributes( $items['img-name'] );
		$items['img-width']  = $width;
		$items['img-height'] = $height;

		echo '<div>';
		$this->render_content( $items );
		echo "<input type='hidden' class='js_new_data' value='" . json_encode( $items ) . "'/>";
		echo '</div>';
	}

	function render_cmb2_field( $field_args, $value, $post_id, $object_type, $field_type_object ){

		if( $field_args->args['id'] === AFG_META_PREFIX . $this->name ){

			$nonce = '';
			if ( current_user_can('edit_post', $post_id )){
				$nonce = wp_create_nonce("afg-update-{$post_id}");
			}

			$this->render_html( $post_id, null, $this->name, AFG_META_PREFIX . $this->name, $nonce );
		}
	}

	protected function _prepare_items( &$items, $post_id, $fieldname ){

		$encoded = false;
		if( is_null( $items )){
			if( $post_id && $fieldname == $this->name ){
				$review_data = $this->meta->load_post( $post_id );
				$items       = $this->meta->pick_value( $review_data, $fieldname );
			}
			if( empty( $items )){
				$items   = $this->get_default_value( compact( 'post_id', 'fieldname' ) );
			}
			$encoded = json_encode( $items );

		} elseif( is_string( $items )){
			$encoded     = $items;
			$items       = json_decode( $items, $assoc = true );
		} else {
			$encoded     = json_encode( $items );
		}

		return $encoded;
	}

	protected function render_content( $items, $echo = true ){

		if( empty( $items )){
			$content = '<div class="content"></div>';
			if( $echo ){
				echo $content;
			}
			return $content;
		}

		$caption = '';
		if( trim( $items['caption'] ) && $items['caption'][0] != '-'){
			$caption = sprintf(
					'<div class="caption" style="width:%spx; height:%spx;"><span>%s</span></div>',
					$items['img-width'],
					$items['img-height'],
					esc_html( $items['caption'] )
			);
		}

		$img = '';
		if( trim( $items['img-name'] )){
			$img = sprintf('<img src="%s" width="%s" height="%s" alt="%s" />',
					home_url().'/'. $items['img-name'],
					$items['img-width'],
					$items['img-height'],
					$items['alt']
			);
		}

		$anchor = sprintf('<a href="%s" title="%s" target="%s" rel="%s">%s%s</a>',
				esc_url_raw( $items['link-url'] ),
				esc_attr( $items['hint'] ),
				esc_attr( $items['target'] ),
				esc_attr( $items['rel'] ),
				$caption,
				$img
		);

		$content = "<div class='content'>$anchor</div>";
		if( $echo ){
			echo $content;
		}
		return $content;
	}

	function render_html( $post_id, $items, $fieldname, $input_id, $nonce, $context = 'not-widget', $params = null ){

		printf('<div class="afg-element %s %s" data-post="%d" data-field="%s" data-nonce="%s" data-input="%s" data-wid="%s">',
				'afg-call-to-action',
				$context,
				$post_id,
				$fieldname,
				$nonce,
				$input_id,
				isset( $params['wid'] ) ? $params['wid'] : ''
		);

		$encoded = $this->_prepare_items( $items, $post_id, $fieldname );

		if( 'widget-settings' != $context ){
			/*printf("<input class='items' type='hidden' id='%s' name='%s' value='%s' />",
					$input_id,
					$this->control_id,
					$encoded
			);*/
		} else {
			//$images = $this->read_images();
			//echo '<pre>['.print_r( $images, true ).']</pre>';
		}

		$this->render_content( $items );

		echo('</div>');
	}

	//to be hooked into sanitize_meta(), but can also be called directly
	function sanitize_value( $items, $meta_key, $meta_type ){

		if( false === strpos( $meta_key, AFG_META_PREFIX . $this->name )){
			//return $meta_value;
			throw new AffiGet_Exception('Unexpected meta_key: ' . $meta_key );
		}

		if( is_string( $items )){
			$items = json_decode( $items, $assoc = true );
		}

		return $items;
	}

	function get_available_links( $post_id, $product_data = null ){

		$links = array();
		if( !$post_id ){
			return self::$link_attributes;
		}

		$product_data = get_post_meta( $post_id, AFG_META_PREFIX . 'product_data', true );
		if( ! $product_data ){
			return $links;
		}

		foreach( self::$link_attributes as $attr => $label ){
			$url = AffiGet_Review_Meta::pick_product_data_value( $product_data, $attr );
			$links[ $attr.'|'.$url ] = $label; /* do not forget to split key before use*/
		}
		return $links;
	}

	function get_available_images(){

		$path = plugin_dir_path( __FILE__ ) .'img';
		$paths = apply_filters('afg_call_to_action__get_image_list_path', array( $path ));

		$extensions = array( 'jpg', 'jpeg', 'png', 'gif' );
		$extensions = apply_filters('afg_call_to_action__get_image_list_ext', $extensions );

		$files = array();
		foreach( $paths as $path ){
			$path = untrailingslashit( $path );
			foreach( $extensions as $ext ){
				$found = glob( "{$path}/*.{$ext}");
				if( ! empty( $found )){
					foreach( $found as $file ){
						$parts = pathinfo( $file );
						$file  = str_replace( ABSPATH, '', $file );
						$files[ $parts['filename'] . '.' . $parts['extension'] ] = $file;
					}
				}
			}
		}
		return $files;
	}


	function get_image_attributes( $filename ){

		if( file_exists( ABSPATH . $filename )){
			list( $width, $height, $type, $attr ) = getimagesize( ABSPATH . $filename );
			$result = array( $width, $height );
		} else {
			$result = array( 220, 45 );
		}
		return $result;
	}

	protected function get_default_value_for_post( $post_id, $params ){

		if( ! $post_id ){
			return null;
		}

		$preset  = (isset( $params['widget_data'] ) && $params['widget_data']) ? $params['widget_data'] : false;

		$items = $preset ?  $preset: $params['defaults'];

		//get a link to product page
		$product_data = get_post_meta( $post_id, AFG_META_PREFIX . 'product_data', true );
		if( $product_data ){
			$items['link-url'] = AffiGet_Review_Meta::pick_product_data_value( $product_data, $items['link-attr'] );
			$items['product']  = AffiGet_Review_Meta::pick_product_data_value( $product_data, 'Title' );
			$items['hint']     = sprintf( self::$link_hints[ $items['link-attr']], $items['product'] );
		}

		update_post_meta( $post_id, AFG_META_PREFIX . $this->name, $items );

		return $items;
	}

	function get_default_value( $params = null ){

		$filename = str_replace( ABSPATH, '', plugin_dir_path( __FILE__ ) .'img/buy-on-amazon.png');

		$defaults = array(
				'link-attr'  => 'DetailPageURL',
				'link-url'   => '',
				'target'     => '_blank',
				'rel'        => 'nofollow',
				'hint'       => self::$link_hints['DetailPageURL'],
				'caption'    => '-'.self::$link_captions['DetailPageURL'], //__('Buy now from Amazon.com', 'afg'),
				'img-name'   => $filename,
				'img-width'  => '',
				'img-height' => '',
				'product'    => '',
				'alt'        => __('nice button', 'afg'),
		);

		list( $width, $height ) = $this->get_image_attributes( $defaults['img-name'] );
		$defaults['img-width']  = $width;
		$defaults['img-height'] = $height;

		if( isset( $params['post_id'] ) && 0 < absint( $params['post_id'] )){
			$params['defaults'] = $defaults;
			$result = $this->get_default_value_for_post( absint( $params['post_id'] ), $params );
			return $result;
		}

		return $defaults;
	}

	function enqueue_scripts_and_styles( $hook ) {

		if( $this->meta->is_review_style_needed() ){
			wp_enqueue_style( 'afg-call-to-action-style', plugins_url( '/css/element.css', (__FILE__)), array(), AFG_VER );
		}

		if( is_admin() && $this->meta->is_review_script_needed() ){

			$params = array(
					'captions' => self::$link_captions,
					'hints'    => self::$link_hints
			);

			wp_localize_script('afg-feature-list-script', 'dummy;
					window.affiget = window.affiget || {};
					affiget.params = affiget.params || {};
					affiget.params.call_to_action', $params
			);

			wp_enqueue_script( 'afg-call-to-action-script', plugins_url( '/js/element.js', (__FILE__)), array(), AFG_VER );
		}
	} // end enqueue_scripts_and_styles
}

endif;

/* EOF */