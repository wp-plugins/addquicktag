<?php
/*
Plugin Name: AddQuicktag
Version: 1.5.4
Plugin URI: http://bueltge.de/wp-addquicktags-de-plugin/120/
Description: Allows you to easily add custom Quicktags to the editor. You can also export and import your Quicktags.
Author: <a href="http://roel.meurders.nl/" >Roel Meurders</a> and <a href="http://bueltge.de" >Frank Bueltge</a>
Last Change: 30.10.2008 20:25:10
*/

// SCRIPT INFO ///////////////////////////////////////////////////////////////////////////
/*
	WP-AddQuicktag for WordPress is in originally by 
	(C) 2005 Roel Meurders - GNU General Public License

	AddQuicktag is an newer version with more functions and worked in WP 2.1
	(C) 2007 Frank Bueltge

	This Wordpress plugin is released under a GNU General Public License. A complete version of this license
	can be found here: http://www.gnu.org/licenses/gpl.txt

	This Wordpress plugin has been tested with Wordpress 2.0, 2.1 and Wordpress 2.3;

	This Wordpress plugin is released "as is". Without any warranty. The authors cannot
	be held responsible for any damage that this script might cause.

*/

// Pre-2.6 compatibility
if ( !defined('WP_CONTENT_URL') )
	define( 'WP_CONTENT_URL', get_option('url') . '/wp-content');
if ( !defined('WP_CONTENT_DIR') )
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );

define( 'WP_CONTENT_FOLDER', str_replace(ABSPATH, '/', WP_CONTENT_DIR) );

function wpaq_textdomain() {

	if (function_exists('load_plugin_textdomain')) {
		if ( !defined('WP_PLUGIN_DIR') ) {
			load_plugin_textdomain('addquicktag', str_replace( ABSPATH, '', dirname(__FILE__) ) );
		} else {
			load_plugin_textdomain('addquicktag', false, dirname(plugin_basename(__FILE__)) );
		}
	}
}


// install options in table _options
function wpaq_install() {
	global $wpdb;

	if (get_option('rmnlQuicktagSettings') == '') {
		$name        = 'rmnlQuicktagSettings';
		$value       = 'a:1:{s:7:"buttons";a:1:{i:0;a:3:{s:4:"text";s:7:"Example";s:5:"start";s:9:"<example>";s:3:"end";s:10:"</example>";}}}';
		$autoload    = 'yes';
		$wpdb->query("INSERT INTO $wpdb->options (option_name, option_value, autoload) VALUES ('$name', '$value', '$autoload')");
	}
}


// unsinstall options in table options
function wpaq_uninstall() {
	
	delete_option('rmnlQuicktagSettings');
}


// options-page in wp-backend
function wpaq_options_page() {
	global $wpdb, $wp_version;
	
	$wpaq_document_root = $_SERVER['DOCUMENT_ROOT'] . $_SERVER['REQUEST_URI'];
	$wpaq_document_root = str_replace("/wp-admin/options-general.php?page=addquicktag.php", WP_CONTENT_FOLDER, $wpaq_document_root);

	$wpaq_link = $_SERVER['REQUEST_URI'];
	$wpaq_link = str_replace("\\", "/", $wpaq_link);
	
	if ($_POST['wpaq']) {
		if ( function_exists('current_user_can') && current_user_can('edit_plugins') ) {

			check_admin_referer('rmnl_nonce');
			$buttons = array();
			for ($i = 0; $i < count($_POST['wpaq']['buttons']); $i++){
				$b = $_POST['wpaq']['buttons'][$i];
				if ($b['text'] != '' && $b['start'] != '') {
					$b['text']    = ($b['text']);
					$b['start']   = stripslashes($b['start']);
					$b['end']     = stripslashes($b['end']);
					$buttons[]    = $b;
				}
			}
			$_POST['wpaq']['buttons'] = $buttons;
			update_option('rmnlQuicktagSettings', $_POST['wpaq']);
			$message = '<br class="clear" /><div class="updated fade"><p><strong>' . __('Options saved.', 'addquicktag') . '</strong></p></div>';

		} else {
			wp_die('<p>'.__('You do not have sufficient permissions to edit plugins for this blog.').'</p>');
		}
	}
	
	// Export sql-option
	if (($_POST['action'] == 'export')) {
		if ( function_exists('current_user_can') && current_user_can('edit_plugins') ) {

			check_admin_referer('rmnl_nonce');
			$wpaq_data = mysql_query("SELECT option_value FROM $wpdb->options WHERE option_name = 'rmnlQuicktagSettings'");
			$wpaq_data = mysql_result($wpaq_data, 0);
			$file_name = $wpaq_document_root . '/wpaq_export-' . date('Y-m-d_G-i-s') . '.wpaq';
			$file_name = str_replace("//", "/", $file_name);
			$fh        = @ fopen($file_name, 'w');
			
			if ($fh == false) {
				$message_export = '<br class="clear" /><div class="error"><p><strong>' . __('Can not open for write!', 'addquicktag') . ' - ' . get_bloginfo('url') . WP_CONTENT_FOLDER . '</strong></p></div>';
			} else {
				@flock($fh, LOCK_EXCLUSIVE);
				$err = @fputs($fh, $wpaq_data);
				@fclose($fh);
		
				if ($err === false) {
					$message_export = '<br class="clear" /><div class="error"><p><strong>' . __('Can not write!', 'addquicktag') . '</strong></p></div>';
				}
		
				$message_export = '<br class="clear" /><div class="updated fade"><p><strong>' . __('AddQuicktag options saved!', 'addquicktag') . '</strong><br />';
				$message_export.= __('Saved in: ', 'addquicktag') . $file_name;
				$message_export.= '</p></div>';
			}

		} else {
			wp_die('<p>'.__('You do not have sufficient permissions to edit plugins for this blog.').'</p>');
		}
	}

	// Import the sql-file
	if (($_POST['action'] == 'import')) {
		if ( function_exists('current_user_can') && current_user_can('edit_plugins') ) {

			check_admin_referer('rmnl_nonce');
			$message_export = '<br class="clear" /><div class="updated fade"><p>';
	
			// check file extension sql
			$str_file_name = $_FILES['datei']['name'];
			$str_file_ext  = explode(".", $str_file_name);
	
			if ($str_file_ext[1] != 'wpaq') {
				$message_export.= __('Invalid file extension!', 'addquicktag');
			} elseif (file_exists($_FILES['datei']['name'])) {
				$message_export.= __('File is exist!', 'addquicktag');
			} else {
		    // path for file
		    $wpaq_document_root = str_replace("/wp-admin/options-general.php?page=addquicktag.php", WP_CONTENT_FOLDER, $wpaq_document_root);
		    $str_ziel = $wpaq_document_root . '/' . $_FILES['datei']['name'];
		    $str_ziel = str_replace("//", "/", $str_ziel);
		    // transfer
		    move_uploaded_file($_FILES['datei']['tmp_name'], $str_ziel);
		    // 	access authorisation
		    chmod($str_ziel, 0644);
				// SQL import
				ini_set('default_socket_timeout', 120);  
				$import_file = file_get_contents($str_ziel);
				$wpdb->query("UPDATE $wpdb->options SET `option_value` = '$import_file' WHERE `option_name` = 'rmnlQuicktagSettings'");
				unlink($str_ziel);
				$message_export.= __('AddQuicktag options imported!', 'addquicktag');
			}
			$message_export.= '</p></div>';

		} else {
			wp_die('<p>'.__('You do not have sufficient permissions to edit plugins for this blog.').'</p>');
		}
	}

	// Uninstall options
	if (($_POST['action'] == 'uninstall')) {
		if ( function_exists('current_user_can') && current_user_can('edit_plugins') ) {

			check_admin_referer('rmnl_nonce');
			wpaq_uninstall();
			$message_export = '<br class="clear" /><div class="updated fade"><p>';
			$message_export.= __('AddQuicktag options have been deleted!', 'addquicktag');
			$message_export.= '</p></div>';

		} else {
			wp_die('<p>'.__('You do not have sufficient permissions to edit plugins for this blog.').'</p>');
		}
	}
	
	$string1 = __('Add or delete Quicktag buttons', 'addquicktag');
	$string2 = __('Fill in the fields below to add or edit the quicktags. Fields with * are required. To delete a tag simply empty all fields.', 'addquicktag');
	$field1  = __('Button Label*', 'addquicktag');
	$field2  = __('Start Tag(s)*', 'addquicktag');
	$field3  = __('End Tag(s)', 'addquicktag');
	$button1 = __('Update Options &raquo;', 'addquicktag');

	// Export strings
	$button2 = __('Export &raquo;', 'addquicktag');
	$export1 = __('Export Quicktag buttons options', 'addquicktag');
	$export2 = __('You can save a .wpaq file with your options in <em>', 'addquicktag') . WP_CONTENT_FOLDER . __('/wpaq_export.wpaq</em>', 'addquicktag');

	// Import strings
	$button3 = __('Upload file and import &raquo;', 'addquicktag');
	$import1 = __('Import Quicktag buttons options', 'addquicktag');
	$import2 = __('Choose a Quicktag (<em>.wpaq</em>) file to upload, then click <em>Upload file and import</em>.', 'addquicktag');
	$import3 = __('Choose a file from your computer: ', 'addquicktag');

	// Uninstall strings
	$button4    = __('Uninstall Options &raquo;', 'addquicktag');
	$uninstall1 = __('Uninstall options', 'addquicktag');
	$uninstall2 = __('This button deletes all options of the WP-AddQuicktag plugin. <strong>Attention: </strong>You cannot undo this!', 'addquicktag');

	// Info
	$info0   = __('About the plugin', 'addquicktag');
	$info1   = __('Further information: Visit the <a href=\'http://bueltge.de/wp-addquicktags-de-plugin/120\'>plugin homepage</a> for further information or to grab the latest version of this plugin.', 'addquicktag');
	$info2   = __('You want to thank me? Visit my <a href=\'http://bueltge.de/wunschliste/\'>wishlist</a>.', 'addquicktag');

	$o       = get_option('rmnlQuicktagSettings');
	?>
	<div class="wrap">
		<h2><?php _e('WP-Quicktag Management', 'wpaq'); ?></h2>
		<?php _e($message . $message_export); ?>
		<br class="clear" />
		<div id="poststuff" class="ui-sortable">
			<div class="postbox">
				<h3><?php _e($string1); ?></h3>
				<div class="inside">
					<br class="clear" />
					<form name="form1" method="post" action="">
						<?php wp_nonce_field('rmnl_nonce'); ?>
						<table summary="rmnl" class="widefat">
							<thead>
								<tr>
									<th scope="col"><?php _e($field1); ?></th>
									<th scope="col"><?php _e($field2); ?></th>
									<th scope="col"><?php _e($field3); ?></th>
								</tr>
							</thead>
							<tbody>
	<?php
		for ($i = 0; $i < count($o['buttons']); $i++) {
			$b          = $o['buttons'][$i];
			$b['text']  = htmlentities(stripslashes($b['text']), ENT_COMPAT, get_option('blog_charset'));
			$b['start'] = htmlentities($b['start'], ENT_COMPAT, get_option('blog_charset'));
			$b['end']   = htmlentities($b['end'], ENT_COMPAT, get_option('blog_charset'));
			$nr         = $i + 1;
			echo '
					<tr valign="top">
						<td><input type="text" name="wpaq[buttons][' . $i . '][text]" value="' . $b['text'] . '" style="width: 95%;" /></td>
						<td><textarea class="code" name="wpaq[buttons][' . $i . '][start]" rows="2" cols="25" style="width: 95%;">' . $b['start'] . '</textarea></td>
						<td><textarea class="code" name="wpaq[buttons][' . $i . '][end]" rows="2" cols="25" style="width: 95%;">' . $b['end'] . '</textarea></td>
					</tr>
			';
		}
		?>
								<tr valign="top">
									<td><input type="text" name="wpaq[buttons][<?php _e( $i ); ?>][text]" value="" tyle="width: 95%;" /></td>
									<td><textarea class="code" name="wpaq[buttons][<?php _e( $i ); ?>][start]" rows="2" cols="25" style="width: 95%;"></textarea></td>
									<td><textarea class="code" name="wpaq[buttons][<?php _e( $i ); ?>][end]" rows="2" cols="25" style="width: 95%;"></textarea></td>
								</tr>
							</tbody>
						</table>
						<br class="clear" />
						<p><?php _e($string2); ?></p>
						<p class="submit">
							<input class="button" type="submit" name="Submit" value="<?php _e( $button1 ); ?>" />
						</p>
					</form>
		
				</div>
			</div>
		</div>
		
		<div id="poststuff" class="ui-sortable">
			<div class="postbox closed">
				<h3><?php _e($export1); ?></h3>
				<div class="inside">
				
					<form name="form2" method="post" action="options-general.php?page=addquicktag.php">
						<?php wp_nonce_field('rmnl_nonce'); ?>
						<p><?php _e($export2); ?></p>
						<br class="clear" />
						<p id="submitbutton">
							<input class="button" type="submit" name="Submit_export" value="<?php _e($button2); ?>" /> 
							<input type="hidden" name="action" value="export" />
						</p>
					</form>

				</div>
			</div>
		</div>

		<div id="poststuff" class="ui-sortable">
			<div class="postbox closed">
				<h3><?php _e($import1); ?></h3>
				<div class="inside">

					<form name="form3" enctype="multipart/form-data" method="post" action="">
						<?php wp_nonce_field('rmnl_nonce'); ?> 
						<p><?php _e($import2); ?></p>
						<p>
							<label for="datei_id"><?php _e($import3); ?></label>
							<input name="datei" id="datei_id" type="file" />
						</p>
						<br class="clear" />
						<p id="submitbutton">
							<input class="button" type="submit" name="Submit_import" value="<?php _e($button3); ?>" />
							<input type="hidden" name="action" value="import" />
						</p>
					</form>
					
				</div>
			</div>
		</div>
		
		<div id="poststuff" class="ui-sortable">
			<div class="postbox closed">
				<h3><?php _e($uninstall1); ?></h3>
				<div class="inside">
					
					<form name="form4" method="post" action="">
						<?php wp_nonce_field('rmnl_nonce'); ?>
						<p><?php _e($uninstall2); ?></p>
						<br class="clear" />
						<p id="submitbutton">
							<input class="button" type="submit" name="Submit_uninstall" value="<?php _e($button4); ?>" /> 
							<input type="hidden" name="action" value="uninstall" />
						</p>
					</form>
					
				</div>
			</div>
		</div>
		
		<div id="poststuff" class="ui-sortable">
			<div class="postbox closed">
				<h3><?php _e($info0); ?></h3>
				<div class="inside">

					<p><?php _e($info1); ?><br />&copy; Copyright 2007 - <?php _e( date("Y") ); ?> <a href="http://bueltge.de">Frank B&uuml;ltge</a> | <?php _e($info2); ?></p>
			
				</div>
			</div>
		</div>
		
		<script type="text/javascript">
		<!--
		<?php if ( version_compare( substr($wp_version, 0, 3), '2.7', '<' ) ) { ?>
		jQuery('.postbox h3').prepend('<a class="togbox">+</a> ');
		<?php } ?>
		jQuery('.postbox h3').click( function() { jQuery(jQuery(this).parent().get(0)).toggleClass('closed'); } );
		jQuery('.postbox.close-me').each(function(){
			jQuery(this).addClass("closed");
		});
		//-->
		</script>
		
	</div>
<?php
} //End function wpaq_options_page


// only for post.php, page.php, post-new.php, page-new.php, comment.php
if (strpos($_SERVER['REQUEST_URI'], 'post.php') || strpos($_SERVER['REQUEST_URI'], 'post-new.php') || strpos($_SERVER['REQUEST_URI'], 'page-new.php') || strpos($_SERVER['REQUEST_URI'], 'page.php') || strpos($_SERVER['REQUEST_URI'], 'comment.php')) {
	add_action('admin_footer', 'wpaq_addsome');

	function wpaq_addsome() {
		$o = get_option('rmnlQuicktagSettings');
		if (count($o['buttons']) > 0) {
			echo '
				<script type="text/javascript">
					<!--
					if (wpaqToolbar = document.getElementById("ed_toolbar")) {
						var wpaqNr, wpaqBut, wpaqStart, wpaqEnd;
			';
						for ($i = 0; $i < count($o['buttons']); $i++) {
							$b = $o['buttons'][$i];
							$txt = html_entity_decode(stripslashes($b['txt']), ENT_COMPAT, get_option('blog_charset'));
							$text = stripslashes($b['text']);
							$b['text'] = stripslashes($b['text']);
							$start = preg_replace('![\n\r]+!', "\\n", $b['start']);
							$start = str_replace("'", "\'", $start);
							$end = preg_replace('![\n\r]+!', "\\n", $b['end']);
							$end = str_replace("'", "\'", $end);
							echo '
								wpaqStart = \'' . $start . '\';
								wpaqEnd = \'' . $end . '\';
								wpaqNr = edButtons.length;
								edButtons[wpaqNr] = new edButton(\'ed_wpaq' . $i . '\', \'' . $b['txt'] . '\', wpaqStart, wpaqEnd,\'\');
								var wpaqBut = wpaqToolbar.lastChild;
								while (wpaqBut.nodeType != 1) {
									wpaqBut = wpaqBut.previousSibling;
								}
								wpaqBut = wpaqBut.cloneNode(true);
								wpaqToolbar.appendChild(wpaqBut);
								wpaqBut.value = \'' . $b['text'] . '\';
								wpaqBut.title = wpaqNr;
								wpaqBut.onclick = function () {edInsertTag(edCanvas, parseInt(this.title));}
								wpaqBut.id = "ed_wpaq' . $i .'";
							';
						}
				echo '
					}

					//-->
				</script>
				';
		}
	} //End wpaq_addsome
} // End if


// add to wp
if ( function_exists('register_activation_hook') )
	register_activation_hook(__FILE__, 'wpaq_install');
if ( function_exists('register_uninstall_hook') )
	register_uninstall_hook(__FILE__, 'wpaq_uninstall');
if ( is_admin() ) {
	add_action('init', 'wpaq_textdomain');
	add_action('admin_menu', 'wpaq_add_settings_page');
	add_action('in_admin_footer', 'wpaq_admin_footer');
}


/**
 * Add action link(s) to plugins page
 * Thanks Dion Hulse -- http://dd32.id.au/wordpress-plugins/?configure-link
 */
function wpaq_filter_plugin_actions($links, $file){
	static $this_plugin;

	if( ! $this_plugin ) $this_plugin = plugin_basename(__FILE__);

	if( $file == $this_plugin ){
		$settings_link = '<a href="options-general.php?page=addquicktag.php">' . __('Settings') . '</a>';
		$links = array_merge( array($settings_link), $links); // before other links
	}
	return $links;
}


/**
 * Images/ Icons in base64-encoding
 * @use function wpag_get_resource_url() for display
 */
if( isset($_GET['resource']) && !empty($_GET['resource'])) {
	# base64 encoding performed by base64img.php from http://php.holtsmark.no
	$resources = array(
		'addquicktag.gif' =>
		'R0lGODlhCwAJALMPAPL19Y2cnLzNzZempsXV1VpfX6WysrS/v5'.
		'+trXmDg9Xh4drr66W5uay6urnHx////yH5BAEAAA8ALAAAAAAL'.
		'AAkAAARA8D0gmBMESMUIK0XAVNzQOE6QCIJhIMOANMRCHG+MuI'.
		'5yG4PAzjDyORqyxKwh8AlUAEUiQVswqBINIHEIHCSPCAA7'.
		'');
	
	if(array_key_exists($_GET['resource'], $resources)) {

		$content = base64_decode($resources[ $_GET['resource'] ]);

		$lastMod = filemtime(__FILE__);
		$client = ( isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false );
		// Checking if the client is validating his cache and if it is current.
		if (isset($client) && (strtotime($client) == $lastMod)) {
			// Client's cache IS current, so we just respond '304 Not Modified'.
			header('Last-Modified: '.gmdate('D, d M Y H:i:s', $lastMod).' GMT', true, 304);
			exit;
		} else {
			// Image not cached or cache outdated, we respond '200 OK' and output the image.
			header('Last-Modified: '.gmdate('D, d M Y H:i:s', $lastMod).' GMT', true, 200);
			header('Content-Length: '.strlen($content));
			header('Content-Type: image/' . substr(strrchr($_GET['resource'], '.'), 1) );
			echo $content;
			exit;
		}
	}
}


/**
 * Display Images/ Icons in base64-encoding
 * @return $resourceID
 */
function wpag_get_resource_url($resourceID) {
	
	return trailingslashit( get_bloginfo('url') ) . '?resource=' . $resourceID;
}


/**
 * settings in plugin-admin-page
 */
function wpaq_add_settings_page() {
	global $wp_version;
	if ( function_exists('add_options_page') && current_user_can('manage_options') ) {

		$menutitle = '';
		if ( version_compare( $wp_version, '2.6.999', '>' ) ) {
			$menutitle = '<img src="' . wpag_get_resource_url('addquicktag.gif') . '" alt="" />';
		}
		$menutitle .= ' ' . __('Add Quicktag', 'addquicktag');

		add_options_page( __('WP-Quicktag &ndash; Add Quicktag', 'addquicktag'), $menutitle, 9, basename(__FILE__), 'wpaq_options_page');
		add_filter('plugin_action_links', 'wpaq_filter_plugin_actions', 10, 2);
	}
}


/**
 * credit in wp-footer
 */
function wpaq_admin_footer() {
	if( basename($_SERVER['REQUEST_URI']) == 'options-general.php?page=addquicktag.php') {
		$plugin_data = get_plugin_data( __FILE__ );
		printf('%1$s plugin | ' . __('Version') . ' %2$s | ' . __('Author') . ' %3$s<br />', $plugin_data['Title'], $plugin_data['Version'], $plugin_data['Author']);
	}
}
?>