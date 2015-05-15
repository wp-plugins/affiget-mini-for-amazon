<?php
/**
 *
 */
class AffiGet_Admin_Bookmarklet
{
	protected $user_key;

	function __construct(){

		$this->user_key = get_option( AffiGet_Admin::OPTION_USER_KEY );
	}

	protected function get_bookmarklet_code(){

		ob_start();

?>
;(function(w, d, a){
var s;
if(typeof a.starter !== 'undefined'){ a.starter.run(); return; }
a.config = a.config || {};
a.config['homeUrl']   = '<?php echo get_bloginfo('wpurl'); ?>';
a.config['adminDir']  = '<?php echo admin_url(); ?>';
a.config['wpAjaxUrl'] = '<?php echo admin_url('admin-ajax.php'); ?>';
a.config['pluginDir'] = '<?php echo AFG_URL; ?>';
a.config['userKey']   = '<?php echo $this->user_key; ?>';
a.msg = a.msg || {};
a.msg['unavailable']  = '<?php esc_attr_e('Your site seems to be offline or misconfigured:', 'afg'); ?>';
a.msg['contactAdmin1']= '<?php esc_attr_e("It seems like you do not have the permissions \\nrequired to add/edit content on this site:", 'afg'); ?>';
a.msg['contactAdmin2']= '<?php esc_attr_e("For more details, contact your administrator.", 'afg'); ?>';
a.msg['notAmazonSite']= '<?php printf( esc_attr__("Use AffiGet to instantly post reviews to your blog\\nwhile browsing Amazon!\\n\\nThis button creates posts for your website\\n%s (%s).\\n\\nPlease navigate to a product page on Amazon to see it in action.", 'afg'), get_bloginfo('name'), home_url()); ?>';
a.msg['initializing'] = '<?php esc_attr_e("AffiGet is creating a product review for your site....", 'afg'); ?>';

s = d.createElement('script');
s.type    = 'text/javascript';
s.src     = a.config['pluginDir'] + 'dialog/js/affiget-starter.js?r='+Math.random();
s.onerror = function(e){alert(a.msg['unavailable']+'\n\n'+a.config['homeUrl']);d.getElementById('afg-loader').style.display='none';};
d.getElementsByTagName('head')[0].appendChild(s);
})(window, document, window.affiget = window.affiget || {});
<?php

		return ob_get_clean();
	}

	function settings_section(){

		if( ! current_user_can('edit_posts') ){
			echo '<p>';
			_e('You are not allowed to edit posts. Some features are disabled.', 'afg');
			echo '</p>';
 			return;
		} ?>

<div class="tool-box">
	<h3><?php esc_html_e('AffiGet bookmarklet', 'afg');?></h3>
	<p><?php _e('<em>Drag</em> the <b>Afg+ Button</b> you see below to your Bookmarks/Favorites toolbar.', 'afg'); ?>
	<br />
	</p>
	<table class="form-table"><tbody>
			<tr valign="top"><th scope="row"></th>
	<?php

	$label       = 'Afg+ ' . get_bloginfo('name');
	$description = __('Submit product data as a new draft post. Selected text will serve as an introduction.', 'afg');
	$bookmarklet = $this->get_bookmarklet_code();

?>
<td>
<div style="display: inline-block; padding: 25px 45px; margin-left: -45px; border: 1px dashed black;">
<p class="pressthis pressthis-bookmarklet-wrapper" style="display: inline;">
	<a class="pressthis-bookmarklet" title="<?php esc_attr_e( $description );?>"
		onclick="alert('<?php esc_attr_e('You have to drag me to your Bookmarks/Favorites toolbar first!', 'afg'); ?>'); return false;"
		oncontextmenu=""
		href="javascript:<?php echo $bookmarklet;?>"> <span><?php esc_html_e( $label ); ?></span>
	</a>
</p>
</div>
<br/>
<a style="margin-top:0.7em; margin-left: 1em; display:inline-block; font-style: italic; font-size: 0.8em;" id="instructions-handle" onclick="jQuery('#instructions-block').fadeIn('slow');jQuery(this).css('visibility','hidden');return false;">
<?php esc_html_e('Toolbar not showing?', 'afg'); ?>
</a>
</td></tr></tbody></table>
	<?php //echo '<pre>'.print_r( $bookmarklet, true ).'</pre>';?>
	<div id="instructions-block" style="display: none; margin-top:-2em;">
		<p><strong><?php esc_html_e('If your Bookmarks/Favorites toolbar is nowhere to be seen,', 'afg'); ?></strong> <?php esc_html_e('you have to unhide it first:', 'afg'); ?></p>
		<ul style="margin-top:-0.5em; margin-left: 2em;">
			<li><?php _e('<strong><i>FireFox:</i></strong> right-click anywhere on the top toolbar. Check "Bookmark toolbar".', 'afg');?></li>
			<li><?php _e('<strong><i>Chrome:</i></strong> in the top-right corner of the browser window, click the Chrome menu. Select Bookmarks > "Show Bookmarks Bar".', 'afg');?></li>
			<li><?php _e('<strong><i>Safari:</i></strong> go to the View menu and select "Show Bookmarks Bar" or "Show Favorites bar".', 'afg');?></li>
			<li><?php _e('<strong><i>Internet Explorer:</i></strong> Click the Tools button, point to Toolbars, and click "Favorites Bar" to mark it.', 'afg');?></li>
			<li><a style="font-size: 1em;" href="#"  onclick="jQuery('#instructions-block').hide();jQuery('#instructions-handle').css('visibility', 'visible');return false;"><?php esc_html_e('OK, got it!', 'afg');?></a></li>
		</ul>
	</div>
</div><!-- tool-box -->
	<?php
	}
}/* AffiGet_Admin_Bookmarklet */
/* EOF */