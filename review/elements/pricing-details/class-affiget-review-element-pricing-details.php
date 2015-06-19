<?php
if( ! defined ( 'ABSPATH' ) ) { exit; }

if ( ! class_exists('AffiGet_Review_Element_Pricing_Details', false)):

/**
 *
 * Widget to provide different views of the product data managed by AffiGet_Review_Element_Pricing_Details.
 *
 * @since      1.0.0
 * @package    AffiGet
 * @subpackage AffiGet/review
 * @author     Saru Tole <sarutole@affiget.com>
 *
 */

class AffiGet_Review_Element_Pricing_Details extends AffiGet_Abstract_Element
{

	protected $map_conditions_to_attributes = array(
			'new'         => 'LowestNewPrice',
			'used'        => 'LowestUsedPrice',
			'collectible' => 'LowestCollectiblePrice',
			'refurbished' => 'LowestRefurbishedPrice',
	);


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
		add_action( 'cmb2_render_afg_pricing_details', array(&$this, 'render_cmb2_field'), 10, 5 );

		//assets
		add_action( 'admin_enqueue_scripts', array(&$this, 'enqueue_scripts_and_styles'));
		add_action( 'wp_enqueue_scripts', array(&$this, 'enqueue_scripts_and_styles'));
	}

	protected function get_settings_config(){

		$new_fields = array(
				'allow_conditions'  => array(
						'name'    => 'allow_conditions',
						'atts'    => 'size="55"',
						'type'    => 'checklist',
						'options' => array(
								'new'         => __('New', 'afg'),
								'used'        => __('Used', 'afg'),
								'collectible' => __('Collectible', 'afg'),
								'refurbished' => __('Refurbished', 'afg'),
						),
						'default' => 'new',
						'label'   => __('Allowed conditions', 'afg'),
						'hint'    => __('A list of conditions to take into account when calculating the best price.', 'afg'),
						'help'    => __('Help'),
				),

				'show_details'  => array(
						'name'    => 'show_details',
						'atts'    => 'size="55"',
						'type'    => 'checklist',
						'options' => array(
								'list_price'       => __('List price','afg'),
								'best_price'       => __('Best price','afg'),
								'saved_amount'     => __('Saved amount','afg'),
								'saved_percentage' => __('Saved percentage','afg'),
								'condition'        => __('Condition','afg'),
								'items_in_store'   => __('Items in store','afg'),
								'offer_details'    => __('Offer details','afg'),
								'variant_details'  => __('Variant details','afg'),
						),
						'default' => 'list_price,best_price,saved_amount,saved_percentage,condition,items_in_store,offer_details,variant_details',
						'label'   => __('Allowed conditions', 'afg'),
						'hint'    => __('A list of conditions to take into account when calculating the best price.', 'afg'),
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
				'type'    => 'afg_pricing_details',
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
				'afg-pricing-details',
				'contains-'.$format,
				$context,
				$post_id,
				$fieldname,
				$nonce,
				$input_id,
				isset( $params['wid'] ) ? $params['wid'] : ''
		);

		$encoded = $this->_prepare_items( $items, $post_id, $fieldname );

		//echo '<pre>Items:['.print_r( $items, true) . '</pre>';

		if( 'widget-settings' != $context ){
			printf("<input class='items' type='hidden' id='%s' name='%s' value='%s' />",
					$input_id,
					$this->control_id,
					$encoded
			);
		}

		$this->render_content( $items );

		echo('</div>');
	}

	function get_preview( $post_id, $options ){

		$items = $this->get_default_value_for_post( $post_id, array('widget_data' => $options ));
		echo '<div>';
		$this->render_content( $items );
		echo "<input type='hidden' class='js_new_data' value='" . json_encode( $items ) . "'/>";
		echo '</div>';
	}

	protected function render_content( $items, $echo = true ){

		if( empty( $items )){
			$content = '<div class="content"></div>';
			if( $echo ){
				echo $content;
			}
			return $content;
		}

		$prices  = $items['prices'];
		$labels  = $items['labels'];
		$options = $items['options'];

		$html = '';

		$show_details = $options['show_details'];

		if( ! isset( $labels['list_price'] )){
			return '';
			//echo '<pre>';
			//throw new AffiGet_Exception('No labels!');
		}

		if( $labels['list_price'] ){

			$price_part = sprintf(
					_x('$%s', 'localized price display', 'afg'),
					number_format( $prices['list_price'], 2 )
			);

			if( in_array( 'best_price', $show_details ) && $prices['best_price'] && $prices['list_price'] !== $prices['best_price'] ){
				$price_part = sprintf('<span class="list-price old">%s</span>', $price_part );
			} else {
				$price_part = sprintf('<span class="list-price current">%s</span>', $price_part );
			}

			$html = sprintf( "<li>{$labels['list_price']}</li>", $price_part );
		}

		if( $labels['best_price'] ){

			$price_part = sprintf(
					_x('$%s', 'localized price display', 'afg'),
					number_format( $prices['best_price'], 2 )
			);
			$price_part = sprintf('<span class="best-price">%s</span>', $price_part );

			$condition = '';
			if( $labels['condition'] ){
				$condition .= " <span class='condition'>{$labels['condition']}</span>";
			}

			$html .= sprintf( "<li>{$labels['best_price']}</li>", $price_part, $condition );
		}

		if( $labels['saved'] ){
			$saved = '';

			$amount  = '';
			$percent = '';
			if( in_array( 'saved_amount', $show_details ) ){
				$amount = sprintf(
						'<span class="saved-amount">$%s</span>',
						number_format( $prices['saved_amount'], 2 )
				);
			}
			if( in_array( 'saved_percentage', $show_details )){
				if( $amount ){
					//with parentheses
					$percent = sprintf(
							' <span class="saved-percentage">(%s&#37;)</span>',
							number_format( $prices['saved_percentage'], 1)
					);
				} else {
					//with no parentheses
					$percent = sprintf(
							'<span class="saved-percentage">%s&#37;</span>',
							number_format( $prices['saved_percentage'], 1)
					);
				}
			}

			$html .= sprintf( "<li>{$labels['saved']}</li>", $amount, $percent );
		}

		$content = "<div class='content'><ul>{$html}</ul></div>";
		if( $echo ){
			echo $content;
		}
		return $content;
	}

	function direct_update( $post_id, $fieldname, $items, $part_id = 0 ){

		if( is_string( $items )){
			$items = json_decode( $items, true );
		}

		$base_meta_key = AFG_META_PREFIX . $this->name; //XXX here we should use declared_meta_field to be more correct

		if( $this->name == $fieldname ){

			$result = $this->repick_product_prices( $items, $post_id, $items['options'] );
			if( $result ){
				$result    = update_post_meta( $post_id, $base_meta_key, $items );
			}

		} else {
			if( ! $part_id ){

				$items  = $this->sanitize_value( $items, $base_meta_key, 'post' );
				$result = $this->repick_product_prices( $items, $post_id, $items['options'] );
				if( $result ){
					return null;
				}

				//most likely this comes from a standard widget
				$postfix   = preg_replace("/[^0-9]/", "", $fieldname ); //remove all non-numbers
				$result    = update_post_meta( $post_id, $base_meta_key . '_' . $postfix, $items );

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

						$items  = $this->sanitize_value( $items, $base_meta_key, 'post' );
						$result = $this->repick_product_prices( $items, $post_id, $items['options'] );
						if( $result ){
							return null;
						}

						$widget['items'] = json_encode( $items );
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
	function sanitize_value( $items, $meta_key, $meta_type ){

		if( false === strpos( $meta_key, AFG_META_PREFIX . $this->name )){
			//return $meta_value;
			throw new AffiGet_Exception('Unexpected meta_key: ' . $meta_key );
		}

		if( is_string( $items )){
			$items = json_decode( $items, $assoc = true );
		}

		$cleaned = array(
			'options' => array(
				'allow_conditions' => array(),
				'show_details'     => array(),
			),
			'prices' => array(),
			'labels' => array(),
		);

		if( is_array( $items )){
			if( is_array( $items['options'] )){
				if( is_array( $items['options']['allow_conditions'] )){
					$cleaned['options']['allow_conditions'] = $items['options']['allow_conditions'];
				}
				if( is_array( $items['options']['show_details'] )){
					$cleaned['options']['show_details'] = $items['options']['show_details'];
				}
			}
			if( is_array( $items['prices'] )){
				$cleaned['prices'] = $items['prices'];
			}
			if( is_array( $items['labels'] )){
				$cleaned['labels'] = $items['labels'];
			}
		}

		if( empty( $cleaned['options']['allow_conditions'] )){
			$cleaned['options']['allow_conditions'][] = 'new';
		}
		if( empty( $cleaned['options']['show_details'] )){
			$cleaned['options']['show_details'][] = 'list_price';
		}

		return $cleaned;
	}

	protected function repick_product_prices( &$items, $post_id, $options ){

		$product_data = get_post_meta( $post_id, AFG_META_PREFIX . 'product_data', true );
		if( ! $product_data ){
			return null;
		}
		$items['prices'] = $this->prepare_prices( $product_data, $options['allow_conditions'] );
		$items['labels'] = $this->prepare_labels( $items['prices'], $options['show_details'] );

		//afg_log(__METHOD__, compact('items', 'options'));

		return true;
	}

	protected function prepare_labels( $prices, $show_details ){

		$labels = array(
				'list_price' => '',
				'best_price' => '',
				'condition'  => '',
				'saved'      => '',
		);

		if( in_array( 'list_price', $show_details ) && $prices['list_price'] ){
			if( in_array( 'best_price', $show_details ) && $prices['best_price'] && $prices['list_price'] !== $prices['best_price'] ){
				$labels['list_price'] = __('Regular price: %s', 'afg');
			} else {
				$labels['list_price'] = __('Price from: %s', 'afg');
			}
		}

		if( in_array( 'best_price', $show_details ) && $prices['best_price'] && $prices['list_price'] !== $prices['best_price']){
			if( ! $prices['list_price']){
				$labels['best_price'] = _x('Price from: %1$s%2$s', 'price, condition', 'afg');
			} else {
				$labels['best_price'] = _x('Current offer: %1$s%2$s', 'price, condition', 'afg');
			}

			$conditions = array(
					//''          => __('','afg'),
					'special'     => __('(special)', 'afg'),
					'new'         => __('(new)', 'afg'),
					'used'        => __('(used)', 'afg'),
					'refurbished' => __('(refurbished)', 'afg'),
					'collectible' => __('(collectible)', 'afg'),
					'variant'     => __('(variant)', 'afg'),
			);
			if( in_array( 'condition', $show_details ) && array_key_exists( $prices['condition'], $conditions )){
				$labels['condition'] = $conditions[ $prices['condition'] ];
			}
		}

		if( in_array( 'saved_amount', $show_details ) || in_array( 'saved_percentage', $show_details )){
			if( $prices['best_price'] && $prices['list_price'] && $prices['best_price'] < $prices['list_price'] ){
				$labels['saved'] = _x('You save: %1$s %2$s', 'saved amount, saved percentage', 'afg');
			}
		}
		return apply_filters('afg_pricing_details__prepare_labels', $labels, $prices, $show_details );
	}

	protected function prepare_prices( $product_data, $allow_conditions ){

		//find min price
		$min_amount    = PHP_INT_MAX;
		$min_condition = '';
		$min_currency  = '';
		foreach( $allow_conditions as $condition ){
			if( array_key_exists( $condition, $this->map_conditions_to_attributes )){
				$price = AffiGet_Review_Meta::pick_product_data_value( $product_data, $this->map_conditions_to_attributes[ $condition ] );
				if( $price['Amount'] && $price['Amount'] < $min_amount ){
					$min_amount    = $price['Amount'];
					$min_condition = $condition;
					$min_currency  = $price['CurrencyCode'];
				}
			}
		}

		$list_price  = AffiGet_Review_Meta::pick_product_data_value( $product_data, 'ListPrice' );
		$list_amount = $list_price['Amount'];
		if( ! $list_amount ){
			if( $min_amount != PHP_INT_MAX ){
				$list_amount = $min_amount;
			} else {
				$list_amount = 0;
			}
		}

		$saved_amount  = 0;
		$saved_percent = 0;
		if( PHP_INT_MAX != $min_amount && $list_amount != $min_amount){
			//$times = $saved_amount * 100;
			//afg_log(__METHOD__, compact('list_amount', 'min_amount', 'times'));
			$saved_amount  = $list_amount - $min_amount;
			$saved_percent = round(( $saved_amount * 100 ) / $list_amount, 2 );
		} else {
			$min_amount = $list_amount;
		}

		$result = array(
				'list_price'       => $list_amount ? ($list_amount / 100) : 0,
				'best_price'       => $min_amount ? ($min_amount / 100): 0,
				'currency'         => $min_currency,
				'saved_amount'     => $saved_amount ? ($saved_amount / 100): 0,
				'saved_percentage' => $saved_percent,
				'condition'        => $min_condition,
				'items_in_store'   => -1,
				'offer_details'    => '',
				'variant_details'  => '',
		);
		return $result;
	}

	protected function get_default_value_for_post( $post_id, $params ){

		$preset  = (isset( $params['widget_data'] ) && $params['widget_data']) ? $params['widget_data'] : false;

		$items = array(
			'options' => $preset ?  $preset['options'] : $params['defaults']['options']
		);

		$result = $this->repick_product_prices( $items, $post_id, $items['options'] );

		return $result ? $items: null;
	}

	function get_default_value( $params = null ){

		//get default from settings
		$defaults = array(
				'options' => array(
						'allow_conditions' => array_map('trim', explode(',', $this->settings['allow_conditions'] )),
						'show_details'     => array_map('trim', explode(',', $this->settings['show_details'] )),
				),
				'prices'  => array(
						'list_price'       => 12.34,
						'best_price'       => 7.89,
						'saved_amount'     => 12.34-7.89,
						'saved_percentage' => ((12.34-7.89)*100)/12.34,
						'currency'         => 'USD',
						'condition'        => 'used',
						'items_in_store'   => '10',
						'offer_details'    => '',
						'variant_details'  => '',
				),
				'labels'   => null
		);

		//get default from post (can be merged with widget settings that are passed as one of the params)
		if( isset( $params['post_id'] ) && 0 < absint( $params['post_id'] )){
			$params['defaults'] = $defaults;
			$result = $this->get_default_value_for_post( absint( $params['post_id'] ), $params );
			if( $result ){
				return $result;
			}
		}

		$defaults['labels'] = $this->prepare_labels( $defaults['prices'], $defaults['options']['show_details'] );

		//afg_log(__METHOD__, compact('params', 'defaults'));

		return $defaults;
	}

	function enqueue_scripts_and_styles( $hook ) {

		if( $this->meta->is_review_style_needed() ){
			wp_enqueue_style( 'afg-pricing-details-style',
					plugins_url( '/css/element.css', (__FILE__)),
					array(),
					AFG_VER
			);
		}

		if( $this->meta->is_review_script_needed() ){
			wp_enqueue_script( 'afg-pricing-details-script',
					plugins_url( '/js/element.js', (__FILE__)),
					array('afg-raty-script'),
					AFG_VER
			);
		}

	} // end enqueue_scripts_and_styles
}

endif;

/* EOF */