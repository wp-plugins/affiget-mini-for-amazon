<?php

/**
* Controller class. Has Product Meta object as its parent.
* Delegates database manipulation functions to Product DAO (accessed via parent meta).
*
* @link       http://affiget.com
* @since      1.0.0
*
* @package    AffiGet
* @subpackage AffiGet/review
*/

/**
*
* This class defines all code necessary to run during the plugin's activation.
*
* @since      1.0.0
* @package    AffiGet
* @subpackage AffiGet/review
* @author     Saru Tole <sarutole@affiget.com>
*/
class AffiGet_Review_Controller {

	const EVENT_PRODUCT_UPDATED = 'afg_product_updated';
	const EVENT_IMAGES_UPDATED  = 'afg_images_updated';

	/**
	 *
	 * @var AffiGet_Review_Meta
	 */
	protected $meta;

	/**
	 *
	 * @param AffiGet_Review_Imager $imager
	 */
	protected $imager;

	public function __construct( AffiGet_Review_Meta $meta ){

		$this->meta = $meta;

		//listen on when a dialog config is requested
		//add_action('afg_dialog__get_current_config__start', array($this, 'sideload_review'), 10, 2);

		if( ! class_exists('AffiGet_Review_Imager' )){
			require_once 'class-affiget-review-imager.php';
		}
	}

	public function sideload_review( $user_key, $referrer_address ){

		if( ! $referrer_address ){
			afg_log( 'Cannot build review for an empty referrer', $_REQUEST );
			return;
		}

		$parts = '';
		/*ASIN 10-character alphanumeric unique identifier assigned by Amazon.com and its partners for product identification within the Amazon.com organization */
		$product_code = preg_match('/\/([A-Za-z_0-9]{10})\//', $referrer_address, $parts );
		if( ! $product_code ){
			afg_log( 'Cannot resolve ASIN for referrer', $referrer_address );
			return;
		}
		$product_code = $parts[1];

		$secret       = utf8_uri_encode('affiget-'.preg_replace("/[^a-zA-Z]+/", '', NONCE_SALT));
		$nonce        = wp_create_nonce('afg-prepare-review');

		$params = array(
				'action'           => 'afg_prepare_review',
				'afg_product_code' => $product_code,
				'afg_secret'       => $secret,
				'afg_user_key'     => $user_key,
				'_wpnonce'         => $nonce,
		);

		$url = get_admin_url(null, 'admin-ajax.php?') . http_build_query( $params );

		$args = array(
				'timeout'      => 1,
				'blocking'     => false,
		);

		//afg_log( __METHOD__, array('url'=>$url, 'args' => $args) );

		$result = wp_remote_get( $url, $args );
		//effectively, calls $this->ajax_prepare_review()
	}

	public function prepare_review_silent( $params, $filter = true ){

		$post_id      = isset( $params['post_id'] ) ? $params['post_id'] : null;
		$product_code = isset( $params['product_code'] ) ? $params['product_code'] : null;

		if( ! $post_id && ! $product_code ){
			//throw new AffiGet_Exception('Post ID or product code must be specified!');
			$result = array(
					'error'   => 'wrong-params',
					'message' => __('review could not be identified', 'afg')
			);
			return $result;
		}

		$review_data = null;
		$reviews     = null;
		$result      = null;

		if( $post_id ){
			$review_data = $this->load_review_data_by_post_id( $post_id );
			if( is_null( $review_data )){
				$result = array(
						'error'   => 'wrong-post',
						'message' => __('review could not be found', 'afg')
				);
				return $result;
			}
		} else {
			if( !$product_code ){
				$result = array(
						'error'   => 'wrong-product',
						'message' => esc_attr__('invalid product code', 'afg')
				);
				return $result;
			}

			$reviews = $this->get_review_posts_by_product_code( $product_code );

			if( empty( $reviews ) ){

				$review_data = $this->init_review_post( $product_code );
				if( is_wp_error( $review_data )){
					$result = array(
							'error'   => 'error-preparing-review',
							'message' => $review_data->get_error_message()
					);
					return $result;
				}
				//flatten review data to be sent back to the dialog
				$review_data = array_merge( $review_data['post_fields'], $review_data['meta_fields'], $review_data['taxonomic_fields'] );
				$review_data['is_new'] = true;

			} elseif( count( $reviews ) == 1 ){

				//if only one result, load full data for extended review fields
				$post_id = $reviews[0]['ID'];
				$review_data = $this->load_review_data_by_post_id( $post_id );
				if( is_null( $review_data )){
					$result = array(
							'error'   => 'malformed-review',
							'message' => __('review data could not be loaded', 'afg')
					);
					return $result;
				}
				$review_data = array_merge( $review_data['post_fields'], $review_data['meta_fields'], $review_data['taxonomic_fields'] );
				$review_data['is_new'] = false;
			} //else: return ALL reviews that have specified product_code
		}

		$prepared = (count( $reviews ) > 1) ? $reviews[0]: $review_data;
		if( $filter ){
			$prefiltered = array(
					'ID'            => $prepared['ID'],
					'post_title'    => $prepared['post_title'],
					'post_status'   => $prepared['post_status'],
					'post_date_gmt' => $prepared['post_date_gmt'],
					'auto_date_gmt' => $prepared['auto_date_gmt'],
					'isNew'         => $prepared['is_new'],
					'nonceDelete'   => wp_create_nonce('afg-delete-review-'.$prepared['ID']),
					'nonceModify'   => wp_create_nonce('afg-update-'.$prepared['ID'])
			);
			return apply_filters( 'afg_review_controller__prepare_review_minimal', $prefiltered, $prepared );
		} else {
			return $prepared;
		}
	}

	public function ajax_prepare_review(){

		$timer = microtime(true);

		$nonce = isset( $_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : null;

		$extended = isset( $_REQUEST['extended'] ) ? $_REQUEST['extended'] == 'true' : false;

		$user = wp_get_current_user();
		$uid = (int) $user->ID;

		if( !$nonce || !wp_verify_nonce( $nonce, 'afg-prepare-review' )){

			$this->send_jsonp_error(array(
					'error'   => 'wrong-nonce',
					'message' => esc_attr__('nonce could not be verified', 'afg')
			));
			die();
		}

		$user_key = $_REQUEST['afg_user_key'];
		if( $user_key !== get_option( AffiGet_Admin::OPTION_USER_KEY )){
			$this->send_jsonp_error(array(
					'error'   => 'wrong-key',
					'message' => esc_attr__('unknown user key', 'afg')
			));
			die();
		}

		$post_id      = isset( $_REQUEST['afg_post_id']) && 0 < absint( $_REQUEST['afg_post_id']) ? absint( $_REQUEST['afg_post_id'] ) : null;
		$product_code = isset( $_REQUEST['afg_product_code']) ? $_REQUEST['afg_product_code'] : null;

		$review = $this->prepare_review_silent(compact('post_id', 'product_code'), !$extended );

		if( !array_key_exists('error', $review) ){
			$response = array(
					'action'  => 'afg_prepare_review',
					'nonce'   => wp_create_nonce( 'afg-get-product-data' ),
					'reviews' => array( $review ),
			);
			$this->send_jsonp_success( $response );
			die();
		} else {
			$this->send_jsonp_error( $review );
			die();
		}
	}

	public function ajax_get_product_data(){

/*		$nonce = isset( $_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : null;
		if( !$nonce || !wp_verify_nonce( $nonce, 'afg-get-product-data' )){

			$this->send_jsonp_error(array(
					'error'   => 'wrong-nonce',
					'message' => esc_attr__('nonce could not be verified', 'afg')
			));
			die();
		}
*/

		//$user = wp_get_current_user();
		//$uid = (int) $user->ID;

		$user_key = isset( $_REQUEST['afg_user_key']) ? $_REQUEST['afg_user_key'] : null;
		if( $user_key && $user_key !== get_option( AffiGet_Admin::OPTION_USER_KEY )){
			$this->send_jsonp_error(array(
					'error'   => 'wrong-key',
					'message' => esc_attr__('unknown user key', 'afg')
			));
			die();
		}

		$post_id = isset( $_REQUEST['afg_post_id']) && 0 < absint( $_REQUEST['afg_post_id']) ? absint( $_REQUEST['afg_post_id'] ) : null;
		if( ! $post_id ){
			$this->send_jsonp_error(array(
					'error'   => 'missing-parameter',
					'message' => esc_attr__('post id not specified', 'afg')
			));
			die();
		}

		$product_code = isset( $_REQUEST['afg_product_code'] ) ? $_REQUEST['afg_product_code'] : null;
		if( ! $product_code ){
			$this->send_jsonp_error(array(
					'error'   => 'missing-parameter',
					'message' => esc_attr__('product code not specified', 'afg')
			));
			die();
		}

		//product data is added here asynchronously via $this->_sideload_amazon_product_data()
		$product_data = get_post_meta( $post_id, AFG_META_PREFIX . 'product_data', true );

		if( $product_data ){

			add_filter('afg_review_controller__get_product_data', array(&$this, '_filter_product_data'));
			$filtered_product = apply_filters('afg_review_controller__get_product_data', $product_data );

			$this->send_jsonp_success( $filtered_product );

			//$this->send_jsonp_raw( $json_encoded );
			die();

		} else {
			$this->send_jsonp_error(array(
					'error'   => 'no-product-data',
					'message' => esc_attr__('product data is not attached', 'afg')
			));
			die();
		}
	}

	public function _filter_product_data( $product_data ){

		$result = array();
		$result['Attributes'] = $this->meta->pick_product_data_value( $product_data, 'ItemAttributes' );
		$result['Images'] = $this->meta->pick_product_data_value( $product_data, 'Images' );

		$result['Attributes']['Title'] = sanitize_post_field('post_title', $result['Attributes']['Title'], '', 'db' );
		$result['Attributes']['Slug']  = sanitize_title_with_dashes( $result['Attributes']['Title'], '', 'save' );

		return $result;
	}

	public function ajax_update_review(){

		//AffiGet_Mini::add_cors_header();

		$nonce = isset( $_REQUEST['_wpnonce']) ? $_REQUEST['_wpnonce'] : null;

/*		if( ! wp_verify_nonce( $nonce, 'afg-prepare-review' )){
			Afg_Response::send_error( 'invalid-nonce', 'problem with nonce' );
			return;
		}
*/
		$post_id      = (isset( $_REQUEST['afg_post_id']) && 0 < absint( $_REQUEST['afg_post_id'] )) ? absint( $_REQUEST['afg_post_id'] ) : null;
		$product_code = isset( $_REQUEST['afg_product_code']) ? $_REQUEST['afg_product_code'] : null;

		if( ! $post_id || ! $product_code ){
			$this->send_jsonp_error(array(
					'error'   => 'missing-parameter',
					'message' => 'post_id or product_code not specified'
			));
			die();
		}

		if( is_null( get_post( $post_id ))){
			$review_data = $this->init_review_post( $product_code );
			if( is_wp_error( $review_data )){
				$this->send_jsonp_error(array(
						'error'   => 'error-preparing-review',
						'message' => $review_data->get_error_message()
				));
				die();
			}
			$post_id = $review_data['post_fields']['ID'];
		} else {

			$new_review_data = $this->prepare_review_data_update( $post_id, $product_code );
			/*if( is_wp_error( $new_review_data )){
				$this->send_jsonp_error(array(
						'error'   => 'error-preparing-review',
						'message' => $review_data->get_error_message()
				));
				die();
			}*/

			$review_data = $this->load_review_data_by_post_id( $post_id );
			if( ! $review_data ){
				$this->send_jsonp_error(array(
				 		'error'   => 'error-loading-review',
				 		'message' => $review_data->get_error_message()
				));
				die();
			}

			//performs database changes and modifies data in the first array
			$result = $this->meta->storage->apply_differences( $review_data, $new_review_data );
			if( is_wp_error( $result )){
				$this->send_jsonp_error(array(
						'error'   => 'error-resolving-differences',
						'message' => esc_attr__('review data could not be updated', 'afg')
				));
				die();
			}
		}

		$data = array(
				'post_id'      => $post_id,
				'product_code' => $product_code,
				'review'       => $review_data,
		);

		wp_send_json_success( $data );
		die();
	}

	public function ajax_delete_review(){

		$user_key = isset( $_REQUEST['afg_user_key']) ? $_REQUEST['afg_user_key'] : null;
		if( $user_key && $user_key !== get_option( AffiGet_Admin::OPTION_USER_KEY )){
			$this->send_jsonp_error(array(
					'error'   => 'wrong-key',
					'message' => esc_attr__('unknown user key', 'afg'),
					'ref'     => $user_key
			));
			die();
		}

		$post_id = isset( $_REQUEST['afg_post_id'] ) && 0 < absint( $_REQUEST['afg_post_id']) ? absint( $_REQUEST['afg_post_id'] ) : null;
		if( ! $post_id ){
			$this->send_jsonp_error(array(
					'error'   => 'missing-parameter',
					'message' => esc_attr__('post id not specified', 'afg'),
					'ref'     => compact('post_id', 'user_key')
			));
			die();
		}

		$nonce = isset( $_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : null;
		if( ! $nonce || ! wp_verify_nonce( $nonce, 'afg-delete-review-' . $post_id )){

			$this->send_jsonp_error(array(
					'error'   => 'wrong-nonce',
					'message' => esc_attr__('nonce could not be verified', 'afg'),
					'ref'     => compact('post_id', 'user_key', 'nonce')
			));
			die();
		}

		if( false === $this->meta->storage->delete_review( $post_id )){
			$this->send_jsonp_error(array(
					'error'   => 'operation-failed',
					'message' => esc_attr__('post could not be deleted', 'afg'),
					'ref'     => compact('post_id', 'user_key', 'nonce')
			));
		} else {
			$this->send_jsonp_success(array(
					'code'    => 'post-deleted',
					'post_id' => $post_id
			));
		}
		die();
	}

	public function ajax_retrieve_review_field(){

		$post_id = isset( $_REQUEST['afg_post_id'] ) && 0 < absint( $_REQUEST['afg_post_id'] ) ? absint( $_REQUEST['afg_post_id'] ) : null;
		if( ! $post_id ){
			$this->send_jsonp_error(array(
					'error'   => 'missing-parameter',
					'message' => esc_attr__('post not specified', 'afg')
			));
			die();
		}

		if( ! current_user_can('read', $post_id )){
			$this->send_jsonp_error(array(
					'error'   => 'access-denied',
					'message' => esc_attr__('current user cannot read post '.$post_id, 'afg')
			));
			die();
		}

		$fieldname = isset( $_REQUEST['field'] ) ? $_REQUEST['field'] : null;
		if( ! $fieldname ){
			$this->send_jsonp_error(array(
					'error'   => 'missing-parameter',
					'message' => esc_attr__('field not specified', 'afg')
			));
			die();
		}

		$basename = $fieldname;
		if( false !== strpos( $fieldname, 'product-details' )){
			$basename = 'product_details';
		} elseif( false !== strpos( $fieldname, 'star-ratings' )){
			$basename = 'star_ratings';
		}

		if( $this->meta->is_unknown_field( $basename )){
			$this->send_jsonp_error(array(
					'error'   => 'wrong-parameter',
					'message' => esc_attr__('unknown field requested', 'afg'),
					'ref'     => compact( 'post_id', 'fieldname' )
			));
			die();
		}

		$value = null;
		if( $this->meta->is_meta_field( $basename )){

			$meta_key = AFG_META_PREFIX . $basename;
			if( has_filter("sanitize_post_meta_{$meta_key}") ){

				//$value = get_post_meta( $post_id, AFG_META_PREFIX . $fieldname, true );

				if( ! $value && in_array( $basename, array('star_ratings', 'product_details'))){
					//ob_start();

					$nonce = '';
					if ( current_user_can('edit_post', $post_id )){
						$nonce = wp_create_nonce("afg-update-{$post_id}");
					}
					$this->meta->get_element( $basename )->render_html(
							$post_id,
							null,
							'temp',
							'temp',
							$nonce,
							'widget-settings',
							array('wid' => isset( $_REQUEST['wid'] ) ? $_REQUEST['wid'] : '')
					);

					//$value = ob_get_clean();
					exit;
				} elseif( in_array( $basename, array('pricing_details', 'call_to_action')) ) {
					if( isset( $_REQUEST['options'] )){
						$options = json_decode( wp_unslash( $_REQUEST['options']), true);
						$this->meta->get_element( $basename )->get_preview( $post_id, $options );
					} else {
						echo '<div><div class="content">' . __('Preview not available.', 'afg') . '</div></div>';
					}
					exit;
				}
			}
		} elseif( $this->meta->is_post_field( $fieldname )){
			if( 'post_title' == $fieldname ){
				$value = get_the_title( $post_id );
			}
		}

		if( ! is_null( $value )){
			$nonce = '';
			if( current_user_can('edit_post', $post_id ) ){
				$nonce = wp_create_nonce( "afg-update-{$post_id}" );
			}

			$data = array(
					'post_id' => $post_id,
					'field'   => $fieldname,
					'value'   => $value,
					'nonce'   => $nonce //will be required when making update requests
			);
			$this->send_jsonp_success( $data );
		}

		$this->send_jsonp_error(array(
				'error'   => 'unsupported-request',
				'message' => esc_attr__('ajax retrieval not supported', 'afg'),
				'ref'     => $fieldname
		));
	}

	public function ajax_update_review_field(){

		$post_id = ( isset( $_REQUEST['afg_post_id'] ) && 0 < absint( $_REQUEST['afg_post_id'] )) ? absint( $_REQUEST['afg_post_id'] ) : null;
		if( ! $post_id ){
			wp_send_json_error(array(
					'error'   => 'missing-parameter',
					'message' => esc_attr__('post not specified', 'afg'),
					'ref'     => $_REQUEST['afg_post_id']
			));
			die();
		}

		if( ! current_user_can('edit_post', $post_id )){
			wp_send_json_error(array(
					'error'   => 'access-denied',
					'message' => esc_attr__('current user cannot edit post ', 'afg'),
					'ref'     => $post_id
			));
			die();
		}

		$fieldname = isset( $_REQUEST['field'] ) ? $_REQUEST['field'] : null;
		if( ! $fieldname ){
			wp_send_json_error(array(
					'error'   => 'missing-parameter',
					'message' => esc_attr__('field not specified', 'afg')
			));
			die();
		}

		$basename = $fieldname;
		if( false !== strpos( str_replace('-', '_', $fieldname), 'product_details' )){
			$basename = 'product_details';
		} elseif( false !== strpos( str_replace('-', '_', $fieldname), 'star_ratings' )){
			$basename = 'star_ratings';
		}

		if( $this->meta->is_unknown_field( $basename )){
			wp_send_json_error(array(
					'error'   => 'wrong-parameter',
					'message' => esc_attr__('trying to update an unknown field', 'afg'),
					'ref'     => $fieldname
			));
			die();
		}

		$nonce = isset( $_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : null;

		if( ! $nonce || ! wp_verify_nonce( $nonce, "afg-update-{$post_id}" )){

			wp_send_json_error(array(
					'error'   => 'wrong-nonce',
					'message' => esc_attr__('nonce could not be verified: ', 'afg'),
					'ref'     => $nonce
			));
			die();
		}

		//afg_log(__METHOD__, $_REQUEST);

		if( ! isset( $_REQUEST[ $fieldname ] ) ){
			wp_send_json_error(array(
					'error'   => 'missing-parameter',
					'message' => esc_attr__('new value not specified', 'afg'),
					'ref'     => $fieldname
			));
			die();
		}
		$new_value = wp_unslash( $_REQUEST[ $fieldname ] );

		if( $this->meta->is_meta_field( $basename )){

			$base_meta_key = AFG_META_PREFIX . $basename;
			if( has_filter("sanitize_post_meta_{$base_meta_key}") ){

				$result = $this->meta->get_element( $basename )->direct_update(
						$post_id,
						$fieldname,
						$new_value,
						isset( $_REQUEST['wid'] ) ? $_REQUEST['wid'] : ''
				);

				if( $result === false ){
					wp_send_json_error( array(
							'error'   => 'operation-failed',
							'message' => esc_attr__('field could not be updated', 'afg'),
							'ref'     => compact( 'post_id', 'fieldname', 'new_value', 'basename' )
					));
				} else {
					wp_send_json_success();
				}
			} else {
				//echo $basename.' has no sanitizer, therefore, it cannot be updated!';
			}

		} elseif( $this->meta->is_post_field( $fieldname )){
			if( 'post_status' == $fieldname ){
				if( 'publish' == $_REQUEST[ $fieldname ] ){
					wp_publish_post( $post_id );

					$post = get_post( $post_id );

					$this->send_jsonp_success(array(
							'code'    => 'post-published',
							'post_id' => $post_id,
							'value'   => $post->post_status
					));
				}
			} elseif( 'post_title' == $fieldname ){
				$value = $_REQUEST[ $fieldname ];

				$post_name  = sanitize_title_with_dashes( $value, '', 'save' );

				$postarr = array(
						'ID'         => $post_id,
						'post_title' => $value,
						'post_name'  => $post_name
				);

				$postarr = apply_filters('afg_review_controller__update_post_title', $postarr );

				$result = wp_update_post( $postarr, true );
				if( is_wp_error( $result )){
					$this->send_jsonp_error(array(
							'error'   => 'operation-failed',
							'message' => esc_attr__('field could not be updated', 'afg'),
							'ref'     => compact( 'post_id', 'fieldname', 'value' )
					));
				} else {
					$post = get_post( $post_id );

					$this->send_jsonp_success( array(
							'code'    => 'title-updated',
							'post_id' => $post_id,
							'value'   => $post->post_title
					));
				}
			}
			$this->send_jsonp_error(array(
					'error'   => 'unsupported-request',
					'message' => esc_attr__('update could not be performed', 'afg'),
					'ref'     => $fieldname
			));
		}

		wp_send_json_error(array(
				'error'   => 'unsupported-request',
				'message' => esc_attr__('update could not be performed', 'afg'),
				'ref'     => $fieldname
		));
	}

	/**
	 *
	 * @param string $post_id
	 * @param string $product_code
	 * @return array with a list of posts
	 */
	protected function get_review_post_by_id( $post_id ){

		$oldPost = null;

		if( $post_id && $post_id != 'undefined' ){
			$oldPost = get_post( $post_id, ARRAY_A );
			if( $oldPost ){
				//afg_log( 'Existing Product Post found by post_id (' . $post_id . ').' );
			} else{
				afg_log( 'WARNING: no Product could be found by the specified post_id (' . $post_id . ')!' );
			}
		}
		return $oldPost;
	}

	/**
	 *
	 * @param string $product_code
	 * @return Ambigous <NULL, multitype:, multitype:NULL >
	 */
	protected function get_review_posts_by_product_code( $product_code, $output = ARRAY_A ){

		$args = array(
				'meta_query' => array(
						array(
								'key'   => AFG_META_PREFIX . 'product_code',
								'value' => $product_code,
						)
				),
				'post_status' => array( 'draft', 'publish', 'pending', 'future', 'private' ),
				'post_type'   => array( $this->meta->post_type_name )
		);

		$posts = get_posts( $args );
		if( ! empty( $posts )){
			if ( $output == OBJECT ){
				return $posts;
			} else {
				$result = array();
				foreach( $posts as $p ){
					if( $output == ARRAY_A ){
						$result[] = $p->to_array();
					} elseif( $output == ARRAY_N ){
						$result[] = array_values( $p->to_array() );
					}
				}
				return $result;
			}
		}
		return $posts;
	}

	//
	// To utilize caching, use AffiGet_Abstract_Meta->load_post().
	//
	function load_review_data_by_post_id( $post_id ){

		$review_data = array();

		/**
		 * var AffiGet_Review_Storage
		 */
		$storage = $this->meta->storage;

		$storage->load_post_fields( $review_data, $post_id );
		$storage->load_meta_fields( $review_data, $post_id  );
		$storage->load_taxonomic_fields( $review_data, $post_id );

		return $review_data;
	}

	protected function init_review_post( $product_code ){

		$review_data = $this->prepare_review_data_init( $product_code );

		//here $review_data['post_fields']['ID'] is not set yet!

		//note, $review_data is passed by reference, so its content will come modified
		$result = $this->meta->storage->store_review_data( $review_data );
		if( ! is_wp_error( $result )){
			//async call to prepare product data
			$this->_sideload_amazon_product_data( $product_code, $review_data['post_fields']['ID'] );
		} else {
			return $result;
		}

		/*afg_log( array(
				'Method' =>__METHOD__,
				'Line'   =>__LINE__,
				'Review' => $review_data
		));*/

		return $review_data;
	}

	protected function _get_post_defaults( $product_code ){

		$action         = $_REQUEST['action'];
		$post_type_name = $this->meta->post_type_name;

		//
		// Collect defaults
		//
		$defaults = array(
				'post_type'      => $post_type_name,
				'post_date'      => get_date_from_gmt( date('Y-m-d H:i:00')),
				'post_title'     => sprintf(__( 'Product %s', 'afg' ), $product_code ),
				'post_status'    => 'draft',
				'post_author'    => 1,
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
				'post_password'  => '',
				'post_parent'    => 0,
				'menu_order'     => 0,
				'post_mime_type' => 'text/html',
				'pinged'         => false,
				'post_content'   => '',
				'post_excerpt'   => ''
		);

		return apply_filters(
				'afg_review_controller__get_post_defaults',
				$defaults,
				$post_type_name,
				$action,
				$product_code
		);

	}

	protected function prepare_review_data_init( $product_code ){

		$defaults = $this->_get_post_defaults( $product_code );

		$review_data = null;

		//
		//collect data from $_REQUEST
		//

		//only basic fields are available with the initial afg_prepare_review() call

		$post_fields = array(); //temp storage

		if( isset( $_REQUEST['afg_main_title'] )){
			$post_fields['post_title']  = sanitize_post_field('post_title', $_REQUEST[ 'afg_main_title' ], '', 'db' );
		}
		if( isset( $_REQUEST['afg_main_slug'] )){
			$post_fields['post_name']   = sanitize_title_with_dashes( $_REQUEST[ 'afg_main_slug' ], '', 'save' );
		}
		if( ! isset( $post_fields['post_name'] ) && isset( $post_fields['post_title'] )){
			//derive from title
			$post_fields['post_name']   = sanitize_title_with_dashes( $post_fields['post_title'], '', 'save' );
		}
		if( isset( $_REQUEST['afg_post_status'] )){
			$post_fields['post_status'] = sanitize_text_field( $_REQUEST['afg_post_status'] );
		}

		$review_data = array(
				'post_fields'      => wp_parse_args( $post_fields, $defaults ),
				'meta_fields'      => array(),
				'taxonomic_fields' => array(),
		);

		//extensions are supposed to add product_code and source_url
		//autoscheduling logic can be added via this hook
		//extensions can hook to this to fetch (sideload) product details and product images
		do_action_ref_array( 'afg_review_controller__prepare_review_data_init', array( &$review_data, $defaults ));

		return $review_data;
	}


	protected function prepare_review_data_update( $post_id, $product_code ){

		//note, extended fields are available only for updateReview

		if( ! isset( $_REQUEST['afg_review_data'] )){
			//self::_log( $_REQUEST );
			throw new Afg_Exception('Parameter afg_review_data is expected for afg_update_review call!');
		}
		$mixed_data = json_decode( stripslashes( $_REQUEST['afg_review_data'] ), $as_array = true );

		$defaults = $this->_get_post_defaults( $product_code );

		//collect and sanitize incoming values

		$review_data = array();

		$this->read_post_fields( $review_data, $mixed_data, $post_id, $defaults );
		$this->read_meta_fields( $review_data, $mixed_data, $post_id, $defaults );
		$this->read_taxonomic_fields( $review_data, $mixed_data, $post_id, $defaults );

		do_action_ref_array( 'afg_review_controller__prepare_review_data_update', array( &$review_data, $defaults) );

		return $review_data;
	}

	protected function read_post_fields( array &$review_data, array $mixed_data, $post_id, $defaults ){

		if( ! isset( $review_data['post_fields'] )){
			$review_data['post_fields'] = array();
		}

		$known_fields = $this->meta->known_post_fields;

		foreach( $known_fields as $field => $config ){

			//flat values are mixed with associative pairs,
			//so we need to take the flat values out
			if( is_numeric( $field ) ){
				$field = $config;
			}

			$value = null;

			//acquire value
			if( isset( $mixed_data[ $field ] )){
				$value = $mixed_data[ $field ];
			} elseif( isset( $defaults[ $field ] )){
				$value = $defaults[ $field ];
			}

			//sanitize value
			//if( isset( $value ) ){
				if( has_filter("afg_review_controller__read_post_fields__sanitize_{$field}" )){
					$review_data['post_fields'][ $field ] = apply_filters( "afg_review_controller__read_post_fields__sanitize_{$field}", $value, $post_id, 'raw' );
				} else {
					$review_data['post_fields'][ $field ] = sanitize_post_field( $field, $value, $post_id, 'raw' );
				}
				//if( is_string( $this->post_fields[ $field ] )){
				//	$this->post_fields[ $field ] = str_replace('&amp;', '&', $this->post_fields[ $field ] );
				//}
			//}
		}
		//echo '<pre>' . print_r($this->post_fields, true) . '</pre>';

		//hook to this action to perform some cross-validation of post fields
		do_action_ref_array( 'afg_review_controller__read_post_fields__validate', array( &$review_data['post_fields'], $post_id, $defaults, $_REQUEST['action']) );
	}

	protected function read_meta_fields( array &$review_data, array $mixed_data, $post_id, $defaults ){

		if( ! isset( $review_data['meta_fields'] )){
			$review_data['meta_fields'] = array();
		}

		$known_field = $this->meta->known_meta_fields;

		foreach( $known_field as $field => $config ){

			if( is_numeric( $field ) ){
				$field = $config;
			}

			/*if ($field == 'product'){
			 if( ! isset( $sourceData[ $field ])){
			echo '<pre>';
			throw new Afg_Exception('Product information is missing!'.compact('fieldConfigs', 'post_id', 'defaults'));
			}
			echo '<pre>Product:['.print_r( $sourceData[ $field ], true ).']</pre>';
			}*/

			//acquire value
			$value = null;
			if( isset( $mixed_data[ $field ] )){
				$value = $mixed_data[ $field ];
			} elseif( isset( $defaults[ $field ] )){
				$value = $defaults[ $field ];
			}

			//sanitize value
			if( null !== $value ){

				$meta_key = AFG_META_PREFIX . $field;
				$filter = "sanitize_post_meta_{$meta_key}"; //will be also called by sanitize_meta()

				if( has_filter( $filter )){
					$review_data['meta_fields'][ $field ] = apply_filters( $filter, $meta_key, $value, 'post' );
				} else {
					$review_data['meta_fields'][ $field ] = wp_kses_data( $value );
				}
			}
		}

		//hook to this action to perform some cross-validation of meta fields
		do_action_ref_array( 'afg_review_controller__read_meta_fields__validate', array( &$review_data['meta_fields'], $post_id, $defaults, $_REQUEST['action']) );
	}

	protected function read_taxonomic_fields( array &$review_data, array $mixed_data, $post_id, $defaults ){

		if( ! isset( $review_data['taxonomic_fields'] )){
			$review_data['taxonomic_fields'] = array();
		}

		$known_fields = $this->meta->known_taxonomic_fields;

		$source = &$mixed_data['labels'];

		if( ! empty( $source )){

			foreach( $known_fields as $field => $taxonomy ){

				if( ! is_string( $field ) || ! is_string( $taxonomy ) ){
					throw new Afg_Exception('All items in taxonomic field config should be associative pairs, containing field_name as key and taxonomy_name as value!');
				}

				//assign a sanitized value
				if( isset( $source[ $taxonomy ] )){
					$review_data['taxonomic_fields'][ $field ] = apply_filters("afg_review_controller__read_taxonomic_fields__sanitize_{$field}", $source[ $taxonomy ], $taxonomy, 'raw', $post_id );
				}
			}
		}

		//hook to this in order to do some cross-validation of taxonomic fields
		do_action_ref_array( 'afg_review_controller__read_taxonomic_fields__validate', array( &$review_data['taxonomic_fields'], $post_id, $defaults, $_REQUEST['action'] ));
	}

	//asynchronously downloads amazon product data and attaches it as a meta field to post
	protected function _sideload_amazon_product_data( $product_code, $post_id ){

		$args = array(
				'timeout'      => 1,
				'blocking'     => false,
		);

		$secret = utf8_uri_encode('affiget-'.preg_replace("/[^a-zA-Z]+/", '', NONCE_KEY&'aQws'));

		$params = array(
				'action'           => 'afg_fetch_amazon_product',
				'afg_post_id'      => $post_id,
				'afg_product_code' => $product_code,
				'afg_secret'       => $secret,
		);

		$url = get_admin_url(null, 'admin-ajax.php?') . http_build_query( $params );

		$result = wp_remote_get( $url, $args );
		//effectively, calls $this->ajax_fetch_amazon_product()
	}

	public function ajax_fetch_amazon_product(){

		set_time_limit( 10 );//secs
		add_filter( 'http_request_timeout', array( &$this, '_extend_wp_timeout') );

		$product_code = isset( $_REQUEST['afg_product_code'] ) ? $_REQUEST['afg_product_code'] : null;
		if( ! $product_code ){
			afg_log('Could not fetch Amazon product: product code not specified.');
			die();
		}

		$post_id = isset( $_REQUEST['afg_post_id']) && is_numeric( $_REQUEST['afg_post_id']) ? $_REQUEST['afg_post_id'] : null;
		if( ! $post_id ){
			afg_log('Could not fetch Amazon product: post id not specified.');
			die();
		}

		$secret    = isset( $_REQUEST['afg_secret'] ) ? $_REQUEST['afg_secret'] : null;
		$my_secret = 'affiget-'.preg_replace("/[^a-zA-Z]+/", '', NONCE_KEY&'aQws');
		if( !$secret || ($secret != $my_secret)){
			afg_log("Could not fetch product {$product_code} for post {$post_id}: wrong secret.");
			die();
		}

		$success = $this->fetch_amazon_product_data( $product_code, $post_id );

		if( $success ){
			//afg_log("Successfully fetched product {$product_code} for post {$post_id}.");
		} else {
			afg_log("Could not fetch product {$product_code} for post {$post_id}.");
		}
		die();
	}

	function _assign_description( $post_id, $product_data, $is_new ){

		$current = get_post_meta( $post_id, AFG_META_PREFIX . 'review_content', true );
		if( ! $current ){
			$descriptions = $this->meta->pick_product_data_value( $product_data, 'EditorialReviews' );
			$description = '';
			if(! empty( $descriptions )){
				foreach( $descriptions as $desc ){
					if( 'Product Description' == $desc['Source'] ){
						$description = balanceTags( html_entity_decode( $desc['Content'] ), $force = true );
						break;
					}
				}
			}

			$description = apply_filters( 'afg_review_controller__assign_description', $description, $post_id, $product_data, $is_new );
			if( $description ){
				update_post_meta( $post_id, AFG_META_PREFIX . 'review_content', $description );
			}
		}
	}

	function _assign_category_and_tags( $post_id, $product_data, $is_new ){

		if( ! $is_new ) return;

		$category = $this->meta->pick_product_data_value( $product_data, 'ProductGroup' );

		$tags = array();

		//take ProductTypeName
		$tag      = $this->meta->pick_product_data_value( $product_data, 'ProductTypeName' );
		$tag      = strtolower( str_replace('_', '-', $tag ));
		if( $tag ){
			$tags[] = $tag;
		}

		//take Deparment
		$tag      = $this->meta->pick_product_data_value( $product_data, 'Department' );
		$tag      = strtolower( str_replace('_', '-', $tag ));
		if( $tag ){
			$tags[] = $tag;
		}

		$taxarr = apply_filters( 'afg_review_controller__assign_category_and_tags',
				array(
					'category' => array( $category ),
					'post_tag' => $tags
				),
				$post_id,
				$product_data,
				$is_new
		);

		if( ! empty( $taxarr )){
			$this->meta->storage->commit_taxonomic_fields( $post_id, $taxarr );

			$this->inherit_from_latest_in_category( $taxarr, $post_id, $product_data, $is_new );
		}
	}

	protected function inherit_from_latest_in_category( $taxarr, $post_id, $product_data, $is_new ){

		//cats of current post
		$cats = wp_get_object_terms( $post_id, 'category', array('fields' => 'ids') );

		$args = array(
				'posts_per_page' => 1,
				'category__in'   => array( $cats[0] ),
				'post_type'      => $this->meta->post_type_name,
				'meta_key'       => AFG_META_PREFIX . 'product_details',
				'post_status'    => array('publish','future','draft'),
				'orderby'        => 'modified'
		);
		$prototype_post_id = 0;

		//find last modified Published | Scheduled | Draft review in category
		$latest_cat_review = new WP_Query( $args );
		if( $latest_cat_review->have_posts() ){
			$prototype_post_id = $latest_cat_review->post->ID;
		} else {
			//find last modified Published | Scheduled | Draft review in any category
			unset( $args['category__in'] );
			$latest_cat_review = new WP_Query( $args );
			if( $latest_cat_review->have_posts() ){
				$prototype_post_id = $latest_cat_review->post->ID;
			}
		}

		//note, $prototype_post_id can still be 0.
		do_action('afg_review_controller__inherit_from_latest_in_category', $post_id, $product_data, $is_new, $cats, $prototype_post_id );
	}

	function _assign_title( $post_id, $product_data, $is_new ){

		$asin = $this->meta->pick_product_data_value( $product_data, 'ASIN' );
		$p = get_post( $post_id );

		if( ! $is_new || $p->post_title != sprintf(__( 'Product %s', 'afg' ), $asin ))
			return; //do not change title if the post is not considered "new" OR the title has been modified

		$post_title = $this->meta->pick_product_data_value( $product_data, 'Title' );
		$post_name  = sanitize_title_with_dashes( $post_title, '', 'save' );

		//update post title to match product title
		$postarr = array(
				'ID'         => $post_id,
				'post_title' => $post_title,
				'post_name'  => $post_name
		);

		$postarr = apply_filters( 'afg_review_controller__assign_title',
				$postarr,
				$post_id,
				$product_data,
				$is_new
		);
		if( ! empty( $postarr ) ){
			$result = wp_update_post( $postarr, true );
		}
	}

	//method to download full details by product ASIN and
	public function fetch_amazon_product_data( $product_code, $post_id = null  ){

		$amazon = $this->meta->plugin->get_admin()->get_amazon();
		$product_data = $amazon->fetch_product_data( $product_code, null, ARRAY_A );//ARRAY_A is more expensive than JSON

		if( is_wp_error( $product_data )){
			//echo '<pre>'.print_r( $product, true ).'</pre>';
			return false;
		} else {

			$is_new = false !== get_post_meta( $post_id, AFG_META_PREFIX . 'product_data', true );

			update_post_meta( $post_id, AFG_META_PREFIX . 'product_code', $product_code );
			update_post_meta( $post_id, AFG_META_PREFIX . 'product_data', $product_data );
			update_post_meta( $post_id, AFG_META_PREFIX . 'product_data_timestamp', time() );

			add_action( AffiGet_Review_Controller::EVENT_PRODUCT_UPDATED, array( &$this, '_assign_description'), 10, 3 );
			add_action( AffiGet_Review_Controller::EVENT_PRODUCT_UPDATED, array( &$this, '_assign_category_and_tags'), 10, 3 );
			add_action( AffiGet_Review_Controller::EVENT_PRODUCT_UPDATED, array( &$this, '_assign_title'), 100, 3 );

			do_action( AffiGet_Review_Controller::EVENT_PRODUCT_UPDATED, $post_id, $product_data, $is_new );

			$thumb_id = get_post_meta( $post_id, '_thumbnail_id', true );
			if( $is_new || false === $thumb_id ){ //is new OR has no featured image attached, yet.
				$product_images = array();

				$images   = $this->meta->pick_product_data_value( $product_data, 'Images' );
				foreach( $images as $img ){
					$product_images[] = array( 'source' => $img );
					//can also pass 'title', 'caption', 'filename', 'alt', 'status'
				}

				if( is_null( $this->imager )){
					$this->imager = new AffiGet_Review_Imager();
					//imager automatically hooks to EVENT_IMAGES_UPDATED
					//to download and attach actual image files
				}

				$post_title = $this->meta->pick_product_data_value( $product_data, 'Title' );
				do_action( AffiGet_Review_Controller::EVENT_IMAGES_UPDATED,
						compact('post_id', 'product_code', 'product_images', 'post_title')
				);
			}

			return true;
		}
	}

	//credit: http://wordpress.org/support/topic/plugin-wp-smushit-fix-for-timeouts-after-5000-milliseconds
	public function _extend_wp_timeout( $time ) {

		$time = 10; //new number of seconds
		return $time;
	}

	/**
	 * Send a JSON response back to an Ajax request.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $response Variable (usually an array or object) to encode as JSON,
	 *                        then print and die.
	 */
	function send_jsonp( $response ) {
		@header( 'Content-Type: application/javascript; charset=' . get_option( 'blog_charset' ) );

		$callback = isset( $_REQUEST['callback'] ) ? $_REQUEST['callback'] : ';';

		echo $callback.'(';
		echo wp_json_encode( $response );
		echo ');';

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
			wp_die();
		else
			die;
	}

	/**
	 * Send a JSONP response back to an Ajax request, indicating success.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $data Data to encode as JSON, then print and die.
	 */
	function send_jsonp_success( $data = null ) {
		$response = array( 'success' => true );

		if ( isset( $data ) )
			$response['data'] = $data;

		$this->send_jsonp( $response );
	}

	/**
	 * Send a JSONP response back to an Ajax request, indicating error.
	 *
	 * Adapted from wp_send_json_error.
	 *
	 * @param string $data
	 */
	function send_jsonp_error( $data = null ) {

		$response = array( 'success' => false );

		if ( isset( $data ) ) {
			if ( is_wp_error( $data ) ) {
				$result = array();
				foreach ( $data->errors as $code => $messages ) {
					foreach ( $messages as $message ) {
						$result[] = array( 'code' => $code, 'message' => $message );
					}
				}

				$response['data'] = $result;
			} else {
				$response['data'] = $data;
			}
		}

		$this->send_jsonp( $response );
	}


	function send_jsonp_raw( $response ) {
		@header( 'Content-Type: application/javascript; charset=' . get_option( 'blog_charset' ) );

		$callback = isset( $_REQUEST['callback'] ) ? $_REQUEST['callback'] : '';

		echo $callback.'({success:true, data:';
		echo $response;
		echo '});';

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
			wp_die();
		else
			die;
	}
}