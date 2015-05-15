<?php
/**
 *
 */
class AffiGet_Dialog
{
	/**
	 * @var array AffiGet_Abstract_Meta
	 */
	var $metas = array();

	public function __construct(){}

	public function add_supported_meta( AffiGet_Abstract_Meta $meta ){

		$this->metas[ $meta->post_type_name ] = $meta;
	}

	public function ajax_get_current_config(){

		if( ! $this->is_valid_referrer() ){
			$script = "window.affiget.config['error'] = '".__('AffiGet is not supposed to work on this site!', 'afg').'";';
			AffiGet_Admin::output_cacheable_js( $script, $mins = 0 );
			exit;
			//
		}

		$user_key = isset( $_GET['afg_user_key'] ) ? $_GET['afg_user_key']: false;
		if(	! $user_key || $user_key !== get_option( AffiGet_Admin::OPTION_USER_KEY )){
			$script = "window.affiget.config['error'] = '".__('Wrong user key!', 'afg').'";';
			AffiGet_Admin::output_cacheable_js( $script, $mins = 0 );
			exit;
		}

		//$_SERVER['HTTP_REFERER'] = 'http://www.amazon.com/A-Pleasure-Calling-Novel/dp/125006063X/ref=pd_sim_b_5?ie=UTF8&refRID=18YVCRVF88X9M1EB48H1';

		$review_response = null;

		$product_code = null;
		$referrer_address = '';

		if( isset( $_SERVER['HTTP_REFERER']) || isset( $_GET['canonical_link'] )){

			$canonical = isset( $_GET['canonical_link'] ) ? $_GET['canonical_link'] : '';

			$referrer_address = $_SERVER['HTTP_REFERER'];
			do_action('afg_dialog__get_current_config__start', $user_key, $referrer_address, $canonical );

			$parts = '';
			$match = null;
			/*ASIN 10-character alphanumeric unique identifier assigned by Amazon.com and its partners for product identification within the Amazon.com organization */
			if( $canonical ){
				$match = preg_match('/\/([A-Za-z_0-9]{10})$/', $canonical, $parts );
				//afg_log( 'Parts', $parts);
			}
			if( ! $match ){
				afg_log( 'Cannot resolve canonical link. Will rely on referrer address instead.', compact( 'canonical', 'referrer_address' ));
				$match = preg_match('/\/([A-Za-z_0-9]{10})\//', $referrer_address, $parts );
			}
			if( $match ){
				$product_code = $parts[1];
				$timer        = microtime( true );
				$review_response = AffiGet_Review_Meta::get_instance()->controller->prepare_review_silent( compact('product_code') );
				$review_response['time'] = round( microtime(true) - $timer, 5 );
			} else {
				afg_log( 'ERROR. Cannot resolve ASIN.', compact('canonical', 'referrer_address'));
			}
		}

		$config = array(
				'afg_ver'        => AFG_VER,
				'afg_ext'        => array(),
				'jQ_ver'         => '1.11.2',
				'jQUI_ver'       => '1.11.4',
				'jQ_js_url'      => home_url( WPINC ). '/js/jquery/jquery.js',
				'jQUI_js_root'   => home_url( WPINC ). '/js/jquery/ui/',
				'jQUI_js_parts'  => array(
						//'core.js',
						//'widget.js',
						//'effect.js',
						'effect-drop.min.js',
				),
				'jQUI_css_url'   => '', //AFG_URL . 'libs/jQ/jquery-ui-1.11.3.custom/jquery-ui.min.css',
				'base_js_url'    => AFG_URL . 'dialog/js/affiget-mini.js',
				'base_css_url'   => '', //AFG_URL.'dialog/css/affiget-mini.css',
				'canEditPosts'   => current_user_can('edit_posts') ? 'true' : 'false',
		);
		$config = apply_filters( 'afg_dialog__get_current_config', $config );

		$script = ";(function(cfg){\n";
		foreach( $config as $key => $value ){
			$script .= "  cfg['" . esc_js( $key ) . "'] = ". json_encode( $value ) .";\n";
		}
		$script .= "})(window.affiget.config = window.affiget.config || {});";

		$messages = array(
			'logo'                => '',
			'logoHint'            => sprintf(_x('AffiGet Mini v.%s', '%s stands for current version number','afg'), AFG_VER),
			'newReviewSuccess'    => __('A <strong>draft product review</strong> successfully submitted to your site.', 'afg'),
			'newReviewProblem'    => __('AffiGet works only on Amazon product pages!', 'afg'),
			'reviewAlreadyExists' => __('A review for this product already exists on your site.', 'afg'),
			'editReview'          => __('Edit', 'afg'),
			'editReviewHint'      => __('Edit product review', 'afg'),
			'editTitleHint'       => __('Click to start editing this review title','afg'),
			'changeTitle'         => __('Click to set this title for your review or hit ESC to cancel','afg'),
			'emptyTitleProblem'   => __('Title for your review cannot be empty!','afg'),
			'changeTitleProblem'  => __('Review title could not be changed.','afg'),
			'publishReview'       => __('Publish', 'afg'),
			'publishReviewHint'   => __('Make this review public', 'afg'),
			'publishReviewSuccess'=> __('Product review successfully published.', 'afg'),
			'publishReviewProblem'=> __('Product review could not be published.', 'afg'),
			'publishReviewDisabledHint' => __('This review is already published.', 'afg'),
			'viewReview'          => __('View', 'afg'),
			'viewReviewHint'      => __('View product review', 'afg'),
			'deleteReview'        => __('Delete', 'afg'),
			'deleteReviewConfirm' => __('Are you sure you want to move this review to trash?', 'afg'),
			'deleteReviewHint'    => __('Trash product review', 'afg'),
			'deleteReviewSuccess' => __('Product review successfully moved to trash.', 'afg'),
			'deleteReviewProblem' => __('Product review could not be moved to trash.', 'afg'),
			'deleteReviewDisabledHint' => __('This review cannot be mvoed to trash, because it is already published.', 'afg'),
			'close'               => __('Close', 'afg'),
			'closeHint'           => __('Close this toolbar', 'afg'),
			'closing'             => __('Closing...', 'afg'),
		);

		$messages = apply_filters('afg_dialog__get_current_config__messages', $messages);

		$script .= "\n;(function(msg){\n";
		foreach( $messages as $key => $value ){
			$script .= "  msg['" . esc_js( $key ) . "'] = '".( $value )."';\n";
		}
		$script .= "})(window.affiget.msg = window.affiget.msg || {});";

		if( $review_response ){
			$script .= "\n;(function(afg){\n";
			$script .= "  afg.review = ".( json_encode($review_response) ).";\n";
			$script .= "})(window.affiget = window.affiget || {});";
		}

		$request_details = array(
			'referrer'    => $referrer_address,
			'postType'    => AffiGet_Review_Meta::get_instance()->post_type_name,
			'productCode' => $product_code,
			'nonce'       => wp_create_nonce('afg-prepare-review')
		);

		//the following will might be used to make repetetive requests to prepare review
		$script .= "\n;(function(afg){\n";
		$script .= "  afg.request = ".( json_encode( $request_details ) ).";\n";
		$script .= "})(window.affiget = window.affiget || {});";

		AffiGet_Admin::output_cacheable_js( $script, $mins = 0 ); /* no caching! */
		exit;
	}

	protected function is_valid_referrer(){

		if( isset( $_SERVER['HTTP_REFERER'] )){

			$whitelist = array_keys( AffiGet_Admin_Amazon::get_amazon_domains() );
			$whitelist[] = $_SERVER['HTTP_HOST']; //for convenience

			$parts = parse_url( $_SERVER['HTTP_REFERER'] );
			$parts['host'] = str_replace( 'www.', '', $parts['host'] );
			return in_array( $parts['host'], $whitelist );
		}
		return false; /* TODO: return false in production */
	}

} /* AffiGet_Dialog */

/* EOF */