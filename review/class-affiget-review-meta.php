<?php
/**
* Declares a custom post type to manage Amazon product reviews (one product per post).
*
* @link       http://affiget.com
* @since      1.0.0
*
* @package    AffiGet
* @subpackage AffiGet/review
*/

/**
*
* @since      1.0.0
* @package    AffiGet
* @subpackage AffiGet/review
* @author     Saru Tole <sarutole@affiget.com>
*/

/**
 * These properties can be accessed via __get() magic
 *
 * @property AffiGet_Mini plugin
 * @property string  post_type_name
 * @property AffiGet_Review_Controller controller
 * @property AffiGet_Review_Storage storage
 * @property AffiGet_Review_Admin admin
 * @property AffiGet_Review_Renderer renderer
 */
class AffiGet_Review_Meta extends AffiGet_Abstract_Meta {

	/**
	 * Reference to the single instance of this class.
	 *
	 * @var AffiGet_Review_Meta
	 */
	protected static $_instance = null;

	/**
	 * Store posts that were already fully loaded.
	 *
	 * @var array of post_data arrays
	 */
	protected $cached_posts = array();

	/**
	 *
	 * @var AffiGet_Review_Controller
	 */
	protected $_controller;

	/**
	 *
	 * @var AffiGet_Review_Storage
	 */
	protected $_storage;

	/**
	 *
	 * @var AffiGet_Review_Admin
	 */
	protected $_admin;

	/**
	 *
	 * @var AffiGet_Review_Renderer
	 */
	protected $_renderer;

	//--------------------------------------------------------------------------

	/**
	 * A factory method to build/gain access to the single instance of this class.
	 *
	 * @return AffiGet_Review_Meta
	 */
	public static function get_instance( AffiGet_Mini $parent_plugin = null, $post_type_name = 'review' ){

		if( null === self::$_instance ){
			$i = 1;
			while ( post_type_exists( $post_type_name )){
				$post_type_name = $post_type_name . $i;
				$i++;
			}

			$args = array(
					'post_type_name' => $post_type_name,
					'singular_label' => _x('Review',  'Custom post type (singular)', 'afg'),
					'plural_label'   => _x('Reviews', 'Custom post type (plural)', 'afg'),
					'description'    => _x('Review',  'Custom post type description', 'afg')
			);

			self::$_instance = new self( $parent_plugin, $args );
		}

		return self::$_instance;
	}

	final protected function __clone(){}

	protected function __construct( AffiGet_Mini $parent_plugin, $params ){

		parent::__construct( $parent_plugin, $params );

		require_once( 'class-affiget-review-controller.php' );
		require_once( 'class-affiget-review-storage.php' );
		require_once( 'class-affiget-review-admin.php' );
		require_once( 'class-affiget-review-renderer.php' );

		$this->_controller = new AffiGet_Review_Controller( $this );
		$this->_storage    = new AffiGet_Review_Storage( $this );
		$this->_admin      = new AffiGet_Review_Admin( $this );
		$this->_renderer   = new AffiGet_Review_Renderer( $this );

		$this->_register_elements();

		$this->_register_known_fields();
	}

	/**
	 * Whitelist known fields. These declarations are primarily needed for $this->storage.
	 */
	protected function _register_known_fields(){

		add_filter('afg_review_meta__declare_post_fields', array(&$this, '_declare_post_fields'), 9, 2);
		$this->_known_post_fields      = apply_filters( 'afg_review_meta__declare_post_fields', $this->_known_post_fields, $this );

		$this->_known_meta_fields      = apply_filters( 'afg_review_meta__declare_meta_fields', $this->_known_meta_fields, $this );

		add_filter('afg_review_meta__declare_taxonomic_fields', array(&$this, '_declare_taxonomic_fields'), 9, 2);
		$this->_known_taxonomic_fields = apply_filters( 'afg_review_meta__declare_taxonomic_fields', $this->_known_taxonomic_fields, $this );
	}

	/**
	 * Review is constituted of elements. We register all of them here.
	 *
	 * To simplify presentation/layout some elements have corresponding widgets.
	 * Elements should be registered before widgets, because some elements hook to their corresponding widgets via actions/filters.
	 */
	protected function _register_elements(){

		require_once( 'elements/class-affiget-review-element-post-status.php' );
		require_once( 'elements/review-part/class-affiget-element-review-part.php' );
		require_once( 'elements/product-details/class-affiget-review-element-product-details.php' );
		require_once( 'elements/star-ratings/class-affiget-review-element-star-ratings.php' );
		require_once( 'elements/pricing-details/class-affiget-review-element-pricing-details.php' );
		require_once( 'elements/call-to-action/class-affiget-review-element-call-to-action.php' );

		$params = array(
				'title'               => __('Post status', 'afg'),
				'label'               => __('Post status', 'afg'),
				'description'         => '',
				'status'              => 'enabled',
				'dialog'              => 'enabled',
				'dialog_mode'         => 'always_enabled',
				'presentation'        => 'manual',
				'presentation_mode'   => 'modifiable',
				'shortcode'           => 'disabled',
				'shortcode_mode'      => 'modifiable',
				'declare_meta_fields' => array('auto_date_gmt'),
		);
		$this->_elements['post_status']
				= new AffiGet_Review_Element_Post_Status( $this, 'post_status', $params );

		$params = array(
				'title'               => __('-Introduction', 'afg'),
				'label'               => __('Introduction', 'afg'),
				'description'         => __('Introduction/Excerpt for this review', 'afg'),
				'status'              => 'enabled',
				'dialog'              => 'enabled',
				'dialog_mode'         => 'modifiable',
				'presentation'        => 'manual',
				'presentation_mode'   => 'modifiable',
				'shortcode'           => 'disabled',
				'shortcode_mode'      => 'modifiable',
				'declare_meta_fields' => array('review_intro'),
				'metabox_position'    => 1,
				'textarea_rows'       => 3,
		);
		$this->_elements['review_intro']
				= new AffiGet_Element_Review_Part( $this, 'review_intro', $params );

		$params = array(
				'title'               => __('-Product Details', 'afg'),
				'label'               => __('Product Details', 'afg'),
				'description'         => __('Available/presented product details. Also editable on the front-end!', 'afg'),
				'status'              => 'enabled',
				'dialog'              => 'enabled',
				'dialog_mode'         => 'modifiable',
				'presentation'        => 'auto',
				'presentation_mode'   => 'modifiable',
				'shortcode'           => 'disabled',
				'shortcode_mode'      => 'modifiable',
				'declare_meta_fields' => array( 'product_details' ),
				'metabox_position'    => 5,
				'visible_attributes'  => array('Title'),
		);

		$this->_elements['product_details']
				= new AffiGet_Review_Element_Product_Details( $this, 'product_details', $params );

		$params = array(
				'title'               => __('-Review', 'afg'),
				'label'               => __('Review', 'afg'),
				'description'         => __('Main review text', 'afg'),
				'status'              => 'enabled',
				'dialog'              => 'enabled',
				'dialog_mode'         => 'modifiable',
				'presentation'        => 'auto',
				'presentation_mode'   => 'modifiable',
				'shortcode'           => 'disabled',
				'shortcode_mode'      => 'modifiable',
				'declare_meta_fields' => array('review_content'),
				'metabox_position'    => 3,
				'textarea_rows'       => 10,
		);
		$this->_elements['review_content']
				= new AffiGet_Element_Review_Part( $this, 'review_content', $params );

		$params = array(
				'title'               => __('-Conclusion', 'afg'),
				'label'               => __('Conclusion', 'afg'),
				'description'         => __('Conclusion/Summary for this review', 'afg'),
				'status'              => 'enabled',
				'dialog'              => 'enabled',
				'dialog_mode'         => 'modifiable',
				'presentation'        => 'auto',
				'presentation_mode'   => 'modifiable',
				'shortcode'           => 'disabled',
				'shortcode_mode'      => 'modifiable',
				'declare_meta_fields' => array('review_conclusion'),
				'metabox_position'    => 4,
				'textarea_rows'       => 3,
		);
		$this->_elements['review_conclusion']
				= new AffiGet_Element_Review_Part( $this, 'review_conclusion', $params );

		$params = array(
				'title'               => __('-Star Ratings', 'afg'),
				'label'               => __('Rating', 'afg'),
				'description'         => __('Stars', 'afg'),
				'status'              => 'enabled',
				'dialog'              => 'enabled',
				'dialog_mode'         => 'modifiable',
				'presentation'        => 'auto',
				'presentation_mode'   => 'modifiable',
				'shortcode'           => 'disabled',
				'shortcode_mode'      => 'modifiable',
				'declare_meta_fields' => array( 'star_ratings' ),
				'metabox_position'    => 6,
		);

		$this->_elements['star_ratings']
				= new AffiGet_Review_Element_Star_Ratings( $this, 'star_ratings', $params );

		$params = array(
				'title'               => __('-Pricing Details', 'afg'),
				'label'               => __('Pricing', 'afg'),
				'description'         => __('Pricing Details', 'afg'),
				'status'              => 'enabled',
				'dialog'              => 'enabled',
				'dialog_mode'         => 'modifiable',
				'presentation'        => 'auto',
				'presentation_mode'   => 'modifiable',
				'shortcode'           => 'disabled',
				'shortcode_mode'      => 'modifiable',
				'declare_meta_fields' => array( 'pricing_details' ),
				'metabox_position'    => 7,
		);

		$this->_elements['pricing_details']
				= new AffiGet_Review_Element_Pricing_Details( $this, 'pricing_details', $params );

		$params = array(
				'title'               => __('-Call-to-Action', 'afg'),
				'label'               => __('Call-to-Action', 'afg'),
				'description'         => __('Call-to-Action', 'afg'),
				'status'              => 'enabled',
				'dialog'              => 'enabled',
				'dialog_mode'         => 'modifiable',
				'presentation'        => 'auto',
				'presentation_mode'   => 'modifiable',
				'shortcode'           => 'disabled',
				'shortcode_mode'      => 'modifiable',
				'declare_meta_fields' => array( 'call_to_action' ),
				'metabox_position'    => 8,
		);

		$this->_elements['call_to_action']
				= new AffiGet_Review_Element_Call_to_Action( $this, 'call_to_action', $params );

	}

	function _declare_post_fields( array $fields ){

		$more_fields = array(
				'ID', 'post_title', 'post_date', 'post_date_gmt',
				'post_excerpt',
				'post_content',
				'post_type', 'post_author', 'post_status',
				'comment_status', 'ping_status',
				'post_password', 'post_parent', 'post_name',
				'to_ping', 'pinged', 'post_modified', 'post_modified_gmt',
				'post_content_filtered', 'guid', 'menu_order',
				'post_mime_type', 'comment_count'
		);
		return array_merge( $fields, $more_fields );
	}

	function _declare_taxonomic_fields( array $fields ){

		$fields['category'] = ''; //a field will be stored as a taxonomy term, if assigned
		$fields['post_tag'] = ''; //a field will be stored as a taxonomy term, if assigned

		return $fields;
	}

	/**
	 * Magic method to control access to properties.
	 *
	 * @param string $property
	 * @throws AffiGet_Exception
	 * @return AffiGet_Mini|AffiGet_Review_Controller|AffiGet_Review_Storage|AffiGet_Review_Admin|AffiGet_Review_Renderer|multitype:
	 */
	public function __get( $property ){

		switch( $property ){
			case 'controller':
				return $this->_controller;
			case 'storage':
				return $this->_storage;
			case 'admin':
				return $this->_admin;
			case 'renderer':
				return $this->_renderer;
			default:
				return parent::__get( $property );
		}
	}

	/**
	 * Make sure posts of our Review post type are included alongside regular posts.
	 */
	public function filter_pre_get_posts( $query ) {

		if( ! is_admin() && ! is_singular() && ! in_the_loop() ){

			$afg_types = array( $this->_post_type_name );

			$types = $query->get('post_type');

			if( $types == 'nav_menu_item' ){
				return;
			}

			if( is_array( $types ) ){
				$afg_types = array_merge( $afg_types, $types );
			} else {
				array_unshift( $afg_types, $types );
			}

			$query->set( 'post_type', $afg_types );
		}
		return;
	}

	/**
	 * The primary method to load a custom post with all of its constituting parts.
	 *
	 * @see AffiGet_Abstract_Meta::load_post()
	 */
	public function load_post( $post_id, $bust_cache = false ){

		if( $bust_cache || ! isset( $this->cached_posts[ $post_id ] ) ){
			$this->cached_posts[ $post_id ] = $this->controller->load_review_data_by_post_id( $post_id );
		}
		return $this->cached_posts[ $post_id ];
	}

	/**
	 * Pick attribute value from raw product data (as retrieved from Amazon).
	 *
	 * @param array $product_data
	 * @param string $fieldname
	 * @return NULL|multitype:NULL
	 */
	public static function pick_product_data_value( array $product_data, $fieldname ){

		if( empty( $product_data ) || !$fieldname ){
			return null;
		}
		//echo '<pre>'.print_r($product_data, true).'</pre>';

		if( 'True' != $product_data['IsValid'] ){
			return null;
		}

		if( empty( $product_data['Items'] )){
			return null;
		}

		$item = $product_data['Items'][0];

		$itemLinkFields = array(
				'TechnicalDetails',
				'AddToBabyRegistry',
				'AddToWeddingRegistry',
				'AddToWishlist',
				'TellAFriend',
				'AllCustomerReviews',
				'AllOffers'
		);

		if( 'Images' == $fieldname && ! empty( $item['ImageSets'])){
			$result = array(
					0 => null
			);
			foreach( $item['ImageSets'] as $img ){
				if( 'primary' == $img['Category'] ){
					$result[0] = $img['LargeImage']['URL'];
				} else {
					$result[] = $img['LargeImage']['URL'];
				}
			}
			return $result;
		} elseif( array_key_exists( $fieldname, $item )){
			return $item[ $fieldname ];
		} elseif( isset( $item['ItemAttributes'] ) && array_key_exists( $fieldname, $item['ItemAttributes'] )){
			return $item['ItemAttributes'][ $fieldname ];
		} elseif( isset( $item['OfferSummary'] ) && array_key_exists( $fieldname, $item['OfferSummary'] )){
			return $item['OfferSummary'][ $fieldname ];
		} elseif( isset( $item['Offers'] ) && array_key_exists( $fieldname, $item['Offers'] )){
			return $item['Offers'][ $fieldname ];
		} elseif( isset( $item['CustomerReviews'] ) && array_key_exists( $fieldname, $item['CustomerReviews'] )){
			return $item['CustomerReviews'][ $fieldname ];
		} elseif( isset( $item['ItemLinks'] ) && in_array( $fieldname, $itemLinkFields )){
			foreach( $item['ItemLinks'] as $link ){
				if( str_replace(' ', '', $link['Description'] ) == $fieldname ){
					return $link['URL'];
				}
			}
		}
		return null;
	}

	function is_element_style_needed(){
		global $post;

		if( is_admin() ){
			$screen = get_current_screen();

			if( 'widgets' == $screen->id || 'post' == $screen->base || 'customize' == $screen->base ){
				if( 'post' == $screen->base && $screen->post_type != $this->post_type_name ){
					return false;
				}
				return true;
			}
			return false;
		}
		return true;
	}

	function is_element_script_needed(){
		global $post;

		if( is_admin() ){
			$screen = get_current_screen();

			if( 'widgets' == $screen->id || 'post' == $screen->base || 'customize' == $screen->base ){
				if( 'post' == $screen->base && $screen->post_type != $this->post_type_name ){
					return false;
				}
				return true;
			}
		} else {
			if( is_singular() && $post->post_type == $this->post_type_name ){
				if( is_user_logged_in() && current_user_can('edit_post', $post->ID)){
					return true;
				}
			}
		}
		return false;
	}
}