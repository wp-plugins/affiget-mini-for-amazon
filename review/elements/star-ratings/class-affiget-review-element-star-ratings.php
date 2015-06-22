<?php
if( ! defined ( 'ABSPATH' ) ) { exit; }

if ( ! class_exists('AffiGet_Review_Element_Star_Ratings', false)):

/**
 *
 * Widget to provide different views of the product data managed by AffiGet_Review_Element_Star_Ratings.
 *
 * @since      1.0.0
 * @package    AffiGet
 * @subpackage AffiGet/review
 * @author     Saru Tole <sarutole@affiget.com>
 *
 */

class AffiGet_Review_Element_Star_Ratings extends AffiGet_Abstract_Element
{
	const DEFAULT_RATING = 4;

	function __construct( AffiGet_Abstract_Meta $meta, $name, array $params ){

		parent::__construct( $meta, $name, $params ); //calls resolve_settings(), which calls get_settings_config()

		if( ! $this->is_status( AffiGet_Abstract_Element::STATUS_ENABLED ) ) return;

		$element_name = $this->name;
		add_action("afg_front__html_{$element_name}", array(&$this, 'front_html'), 10, 1);

		//metavalue
		$meta_key = AFG_META_PREFIX . $this->name;
		add_filter( "sanitize_post_meta_{$meta_key}", array(&$this, 'sanitize_value' ), 10, 3);

		//metabox
		add_action( 'afg_review_renderer__register_metabox_fields', array(&$this, 'register_cmb2_fields'));
		add_action( "cmb2_render_{$this->control_id}", array(&$this, 'render_cmb2_field'), 10, 5 );
		add_filter( "cmb2_types_esc_{$this->control_id}", array(&$this, 'escape_cmb2_field'), 10, 4 );
		//add_filter( "cmb2_sanitize_{$this->control_id}", array(&$this, 'sanitize_cmb2_field'), 10, 5 );

		//assets
		add_action( 'admin_enqueue_scripts', array(&$this, 'enqueue_scripts_and_styles'));
		add_action( 'wp_enqueue_scripts', array(&$this, 'enqueue_scripts_and_styles'));
	}


	protected function get_settings_config(){

		$new_fields = array(
				'aspects' => array(
						'name'    => 'aspects',
						'atts'    => 'size="55"',
						'type'    => 'text',
						'default' => _x('Rating', 'a comma-separated list of supported rating aspects', 'afg'),
						'label'   => __('Rating aspects', 'afg'),
						'hint'    => __('A comma-separated list, e.g. <br/><code>Design, Quality, Service, Price</code>.', 'afg'),
						'help'    => __('Help'),
				),
				'ranks' => array(
						'name'    => 'ranks',
						'atts'    => 'size="55"',
						'type'    => 'text',
						'default' => __('Bad, Poor, Normal, Good, Perfect', 'afg'),
						'label'   => __('Rating scores', 'afg'),
						'hint'    => __('A comma-separated list, e.g. <br/><code>Bad, Poor, Normal, Good, Perfect</code>.', 'afg'),
						'help'    => __('Help'),
				),
				//images_path must contain files: star-on.png, star-off.png, star-half.png, star-rating.png
				'images_path' => array(
						'name'    => 'images_path',
						'atts'    => 'size="70"',
						'type'    => 'text',
						'default' => plugin_dir_url(__FILE__) . 'libs/raty/images/',
						'label'   => __('Path to images', 'afg'),
						'hint'    => sprintf(__('Must contain images: %s', 'afg'), '<code>star-on.png, star-off.png, star-half.png, star-rating.png</code>.'),
						'help'    => __('Help'),
				),
				'display_format' => array(
						'name'    => 'display_format',
						'atts'    => '',
						'type'    => 'dropdown',
						'options' => array(
								'table' => __('Table - labels in first column (default)', 'afg'),   /* labels in the first column */
								'list'  => __('List - labels in front of values', 'afg'),    /* labels before values */
								'form'  => __('Form - small labels below values', 'afg'),    /* small labels under values */
								'divs'  => __('Generic - divs and spans', 'afg'), /* divs and spans */
						),
						'default' => 'table',
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
				'id'      => '_' . AFG_META_PREFIX . $this->name, //prefixed with underscore to avoid showing a new entry in Custom fields
				'type'    => 'afg_star_ratings',
				//'options' => array( 'textarea_rows' => $this->settings[ 'textarea_rows' ] ),
				'position'=> $this->settings[ 'metabox_position' ],
				'metabox' => array(
						'id'            => $this->control_id. '_metabox',
						'title'         => $this->settings['label'],
						'object_types'  => array( $post_type_name ), // Post type
						'context'       => 'side',
						'priority'      => 'low', //high/core/default/low
						'show_names'    => false, // Show field names on the left
						// 'cmb_styles' => false, // false to disable the CMB stylesheet
						// 'closed'     => true, // true to keep the metabox closed by default
				)
		);
	}

	function front_html( array $review_data ){

		$post_id = $review_data['post_fields']['ID'];

		$nonce = '';
		if ( current_user_can('edit_post', $post_id )){
			$nonce = wp_create_nonce("afg-update-{$post_id}");
		}

		ob_start();
		$this->render_html( $post_id, null, $this->name, AFG_META_PREFIX . $this->name, $nonce );
		$content = ob_get_clean();
		if( $content ){
			return $this->front_title() . $content;
		}
		return;
	}

	function render_cmb2_field( $field_args, $value, $post_id, $object_type, $field_type_object ){

		if( $field_args->args['id'] === '_'.AFG_META_PREFIX . $this->name ){

			$nonce = '';
			if ( current_user_can('edit_post', $post_id )){
				$nonce = wp_create_nonce("afg-update-{$post_id}");
			}

			$this->render_html( $post_id, null, $this->name, AFG_META_PREFIX . $this->name, $nonce );
		}
	}

	function escape_cmb2_field( $null, $meta_value, $args, $field ){
		//if any non-null value is returned, dumb default escaping is avoided
		//afg_log(__METHOD__, compact('null', 'meta_value', 'args', 'field'));

		//short-circuit default escaping, as it results in PHP notices
		//(because passed values cannot be easily converted to string)
		return true;
	}

	function sanitize_cmb2_field( $override_value, $value, $object_id, $field_args, $sanitizer_object ){

		$value = $this->sanitize_value( $value, AFG_META_PREFIX . $this->name, 'post' );
		return $value;

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

	function render_html( $post_id, $items, $fieldname, $input_id, $nonce, $context = 'not-widget', $params = null ){

		$format = $this->settings['display_format'];

		printf('<div class="afg-element %s %s %s" data-post="%d" data-field="%s" data-nonce="%s" data-input="%s" data-wid="%s">',
				'afg-star-ratings',
				'contains-'.$format,
				$context,
				$post_id,
				$fieldname,
				$nonce,
				$input_id,
				isset( $params['wid'] ) ? $params['wid'] : ''
		);

		$encoded = $this->_prepare_items( $items, $post_id, $fieldname );

		if( 'widget-settings' != $context ){
			printf("<input class='items' type='hidden' id='%s' name='%s' value='%s' />",
					$input_id,
					$fieldname,
					$encoded
			);
		}

		if( ! empty( $items ) ){
			$this->_render_items( $items, $format );
		}

		echo('</div>');
	}

	protected function _render_items( $items, $format = 'table' ){

		$path    = $this->settings['images_path'];
		$ranks   = $items['ranks'];
		$aspects = $items['aspects'];

		if( empty( $aspects ) ){
			return;
		}
		if( 'list' == $format ){?>
			<ul class="list"><?php
			foreach( $aspects as $aspect ){ ?>
				<li class="item" data-aspect="<?php esc_attr_e( $aspect[0] );?>">
					<label><?php esc_html_e( $aspect[0] );?></label>
					<?php $this->_render_stars( $aspect, $ranks, $path ); ?>
				</li><?php
			}?>
			</ul><?php
		} else {?>
		  	<table class="list"><?php
		  	foreach( $aspects as $aspect ){ ?>
		  		<tr class="item" data-aspect="<?php esc_attr_e( $aspect[0] );?>">
		  			<th><?php esc_html_e( $aspect[0] );?></th>
		  			<td><?php $this->_render_stars( $aspect, $ranks, $path ); ?></td>
		  		</tr><?php
		  	}?>
		  	</table><?php
		}
	}

	protected function _render_stars( $aspect, $ranks, $path ){?>
		<div><?php
		$alt = 1;
		foreach( $ranks as $rank ){
			if( $aspect[1] >= $alt ){
				$src = $path . 'star-on.png';
			}elseif( $aspect[1] >= $alt - 0.5 ){
				$src = $path . 'star-half.png';
			} else {
				$src = $path . 'star-off.png';
			}
			printf('<img alt="%s" title="%s" src="%s">&nbsp;',
					$alt, esc_attr( $rank ), esc_attr( $src )
			);
			$alt++;
			//the input's name is crazy like that to avoid breaking PageBuilder's javascript
		} ?><input name="widgets[{$i}][score]" type="hidden" value="<?php echo $aspect[1]; ?>" />
  		</div><?php
	}

	function direct_update( $post_id, $fieldname, $new_value, $part_id = 0 ){

		if( is_string( $new_value )){
			$new_value = json_decode( $new_value, true );
		}

		$base_meta_key = AFG_META_PREFIX . $this->name; //XXX here we should use declared_meta_field to be more correct

		if( $this->name == $fieldname ){
			$result    = update_post_meta( $post_id, $base_meta_key, $new_value );
		} else {
			if( ! $part_id ){
				//most likely this comes from a standard widget
				$postfix   = preg_replace("/[^0-9]/", "", $fieldname ); //remove all non-numbers
				$sanitized = $this->sanitize_value( $new_value, $base_meta_key, 'post' );

				//afg_log('updating ', print_r(compact('post_id', 'base_meta_key', 'postfix', 'sanitized'), true));
				$result    = update_post_meta( $post_id, $base_meta_key . '_' . $postfix, $sanitized );
				//afg_log('updated value', print_r(get_post_meta($post_id, $base_meta_key . '_' . $postfix, true), true));
			} else {
				//most likely this is a widget controlled by PageBuilder
				//it stores all of its layout definition in a specific post meta

				$panels_data = get_post_meta( $post_id, 'panels_data', true );

				if( $panels_data && !empty( $panels_data )){
					$widget = false;
					$idx = -1;
					foreach( $panels_data['widgets'] as &$w ){
						$idx++;
						if( isset( $w['wid'] ) && $w['wid'] == $part_id ){
							$widget = &$w;
							break;
						}
					}
					if( $widget ){
						$widget['items'] = json_encode( $this->sanitize_value( $new_value, $base_meta_key, 'post' ));
						$result = update_post_meta( $post_id, 'panels_data', $panels_data );
						if( ! $result ){
							afg_log(__METHOD__, 'could not update panels_data on ' . $post_id. ' with '.print_r($panels_data, true) );
						}
					} else {
						afg_log(__METHOD__, 'widget not found ' . $part_id);
						//echo 'could not find wid '.$part_id;
						$result = false;
					}
				}
			}
		}
		return $result;
	}

	//to be hooked into sanitize_meta(), but can also be called directly
	function sanitize_value( $meta_value, $meta_key, $meta_type ){

		if( false === strpos( $meta_key, AFG_META_PREFIX . $this->name )){
			//return $meta_value;
			throw new AffiGet_Exception('Unexpected meta_key: ' . $meta_key );
		}

		if( is_null( $meta_value )) {
			//return $meta_value;
			throw new AffiGet_Exception('Null meta_value for ' . $meta_key );
		}

		if( is_string( $meta_value )){
			$meta_value = json_decode( $meta_value, $assoc = true );
		}

		$cleaned = array('ranks' => array(), 'aspects' => array());
		if( array_key_exists('ranks', $meta_value )){
			foreach( $meta_value['ranks'] as $rank ){
				if( trim( $rank ) && is_string( $rank ) ){
					$cleaned['ranks'][] = sanitize_text_field( $rank );
				}
			}
		}
		if( array_key_exists('aspects', $meta_value )){
			foreach( $meta_value['aspects'] as $item ){
				if( is_array( $item ) && count( $item ) == 2 ){
					if( is_string( $item[0] ) && is_numeric( $item[1] ) && round( $item[1], 1 ) <= 10 ){
						$cleaned['aspects'][] = array(
								sanitize_text_field( trim( $item[0] )),
								round( $item[1], 1 )
						);
					}
				}
			}
		}

		if( empty( $cleaned )){
			$cleaned['ranks'] = explode( ',', $this->settings['ranks'] );

			$aspects = explode( ',', $this->settings['aspects'] );
			foreach( $this->settings['aspects'] as $aspect ){
				$cleaned['aspects'] = array(
						trim( $aspect ),
						0
				);
			}
		}

		return $cleaned;
	}

	protected function get_default_value_for_post( $post_id, $params ){

		$preset  = (isset( $params['widget_data'] ) && $params['widget_data']) ? $params['widget_data'] : false;

		//will take aspects and values from the base metafield, if that is already set
		$prototype = get_post_meta( $post_id, AFG_META_PREFIX . $this->name, true );

		if( $preset ){
			$filtered = array(); //include only rating aspects that are mentioned in $preset
			foreach( $preset['aspects'] as $aspect ){
				$found = false;
				if( $prototype ){
					foreach( $prototype['aspects'] as $current ){
						if( $current[0] === $aspect[0] ){
							$found = true;
							$filtered[] = $current;
							break;
						}
					}
				}
				if( ! $found ){
					$filtered[] = array( $aspect[0], 0 );
				}
			}
			$preset['aspects'] = $filtered;

			if( false === $prototype ){
				update_post_meta( $post_id, AFG_META_PREFIX . $this->name, $preset );
			}
			return $preset;
		}
		return $prototype;
	}

	function get_default_value( $params = null ){

		//get default from post (can be merged with widget settings that are passed as one of the params)
		if( isset( $params['post_id']) && 0 < absint( $params['post_id'] )){
			$post_id = absint( $params['post_id'] );
			$result = $this->get_default_value_for_post( $post_id, $params );
			if( $result ){
				return $result;
			}
		}

		//get default from settings
		$result = array(
			'ranks'   => array_map('trim', explode( ',', $this->settings['ranks'] )),
			'aspects' => array()
		);

		$aspects = array_map('trim', explode( ',', $this->settings['aspects'] ));
		foreach( $aspects as $aspect ){
			$result['aspects'][] = array( $aspect, self::DEFAULT_RATING );//Assigning default rating of four!
		}

		return $result;
	}

	function enqueue_scripts_and_styles( $hook ) {

		if( $this->meta->is_review_style_needed() ){
			wp_enqueue_style( 'afg-raty-style', plugins_url( '/libs/raty/jquery.raty.css', (__FILE__)), array(), AFG_VER );
			wp_enqueue_style( 'afg-star-ratings-style', plugins_url( '/css/element.css', (__FILE__)), array('afg-raty-style'), AFG_VER );
		}

		if( $this->meta->is_review_script_needed() ){
			wp_enqueue_script( 'afg-raty-script', plugins_url( '/libs/raty/jquery.raty.js', (__FILE__)), array('jquery'), AFG_VER );
			wp_enqueue_script( 'afg-star-ratings-script', plugins_url( '/js/element.js', (__FILE__)), array('afg-raty-script'), AFG_VER );

			$params = array(
					'aspects'   => array_map('trim', explode(',', $this->settings['aspects'])),
					'ranks'     => array_map('trim', explode(',', $this->settings['ranks'])),
					'images'    => $this->settings['images_path'],
					'wpAjaxUrl' => admin_url('admin-ajax.php'),
			);

			wp_localize_script('afg-star-ratings-script', 'dummy;
	window.affiget = window.affiget || {};
	affiget.params = affiget.params || {};
	affiget.params.star_ratings', $params
			);
		}
	} // end enqueue_scripts_and_styles
}

endif;

/* EOF */