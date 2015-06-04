<?php
/**
 *
 */
class AffiGet_Review_Imager
{
	const SOURCE_IMAGE_META       = 'source_image';

	const MAX_FILENAME_LENGTH     = 95; //symbols
	const MAX_EXECUTION_TIME      = 30; //seconds
	//const MEMORY_LIMIT          = '50M';

	const LOG_FILE_NAME           = 'sideload-images.txt';

	public function __construct(){

		add_action( AffiGet_Review_Controller::EVENT_IMAGES_UPDATED, array(&$this, 'sideload_images') );

		if( ! function_exists('download_url') ){
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		if( ! function_exists('media_handle_sideload') ){
			require_once ABSPATH . 'wp-admin/includes/media.php';
		}
	}

	//
	//
	//
	public function sideload_images( $args = null ){

		if( ! isset( $args['post_id'] ) || ! isset( $args['product_code']) || ! isset( $args['post_title'] ) || ! isset( $args['product_images'] )){
			$this->FAIL('Invalid request parameters()', $args );
			return;
		}

		$post_id         = $args['post_id'];
		$post_title      = $args['post_title'];
		$product_code    = $args['product_code'];
		$product_images  = $args['product_images'];

		if( !is_numeric( $post_id ) || !is_string( $product_code ) || empty( $post_title ) || !is_array( $product_images ) || empty( $product_images )){
			$this->FAIL('Invalid request parameters for Imager::sideload_images()',
						 compact( 'post_id', 'product_code', 'post_title', 'product_images' ));
			return;
		}

		//ini_set( 'memory_limit', self::MEMORY_LIMIT );
		set_time_limit( self::MAX_EXECUTION_TIME );
		add_filter( 'http_request_timeout', array( &$this, '_extend_wp_timeout') );

		$filename = trim( mb_substr( strtolower( sanitize_file_name( $post_title ) ), 0, self::MAX_FILENAME_LENGTH, 'utf8'));

		$defaults = array(
				'status'   => '1',
				'title'    => $post_title,
				'caption'  => $post_title,
				'filename' => $filename,
				'alt'      => $post_title,
		);

		foreach( $product_images as &$img ){
			$img = wp_parse_args( $img, $defaults );
		}

		try {

			$problems = array();
			$img_att = array();

			//key: $source_url; value: attachment_id
			$map_attachment_ids = $this->_get_attachment_mappings( $post_id );//

			foreach( $product_images as $img ){

				$img_url = $img['source'];

				//download only those images that are marked as active
				if( '1' == $img['status'] ){
					if( array_key_exists( $img_url, $map_attachment_ids )){
						$img_att[ $img_url ] = $map_attachment_ids[ $img_url ];
						//$this->_log( "Active image {$img_url} is already attached as {$img_att[ $img_url ]} -- no need to download." );
					} else {
						$attachment_id = $this->fetch_image_and_attach( $post_id, $img, $map_attachment_ids );
						if( is_wp_error( $attachment_id ) ){
							$problems[] = $attachment_id->get_error_message();
						} else {
							$img_att[ $img_url ] = $attachment_id;
						}
					}
				} else { //image status is not '1'

					if( isset( $map_attachment_ids[ $img_url ] )){
						$attachment_id = $map_attachment_ids[ $img_url ];
						//$this->_log( "Disabled image {$img_url} is already attached as [{$attachment_id}] -- no need to download." );
					}
				}
			} // downloading complete

			$this->_invalidate_attachments( $post_id,  $product_images );

			//we store attachment_ids separately from image data,
			//because images array needs to stay intact
			//as we calculate hash for it to check for changes

		} catch ( Exception $e ){

			$this->WARN('Exception occured while sideloading product images',
					array( 'Message' => $e->getMessage(), 'Code' => $e->getCode(), 'Timestamp' => time() ));
		}
	}

	public function _extend_wp_timeout( $time ) {

		$time = self::MAX_EXECUTION_TIME; //new number of seconds
		return $time;
	}

	protected function fetch_image_and_attach( $post_id, $image, $map_attachment_ids ){

		$filename     = $image['filename'];
		$source_url   = $image['source'];
		$post_title   = $image['title'];
		$post_excerpt = $image['caption'];
		$alt          = $image['alt'];

		$attach_id = $this->sideload_image( $post_id, $filename, $source_url, compact('post_title', 'post_excerpt') );

		if( is_wp_error( $attach_id ) ) {
			$this->WARN('Failed to sideload image:',
					compact('post_id', 'post_name', 'source_url', 'post_title', 'attach_id'));
		} else {
			//$attachment_url = wp_get_attachment_url( $attach_id );

			//we will need afg_source_image to filter attachments by source url
			//see also: find_attachment_by_source_url()
			if( ! update_post_meta( $attach_id, AFG_META_PREFIX . self::SOURCE_IMAGE_META, $source_url )){
				$this->WARN('Could not write image source to meta field for attachment:',
						compact('post_id', 'attach_id', 'source_url'));
			}
			update_post_meta( $attach_id, '_wp_attachment_image_alt', $alt );
		}
		return $attach_id;
	}

	protected function sideload_image( $post_id, $filename, $image_url, $attachment_fields ){

		//credit: http://theme.fm/2011/10/how-to-upload-media-via-url-programmatically-in-wordpress-2657/
		if( ! $image_url ){
			afg_log('Image URL paremeter should not be empty', $post_id );
			return;
		}

		$tmpfile = download_url( $image_url );
		$file_array = array(
				'name'     => $this->_get_unique_filename_for_image( $filename, $image_url ),
				'tmp_name' => $tmpfile
		);

		// Check for download errors
		if( is_wp_error( $tmpfile ) ) {
			@unlink( $file_array['tmp_name'] );
			return $tmpfile;
		}

		$id = media_handle_sideload( $file_array, $post_id, '', $attachment_fields );
		// Check for handle sideload errors.
		if( is_wp_error( $id ) ) {
			@unlink( $file_array['tmp_name'] );
			return $id;
		}

		return $id;
	}

	protected function _get_unique_filename_for_image( $filename, $source_url ){

		$filename = sanitize_file_name( $filename );
		$basename = $filename ? $filename : basename( $source_url );

		$imgext = $this->_get_file_extension( $source_url );
		if( empty( $imgext )){//whaaat? no extension?
			$this->WARN('Source image does not seem to have a valid image extension:',
					compact( 'source_url' ));
		}

		$uploads   = wp_upload_dir();
		//$directory = trailingslashit( $uploads['path'] );

		$image_name = trim( mb_substr( $basename, 0, self::MAX_FILENAME_LENGTH,'utf8').'.'.$imgext ); //make it fit into ~99 symbols
		$image_name = sanitize_file_name( $image_name );

		$filename = wp_unique_filename(
				$uploads['path'],
				$image_name,
				$unique_filename_callback = null
		);

		return $filename;
	}

	//
	// get file extension
	//
	protected function _get_file_extension( $path ) {

		//drop the query part from path, as it confuses pathinfo()
		$qpos = strpos( $path, '?' );
		if( false !== $qpos ){
			$path = substr( $path, 0, $qpos );
		}

		return pathinfo( $path, PATHINFO_EXTENSION );
	}

	/**
	 * Map source_url to attachment_id.
	 *
	 * @param int $post_id
	 * @throws AffiGet_Exception when actual meta field cannot be loaded.
	 * @return Array an array of attachment_ids indexed by source_url
	 */
	protected function _get_attachment_mappings( $post_id ){

		$result = array();

		$source_meta = AFG_META_PREFIX . self::SOURCE_IMAGE_META;

		$args = array(
				'order'          => 'ASC',
				'post_type'      => 'attachment',
				'post_parent'    => $post_id,
				'post_mime_type' => 'image',
				'post_status'    => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash'),
				'numberposts'    => -1, //unlimited
				'meta_key'       => $source_meta,//note, this is meta field for the attachment (as opposed to its parent post)
				//'meta_value'     => $source_url, //the actual value does not matter!
		);

		$attachments = get_posts( $args );
		if( $attachments ) {
			foreach( $attachments as $attachment ){
				$source_url = get_post_meta( $attachment->ID, $source_meta, true );
				if( $source_url ){
					$result[ $source_url ] = $attachment->ID;
				} else {
					throw new AffiGet_Exception('Lost a meta field with source image url!');
				}
			}
		}
		return $result;
	}

	protected function _find_attachment_by_source_url( $parent_post_id, $source_url ){

		$args = array(
				'order'          => 'ASC',
				'post_type'      => 'attachment',
				'post_parent'    => $parent_post_id,
				'post_mime_type' => 'image',
				'post_status'    => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash'),
				'numberposts'    => -1,
				'meta_key'       => AFG_META_PREFIX . self::SOURCE_IMAGE_META,//note, this is meta field for attachment (as opposed to its parent post)
				'meta_value'     => $source_url,
		);

		$attachments = get_posts( $args );
		if( $attachments && ! empty( $attachments )){
			return $attachments[0];
		}
		return false;
	}

	/**
	 * Reorder and update attachments based on the details specified by images parameter
	 *
	 * @param int $parent_post_id
	 * @param array $images
	 */
	protected function _invalidate_attachments( $parent_post_id, array $images ){

		$args = array(
				'order'          => 'ASC',
				'post_type'      => 'attachment',
				'post_parent'    => $parent_post_id,
				'post_mime_type' => 'image',
				'post_status'    => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash'),
				'numberposts'    => -1,
		);
		$attachments = get_posts( $args );
		if( ! $attachments ) {
			//$this->_log( 'Could not find any attachments:', $args );
			return;
		}

		$not_stored = array();
		$touched = array();

		//reorder & update attachments
		$first_active = $images[0]; //if no active images found, just use the first one
		$menu_order   = 0;
		$featured     = null;
		foreach( $images as $img ){

			$postarr = array();

			foreach( $attachments as $attachment ) {

				if( ! property_exists( $attachment, 'source' ) ){
					$attachment->source = get_post_meta( $attachment->ID, AFG_META_PREFIX . self::SOURCE_IMAGE_META, true );
				}
				if( ! $attachment->source ){
					continue;
				}

				if( $attachment->source == $img['source'] ){

					if( $img['status'] == '1' && is_null( $featured ) ){
						$featured = $attachment;
					}

					$postarr['ID']             = $attachment->ID;
					$postarr['menu_order']     = $menu_order;

					$postarr['comment_status'] = 'closed';
					$postarr['ping_status']    = 'closed';

					$menu_order++;

					if( $img['status'] != '1' ){
						//hide images where status is not set to '1'
						$postarr['post_status'] = 'private';
					} else {
						$postarr['post_status'] = 'inherit';
					}
					if( isset ( $img['caption'] ) ){
						$postarr['post_title']  = $img['caption'];
					}
					$touched[] = $attachment->ID;
					if( ! wp_update_post( $postarr )){
						$this->WARN('could not update attachment:',
								compact( 'attachment', 'postarr' ));
					} else {
						//$this->_log("updated attachment [#$menu_order]:",
						//		compact( 'img', 'attachment', 'postarr' ));
					}
					break;
				}
			}
		}

		//if no active images were matched, set the first one as a featured post
		if( is_null( $featured )){
			$featured = $attachments[0];
		}
		if( update_post_meta( $parent_post_id, '_thumbnail_id', $featured->ID )){
			//$this->_log( "Thumbnail for [$parent_post_id] set to {$featured->ID}." );
			//allow to invalidate cached versions of this post, etc
			$post_type_name = get_post_type( $parent_post_id );
			do_action("afg_{$post_type_name}__updated", $parent_post_id );

		} else {
			if( $featured->ID == get_post_meta( $parent_post_id, '_thumbnail_id', true )){
				//$this->_log( "Thumbnail for [$parent_post_id] remains the same ({$featured->ID})." );
			} else {
				$this->WARN('Could not assign attachment as a thumbnail for post:',
						compact( 'parent_post_id', 'featured' ));
			}
		}

		//now change order of attachements that have no corresponding images mentioned in $images param
		foreach ( $attachments as $attachment ){

			if( ! in_array( $attachment->ID, $touched )){

				$postarr = array();
				$postarr['ID']            = $attachment->ID;
				//$postarr['post_status'] = 'private'; //leave intact!
				$postarr['menu_order']    = $menu_order;
				$menu_order++;

				if( ! wp_update_post( $postarr )){
					$this->WARN('Could not update attachment:',
							compact( 'attachment', 'postarr' ));
				} else {
					//$this->_log("Updated attachment [#$menu_order]:",
					//		compact( 'attachment', 'postarr' ));
				}
			}
		}
	}

	protected function WARN( $message, $params = null ) {

		if( !empty( $params )){
			afg_log( 'WARNING: ', array( $message => $params ));
		} else {
			afg_log( 'WARNING: ' . $message );
		}
	}

	protected function FAIL( $message, $params ){

		if( !empty( $params )){
			afg_log( 'FAILURE: ', array( $message => $params ));
		} else {
			afg_log( 'FAILURE: ' . $message );
		}
	}
}

/* EOF */