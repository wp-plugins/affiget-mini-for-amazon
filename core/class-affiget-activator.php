<?php
/**
 * Fired during plugin activation
 *
 * @link       http://affiget.com
 * @since      1.0.0
 *
 * @package    AffiGet
 * @subpackage AffiGet/core
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    AffiGet
 * @subpackage AffiGet/core
 * @author     Saru Tole <sarutole@affiget.com>
 */
class AffiGet_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		$user_key = get_option( AffiGet_Admin::OPTION_USER_KEY );
		if( false === $user_key ){
			$key = uniqid( $prefix = null, $more_entropy = true );
			update_option( AffiGet_Admin::OPTION_USER_KEY, $key );
		}
	}
}