<?php
/**
* Class for data access object. Has Meta object as its parent. Does not know about Controller.
*
* DAO should not be accessing $_POST or $_GET: all params should be readily passed as arguments to functions.
*
* @link       http://affiget.com
* @since      1.0.0
*
* @package    AffiGet
* @subpackage AffiGet/review
*/

/**
 * Fired during plugin activation.
*
* This class defines all code necessary to run during the plugin's activation.
*
* @since      1.0.0
* @package    AffiGet
* @subpackage AffiGet/review
* @author     Saru Tole <sarutole@affiget.com>
*/
class AffiGet_Review_Storage {

	/**
	 *
	 * @var AffiGet_Product_Meta $meta
	 */
	protected $meta;

	public function __construct( AffiGet_Review_Meta $meta ){

		$this->meta = $meta;
	}

	public function load_post_fields( array &$review_data, $post ){

		//afg_log(__METHOD__, $post);
		if( is_numeric( $post ) ){
			$review_data['post_fields'] = get_post( $post, ARRAY_A );
			if( ! $review_data['post_fields'] ){
				echo '<pre>';
				throw new AffiGet_Exception('Failed to load post fields:' . compact('post'));
			}
		} elseif( is_object( $post ) && is_a( $post, 'WP_Post' ) ){
			$review_data['post_fields'] = $post->to_array();
		} elseif ( ! is_array( $post ) ) {
			throw new AffiGet_Exception('Unexpected type of $post parameter: ' . compact('post'));
		}
	}

	//
	// Should be called AFTER $review has its ID assigned.
	//
	public function load_meta_fields( array &$review_data, $post_id = null ) {

		if( is_null( $post_id )){
			$post_id = $review_data['post_fields']['ID'];
		}

		if( ! $post_id ){
			throw new AffiGet_Exception('Failed to load meta fields:' . compact('review_data'));
		}

		if( ! isset( $review_data['meta_fields'] )){
			$review_data['meta_fields'] = array();
		}

		$fields  = get_post_custom( $post_id );
		if( $fields ){
			foreach( $this->meta->known_meta_fields as $field_name => $config ){

				$key = AFG_META_PREFIX . $field_name; //filter?

				if( isset( $fields[ $key ] ) ){
					$value = $fields[ $key ][0];
					if( is_serialized( $value ) ){
						$value = unserialize( $value );
					} else {
						//$value = $value;
					}
					if( has_filter("afg_review_storage__load_review_{$field_name}") ){
						$value = apply_filters("afg_review_storage__load_review_{$field_name}", $value, 'raw', $post_id );
					}
					$review_data['meta_fields'][ $field_name ] = $value;
				}
			}
		}
	}

	//
	// get taxonomies, terms, categories
	// Should be called AFTER $review has its ID assigned.
	//
	public function load_taxonomic_fields( array &$review_data, $post_id = null, $post_type = null ) {

		if( is_null( $post_id )){
			$post_id = $review_data['post_fields']['ID'];
		}
		if( is_null( $post_type )){
			$post_type = $review_data['post_fields']['post_type'];
		}
		if( ! $post_id || ! $post_type ){
			throw new AffiGet_Exception('Could not load taxonomic fields:' . compact('review_data', 'post_id', 'post_type'));
		}

		// get post type taxonomies
		$taxonomies = get_object_taxonomies ( $post_type );

		if( ! isset( $review_data['taxonomic_fields'] )){
			$review_data['taxonomic_fields'] = array();
		}

		foreach( $taxonomies as $taxonomy_name ) {

			// get the terms related to post
			$terms = get_the_terms ( $post_id, $taxonomy_name );

			//echo '<pre>'.$taxonomy_name.':'.print_r($terms, true).'</pre>';

			if( ! empty( $terms )) {
				$tax = array();
				foreach( $terms as $term ) {
					$tax[ $term->term_id ] = html_entity_decode( $term->name ); //name comes sans slashes, entity-encoded
				}
				$review_data['taxonomic_fields'][ $taxonomy_name ] = $tax;
			}
		}
	}

	public function store_review_data( array &$review_data ){

		if( empty( $review_data )){
			throw new AffiGet_Exception('Parameter review_data should not be empty');
		}
		if( ! isset( $review_data['post_fields'] )){
			throw new AffiGet_Exception('Parameter $review_data[post_fields] should not be empty!');
		}

		$has_meta = isset( $review_data['meta_fields'] )      && !empty( $review_data['meta_fields'] );
		$has_tax  = isset( $review_data['taxonomic_fields'] ) && !empty( $review_data['taxonomic_fields'] );

		$result = $this->commit_post_fields( $review_data['post_fields'] );
		if( ! is_wp_error( $result )){
			$review_data['post_fields'] = $result;
			$post_id = $review_data['post_fields']['ID'];
			if( $has_meta ){
				//afg_log( $review_data );
				$result = $this->commit_meta_fields( $post_id, $review_data['meta_fields'] );
				if( ! is_wp_error( $result )){
					$review_data['meta_fields'] = $result;
				}
			}
			if( $has_tax ){
				$result = $this->commit_taxonomic_fields( $post_id, $review_data['taxonomic_fields'] );
				if( ! is_wp_error( $result )){
					$review_data['taxonomic_fields'] = $result;
				}
			}
			if( is_wp_error( $result )){
				return $result;
			}
			return true;
		} else {
			return $result;
		}
	}

	function commit_post_fields( array $post_data ){

		if( isset( $post_data['post_content'] )){
			$post_data['post_content'] = addslashes( $post_data['post_content'] );
		}
		if( isset( $post_data['post_excerpt'] )){
			$post_data['post_excerpt'] = addslashes( $post_data['post_excerpt'] );
		}

		$post_data = apply_filters('afg_review_storage__commit_post_fields', $post_data );

		//afg_log(__METHOD__, compact('post_data') );

		$post_id = isset( $post_data['ID'] ) ? $post_data['ID'] : null;

		//avoid regenerating post_content on every meta update

		if( $post_id ){

			$result = wp_update_post( $post_data, $wp_error = true );
			if( ! is_wp_error( $result )){
				//we need new post_id and other fully-escaped values
				$inst = WP_Post::get_instance( $post_id );
				return $inst->to_array();
			}
			return $result;

		} else {
			//do not generate post content on first insert

			$was_filtering = remove_filter('wp_insert_post_data', array( $this->meta->renderer, 'build_post_content' ));

			$result = wp_insert_post( $post_data, $wp_error = true );

			if( $was_filtering ){
				add_filter( 'wp_insert_post_data', array( $this->meta->renderer, 'build_post_content'), 10, 2 );
			}
			if( ! is_wp_error( $result )){
				//we need new post_id and other fully-escaped values
				$inst = WP_Post::get_instance( $result ); //result will contain post_id value
				//afg_log(array('Inserted post'=>$inst->to_array()));
				return $inst->to_array();
				//echo $wp_error->get_error_message();
			} else {
				afg_log(array('Could not insert new post' => $result));
			}
			return $result;
		}
	}

	function commit_meta_fields( $post_id, array $meta_data ){

		if( ! $post_id ){
			throw new AffiGet_Exception('Parameter post_id cannot be empty!');
		}

		//avoid regenerating post_content on every meta update
		$was_filtering = remove_filter('updated_postmeta', array($this->meta->renderer, 'trigger_build_post_content'));

		//it is not safe to pass entire $review_data object, as it's in a transitionary state
		//so we pass only meta_data
		$meta_data = apply_filters('afg_review_storage__commit_meta_fields', $meta_data, 10 );

		foreach( $meta_data as $field_name => $value ){

			if( $this->meta->is_meta_field( $field_name ) ){
				if( is_string( $value ) ){
					if( is_serialized( $value ) ){
						$value = unserialize( $value );
					} else {
						$value = addslashes( $value );
					}
				}
				$meta_key = AFG_META_PREFIX . $field_name; //filter?

				//echo '<pre>'.$field_name.':'.print_r($value, true).'</pre>'."\n";

				update_post_meta( $post_id , $meta_key, $value );
			} else {
				//echo '<pre>';
				//print_r($this->meta->known_meta_fields);
				throw new AffiGet_Exception('Field ['. $field_name .'] is not considered to be a metafield.');
			}
		}

		if( $was_filtering ){
			add_filter( 'updated_postmeta', $this->meta->renderer, 'trigger_build_post_content', 10, 5 );
			//trigger content update directly:
			wp_update_post( array( 'ID' => $post_id, 'post_type' => $this->meta->post_type_name ));
		}
		return $meta_data;
	}

	function commit_taxonomic_fields( $post_id, array $taxonomic_data ){

		if( ! $post_id ){
			throw new AffiGet_Exception('Parameter post_id cannot be empty!');
		}

		//it is not safe to pass entire $review object, as it's in a transition state
		$taxonomic_data = apply_filters('afg_review_storage__commit_taxonomic_fields', $taxonomic_data );

		foreach( $taxonomic_data as $tax => &$terms ){

			if( ! empty( $terms ) ){
				foreach( $terms as $id => &$term_name ){
					//note, $id here is not necessarily term_id, maybe just numeric array index
					$term_name = htmlspecialchars( addslashes_gpc( $term_name ));
				}
				$terms = $this->_maybe_insert_missing_terms( array_values( $terms ), $tax );
			}
			//now $terms are properly indexed by term_id

			$term_ids = null;
			if( ! empty( $terms )){
				$term_ids = array_keys( $terms );
			} else {
				//echo 'Taxonomy is empty:'.$tax;
			}
			$result = wp_set_object_terms( $post_id, $term_ids, $tax, $append = false );
		}
		return $taxonomic_data;
	}

	function apply_differences( array $older, array $newer ){

		//echo '<pre>'.print_r(array('request' => $_REQUEST), true).'</pre>';
		//echo '<pre>'.print_r(array('older' => $older, 'newer' => $newer ), true).'</pre>';

		$preserve_fields = array( 'ID', 'post_modified', 'post_modified_gmt', 'guid', 'post_type' );
		$preserve_fields = apply_filters('afg_review_storage__apply_differences__preserve_fields', $preserve_fields);

		$pf = &$older['post_fields'];
		$post_id = $pf['ID'];
		$changed = false;

		foreach( $newer['post_fields'] as $field => $value ){
			if( in_array( $field, $preserve_fields ) ){
				continue;
			}

			if( is_array( $value ) || is_object( $value ) || $pf[ $field ] != $value ){
				$pf[ $field ] = $value;
				$changed = true;
			}
		}

		//commit changed post
		if( $changed ){
			$result = $this->commit_post_fields( $pf );
			if( is_wp_error(  $result )){
				return $result;
			}
			$older['post_fields'] = $result;
		}

		//calculate differences in meta fields
		$mod = array();
		$mf = &$older['meta_fields'];
		foreach( $newer['meta_fields'] as $field => $value ){
			if( in_array( $field, $preserve_fields ) ){
				continue;
			}
			if( ! isset( $mf[ $field ] ) || serialize( $mf[ $field ] ) !== serialize( $value ) ){
				$mf[ $field ] = $value;
				$mod[ $field ] = $value;
			}
		}

		if( ! empty( $mod )){
			//update modified meta
			$this->commit_meta_fields( $post_id, $mod );
			if( is_wp_error(  $result )){
				return $result;
			}
			$older['meta_fields'] = $result;
		}

		//remove deleted meta
		//$del = array_diff( $older, $newer );//old fields that are not in the new array
		//foreach( $del as $field ){
		//	if( in_array( $field, $preserve_fields ) ){
		//		continue;
		//	}
		//	delete_post_meta( $post_id, $field );
		//}

		//calculate differences in taxonomic fields
		$tf = &$older['taxonomic_fields'];
		$changed = false;
		foreach( $newer['taxonomic_fields'] as $field => $value ){
			if( in_array( $field, $preserve_fields ) ){
				continue;
			}

			if( isset( $tf[ $field ]) ){

				$current = $tf[ $field ];

				if( !$current && $value ){
					$tf[ $field ] = $value;
					$changed = true;
				} else {
					if( ! isset( $tf[ $field ] ) || serialize( $tf[ $field ] ) != serialize( $value ) ){
						$tf[ $field ] = $value;
						$changed = true;
					}
				}
			} else {
				$tf[ $field ] = $value;
				$changed = true;
			}
		}

		//commit taxonomy terms
		if( $changed ){
			$result = $this->commit_taxonomic_fields( $post_id, $tf );
			if( is_wp_error(  $result )){
				return $result;
			}
			$older['taxonomic_fields'] = $result;
		}
		//echo '<pre>'.print_r(array('merged' => $older), true).'</pre>';
	}


	/**
	 *
	 * @param array $params Must contain post_id, and optionally hard_delete.
	 * @return mixed status record
	 */
	public function delete_review( $post_id, $hard_delete = false, $delete_media = false ){

		if( ! $post_id ) return false;

		if( $delete_media ){
			$images = get_children( array (
					'post_parent'    => $post_id,
					'post_type'      => 'attachment',
					'posts_per_page' => -1,
			));

			if( ! empty( $images )){
				foreach ( $images as $attachment_id => $attachment ) {
					wp_delete_attachment( $attachment_id, $hard_delete );
				}
			}
		}

		//Deletes comments, post meta fields, and terms associated with the post.
		return wp_delete_post( $post_id, $hard_delete );
	}

	//
	//adapted from from get_terms_by()
	//
	protected function _maybe_insert_missing_terms( $names_or_ids, $taxonomy ) {

		global $wpdb;

		if( ! taxonomy_exists( $taxonomy ) )
			return false;

		if( empty( $names_or_ids ))
			return false;

		//assuming names & ids are already sanitized
		$list  = "'" . join( "','", $names_or_ids ) . "'";

		//afg_log( __METHOD__ .' ('. $taxonomy.') Requested names:', $names_or_ids );

		$terms = $wpdb->get_results( $wpdb->prepare( "SELECT t.term_id, t.name FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy = %s AND (t.name IN ( $list ) OR t.term_id IN ( $list ))", $taxonomy ), OBJECT_K );

		//echo '<br/>Query results:<pre>' . print_r( $terms, true ).'</pre>';
		//afg_log( __METHOD__ . ' Retrieved names:', $terms );

		$resolved = array();

		$new_terms = array();

		foreach( $names_or_ids as $n ){

			if( is_numeric( $n )){

				if( array_key_exists( $n, $terms )){
					$resolved[ $n ] = $terms[ $n ]->name;
					unset( $terms[ $n ]); //drop it from results to optimize further searches, if any
					//continue;
				} else {
					throw new AffiGet_Exception( 'Unknown term id: '.$n );
				}

			} else {
				$found = false;
				foreach( $terms as $term_id => $term ){
					if( addslashes_gpc( $n ) == $term->name ){
						$found = $term_id;
					}
				}
				if( false !== $found ){
					$resolved[ $found ] = $terms[ $found ]->name;
					unset( $terms[ $found ]); //drop it from results to optimize further searches, if any
				} else {
					$new_terms[] = $n;
				}
			}
		}

		//echo '<br/>New terms:' . print_r( $new_terms, true );
		//afg_log( __METHOD__ . ' New terms:', $terms );

		if( ! empty( $new_terms )){

			foreach( $new_terms as $term ){
				if( $term != '' ){
					//afg_log( __METHOD__ . ' Inserting term:', $term );
					$inserted = wp_insert_term( $term, $taxonomy );
					if( is_wp_error( $inserted ) ){
						//echo '<pre>';
						//print_r( $inserted );
						//afg_log( __METHOD__ . " Term '{$term}' could not be inserted into taxonomy '{$taxonomy}'.", $inserted );
						//throw new AffiGet_Exception("Term '{$term}' could not be inserted into taxonomy '{$taxonomy}'.");
					} else {
						$resolved[ $inserted['term_id'] ] = $term;
						//afg_log( __METHOD__ . " Term inserted: ", array( $inserted['term_id'], $resolved[ $inserted['term_id'] ] ));
					}
				}
			}
		}

		//afg_log(__METHOD__ . ' Resolved terms:', $resolved );

		return $resolved;
	}
}