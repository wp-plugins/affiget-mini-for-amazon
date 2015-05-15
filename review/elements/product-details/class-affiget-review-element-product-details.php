<?php
if( ! defined ( 'ABSPATH' ) ) { exit; }

if ( ! class_exists('AffiGet_Review_Element_Product_Details', false)):

/**
 *
 * Widget to provide different views of the product data managed by AffiGet_Review_Element_Product_Details.
 *
 * @since      1.0.0
 * @package    AffiGet
 * @subpackage AffiGet/review
 * @author     Saru Tole <sarutole@affiget.com>
 *
 */

class AffiGet_Review_Element_Product_Details extends AffiGet_Abstract_Element
{
	static protected $product_details;
	static protected $link_attributes;
	static protected $price_attributes;
	static protected $complex_attributes;

	protected $features;

	const IDX_POS  = 0;
	const IDX_LBL  = 1;
	const IDX_VAL  = 2;
	const IDX_ACT  = 3;

	function __construct( AffiGet_Abstract_Meta $meta, $name, array $params ){

		if( is_null( self::$product_details )){
			self::$product_details = array(
					'ASIN'                   => _x('ASIN', 'product attribute', 'afg'),
					'Title'                  => _x('Title', 'product attribute', 'afg'),
					'Author'                 => _x('Author', 'product attribute', 'afg'),
					'Publisher'              => _x('Publisher', 'product attribute', 'afg'),
					'Binding'                => _x('Binding', 'product attribute', 'afg'),
					'Edition'                => _x('Edition', 'product attribute', 'afg'),
					'ISBN'                   => _x('ISBN-10', 'product attribute', 'afg'),
					'Department'             => _x('Department', 'product attribute', 'afg'),
					'PublicationDate'        => _x('Publication date', 'product attribute', 'afg'),
					'NumberOfPages'          => _x('Number of pages', 'product  attribute', 'afg'),
					'Languages'              => _x('Languages', 'product attribute', 'afg'),
					'ItemDimensions'         => _x('Item dimensions', 'product attribute', 'afg'),
					'Label'                  => _x('Label', 'product attribute', 'afg'),
					'Manufacturer'           => _x('Manufacturer', 'product attribute', 'afg'),
					'ProductGroup'           => _x('Product group', 'product attribute', 'afg'),
					'Studio'                 => _x('Studio', 'product attribute', 'afg'),
					'NumberOfItems'          => _x('Number of items', 'product attribute', 'afg'),
					'PackageDimensions'      => _x('Package dimensions', 'product attribute', 'afg'),
					'ProductTypeName'        => _x('Product type name', 'product attribute', 'afg'),
					'ListPrice'              => _x('List price', 'product attribute', 'afg'),
					'LowestNewPrice'         => _x('Lowest New Price', 'product attribute', 'afg'),
					'LowestUsedPrice'        => _x('Lowest Used Price', 'product attribute', 'afg'),
					'LowestCollectiblePrice' => _x('Lowest Collectible Price', 'product attribute', 'afg'),
					'LowestRefurbishedPrice' => _x('Lowest Refurbished Price', 'product attribute', 'afg'),
					'IsEligibleForTradeIn' => _x('Is eligible for trade-in', 'product attribute', 'afg'),
					'TradeInValue'         => _x('Trade-in value', 'product attribute', 'afg'),

					'EANList'              => _x('EAN list', 'product attribute', 'afg'),
					'EAN'                  => _x('EAN', 'product attribute', 'afg'),

					'DetailPageURL'        => _x('Product Details', 'link to product page', 'afg'),
					'TechnicalDetails'     => _x('Technical Details', 'link to product page', 'afg'),
					'AddToBabyRegistry'    => _x('Add To Baby Registry', 'link to product page', 'afg'),
					'AddToWeddingRegistry' => _x('Add To Wedding Registry', 'link to product page', 'afg'),
					'AddToWishlist'        => _x('Add To Wishlist', 'link to product page', 'afg'),
					'TellAFriend'          => _x('Tell A Friend', 'link to product page', 'afg'),
					'AllCustomerReviews'   => _x('All Customer Reviews', 'link to product page', 'afg'),
					'AllOffers'            => _x('All Offers', 'link to product page', 'afg'),
			);

			self::$link_attributes = array(//requires anchor formatting
					'DetailPageURL',
					'TechnicalDetails',
					'AddToBabyRegistry',
					'AddToWeddingRegistry',
					'AddToWishlist',
					'TellAFriend',
					'AllCustomerReviews',
					'AllOffers',
			);

			self::$price_attributes = array(
					'ListPrice',
					'LowestNewPrice',
					'LowestUsedPrice',
					'LowestCollectiblePrice',
					'LowestRefurbishedPrice',
			);

			self::$complex_attributes = array( //requires special formatting
					'Languages',
					'ItemDimensions',
					'PackageDimensions',
					'ProductTypeName',
					'TradeInValue',
					'IsEligibleForTradeIn',
			);
		}

		if( isset( $params['features'] ) ){
			$this->features = $params['features'];
		} else {
			$this->features = self::$product_details;
		}

		//must be resolved before parent constructor is called!
		//(because it is used to resolve defaults for settings)
		$this->features = apply_filters('afg_product_details_items', $this->features, $this->name );

		//echo 'Features<br/><pre>'.print_r($this->features, true).'</pre>';

		parent::__construct( $meta, $name, $params ); //calls resolve_settings(), which calls get_settings_config()

		if( ! $this->is_enabled() ) return;

		$element_name = $this->name;
		if( ! is_admin() && $this->is_auto_presentation()){
			add_action("afg_front__html_{$element_name}", array(&$this, 'front_html'), 10, 1);
		}

		$meta_key = AFG_META_PREFIX . $this->name;
		add_filter( "sanitize_post_meta_{$meta_key}", array(&$this, 'sanitize_value' ), 10, 3);

		//metabox
		add_action( 'afg_review_renderer__register_metabox_fields', array(&$this, 'register_cmb2_fields'));
		add_action( 'cmb2_render_afg_product_details', array(&$this, 'render_cmb2_field'), 10, 5 );

		//assets
		add_action( 'admin_enqueue_scripts', array(&$this, 'enqueue_scripts_and_styles'));
		add_action( 'wp_enqueue_scripts', array(&$this, 'enqueue_scripts_and_styles'));

	}

	protected function get_settings_config(){

		$new_fields = array(
				'available_items' => array(
						'name'    => 'available_items',
						'atts'    => 'size="55"',
						'type'    => 'text',
						'default' => implode(',', apply_filters('afg_product_details_available_items', array_keys( $this->features ), $this->name )),
						'label'   => __('Enabled product attributes', 'afg'),
						'hint'    => __('A comma-separated list, e.g. <br/><code>Title,Author,Publisher,NumberOfPages,ISBN</code>.', 'afg'),
						'help'    => __('Help'),
				),
				'visible_items' => array(
						'name'    => 'visible_items',
						'atts'    => 'size="55"',
						'type'    => 'text',
						'default' => implode(',', apply_filters('afg_product_details_visible_items',
								isset( $this->init_params['visible_attributes'] ) ? $this->init_params['visible_attributes'] : array('Title','Author','Edition','Binding','ISBN','Publisher','NumberOfPages','PublicationDate'),
								$this->name )
						),
						'label'   => __('A subset of product attributes to show to visitors by default', 'afg'),
						'hint'    => __('A comma-separated list, e.g. <br/><code>Title,Author,Publisher,NumberOfPages,Binding,ISBN,PublicationDate</code>.', 'afg'),
						'help'    => __('Help'),
				),
				'presentation_format' => array(
						'name'    => 'presentation_format',
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
				'id'      => AFG_META_PREFIX . $this->name,
				'type'    => 'afg_product_details', /* will hook to cmb2_render_afg_product_details to render this */
				//'options' => array( 'textarea_rows' => $this->settings[ 'textarea_rows' ] ),
				'position'=> $this->settings[ 'metabox_position' ],
				'metabox' => array(
						'id'            => $this->control_id. '_metabox',
						'title'         => $this->settings['label'],
						'object_types'  => array( $post_type_name ), // Post type
						'context'       => 'normal', //side/normal/advanced
						'priority'      => 'low', //high/core/default/low
						'show_names'    => false, // Show field names on the left
						'cmb_styles'    => false, // false to disable the CMB stylesheet
						// 'closed'     => true,  // true to keep the metabox closed by default
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
		$this->render_html( $post_id, null, $this->name, $this->control_id, $nonce );
		$content = ob_get_clean();
		if( $content ){
			return $this->front_title() . $content;
		}
		return;
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
				$items   = $this->get_default_value( compact( 'post_id', 'fieldname' ));
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

		$format = $this->settings['presentation_format'];

		printf('<div class="afg-element %s %s %s" data-post="%d" data-field="%s" data-nonce="%s" data-input="%s" data-wid="%s">',
				'afg-feature-list',
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
			printf('<input class="items" type="hidden" id="%s" name="%s" value=\'%s\' />',
					$input_id,
					$fieldname,
					$encoded
			);
		}

		if( ! empty( $items ) ){
			$this->_render_items( $items, $format );
		}

		echo('</div>');

		//echo (__METHOD__.':'.__LINE__).'<br /><pre>';
		//print_r(get_post_meta($post_id));
		//echo '<pre>';
	}

	protected function _render_items( $items, $format = 'table'){

		$before   = '';
		$headers  = '';
		$between  = '';
		$template = '';
		$after    = '';

		switch( $format ){
			case 'divs':
				$before   = '<div class="list">';
				$template = '<div data-attr="%1$s" data-lbl="%3$s" data-type="%5$s" class="item%2$s"><span class="label">%3$s</span><span class="value">%4$s</span></div>';
				$after    = '</div>';
				break;
			case 'list':
				$before   = '<ul class="list regular">';
				$template = '<li data-attr="%1$s" data-lbl="%3$s" data-type="%5$s" class="item%2$s"><label>%3$s</label><span>%4$s</span></li>';
				$after    = '</ul>';
				break;
			case 'form':
				$before   = '<ul class="list form">';
				$template = '<li data-attr="%1$s" data-lbl="%3$s" data-type="%5$s" class="item%2$s"><span>%4$s</span><label>%3$s</label></li>';
				$after    = '</ul>';
				break;
			default: //'table':
				$before   = '<table class="list"><tbody>';
				$template = '<tr data-attr="%1$s" data-type="%5$s" data-lbl="%3$s" class="item%2$s"><th>%3$s</th><td>%4$s</td></tr>';
				$after    = '</tbody></table>';
		}

		echo $before;
		foreach( $items as $attr => $item ){
			//if( !$item['value'] ) continue;

			$type = $this->get_attribute_type( $attr );
			$val  = $this->format_attribute_value( $attr, $type, $item[ self::IDX_LBL ], $item[ self::IDX_VAL ] );

			printf( $template,
					esc_attr( $attr ),
					($item[ self::IDX_ACT ] ? '' : ' disabled hidden').(!$val ? ' empty': ''),
					esc_html( $item[ self::IDX_LBL ] ),
					$val,
					$type
			);
		}
		echo $after;
	}

	function direct_update( $post_id, $fieldname, $new_value, $part_id = 0 ){

		if( is_string( $new_value )){
			$new_value = json_decode( $new_value, true );
		}

		$base_meta_key = AFG_META_PREFIX . $this->name;

		if( $this->name === $fieldname ){
			//afg_log(__METHOD__, compact('post_id', 'fieldname', 'base_meta_key', 'new_value'));
			$result    = update_post_meta( $post_id, $base_meta_key, $new_value );
		} else {
			if( ! $part_id ){
				//most likely this comes from a standard widget
				$postfix   = preg_replace("/[^0-9]/", "", $fieldname ); //remove all non-numbers
				$sanitized = $this->sanitize_value( $new_value, $base_meta_key, 'post' );
				$result    = update_post_meta( $post_id, $base_meta_key . '_' . $postfix, $sanitized );

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
							afg_log(__METHOD__, 'could not update panels_data on ' . $post_id. 'with '.print_r($panels_data, true) );
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

		if( is_string( $meta_value )){
			$meta_value = json_decode( $meta_value, $assoc = true );
		}

		//afg_log(__METHOD__, compact('meta_value', 'meta_key', 'meta_type'));

		$cleaned = array();
		if( is_array( $meta_value )){
			foreach( $meta_value as $attr => $item ){
				/*
				$attr: attribute name - strip tags, scripts, whitespace (can be empty)
				Every item must be an array with four elements:
				$item[0]:type - text, link, price, dimensions
				$item[1]:current label  - strip tags, scripts, whitespace (can be empty)
				$item[2]:current value  - strip tags, scripts, whitespace (can be empty)
				$item[3]:current status - 1 or 0
				*/

				if( ! is_array( $item ) || count( $item ) != 4 ){
					continue;
				}

				$attr    = sanitize_text_field( $attr );

				//type
				if( !is_string( $item[0] ) || !in_array( $item[0], array('link', 'price', 'dim'))){
					$item[0] = $this->get_attribute_type( $attr );
				}

				//label
				$item[1] = sanitize_text_field( $item[1] );//lbl

				//value
				switch( $item[0] ){
					case 'price':
						$item[2] = sanitize_text_field( $item[2] );
						break;
					case 'link':
						$item[2] = array(
							'href'  => isset( $item[2]['href'] )  ? esc_url_raw( $item[2]['href']) : '',
							'title' => isset( $item[2]['title'] ) ? sanitize_text_field( $item[2]['title']) : '',
							'text'  => isset( $item[2]['text'] )  ? sanitize_text_field( $item[2]['text'] ): '',
						);
						break;
					case 'dim':
						$item[2] = sanitize_text_field( $item[2] );
						break;
					default: //text
						$item[2] = sanitize_text_field( $item[2] );
				}

				//status/visibility
				$item[3] = ($item[3] === 1 || $item[3] === '1') ? 1 : 0;

				$cleaned[ $attr ] = $item;
			}
		}

		//print_r($cleaned);
		//afg_log(__METHOD__, compact('meta_value', 'cleaned'));

		return $cleaned;
	}

	function get_default_value( $params = null ){

		//$configure = (isset( $params['prepare_widget_settings'] ) && $params['prepare_widget_settings']);

		$preset    = (isset( $params['widget_data'] ) && $params['widget_data']) ? $params['widget_data'] : false;

		$prototype = false;
		$product   = false;

		$post_id   = (isset( $params['post_id']) && $params['post_id']) ? absint( $params['post_id'] ) : false;
		if( $post_id ){
			//will take labels and values from this base metafield, if already present
			$prototype = get_post_meta( $post_id, AFG_META_PREFIX . $this->name, true );
			$product   = get_post_meta( $post_id, AFG_META_PREFIX . 'product_data', true );
		}

		//afg_log(__METHOD__.':'.__LINE__, compact('params', 'preset', 'prototype') );

		$result = array();
		$i = 0;

		if( $preset ){
			if( is_string( $preset )){
				$preset = json_decode( $preset, $assoc = true );
			}
			$attributes = array_keys( $preset );
		} else {
			$attributes = array_map('trim', explode(',', $this->settings['available_items']));;
		}

		$visible_attributes = array_map('trim', explode(',', $this->settings['visible_items']));

		foreach( $attributes as $attr ){

			if( ! array_key_exists( $attr, $this->features )){
				//echo '<pre>';
				throw new AffiGet_Exception('Unexpected attribute requested: ' . $attr );
			}

			$type = $this->get_attribute_type( $attr );

			if( $prototype ){
				$result[ $attr ] = array(
						$type,
						$prototype[ $attr ][ self::IDX_LBL ],
						$prototype[ $attr ][ self::IDX_VAL ],
						$preset  ? $preset[ $attr ][ self::IDX_ACT ] : $prototype[ $attr ][ self::IDX_ACT ],
				);
			} else {
				$result[ $attr ] = array(
						$type,
						$preset  ? $preset[ $attr ][ self::IDX_LBL ] : $this->features[ $attr ], //standard label
						$this->get_attribute_value( $attr, $type, $this->features[ $attr ], $product ),
						$preset  ? $preset[ $attr ][ self::IDX_ACT ] : ( in_array( $attr, $visible_attributes ) ? 1 : 0 )
				);
			}

			// [0]: current position
			// [1]: current label
			// [2]: current value
			// [3]: is currently visible

			$i++;
		}

		//afg_log(__METHOD__.':'.__LINE__, compact('result') );
		if( $post_id && ! false === $prototype ){
			update_post_meta( $post_id, AFG_META_PREFIX . $this->name, $result );
		}

		return $result;
	}

	protected function get_attribute_type( $attr ){

		if( in_array( $attr, self::$link_attributes ) ){
			return 'link';
		} elseif( in_array( $attr, self::$price_attributes )){
			return 'price';
		} elseif('ItemDimensions' == $attr || 'PackageDimensions' == $attr ){
			return 'dim';
		}
		return 'text';
	}

	protected function get_attribute_value( $attr, $type, $label, $product_data ){

		$raw_value = $product_data ? $this->meta->pick_product_data_value( $product_data, $attr ) : '';

		if( ! $raw_value ){
			return '';
		}

		$value = $raw_value;

		if( 'link' == $type ){
			$value = array(
				'href'  => $value,
				'title' => sprintf(
						esc_attr_x('%s page on Amazon', '%s is link label, e.g. "Add To Wedding Registry"','afg'),
						$label
				),
				'text'  => $label
			);
			$value = apply_filters("afg_product_details_data_{$attr}", $value );
			return $value;

		} elseif( 'price' == $type ){

			return $value['FormattedPrice']; //value contains Amount/CurrencyCode/FormattedPrice

		} elseif( 'dim' == $type ){

			return $value;///XXX: parse

		} elseif( in_array( $attr, self::$complex_attributes )){

			if( 'Languages' == $attr ){

				$original  = '';
				$published = '';
				foreach( $value as $lng ){
					if( 'Published' == $lng['Type'] ){
						$published = $lng['Name'];
					} elseif( 'Original Language' == $lng['Type'] ){
						$original = $lng['Name'];
					}
				}
				return $published;
				/*$value = '';
				if( $published && $original && $published == $original ){
					$value = $original;
				} else {
					if( $published ){
						$value .= '<span class="lang-pub-label">';
						$value .= _x('Published', 'label for publication language', 'afg');
						$value .= '</span><span class="lang-pub-value">'.$published.'</span>';
					}
					if( $original ){
						$value .= $value ? '; ' : '';
						$value .= '<span class="lang-orig-label">';
						$value .= _x('Original', 'label for original language', 'afg');
						$value .= '</span><span class="lang-orig-value">'.$original.'</span>';
					}
				}
				break;
				*/
			}
		}
		return $value;
	}

	protected function format_attribute_value( $attr, $type, $label, $value ){

		if( 'link' == $type ){
			$link_template = '<a href="%1$s" class="%2$s" title="%3$s" rel="nofollow">%4$s</a>';
			$link_template = apply_filters("afg_product_details_display_template_{$attr}", $link_template );

			$value = sprintf( $link_template,
					esc_attr( $value['href'] ),
					esc_attr( $attr ),
					esc_attr( $value['title'] ),
					esc_html( $value['text'] )
			);
			return $value;
		}

		if( 'price' == $type ){
			//echo '<pre>'.print_r($value, true).'</pre>';
			if( ! empty( $value )){
				//return esc_html( $value['FormattedPrice'] );
				return esc_html( $value );
			} else {
				return '';
			}
		}

		if( ! in_array( $attr, self::$complex_attributes )){
			return esc_html( $value );
		}

		switch( $attr ){
/*			case 'Languages':

				$original  = '';
				$published = '';
				foreach( $value as $lng ){
					if( 'Published' == $lng['Type'] ){
						$published = $lng['Name'];
					} elseif( 'Original Language' == $lng['Type'] ){
						$original = $lng['Name'];
					}
				}
				$value = '';
				if( $published && $original && $published == $original ){
					$value = $original;
				} else {
					if( $published ){
						$value .= '<span class="lang-pub-label">';
						$value .= _x('Published', 'label for publication language', 'afg');
						$value .= '</span><span class="lang-pub-value">'.$published.'</span>';
					}
					if( $original ){
						$value .= $value ? '; ' : '';
						$value .= '<span class="lang-orig-label">';
						$value .= _x('Original', 'label for original language', 'afg');
						$value .= '</span><span class="lang-orig-value">'.$original.'</span>';
					}
				}
				break;
*/
			case 'ItemDimensions':
			case 'PackageDimensions':
			case 'ProductTypeName':
				//$value = esc_html( $value );XXX parse dimensions
				break;

			case 'TradeInValue':
				$parts = explode('$', $value);
				if( count( $parts ) == 2 ){
					$value = '$' . $parts[1];//XXX support other currencies and price formats
				}
				break;

			case 'IsEligibleForTradeIn':
				$value = $value == '1' ? _x('Yes', 'eligible for trade-in', 'afg') : _x('No', 'not eligible for trade-in', 'afg');
				break;
		}

		return esc_html( $value );
	}

	function enqueue_scripts_and_styles( $hook ) {

		if( $this->meta->is_element_style_needed() ){

			wp_enqueue_style( 'afg-feature-list-style',
					plugins_url( '/css/element.css', (__FILE__)),
					array(),
					AFG_VER
			);
		}

		if( $this->meta->is_element_script_needed() ){

			wp_enqueue_script( 'afg-feature-list-script',
					plugins_url( '/js/element.js', (__FILE__)),
					array(
							'jquery',
							'jquery-ui-core',
							//'jquery-ui-draggable', //drag lists around
							//'jquery-ui-droppable',
							'jquery-ui-sortable',  //sort items
							'jquery-effects-core',
							//'jquery-effects-pulsate' //pulsate when loosing focus without save
					),
					AFG_VER
			);
		}
	} // end enqueue_scripts_and_styles
}

endif;

/* EOF */