<?php
/**
 * The dashboard-specific functionality related to Amazon Product API.
 *
 * @link       http://affiget.com
 * @since      1.0.0
 *
 * @package    AffiGet
 * @subpackage AffiGet/admin
 */

/**
 * The dashboard-specific functionality related to Amazon Product API.
 *
 * @package    AffiGet
 * @subpackage AffiGet/admin
 * @author     Saru Tole <sarutole@affiget.com>
 */
class AffiGet_Admin_Amazon {

	const OPTION_AMAZON_SETTINGS        = 'afg_amazon_settings';
	const OPTION_AMAZON_SETTINGS_STATUS = 'afg_amazon_settings_status';

	/**
	 * This is used to make calls to Amazon Product API.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      AmazonProductAPI $amazonAPI Makes calls to Amazon Product API.
	 */
	protected $amazonAPI;

	protected static $amazon_domains;

	protected static $amazon_locales;

	/**
	 * Initialize the class.
	 *
	 * @since    1.0.0
	 */
	public function __construct( ) {}

	public function settings_section(){
	?>
		<h3 id="amazon-associates"><?php _e('<em>Amazon Associates</em> account', 'afg');?></h3>
		<p><?php printf(
				__('Please enter your credentials for the %s programme.','afg'),
				'<a href="https://affiliate-program.amazon.com/" target="_blank" rel="external">'.__('Amazon Associates', 'afg').'</a>'
		);
		$defaults = array(
				'access_key'    => '',
				'secret_key'    => '',
				'associate_id'  => '',
				'locale'        => 'US',
				'update_period' => '72'
		);
		$settings = get_option( self::OPTION_AMAZON_SETTINGS );
		$settings = wp_parse_args( $settings, $defaults );

		$status = $this->get_cached_settings_status( $settings ); //valid/invalid/null

		?></p>
		<style>
			.form-table th {
				padding: 10px 10px 10px 0;
			}
			.form-table td {
				padding: 5px 10px;
			}
			.wp-core-ui .button-primary {
				margin-top: 2px;
				margin-left: 1px;
 				padding: 0 1.4em 1px;
			}
			.afg-valid-amazon {<?php
				if( 'valid' !== $status ){
					echo 'display: none;';
				}?>
				color: green;
				margin: 10px 0 0 0px;
				font-size: 0.8em;
			}
			.afg-invalid-amazon {<?php
				if( 'invalid' !== $status ){
					echo 'display: none;';
				}?>
				color: red;
				margin: 10px 0 0 0px;
				font-size: 0.8em;
			}
		</style>
		<form id="amazon_settings_form" method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
			<input type="hidden" value="afg_update_amazon_settings" name="action" />
			<input type="hidden" value="<?php echo wp_create_nonce( 'afg_update_amazon_settings' );?>" name="_wpnonce" id="_wpnonce"/>
			<input type="hidden" value="options-general.php?page=affiget-settings-page" name="_wp_http_referer" />
			<table class="form-table"><tbody>
				<tr valign="top"><th scope="row"><?php esc_html_e('Access key', 'afg');?></th>
					<td><input autocomplete="off" type="text" size="40" name="amazon_settings[access_key]" id="access_key" value="<?php esc_attr_e( $settings['access_key'] );?>" />
				</td></tr>
				<tr valign="top"><th scope="row"><?php esc_html_e('Secret key', 'afg');?></th>
					<td><input autocomplete="off" type="text" size="40" name="amazon_settings[secret_key]" id="secret_key" value="<?php esc_attr_e( $settings['secret_key'] );?>" />
				</td></tr>
				<tr valign="top"><th scope="row"><?php esc_html_e('Associate id', 'afg');?></th>
					<td><input autocomplete="off" style="width: 22em" type="text" name="amazon_settings[associate_id]" id="associate_id" value="<?php esc_attr_e( $settings['associate_id'] );?>" />
				</td></tr>
				<tr valign="top"><th scope="row"><?php esc_html_e('Amazon site / Locale', 'afg');?></th>
					<td><select autocomplete="off" style="width: 22em" name="amazon_settings[locale]" id="locale">
						<?php $this->field_locale_available_options( $settings['locale'] ); ?>
					</select></td>
				</tr>
				<tr style="display:none" valign="top"><th scope="row"><span title="<?php esc_attr_e('How often should Amazon product data be updated');?>" class="hint"><?php esc_html_e('Update period'); ?></span></th>
					<td><select autocomplete="off" style="width: 10em" name="amazon_settings[update_period]" id="update_period">
						<?php $this->field_update_period_available_options( $settings['update_period'] ); ?>
					</select>
					</td>
				</tr>
				<tr valign="top"><th scope="row"></th>
					<td>
						<div class="afg-btn-wrap">
							<input type="submit" class="button-primary" value="<?php esc_attr_e('Save settings');?>" />
							<div class="afg-spinner"></div>
							<div class="afg-valid-amazon"><?php esc_html_e('Account details seem fine.');?></div>
							<div class="afg-invalid-amazon"><?php esc_html_e('Account details not right.');?></div>
						</div>
					</td>
				</tr>
			</tbody></table>
		</form>
<?php
	}

	public static function get_amazon_domains(){

		if( ! self::$amazon_domains ){
			self::$amazon_domains = apply_filters('afg_admin_amazon_get_amazon_domains', array(
				'amazon.com.br' => __('Amazon.com.br - Brasil', 'afg'),
				'amazon.ca'     => __('Amazon.ca - Canada', 'afg'),
				'amazon.cn'     => __('Amazon.cn - China', 'afg'),
				'amazon.de'     => __('Amazon.de - Germany', 'afg'),
				'amazon.es'     => __('Amazon.es - Spain', 'afg'),
				'amazon.fr'     => __('Amazon.fr - France', 'afg'),
				'amazon.in'     => __('Amazon.in - India', 'afg'),
				'amazon.it'     => __('Amazon.it - Italy', 'afg'),
				'amazon.co.jp'  => __('Amazon.co.jp - Japan', 'afg'),
				'amazon.co.uk'  => __('Amazon.co.uk - United Kingdom', 'afg'),
				'amazon.com'    => __('Amazon.com - United States', 'afg')
			));
		}
		return self::$amazon_domains;
	}

	public static function get_amazon_locales(){

		if( ! self::$amazon_locales ){
			self::$amazon_locales = apply_filters('afg_admin_amazon_get_locale_available_options', array(
				'BR' => __('Amazon.com.br - Brasil', 'afg'),
				'CA' => __('Amazon.ca - Canada', 'afg'),
				'CH' => __('Amazon.cn - China', 'afg'),
				'DE' => __('Amazon.de - Germany', 'afg'),
				'ES' => __('Amazon.es - Spain', 'afg'),
				'FR' => __('Amazon.fr - France', 'afg'),
				'IN' => __('Amazon.in - India', 'afg'),
				'IT' => __('Amazon.it - Italy', 'afg'),
				'JP' => __('Amazon.co.jp - Japan', 'afg'),
				'UK' => __('Amazon.co.uk - United Kingdom', 'afg'),
				'US' => __('Amazon.com - United States', 'afg'),
			));
		}
		return self::$amazon_locales;
	}

	protected function field_locale_available_options( $selected = 'US'){

		$options = $this->get_amazon_locales();

		foreach( $options as $opt => $desc ){
			echo '<option value="'.$opt.'" '.selected( $opt, $selected, false ).'>'.esc_html( $desc ).'</option>';
		}
	}

	public function get_update_period_available_options(){

		$options = array(
				'1' => __('1 hour', 'afg'),
				'2' => __('2 hours', 'afg'),
				'3' => __('3 hours', 'afg'),
				'6' => __('6 hours', 'afg'),
				'12' => __('12 hours', 'afg'),
				'24' => __('24 hours', 'afg'),
				'36' => __('36 hours', 'afg'),
				'48' => __('2 days', 'afg'),
				'72' => __('3 days', 'afg'),
		);
		return apply_filters('afg_admin_amazon_get_update_period_available_options', $options );
	}

	protected function field_update_period_available_options( $selected = '72'){

		$options = $this->get_update_period_available_options();

		foreach( $options as $opt => $desc ){
			echo '<option value="'.$opt.'" '.selected( $opt, $selected, false ).'>'.esc_html( $desc ).'</option>';
		}
	}

	public function ajax_update_settings() {

		if( empty( $_POST['amazon_settings'] )){
			wp_die( 0 );
		}
		$settings = $_POST['amazon_settings'];

		ob_start();

			check_admin_referer('afg_update_amazon_settings');

			update_option( self::OPTION_AMAZON_SETTINGS, $settings );

			//options might be modified by the sanitization filter called from inside update_option()!
			$settings = get_option( self::OPTION_AMAZON_SETTINGS );
			$success = $this->maybe_test_settings( $settings, $force_retest = true );

		ob_end_clean(); //not interested in any output!

		if( $success ){
			wp_send_json_success( $settings );
		} else {
			wp_send_json_error( $settings );
		}
	}

	public function sanitize_settings( $values ){

		if( empty( $values ) ){
			return array(
				'access_key'    => '',
				'secret_key'    => '',
				'associate_id'  => '',
				'locale'        => 'US',
				'update_period' => '72'
			);
		}

		$values['access_key']   = sanitize_text_field( $values['access_key'] );
		$values['secret_key']   = sanitize_text_field( $values['secret_key'] );
		$values['associate_id'] = sanitize_text_field( $values['associate_id'] );

		$supported_locales = array('BR', 'CA', 'CH', 'DE', 'ES', 'FR', 'IN', 'IT', 'JP', 'UK', 'US');
		//"BR => Amazon.com.br", "JP" => "Amazon.co.jp", "UK" => "Amazon.co.uk"
		if( ! isset( $values['locale'] )){
			$values['locale'] = 'US';
		} elseif( ! in_array( $values['locale'], $supported_locales )){
			$values['locale'] = 'US';
		}

		if( ! isset( $values['update_period'] )){
			$values['update_period'] = 24;
		} elseif( ! is_numeric( $values['update_period'] )){
			$values['update_period'] = 24;
		} elseif( $values['update_period'] > 999 ){
			$values['update_period'] = 999;
		}

		return $values;
	}

	protected function maybe_test_settings( $settings, $force_retest = false ){

		if( ! empty( $settings )){

			if( $settings['access_key'] && $settings['secret_key'] && $settings['associate_id'] && $settings['locale'] ){

				if( ! $force_retest ){
					//respect cached status if it is marked 'valid' and no older than one hour
					$status = $this->get_cached_settings_status( $settings );
					if(	'valid' === $status ){
						return true;
					}
				}

				if( $this->prepare_API( $settings )){

					//try to fetch some data about "Hitchhiker's Guide to the Galaxy" by Douglas Adams
					$result = $this->fetch_product_details('0345391802', array( AmazonProduct_ResponseGroup::SMALL ));

					if( ! is_wp_error( $result )){
						$this->cache_settings_status( $settings, 'valid' );
						return true;
					}
				}
			}
		}

		$this->cache_settings_status( $settings, 'invalid' );
		return false;
	}

	protected function prepare_API( $settings = null ){

		if( ! $this->amazonAPI ){
			$this->amazonAPI = new AmazonProductAPI();
		}

		try {
			if( is_null( $settings )){
				$settings = get_option( self::OPTION_AMAZON_SETTINGS );
				if( false === $settings ){
					return false;
				}
			}
			$this->amazonAPI->setMode( AmazonProductAPI::MODE_STRICT );
			$this->amazonAPI->setAccessKey(   $settings['access_key']   );
			$this->amazonAPI->setSecretKey(   $settings['secret_key']   );
			$this->amazonAPI->setAssociateId( $settings['associate_id'] );
			$this->amazonAPI->setLocale(      $settings['locale']       );
		} catch( Exception $e ){
			//$data = array( 'Message' => $e->getMessage(), 'Code' => $e->getCode(), 'Timestamp' => time() );
			//return new WP_Error( 'exception', 'Invalid Amazon Affiliates account settings', $data );
			return false;
		}
		return true;
	}

	private function get_cached_settings_status( $settings ){

		$last_status = get_option( self::OPTION_AMAZON_SETTINGS_STATUS );

		if( $last_status !== false ){
			if( serialize( $settings ) == $last_status['settings'] ){
				if( time() < $last_status['timestamp'] + 3600 ){
					return $last_status['status'];
				}
			}
		}
		return null;
	}

	private function cache_settings_status( $settings, $status ){

		$cache = array(
				'settings'  => serialize( $settings ),
				'timestamp' => time(),
				'status'    => ('valid' === $status) ? 'valid' : 'invalid'
		);
		update_option( self::OPTION_AMAZON_SETTINGS_STATUS, $cache );
	}

	public function fetch_product_data( $product_code, array $responseGroups = null, $output = ARRAY_A ){

		$result = $this->fetch_product_details( $product_code, $responseGroups );

		//afg_log(__METHOD__, array( $product_code => $result ));

		if( ! is_wp_error( $result )){
			if( 'JSON' == $output ){
				//afg_log(__METHOD__, array( $product_code => $result->toJSON() ));
				return $result->toJSON();
			} elseif( ARRAY_A == $output ) {
				//afg_log(__METHOD__, array( $product_code => json_decode( $result->toJSON(), true )));
				return json_decode( $result->toJSON(), true );
			}
		}
		return $result;
	}

	/**
	 * @param string $product_code
	 * @param array $responseGroups
	 * @return WP_Error|Ambigous <AmazonProduct_Result, NULL>
	 */
	protected function fetch_product_details( $product_code, array $responseGroups = null ){

		if( ! $this->amazonAPI ){
			$ready = $this->prepare_API();
			if( ! $ready ){
				return new WP_Error( 'error', 'Product API is not ready.' );
			}
		}

		try {
			$defaultResponseGroups = array(
					AmazonProduct_ResponseGroup::ITEM_ATTRIBUTES,
					AmazonProduct_ResponseGroup::IMAGES,
					AmazonProduct_ResponseGroup::OFFERS,
					AmazonProduct_ResponseGroup::REVIEWS,
					AmazonProduct_ResponseGroup::PROMOTION_SUMMARY,
					AmazonProduct_ResponseGroup::EDITORIAL_REVIEW,  //includes product description
					AmazonProduct_ResponseGroup::VARIATION_SUMMARY,	//only parent items have this!
					//AmazonProduct_ResponseGroup::VARIATIONS, 		//for child items!
					//AmazonProduct_ResponseGroup::VARIATION_IMAGES //for child items!
					//AmazonProduct_ResponseGroup::VARIATION_MATRIX //for child items!
			);
			if( empty( $responseGroups )){
				$responseGroups = $defaultResponseGroups;
			}

			$response = $this->amazonAPI->lookupByASIN( $product_code, $responseGroups );

			if( $response ){
				if( ! $response->isSuccess() ){
					return new WP_Error( 'error', 'Call to Amazon API was not successful.', $response );
				}

				if( $response->Errors ){

					if( FALSE !== strpos( $response->Errors[0]->Error, 'AWS.InvalidParameterValue' ) ){
						//wrong product code requested
						return new WP_Error( 'error', 'Call to Amazon API was not successful.', 'Wrong product code' );
					} else {
						return new WP_Error( 'error', 'Call to Amazon API was not successful.', $response->Errors[0]->Error );
					}
				}

				return $response;

			} else {
				return new WP_Error( 'empty', 'Call to Amazon API returned no product data.' );
			}
		} catch ( Exception $e ){
			$data = array( 'Message' => $e->getMessage(), 'Code' => $e->getCode(), 'Timestamp' => time() );
			return new WP_Error( 'exception', 'Exception occured while fetching product details', $data );
		}
	}

	public function admin_notice_invalid_settings(){

		$settings = get_option( self::OPTION_AMAZON_SETTINGS );
		$valid = $this->maybe_test_settings( $settings );

		if( ! $valid ){
			echo '<div class="afg-warning error amazon-settings"><p><strong>'
					.__('AffiGet setup is almost complete:', 'afg')
					.'</strong><br/>&nbsp;&nbsp;&nbsp;&nbsp;'.sprintf(
							__('Please enter %s for the Amazon Associates programme.', 'afg'),
							'<a href="'.admin_url('/options-general.php?page=affiget-settings-page#amazon-associates').'">'.__('your account details', 'afg').'</a>'
					).'</p></div>';
		}
	}
}

/* EOF */