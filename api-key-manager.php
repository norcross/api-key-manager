<?php
/*
Plugin Name: API Key Manager
Plugin URI: http://andrewnorcross.com/plugins/api-key-manager/
Description: Creates an admin page for storing multiple API keys.
Version: 1.0
Author: Andrew Norcross
Author URI: http://andrewnorcross.com

    Copyright 2012 Andrew Norcross

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Start up the engine
class API_Key_Manager
{

	/**
	 * This is our constructor
	 *
	 * @return API_Key_Manager
	 */
	public function __construct() {
		add_action		( 'admin_enqueue_scripts',		array( $this, 'scripts_styles'			), 10		);
		add_action		( 'admin_menu',					array( $this, 'api_manager_settings'	) 			);
		add_action		( 'admin_init', 				array( $this, 'reg_settings'			) 			);
		add_action		( 'admin_init', 				array( $this, 'key_cleanup'				) 			);
		add_action		( 'wp_ajax_save_api',			array( $this, 'save_api'				)			);
		add_filter		( 'plugin_action_links',		array( $this, 'quick_link'				), 10,	2	);
	}

	/**
	 * load textdomain for
	 *
	 * @return WP_FAQ_Manager
	 */


	public function textdomain() {

		load_plugin_textdomain( 'akm', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Scripts and stylesheets
	 *
	 * @return API_Key_Manager
	 */

	public function scripts_styles() {

		$current_screen = get_current_screen();
		if ( 'tools_page_api-manager' == $current_screen->base ) {
			wp_enqueue_style( 'api-manager', plugins_url('/lib/css/api.manager.css', __FILE__), array(), null, 'all' );
			wp_enqueue_script( 'api-manager', plugins_url('/lib/js/api.manager.init.js', __FILE__) , array('jquery'), '1.0', true );
		}

	}


	/**
	 * show settings link on plugins page
	 *
	 * @return API_Key_Manager
	 */

    public function quick_link( $links, $file ) {

		static $this_plugin;

		if (!$this_plugin) {
			$this_plugin = plugin_basename(__FILE__);
		}

    	// check to make sure we are on the correct plugin
    	if ($file == $this_plugin) {

			$settings_link	= '<a href="'.menu_page_url( 'api-manager', 0 ).'">'. __('API Keys', 'akm').'</a>';

        	array_unshift($links, $settings_link);
    	}

		return $links;

	}

	/**
	 * build out settings page and meta boxes
	 *
	 * @return API_Key_Manager
	 */

	public function api_manager_settings() {
	    add_submenu_page('tools.php', __('API Key Manager', 'akm'), __('API Key Manager', 'akm'), 'manage_options', 'api-manager', array( $this, 'api_manager_display' ));

	}

	/**
	 * Register settings
	 *
	 * @return API_Key_Manager
	 */


	public function reg_settings() {
		register_setting( 'apikeys', 'apikeys');
	}


	/**
	 * store array of keysets
	 *
	 * @return API_Key_Manager
	 */

	public function key_cleanup() {

		if (!current_user_can('manage_options') )
			return;

		if ( !isset($_POST['option_page']) )
			return;

		if ( $_POST['option_page'] !== 'apikeys')
			return;

		// we are in the API key manager page, clean up the array
		if ( $_POST['action'] == 'update') :

			$current	= get_option('apikeys');
			$updates	= array();

			$keynames	= $_POST['keyname'];
			$keyvalues	= $_POST['keyvalue'];

			$count		= count( $keynames );

			for ( $i = 0; $i < $count; $i++ ) {

				if ( $keynames[$i] != '' ) :
					$updates[$i]['keyname'] = stripslashes( strip_tags( $keynames[$i] ) );

					if ( $keyvalues[$i] != '' )
						$updates[$i]['keyvalue'] = stripslashes( $keyvalues[$i] );

				endif;
			}

			if ( !empty( $updates ) && $updates != $current )
				update_option( 'apikeys', $updates );

			elseif ( empty($updates) && $current )
				delete_option( 'apikeys' );


			// reload page to clear _POST values
			$url = menu_page_url( 'api-manager', 0 );
			wp_redirect( esc_url_raw( $url ), 301 );
			exit();

		// end repeatable stuff
		endif;


	}

	/**
	 * Display main options page structure
	 *
	 * @return API_Key_Manager
	 */

	public function api_manager_display() {
		if (!current_user_can('manage_options') )
			return;
		?>

		<div class="wrap">
    	<div class="icon32" id="icon-api-manager"><br></div>
		<h2><?php _e('API Key Manager') ?></h2>

        <div id="poststuff" class="metabox-holder has-right-sidebar">
		<?php
		echo $this->settings_side();
		echo $this->settings_open();
		?>

           	<div class="inner-form-text">
           	<p><?php _e('Enter your various API keys and their corresponding values.') ?></p>
            </div>

            <div class="inner-form-options">
	            <form method="post">

					<table id="api-key-rows" width="50%">
					<thead>
						<tr>
							<th width="44%"><?php _e('API Key Name') ?></th>
							<th width="44%"><?php _e('API Key Value') ?></th>
							<th width="4%"></th>
						</tr>
					</thead>
					<tbody>

			    <?php
                settings_fields( 'apikeys' );
				$apikeys = get_option('apikeys');

				if (!empty($apikeys) ) :
				foreach ($apikeys as $apikey) :
					$keyname	= !empty($apikey['keyname']) ? $apikey['keyname'] : '';
					$keyvalue	= !empty($apikey['keyvalue']) ? $apikey['keyvalue'] : '';
					echo '<tr class="api-key-row">';
						echo '<td><input type="text" class="widefat key-name" name="keyname[]" value="'.$keyname.'" /></td>';
                    	echo '<td><input type="text" class="widefat key-value" name="keyvalue[]" value="'.$keyvalue.'" /></td>';
                    	echo '<td><input type="button" name="Remove '.$keyname.'" class="remove-key" value="'. __('Remove') .'" /></td>';
					echo '</tr>';

				endforeach;
				else:
					// an empty one
	                echo '<tr class="api-key-row">';
	                	echo '<td><input type="text" class="widefat key-name" name="keyname[]" value="" /></td>';
	                	echo '<td><input type="text" class="widefat key-value" name="keyvalue[]" value="" /></td>';
	                	echo '<td><input type="button" class="remove-key" value="'. __('Remove') .'" /></td>';
					echo '</tr>';
				endif;

				?>

                <tr class="api-empty-row screen-reader-text">
                	<td><input type="text" class="widefat key-name" name="keyname[]" value="" /></td>
                	<td><input type="text" class="widefat key-value" name="keyvalue[]" value="" /></td>
                	<td><input type="button" class="remove-key" value="<?php _e('Remove') ?>" /></td>
				</tr>

				</tbody>
				</table>


                <input type="button" id="api-clone" class="button button-secondary" value="Add Row">

	    		<p><input type="submit" class="button-primary save-api" value="<?php _e('Save Keys') ?>" /></p>
				</form>

			</div>

           	<div class="inner-form-text">
           	<p><?php _e('To call a specific key value anywhere in a theme, use the following function') ?></p>
            <p><code>$mykey = akm_get_key('something');</code>
            </div>


	<?php echo $this->settings_close(); ?>

	</div>
	</div>

	<?php }

    /**
     * Some extra stuff for the settings page
     *
     * this is just to keep the area cleaner
     *
     * @return API_Key_Manager
     */

    public function settings_side() { ?>

		<div id="side-info-column" class="inner-sidebar">
			<div class="meta-box-sortables">
				<div id="admin-about" class="postbox akm-sidebox">
					<h3 class="hndle" id="about-sidebar"><?php _e('About the Plugin', 'akm'); ?></h3>
					<div class="inside">
						<p><?php _e('Talk to') ?> <a href="http://twitter.com/norcross" target="_blank">@norcross</a> <?php _e('on twitter or visit the', 'akm'); ?> <a href="http://wordpress.org/support/plugin/api-key-manager/" target="_blank"><?php _e('plugin support form') ?></a> <?php _e('for bugs or feature requests.', 'akm'); ?></p>
						<p><?php _e('<strong>Enjoy the plugin?</strong>', 'akm'); ?><br />
						<a href="http://twitter.com/?status=I'm using @norcross's API Key Manager plugin - check it out! http://l.norc.co/apikey/" target="_blank"><?php _e('Tweet about it', 'akm'); ?></a> <?php _e('and consider donating.', 'akm'); ?></p>
						<p><?php _e('<strong>Donate:</strong> A lot of hard work goes into building plugins - support your open source developers. Include your twitter username and I\'ll send you a shout out for your generosity. Thank you!', 'akm'); ?><br />
						<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
						<input type="hidden" name="cmd" value="_s-xclick">
						<input type="hidden" name="hosted_button_id" value="11085100">
						<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
						<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
						</form></p>
					</div>
				</div>
			</div>

			<div class="meta-box-sortables">
				<div id="admin-more" class="postbox">
					<h3 class="hndle" id="links-sidebar"><?php _e('Links', 'akm'); ?></h3>
					<div class="inside">
						<ul>
						<li><a href="http://wordpress.org/extend/plugins/api-key-manager/" target="_blank"><?php _e('Plugin on WP.org', 'akm'); ?></a></li>
						<li><a href="https://github.com/norcross/api-key-manager" target="_blank"><?php _e('Plugin on GitHub', 'akm'); ?></a></li>
						<li><a href="http://wordpress.org/support/plugin/api-key-manager/" target="_blank"><?php _e('Support Forum', 'akm'); ?></a><li>
            			</ul>
					</div>
				</div>
			</div>
		</div> <!-- // #side-info-column .inner-sidebar -->

    <?php }

	public function settings_open() { ?>

		<div id="post-body" class="has-sidebar">
			<div id="post-body-content" class="has-sidebar-content">
				<div id="normal-sortables" class="meta-box-sortables">
					<div class="postbox">
						<div class="inside">

    <?php }

	public function settings_close() { ?>

						<br class="clear" />
						</div>
					</div>
				</div>
			</div>
		</div>

    <?php }

/// end class
}


// Instantiate our class
$API_Key_Manager = new API_Key_Manager();


/**
 * function for theme to call keys
 *
 * @return API_Key_Manager
 */

function akm_get_key($key) {

	// no key sent? GO HOME
	if (empty($key))
		return;

	// get existing keys and bail if empty
	$apikeys = get_option('apikeys');
	if (empty($apikeys))
		return;

	// set an empty warning return first
	//$keyvalue = false;
	$keyvalue = 'NOKEYFOUND';

	foreach ($apikeys as $apikey) :

		if (in_array($key, $apikey)) {
   			$keyvalue = $apikey['keyvalue'];
   			break;
   		}

   	endforeach;

   	return $keyvalue;

}


