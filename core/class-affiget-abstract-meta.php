<?php
/**
 * Base class to be extended by all classes that define custom post types with fields and elements.
 *
 * @since      1.0.0
 * @package    AffiGet
 * @subpackage AffiGet/core
 * @author     Saru Tole <sarutole@affiget.com>
 */
abstract class AffiGet_Abstract_Meta {

	/**
	 *
	 * @var AffiGet_Mini
	 */
	protected $_plugin;

	/**
	 *
	 * @var string
	 */
	protected $_post_type_name;

	/**
	 *
	 * @var string
	 */
	protected $_singular_label;

	/**
	 *
	 * @var string
	 */
	protected $_plural_label;

	/**
	 *
	 * @var string
	 */
	protected $_description;

	// The following 3 collections are used to automate storage mechanism:
	protected $_known_post_fields      = array(); //(fields to be stored as post fields)
	protected $_known_meta_fields      = array(); //(fields to be stored as post meta fields (prefixed with AFG_META_PREFIX)
	protected $_known_taxonomic_fields = array(); //(fields to be stored as taxonomy terms associated with a post)
	//	public $known_relationship_fields = array();

	/**
	 *
	 * @var AffiGet_Abstract_Element array
	 */
	protected $_elements = array();

	protected function __construct( AffiGet_Mini $parent_plugin, array $params ){

		if( ! isset( $params['post_type_name'] ) || ! is_string( $params['post_type_name'] )){
			throw new Exception( 'Parameter post_type_name must be a non-empty string!' );
		}

		$this->_plugin       = $parent_plugin;

		$label = ucfirst( str_replace('_', ' ', $params['post_type_name'] ));
		$defaults = array(
				'singular_label' => $label,
				'plural_label'   => $label . 's',
				'description'    => __( 'Description', 'afg'),
		);
		$params = wp_parse_args( $params, $defaults );

		$this->_post_type_name = $params['post_type_name'];
		$this->_singular_label = $params['singular_label'];
		$this->_plural_label   = $params['plural_label'];
		$this->_description    = $params['description'];

	}

	/**
	 * Register Custom Post Type.
	 */
	function register_custom_post_type() {

		$singular = $this->_singular_label;
		$plural   = $this->_plural_label;

		$labels = array(
				'name'                => $plural,
				'singular_name'       => $singular,
				'menu_name'           => $plural,
				'parent_item_colon'   => sprintf( _x( 'Parent %s:', 'Parent item', 'afg' ), $singular ),
				'all_items'           => sprintf( _x( 'All %s', 'All Items', 'afg'), $plural ),
				'view_item'           => sprintf( _x( 'View %s', 'View Item', 'afg' ), $singular ),
				'add_new_item'        => sprintf( _x( 'Add New %s:', 'Add New Item', 'afg' ), $singular ),
				'add_new'             => __( 'Add New', 'afg' ),
				'edit_item'           => sprintf( _x( 'Edit %s', 'Edit Item', 'afg' ), $singular ),
				'update_item'         => sprintf( _x( 'Update %s:', 'Update Item', 'afg' ), $singular ),
				'search_items'        => sprintf( _x( 'Search %s', 'Search Items', 'afg'), $plural ),
				'not_found'           => __( 'Not found', 'afg' ),
				'not_found_in_trash'  => __( 'Not found in Trash', 'afg' ),
		);
		$args = array(
				'label'               => $this->_post_type_name,
				'description'         => $this->_description,
				'labels'              => $labels,
				'supports'            => array( 'title', 'thumbnail', 'custom-fields', 'comments' ),
				'taxonomies'          => array( 'category', 'post_tag' ),
				'hierarchical'        => false,
				'public'              => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => true,
				'menu_position'       => 5,
				'menu_icon'           => 'dashicons-star-half',
				'can_export'          => true,
				'has_archive'         => false,
				'exclude_from_search' => false,
				'publicly_queryable'  => true,
				'capability_type'     => 'post',
		);

		$args = apply_filters("afg_{$this->_post_type_name}_meta__register_custom_post_type", $args );

		register_post_type( $this->_post_type_name, $args );
	}

	/**
	 * Magic method to control access to protected properties.
	 *
	 * @param string $property
	 * @throws AffiGet_Exception
	 * @return AffiGet_Mini|array|multitype:
	 */
	public function __get( $property ){

		switch( $property ){
			case 'plugin':
				return $this->_plugin;
			case 'post_type_name':
				return $this->_post_type_name;
			case 'known_post_fields':
				return $this->_known_post_fields;
			case 'known_meta_fields':
				return $this->_known_meta_fields;
			case 'known_taxonomic_fields':
				return $this->_known_taxonomic_fields;
			case 'elements':
				return $this->_elements;
		}
		throw new AffiGet_Exception('Unknown property requested: ' . $property );
	}

	/**
	 * Check if a field is considered a "post field".
	 *
	 * @param string $fieldname
	 * @return boolean
	 */
	public function is_post_field( $fieldname ){
		return( in_array( $fieldname, $this->_known_post_fields ) || array_key_exists( $fieldname, $this->_known_post_fields ));
	}

	/**
	 * Check if a field is considered a "meta field".
	 *
	 * @param string $fieldname
	 * @return boolean
	 */
	public function is_meta_field( $fieldname ){
		return( array_key_exists( $fieldname, $this->_known_meta_fields ) || in_array( $fieldname, $this->_known_meta_fields ));
	}

	/**
	 * Check if a field is considered a "taxonomic field".
	 *
	 * @param string $fieldname
	 * @return boolean
	 */
	public function is_taxonomic_field( $fieldname ){
		return( array_key_exists( $fieldname, $this->_known_taxonomic_fields ));
	}

	/**
 	 * Check if a field is known.
 	 *
	 * @param string $fieldname
	 * @return boolean
	 */
	public function is_unknown_field( $fieldname ){
		return ! ( $this->is_post_field( $fieldname ) || $this->is_meta_field( $fieldname ) || $this->is_taxonomic_field( $fieldname ));
	}

	/**
	 * Get element object by its name.
	 *
	 * @param string $element_name
	 * @return AffiGet_Abstract_Element
	 */
	public function get_element( $element_name ){

		if( array_key_exists( $element_name, $this->_elements )){
			return $this->_elements[ $element_name ];
		}
		throw new AffiGet_Exception('Unknown element requested: '. $element_name .'.');
	}

	/**
	 * Get elements filtered by property value.
	 *
	 * @return AffiGet_Abstract_Element array
	 */
	public function get_elements_by_status( $status ){

		$result = array();

		foreach( $this->_elements as $key => $obj ) {
			if( $obj->is_status( $status ) ){
				$result[ $key ] = $obj;
			}
		}
		return apply_filters( "afg_{$this->_post_type_name}_meta__get_elements_by_status", $result, $status );
	}

	/**
	 *
	 * @return AffiGet_Abstract_Element array
	 */
	public function get_elements(){

		return apply_filters( "afg_{$this->_post_type_name}_meta__get_elements", $this->_elements );
	}

	/**
	 * Get an array redisplay of the post (and all of its extended fields).
	 *
	 * Derivative classes should override this.
	 *
	 * @param int $post_id
	 * @throws AffiGet_Exception
	 * @return multitype:Ambigous <WP_Post, NULL, multitype:, unknown>
	 */
	public function load_post( $post_id ){

		if( ! is_numeric( $post_id )){
			throw new AffiGet_Exception('Invalid post_id prameter!');
		}

		$data = get_post( $post_id, ARRAY_A );

		return array( 'post_fields' => $data );
	}

	/**
	 * Pick value from extended post fields array.
	 *
	 * @param array $item_data
	 * @param string $fieldname
	 * @throws AffiGet_Exception raised unknown field is requested
	 * @return Multitype
	 */
	public function pick_value( array $item_data, $fieldname ){

		if( $this->is_post_field( $fieldname )){
			return isset( $item_data['post_fields'][ $fieldname ] ) ? $item_data['post_fields'][ $fieldname ] : null;
		} elseif( $this->is_meta_field( $fieldname )){
			return isset( $item_data['meta_fields'][ $fieldname ] ) ? $item_data['meta_fields'][ $fieldname ] : null;
		} elseif( $this->is_taxonomic_field( $fieldname )){
			return isset( $item_data['taxonomic_fields'][ $fieldname ] ) ? $item_data['taxonomic_fields'][ $fieldname ] : null;
		}
		$id = $item_data['post_fields']['ID'];
		throw new AffiGet_Exception( "Unknown field requested for post ({$id}): {$fieldname}" );
	}
} /* AffiGet_Abstract_Meta */

/* EOF */