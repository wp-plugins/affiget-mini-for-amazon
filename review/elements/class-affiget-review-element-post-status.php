<?php
if( ! defined ( 'ABSPATH' ) ) { exit; }

if( ! class_exists('AffiGet_Review_Element_Post_Status', false )):

class AffiGet_Review_Element_Post_Status extends AffiGet_Abstract_Element
{
	private $statuses;

	function __construct( AffiGet_Abstract_Meta $meta, $name, array $params ){

		parent::__construct( $meta, $name, $params ); //calls resolve_settings(), which calls get_settings_config()

		if( ! $this->is_status( AffiGet_Abstract_Element::STATUS_ENABLED ) ) return;

		$field = 'post_date_gmt';
		add_filter("afg_review_controller__read_post_fields__sanitize_{$field}", array(&$this, 'sanitize_post_date_gmt'), 10, 3);

		add_action('afg_review_controller__read_post_fields__validate', array(&$this, 'validate_post_fields'), 10, 4);

		add_filter('afg_review_controller__get_post_defaults', array(&$this, 'modify_post_defaults'), 10, 4 );

		add_action('afg_review_controller__inherit_from_latest_in_category', array(&$this, 'inherit_from_latest_in_category'), 10, 5 );

		add_action('afg_review_controller__prepare_review_data_init', array(&$this, 'set_auto_date'), 10, 2 );

		add_filter('afg_review_storage__apply_differences__preserve_fields', array(&$this, 'preserve_fields_when_updating'), 10, 1);

		add_action("afg_front__html_{$this->name}", array(&$this, 'front_html'), 10, 1);

		//<state><action><hint>
		$this->statuses = array(
				"private" => array( __('Saved privately', 'afg'),          __('Save privately', 'afg'),         __('Save privately', 'afg')),
				"draft"   => array( __('Saved as a draft', 'afg'),         __('Save as a draft', 'afg'),        __('Save as a draft', 'afg')),
				"pending" => array( __('Marked for review', 'afg'),        __('Save + Mark for review', 'afg'), __('Save + Mark for review', 'afg')),
				"future"  => array( __('Scheduled for publishing', 'afg'), __('Save + Make public', 'afg'),     __('Save + Make public', 'afg')),
				"publish" => array( __('Published', 'afg'),                __('Save + Make public', 'afg'),     __('Save + Make public', 'afg'))
		);
	}

	protected function get_settings_config(){

		$new_fields = array(
				'default_comment_status' => array(
						'name'    => 'default_comment_status',
						'atts'    => '',
						'type'    => 'dropdown',
						'default' => 'open-changeable',
						'options' => array(
								'open'              => __('Open', 'afg'),
								'closed'            => __('Closed', 'afg'),
								'open-changeable'   => __('Open, changeable', 'afg'),
								'closed-changeable' => __('Closed, changeable', 'afg'),
						),
						'label'   => __('Comment status', 'afg'),
						'hint'    => __('Default comment status, whether it can be changed on Afg+ dialog.'),
						'help'    => __(''),
				),
				'default_trackback_status' => array(
						'name'    => 'default_trackback_status',
						'atts'    => '',
						'type'    => 'dropdown',
						'default' => 'closed-changeable',
						'options' => array(
								'open'              => __('Open', 'afg'),
								'closed'            => __('Closed', 'afg'),
								'open-changeable'   => __('Open, changeable', 'afg'),
								'closed-changeable' => __('Closed, changeable', 'afg'),
						),
						'label'   => __('Trackback status', 'afg'),
						'hint'    => __('Default trackback status, whether it can be changed on Afg+ dialog.'),
						'help'    => __(''),
				),
				'schedule_mode' => array(
						'name'    => 'schedule_mode',
						'atts'    => '',
						'type'    => 'buttonset',
						'default' => 'now',
						'options' => array(
								'auto'  => __('Autoscheduled ("auto")', 'afg'),
								'now'   => __('Creation time ("now")', 'afg'),
								//'none'       => __('No link', 'afg'),//not supported in standard [gallery]!
						),
						'label'   => __('Post date', 'afg'),
						'hint'    => __('Date/time to assign to every new review', 'afg'),
						'help'    => __('', 'afg'),
				),
				'schedule_period_hours' => array(
						'name'    => 'schedule_period_hours',
						'atts'    => '',
						'type'    => 'dropdown',
						'default' => '24',
						'options' => array(
								'1'   => __('Every hour', 'afg'),
								'2'   => __('Every 2 hours', 'afg'),
								'3'   => __('Every 3 hours', 'afg'),
								'4'   => __('Every 4 hours', 'afg'),
								'5'   => __('Every 5 hours', 'afg'),
								'6'   => __('Every 6 hours', 'afg'),
								'7'   => __('Every 7 hours', 'afg'),
								'8'   => __('Every 8 hours', 'afg'),
								'12'  => __('Every 12 hours', 'afg'),
								'24'  => __('Every day', 'afg'),
								'48'  => __('Every 2nd day', 'afg'),
								'72'  => __('Every 3rd day', 'afg'),
								'168' => __('Every week', 'afg'),
								'720' => __('Every month', 'afg'),
						),
						'label'   => __('Auto-post frequency', 'afg'),
						'hint'    => __('Post frequency for auto-scheduling'),
						'help'    => __(''),
				),
				'schedule_start_time' => array(
						'name'    => 'schedule_start_time',
						'atts'    => '',
						'type'    => 'dropdown',
						'default' => '8',
						'options' => array(
								'6'  => __(' 6:00 AM', 'afg'),
								'7'  => __(' 7:00 AM', 'afg'),
								'8'  => __(' 8:00 AM', 'afg'),
								'9'  => __(' 9:00 AM', 'afg'),
								'10' => __('10:00 AM', 'afg'),
								'11' => __('11:00 AM', 'afg'),
								'12' => __('12:00 PM', 'afg'),
								'13' => __(' 1:00 PM', 'afg'),
								'14' => __(' 2:00 PM', 'afg'),
								'15' => __(' 3:00 PM', 'afg'),
								'16' => __(' 4:00 PM', 'afg'),
								'17' => __(' 5:00 PM', 'afg'),
								'18' => __(' 6:00 PM', 'afg'),
						),
						'label'   => __('No auto-posts before', 'afg'),
						'hint'    => __('No auto-posts will be scheduled BEFORE this hour.', 'afg'),
						'help'    => __(''),
				),
				'schedule_end_time' => array(
						'name'    => 'schedule_end_time',
						'atts'    => '',
						'type'    => 'dropdown',
						'default' => '18',
						'options' => array(
								'12' => __('12:00 PM', 'afg'),
								'13' => __(' 1:00 PM', 'afg'),
								'14' => __(' 2:00 PM', 'afg'),
								'15' => __(' 3:00 PM', 'afg'),
								'16' => __(' 4:00 PM', 'afg'),
								'17' => __(' 5:00 PM', 'afg'),
								'18' => __(' 6:00 PM', 'afg'),
								'19' => __(' 7:00 PM', 'afg'),
								'20' => __(' 8:00 PM', 'afg'),
								'21' => __(' 9:00 PM', 'afg'),
								'22' => __('10:00 PM', 'afg'),
								'23' => __('11:00 PM', 'afg'),
								'24' => __('12:00 AM', 'afg'),
						),
						'label'   => __('No auto-posts after', 'afg'),
						'hint'    => __('No auto-posts will be scheduled AFTER this hour', 'afg'),
						'help'    => __(''),
				),
				'default_action' => array(
						'name'    => 'default_action',
						'atts'    => '',
						'type'    => 'dropdown',
						'default' => 'publish',
						'options' => array(
								'private' => __('Save privately', 'afg'),
								'draft'   => __('Save as a draft', 'afg'),
								'pending' => __('Save + Mark for review', 'afg'),
								'publish' => __('Save + Make public', 'afg'),
						),
						'label'   => __('Default status for first save', 'afg'),
						'hint'    => __('Suggested status for a review when it gets saved for the first time', 'afg'),
						'help'    => __(''),
				),

		);

		return array_merge( parent::get_settings_config(), $new_fields );
	}

	function modify_post_defaults( $defaults, $post_type_name, $action, $product_code ){

		//if( $this->meta->post_type_name == $post_type_name ){
			$defaults['comment_status'] = str_replace('-changeable', '', $this->settings['default_comment_status']);
			$defaults['ping_status']    = str_replace('-changeable', '', $this->settings['default_trackback_status']);
		//}
		return $defaults;
	}

	function inherit_from_latest_in_category( $post_id, $product_data, $is_new, $cats, $prototype_post_id ){

		$post_arr = get_post( $post_id, ARRAY_A );
		//afg_log( __METHOD__, compact('post_id', 'prototype_post_id', 'post_arr' ));

		$mode = 'auto';
		if( $prototype_post_id ){
			$latest = get_post( $prototype_post_id );
			$post_arr['comment_status'] = $latest->comment_status;
			$post_arr['ping_status']    = $latest->ping_status;

			//if prototype has autodate overriden, do not use scheduling on current post
			if( get_post_meta( $prototype_post_id, AFG_META_PREFIX. 'auto_date_gmt', true) !== $latest->post_date_gmt ){
				$mode = 'now';
			}
		} else {
			$post_arr['comment_status'] = $this->settings['default_comment_status'];
			$post_arr['ping_status']    = $this->settings['default_trackback_status'];
			$mode                       = $this->settings['schedule_mode'];
		}

		if( 'auto' == $mode ){
			//if autodate not yet assigned
			$auto_date_gmt = get_post_meta( $post_id, AFG_META_PREFIX. 'auto_date_gmt', true);
			if( $post_arr['post_date_gmt'] !== $auto_date_gmt ){
				$post_arr['post_date_gmt']     = $auto_date_gmt;
				$post_arr['post_date']         = get_date_from_gmt( $auto_date_gmt );
				$post_arr['post_modified_gmt'] = date('Y-m-d H:i:00' );
				$post_arr['post_modified']     = get_date_from_gmt( $post_arr['post_modified_gmt'] );
			}
		} else {
			//overwrite, in case autodate was initially assigned
			$post_arr['post_date_gmt']     = date('Y-m-d H:i:00' );
			$post_arr['post_date']         = get_date_from_gmt( $post_arr['post_date_gmt'] );
			$post_arr['post_modified_gmt'] = $post_arr['post_date_gmt'];
			$post_arr['post_modified']     = $post_arr['post_date'];
		}

		$result = wp_update_post( $post_arr );
		if( is_wp_error( $result ) ){
			afg_log( __METHOD__, $result );
		}

		return $post_id;
	}

	function set_auto_date( array &$review_data, $defaults ){

		$auto_timestamp = $this->get_autoschedule_timestamp();
		$auto_date_gmt  = date('Y-m-d H:i:00', $auto_timestamp ); //drop seconds if any

		$review_data['meta_fields']['auto_date_gmt'] = $auto_date_gmt;

		//adjust post dates
		if( 'auto' == $this->settings['schedule_mode'] ){
			$review_data['post_fields']['post_date_gmt']     = $auto_date_gmt;
			$review_data['post_fields']['post_date']         = get_date_from_gmt( $auto_date_gmt );
			$review_data['post_fields']['post_modified_gmt'] = date('Y-m-d H:i:00' );
			$review_data['post_fields']['post_modified']     = get_date_from_gmt( $review_data['post_fields']['post_modified_gmt'] );
		} else {
			$review_data['post_fields']['post_date_gmt']     = date('Y-m-d H:i:00' );
			$review_data['post_fields']['post_date']         = get_date_from_gmt( $review_data['post_fields']['post_date_gmt'] );
			$review_data['post_fields']['post_modified_gmt'] = $review_data['post_fields']['post_date_gmt'];
			$review_data['post_fields']['post_modified']     = $review_data['post_fields']['post_date'];
		}
	}

	function sanitize_post_date_gmt( $value, $post_id = '', $context = 'raw' ){

		//if we get a unix timestamp, we need to convert it to a WP date
		if( is_int( $value )){
			return date('Y-m-d H:i:00', $value );
		}
		//calling standard WP function:
		return sanitize_post_field( 'post_date_gmt', $value, $post_id, $context );
	}

	function validate_post_fields( array &$post_fields, $post_id, array $defaults, $context = 'raw' ){

		//echo '<pre>';
		//print_r( $post_fields );

		//gmt time is canonical
		if( isset( $post_fields['post_date_gmt'] )){
			$post_fields['post_date']     = get_date_from_gmt( $post_fields['post_date_gmt'] );
		} elseif( isset( $post_fields['post_date'] )){
			$post_fields['post_date_gmt'] = get_gmt_from_date( $post_fields['post_date'] );
		}

		if( ! $post_fields['post_name'] ){
			//calculate from title
			$post_fields['post_name'] = sanitize_title_with_dashes( $post_fields['post_title'], '', 'save' );
		}
	}

	function preserve_fields_when_updating( $fields_to_preserve ){

		//auto_date_gmt is not to be changed on by the user
		//so we do not return it from the afg dialog
		//and it does not need to be updated

		$fields_to_preserve[] = 'auto_date_gmt';

		return $fields_to_preserve;
	}

	//
	// Backend function for ajax calls
	//
	function autoschedule(){

		$post_id = false;

		if( isset( $_REQUEST['post_id'] ) && 0 < absint( $_REQUEST['post_id'] )){
			$post_id = absint( $_REQUEST['post_id'] );
		}

		$gmt = isset( $_REQUEST['gmt'] ) ? ('true' == $_REQUEST['gmt']) : false;

		echo date( 'D M j Y H:i:00', $this->get_autoschedule_timestamp( $post_id, $gmt ) );
		die();
	}

	static function get_local_datetime( $gmt_datetime ){

		$offset = (int) (60 * ((float) get_option('gmt_offset', 0)));//offset might be a float value
		if( $offset > 0){
			$offset = '+'.$offset. ' minutes';
		} else {
			$offset = '-'.$offset. ' minutes';
		}
		return $gmt_datetime->modify( $offset );
	}

	function get_autoschedule_timestamp( $post_id = false, $gmt = true ){

		if( $post_id ){
			//get from meta
			$date = get_post_meta( $post_id, AFG_META_PREFIX . 'auto_date_gmt', true );
			if( $date ){
				if( $gmt ){
					//echo "GMT: [$gmt]".$date;
					return strtotime( $date );
				}
				//echo "Not GMT for: ".$date;
				return self::get_local_datetime( new DateTime( $date ) )->format('U');
			} //else {
			//	echo 'Found no meta ['.AFG_META_PREFIX . 'auto_date_gmt'. '] for '. $post_id;
			//}
		}

		//if post_id not specified or there is no meta for specified post, calculate a new date
		$opts = array(
				'period'    => intval( $this->settings['schedule_period_hours'] ),
				'startTime' => intval( $this->settings['schedule_start_time'] ),
				'endTime'   => intval( $this->settings['schedule_end_time'] )
		);

		$params = array(
				'numberposts'  => 1,
				'orderby'      => 'post_date_gmt',
				'order'        => 'DESC',
				'post_type'    => $this->meta->post_type_name,
				'post_status'  => array('private','draft','pending','future','publish'),
		);
		$posts = get_posts( $params );

		//echo '<pre>'.print_r($posts, true).'</pre>';

		if( ! isset( $posts[0] )) {
			$lastPostDate = 0;
		} else {
			$lastPostDate = strtotime( $posts[0]->post_date );
		}
		$currentTime = strtotime( current_time('mysql') );
		//echo '<pre>'.print_r($currentTime, true).'</pre><br/>';

		$startingDate = max( $lastPostDate, $currentTime );
		//echo 'Starting date:<br/><pre>'.print_r(date('D M j Y H:i:s',$startingDate), true).'</pre><br/>';

		//randomize minutes
		$randMins   = rand(0, 30) - 15;

		//calculate new time
		$datetime = $startingDate + $opts['period'] * 60 + $randMins;

		//normalize new timestamp to minutes
		$mins = $this->get_mins_from_string( date('0:G:i', $datetime )); //G returns hours in 0-23 range

		//adjust time if it does not fit within the hours specified by startTime - endTime
		//echo $mins .' - ' . $randPeriod . ' - ' . $randMins . ' - '.date('D M j Y H:i:s', $datetime);

		$offset = (int) (60 * ((float) get_option('gmt_offset', 0)));//offset might be a float value

		if( $mins > $opts['endTime'] ) {
			//Too late! Start from next day, and start from startTime + random minutes
			//echo 'Too late for '.$opts['endTime'].': <br/>'.date('D M j Y H:i:s', $datetime).'<br/>';

			$datetime += 60 * 24 * 60; // add a day
			$time = $this->get_time_string( $opts['startTime']*60 + $randMins - $offset);

			$datetime = strtotime( date('Y-M-d', $datetime). ' ' . $time );
			//echo '2) ' . date('D M j Y H:i:s', $datetime) . '; ';

		} elseif( $mins < $opts['startTime'] ) {
			//Too early! Start from startTime + some random minutes
			//echo 'Too early:  <br/>'.date('D M j Y H:i:s', $mins - $offset).'<br/>';


			$day  = strtotime( date('Y-M-d', $datetime) );
			$time = $opts['startTime']*60 + $randMins - $offset;
			$datetime = $day + $time;

		} else {
			$datetime -= ($offset * 60);
		}

		if( $gmt ){
			return $datetime;
		} else {
			//echo '5) [' . date('Y-M-d H:i:s', $datetime). '], [@' . $datetime . ']; ';
			return self::get_local_datetime( new DateTime( '@'.$datetime ) )->format('U');
		}
	}

	//get the number of minutes in a string formatted like 1:23:45 (day:hours:mins)
	//Credits: http://www.nutt.net/tag/auto-future-date/
	protected function get_mins_from_string( $str ) {

		$ray = preg_split('/:|\./', $str);
		if( count( $ray ) > 3 ) {
			//drop all elements beyond third
			$ray = array_slice( $ray, count( $ray ) - 3 );
		}
		while ( count( $ray ) < 3 ) {
			//pad front with zeros
			array_unshift( $ray, 0 );
		}

		return intval( $ray[2] ) + (60 * intval( $ray[1] )) + (60 * 24 * intval( $ray[0] ));
	}

	protected function get_time_string( $num ) {

		$num = intval( $num );
		$d = floor($num / 1440);
		$h = floor(($num - $d * 1440) / 60);
		$m = $num - ($d * 1440) - ($h * 60);

		//return $d.':'.str_pad($h, 2, '0', STR_PAD_LEFT).':'.str_pad($m, 2, '0', STR_PAD_LEFT);

		//ignore days:
		return str_pad($h, 2, '0', STR_PAD_LEFT).':'.str_pad($m, 2, '0', STR_PAD_LEFT).':00';
	}

	//default implementation of how the element will be represented on the front-end
	function front_html( array $review_data ){

		if( empty( $review_data )) return;

		$element_name = $this->name;

		//although element 'knows' how to pick the data needed to present itself on the front,
		//it is advisable to abstract away from it
		$value = $this->meta->pick_value( $review_data, 'post_status' );

		if( ! $value ) return;

		$result = '<div class="afg-element simple ' . $element_name . '">';

		if( $this->settings['title'] ){
			$result .= apply_filters("afg_front__html_{$element_name}_title", '<h4>'.esc_html( $this->settings['title'] ).'</h4>');
		}

		$result .= apply_filters("afg_front__html_{$element_name}_content", esc_html( $this->statuses[ $value ][0] ));

		$result .= '</div>';

		echo apply_filters("afg_front__html_{$element_name}_result", $result );
	}
}

endif;

/* EOF */