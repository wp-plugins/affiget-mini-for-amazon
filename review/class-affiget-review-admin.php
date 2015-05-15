<?php
/**
* Declare functions for Admin side.
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
class AffiGet_Review_Admin {

	/**
	 *
	 * @var AffiGet_Review_Meta
	 */
	protected $meta;

	//--------------------------------------------------------------------------

	public function __construct( AffiGet_Review_Meta $meta ){

		$this->meta = $meta;
	}

	public function add_product_entry_metabox(){

		add_meta_box( 'afg-product-meta-box', __( 'Product', 'afg' ), array(&$this, 'metabox_product'), $this->meta->post_type_name, 'normal', 'high');
	}

	function metabox_product( $post ){

		$post_id = $post->ID;

		$product_id = get_post_meta( $post_id, AFG_META_PREFIX . 'product_code', true );


		echo '<label for="afg_product_code">' . __( 'ASIN:', 'afg' ) . '</label> ';

		echo '<input type="text" name="afg_product_code" value="'.esc_attr( $product_id ).'"/>';

		wp_nonce_field( 'afg_product_code_update', 'afg_product_code_nonce' );

		if( $product_id ){
			echo '<br />';

			//echo '<span>'.$product_id.'</span>';

			$product_data = get_post_meta( $post_id, AFG_META_PREFIX . 'product_data', true );
			echo '<small><pre>'.print_r( $product_data, true ).'</pre></small>';
		}
	}

	function save_metabox_data( $post_id ){

		// exit on autosave
		if( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ){
			return $post_id;
		}
		if( defined('DOING_AJAX') && DOING_AJAX ){
			return $post_id;
		}

		//self::log('Saving: '.$post_id.'; post_type:'. $_POST['post_type'].'; product_code:'. $_POST[ 'afg_product_code'].'; nonce:'.$_POST[ 'afg_product_code_nonce']);

		// check nonce
		if( ! isset( $_POST[ 'afg_product_code'] ) || !wp_verify_nonce( $_POST[ 'afg_product_code_nonce'], 'afg_product_code_update' )){
			return $post_id;
		}

		// check capabilities
		if( $this->meta->post_type_name == $_POST['post_type'] ){
			if( ! current_user_can('edit_post', $post_id )){
				return $post_id;
			}
		} elseif( ! current_user_can('edit_page', $post_id )){
			return $post_id;
		}

		if( trim( $_POST[ 'afg_product_code'] ) !== ''){
			update_post_meta( $post_id, AFG_META_PREFIX . 'product_code', sanitize_text_field( $_POST[ 'afg_product_code'] ));
			$this->meta->controller->fetch_amazon_product_data( $_POST[ 'afg_product_code'], $post_id );

		} else {
			delete_post_meta( $post_id, AFG_META_PREFIX . 'product_code' );
		}
	}

	function admin_list__inject_image_column( $columns, $post_type ){

		//insert Thumb image after the checkbox column
		if( $post_type !== $this->meta->post_type_name )
			return $columns;

		if( isset( $columns['image'] ) )
			return $columns;

		$first_key   = key( $columns );
		$first_value = array_shift( $columns );

		//seems to be no easier way to push a new element
		//to the front of an assoc. array,
		//while preserving its indices and order

		$arr = array_reverse( $columns, true );

		unset($arr['author']);
		unset($arr['tags']);

		$arr['image']       = __( 'Image', 'afg' );
		$arr[ $first_key ]  = $first_value;

		$columns = array_reverse( $arr, true );

		return $columns;
	}

	function admin_list__post_data_row( $column_name, $post_id ){

		switch( $column_name ){

			case 'image':

				$thumb_id = get_post_meta( $post_id, '_thumbnail_id', true );

				//try to load the version which is closest to 50x50
				//it would be much better to request one of the known sizes
				//(that were registered with add_image_size)
				echo wp_get_attachment_image( $thumb_id, 'afg-thumb' );

				break;

			default:
				break;
		}
	}

	function admin_list__product_link( $actions, $post ){

		if( $post->post_type !== $this->meta->post_type_name ){
			return $actions;
		}

		$amazon = $this->get_amazon_link( $post->ID );
		if( ! $amazon ){
			$amazon = _x('Amazon', 'link to amazon', 'afg');
		}

		$actions['amazon'] = $amazon;

		return $actions;
	}

	protected function get_amazon_link( $post_id, $css_class = ''){

		$product_data = get_post_meta( $post_id, AFG_META_PREFIX . 'product_data', true );

		if( $product_data ){
			$url = $this->meta->pick_product_data_value( $product_data, 'DetailPageURL' );


			$amazon_link = sprintf('<a title="%s" target="afg_amazon_link" class="%s" href="%s"><img class="afg-amazon-ico" src="%s" width="16px" height="16px"/></a>',
					esc_attr__('View this product on Amazon', 'afg'),
					esc_attr( $css_class ),
					esc_attr( $url ),
					AFG_ADMIN_URL . 'img/amazon-ico.png'
			);
			return $amazon_link;
		}
		return '';
	}

	function admin_post_edit_product_link(){

		global $post;

		if( ! $post ) return;

		$amazon_link = $this->get_amazon_link( $post->ID, 'button button-small' );

		if( $amazon_link ){
		?>
		<style type="text/css">#afg-product-link a {padding: 2px 3px; margin-left: 5px; margin-right:3px;}</style>
		<script type="text/javascript">/* <![CDATA[ */
		jQuery(document).ready(function($){
			$('#edit-slug-box').append('<span id="afg-product-link"><?php echo $amazon_link; ?></span>');
		});
		/* ]]> */</script><?php
		}
	}

	function admin_post_edit_product_sync(){

		global $post;

		if( ! $post ) return;

		$product_sync = get_post_meta( $post->ID, AFG_META_PREFIX . 'product_data_timestamp', true );

		if( $product_sync ){
			$product_sync = sprintf( _x('%s ago', 'Time difference', 'afg'), human_time_diff( $product_sync, time() ));
		} else {
			$product_sync = sprintf( __( 'Resync now', 'afg' ));
		}

		$link = str_replace('&update_product=true', '', $_SERVER['REQUEST_URI']).'&update_product=true';

		$link = '<a href="'.$link.'" title="' . __('Click to refetch product data from Amazon', 'afg').'">' . $product_sync . '</a>';

		$product_sync = sprintf( '<strong>%s</strong> %s.', __('Data sync.:', 'afg'), $link );

		?>
	<style type="text/css">
	#afg-product-sync { display: inline-block; clear:left; }
	#afg-product-sync a {text-decoration: none; color: #21759B; }
	#afg-product-sync a:hover {text-decoration: underline; color: #D54E21; }
	</style>
	<script type="text/javascript">/* <![CDATA[ */
	  jQuery(document).ready(function($){
		$('#edit-slug-box').append('<br /><div id=\'afg-product-sync\'><?php echo $product_sync; ?></div>');
	  });
	/* ]]> */</script>
	<?php
	}

	function add_update_product_var( $public_query_vars ) {

		$public_query_vars[] = 'update_product';
		return $public_query_vars;
	}

	protected function is_sync_required( $post_id ){

		$update_period_hours = 72;

		//echo '<pre>' . print_r( $update_period_hours, true ) . '</pre>';

		$product_code = get_post_meta( $post_id, AFG_META_PREFIX . 'product_code', true );
		if( ! $product_code ){
			//post has no product code, sync not even possible
			return false;
		}

		$product_timestamp = get_post_meta( $post_id, AFG_META_PREFIX . 'product_data_timestamp', true );

		if( is_numeric( $product_timestamp )) {
			if( (time() - $product_timestamp) < $update_period_hours * 3600){
				//echo '[Sync is not needed]';
				return false;
			}
		}
		//echo '[Sync is needed]';
		return true;
	}

	function update_product_maybe() {

		//echo 'Edit:' .$_REQUEST['post'];

		if( $this->is_sync_required( $_REQUEST['post'] ) || (isset( $_REQUEST['update_product'] ) && 'true' == $_REQUEST['update_product'] )){
			$this->do_update_product( $_REQUEST['post'] );
		}
	}

	function update_product_for_post_maybe( $wp ) {

		global $post;

		//$post is null when the post is scheduled for the future
		//and current user is not logged-in
		if( ! $post )
			return;

		if( $this->is_sync_required( $post->ID ) || (isset( $wp->query_vars['update_product']) && 'true' === $wp->query_vars['update_product'] )){
			$this->do_update_product( $post->ID );
		}
	}

	protected function do_update_product( $post_id ) {

		//product code available?
		$product_code = get_post_meta( $post_id, AFG_META_PREFIX . 'product_code', true );
		if( ! $product_code ){
			add_action( 'admin_notices', array(&$this, 'admin_notice_missing_product_code' ));
			return;
		}

		//uncomment for debugging:
		//delete_post_meta( $post->ID, AFG_META_PREFIX . 'product_data' );

		//perform sychronous download
		if( $this->meta->controller->fetch_amazon_product_data( $product_code, $post_id )){
			add_action( 'admin_notices', array(&$this, 'admin_notice_product_update_success' ));
		} else {
			add_action( 'admin_notices', array(&$this, 'admin_notice_product_update_failure' ));
		}
	}

	function admin_notice_missing_product_code() { ?>

	    <div class="error">
	        <p><?php _e( 'Cannot update product data: missing or invalid product code!', 'afg' ); ?></p>
	    </div><?php
	}

	function admin_notice_product_update_success() { ?>

	    <div class="updated">
	        <p><?php _e( 'Product data successfully updated.', 'afg' ); ?></p>
	    </div><?php
	}

	function admin_notice_product_update_failure() { ?>

	    <div class="error">
	        <p><?php _e( 'Product data could not be updated!', 'afg' ); ?></p>
	    </div><?php
	}
}