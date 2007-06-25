<?php
/*
Plugin Name: AddQuicktag
Version: 0.8
Plugin URI: http://bueltge.de/wp-addquicktags-de-plugin/120
Description: This plugin make it easy, Quicktags add to the editor. It is possible to ex- and import your Quicktags. Use it <a href="options-general.php?page=addquicktag.php">Options --> Add Quicktags</a>
Author: <a href="http://roel.meurders.nl/" >Roel Meurders</a> and <a href="http://bueltge.de" >Frank Bueltge</a>
*/

// SCRIPT INFO ///////////////////////////////////////////////////////////////////////////
/*
	WP-AddQuicktag for Wordpress is in originally by 
	(C) 2005 Roel Meurders - GNU General Public License

	AddQuicktag is an newer version with more functions and worked in WP 2.1
	(C) 2007 Frank Bueltge

	This Wordpress plugin is released under a GNU General Public License. A complete version of this license
	can be found here: http://www.gnu.org/licenses/gpl.txt

	This Wordpress plugin has been tested with Wordpress 2.0, 2.1 and Wordpress 2.3;

	This Wordpress plugin is released "as is". Without any warranty. The authors cannot
	be held responsible for any damage that this script might cause.

*/

// NO EDITING HERE!!!!! ////////////////////////////////////////////////////////////////
if(function_exists('load_plugin_textdomain'))
  load_plugin_textdomain('addquicktag','wp-content/plugins');

function wpaq_install() {
	global $wpdb;

	if (!get_option('rmnlQuicktagSettings') != '') {
		$name        = 'rmnlQuicktagSettings';
		$value       = 'a:1:{s:7:"buttons";a:1:{i:0;a:3:{s:4:"text";s:7:"Example";s:5:"start";s:9:"<example>";s:3:"end";s:10:"</example>";}}}';
		$description = '';
		$autoload    = 'yes';
		$wpdb->query("INSERT INTO $wpdb->options (option_name, option_value, option_description, autoload) VALUES ('$name', '$value', '$description', '$autoload')");
	}
	
	return;
}

if (function_exists('add_action')) {
	add_action('admin_menu', 'wpaq_admin_menu');
	
	if (strpos($_SERVER['REQUEST_URI'], 'addquicktag.php')) {
		add_action('init', 'wpaq_install');
	}
}

function wpaq_admin_menu(){
	add_options_page('WP-Quicktag - Add Quicktags', 'Add Quicktags', 9, basename(__FILE__), 'wpaq_options_page');
}

function wpaq_options_page(){
	global $wpdb;
	$wpaq_document_root = $_SERVER['DOCUMENT_ROOT'] . $_SERVER['REQUEST_URI'];
	$wpaq_document_root = str_replace("/wp-admin/options-general.php?page=addquicktag.php", "/wp-content", $wpaq_document_root);

	$wpaq_link    = $_SERVER['REQUEST_URI'];
	$wpaq_link    = str_replace("\\", "/", $wpaq_link);
	
	if ($_POST['wpaq']){
		$buttons = array();
		for ($i = 0; $i < count($_POST['wpaq']['buttons']); $i++){
			$b = $_POST['wpaq']['buttons'][$i];
			if ($b['text'] != '' && $b['start'] != ''){
				$b['text']    = htmlentities($b['text']);
				$b['start']   = stripslashes($b['start']);
				$b['end']     = stripslashes($b['end']);
				$buttons[]    = $b;
			}
		}
		$_POST['wpaq']['buttons'] = $buttons;
		update_option('rmnlQuicktagSettings', $_POST['wpaq']);
		$message = '<div class="updated"><p><strong>' . __('Options saved.', 'addquicktag') . '</strong></p></div>';
	}
	
	// Export sql-option
	if (($_POST['action'] == 'export')) {
		$wpaq_data = mysql_query("SELECT option_value FROM $wpdb->options WHERE option_name = 'rmnlQuicktagSettings'");
		$wpaq_data = mysql_result($wpaq_data, 0);
		$file_name = $wpaq_document_root . '/wpaq_export-' . date('Y-m-d_G-i-s') . '.wpaq';
		$file_name = str_replace("//", "/", $file_name);
		$fh        = @ fopen($file_name, 'w');
		
		if ($fh == false) {
			$message_export = '<div class="error"><p><strong>' . __('Can not open for write!', 'addquicktag') . '</strong></p></div>';
		} else {
			@flock($fh, LOCK_EXCLUSIVE);
			$err = @fputs($fh, $wpaq_data);
			@fclose($fh);
	
			if ($err === false) {
				$message_export = '<div class="error"><p><strong>' . __('Can not write!', 'addquicktag') . '</strong></p></div>';
			}
	
			$message_export = '<div class="updated"><p><strong>' . __('AddQuicktag options saved!', 'addquicktag') . '</strong><br />';
			$message_export.= __('Saved in: ', 'addquicktag') . $file_name;
			$message_export.= '</p></div>';
		}
	}

	// Import the sql-file
	if (($_POST['action'] == 'import')) {
		$message_export = '<div class="updated"><p><strong>';

		// check file extension sql
		$str_file_name = $_FILES['datei']['name'];
		$str_file_ext  = explode(".", $str_file_name);

		if ($str_file_ext[1] != 'wpaq') {
			$message_export.= __('Invalid file extension!', 'addquicktag');
		} elseif (file_exists($_FILES['datei']['name'])) {
			$message_export.= __('File is exist!', 'addquicktag');
		} else {
	    // path for file
	    $wpaq_document_root = str_replace("/wp-admin/options-general.php?page=addquicktag.php", "/wp-content/", $wpaq_document_root);
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
		$message_export.= '</strong></p></div>';
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
	$export2 = __('You can save a .wpaq file with your options in <em>/wp-content/wpaq_export.wpaq</em>', 'addquicktag');

	// Import strings
	$button3 = __('Upload file and import &raquo;', 'addquicktag');
	$import1 = __('Import Quicktag buttons options', 'addquicktag');
	$import2 = __('Choose a Quicktag (<em>.wpaq</em>) file to upload, then click <em>Upload file and import</em>.', 'addquicktag');
	$import3 = __('Choose a file from your computer: ', 'addquicktag');

	// Info
	$info1   = __('Further information: Visit the <a href=\'http://bueltge.de/wp-addquicktags-de-plugin/120\'>plugin homepage</a> for further information or to grab the latest version of this plugin.', 'addquicktag');
	$info2   = __('You want to thank me? Visit my <a href=\'http://bueltge.de/wunschliste/\'>wishlist</a>.', 'addquicktag');

	$o       = get_option('rmnlQuicktagSettings');
	
	echo <<<EOT
	<div class="wrap">
		<h2>WP-Quicktag Management</h2>
		{$message}
		{$message_export}
		<form name="form1" method="post" action="options-general.php?page=addquicktag.php">
			<fieldset class="options">
				<legend>{$string1}</legend>
				<p>{$string2}</p>
				<table width="100%" cellspacing="2" cellpadding="5" class="editform">
					<tr>
						<th style="text-align: center;">{$field1}</th>
						<th style="text-align: center;">{$field2}</th>
						<th style="text-align: center;">{$field3}</th>
					</tr>
EOT;
		for ($i = 0; $i < count($o['buttons']); $i++){
			$b = $o['buttons'][$i];
			$nr = $i + 1;
			echo <<<EOT
					<tr valign="top" style="text-align: center;">
						<td><input type="text" name="wpaq[buttons][{$i}][text]" value="{$b['text']}" style="width: 95%;" /></td>
						<td><textarea name="wpaq[buttons][{$i}][start]" rows="2" cols="25" style="width: 95%;">{$b['start']}</textarea></td>
						<td><textarea name="wpaq[buttons][{$i}][end]" rows="2" cols="25" style="width: 95%;">{$b['end']}</textarea></td>
					</tr>
EOT;
		}
		echo <<<EOT
					<tr valign="top" style="text-align: center;">
						<td><input type="text" name="wpaq[buttons][{$i}][text]" value="" style="width: 95%;" /></td>
						<td><textarea name="wpaq[buttons][{$i}][start]" rows="2" cols="25" style="width: 95%;"></textarea></td>
						<td><textarea name="wpaq[buttons][{$i}][end]" rows="2" cols="25" style="width: 95%;"></textarea></td>
					</tr>
				</table>
			</fieldset>
			<p class="submit">
				<input type="submit" name="Submit" value="{$button1}" />
			</p>
			</form>
			<form  name="form2" method="post" action="options-general.php?page=addquicktag.php">
				<fieldset class="options">
					<legend>{$export1}</legend>
					<p>{$export2}</p>
					<p class="submit">
						<input type="submit" name="Submit_export" value="{$button2}" /> 
						<input type="hidden" name="action" value="export" />
					</p>
				</fieldset>
			</form>

			<form  name="form3" enctype="multipart/form-data" method="post" action="options-general.php?page=addquicktag.php">
				<fieldset class="options">
					<legend>{$import1}</legend>
					<p>{$import2}</p>
					<p>
						<label for="datei_id">{$import3}</label>
						<input name="datei" id="datei_id" type="file" />
					</p>
					<p class="submit">
						<input type="submit" name="Submit_import" value="{$button3}" />
						<input type="hidden" name="action" value="import" />
					</p>
				</fieldset>
			</form>
			<hr />
			<p><small>{$info1}<br />&copy; Copyright 2007 <a href="http://bueltge.de">Frank B&uuml;ltge</a> | {$info2}</small></p>
		</div>
EOT;
} //End function wpaq_options_page

if (strpos($_SERVER['REQUEST_URI'], 'post.php') || strpos($_SERVER['REQUEST_URI'], 'post-new.php') || strpos($_SERVER['REQUEST_URI'], 'page-new.php') || strpos($_SERVER['REQUEST_URI'], 'page.php')) {
	add_action('admin_footer', 'wpaq_addsome');

	function wpaq_addsome(){
		$o = get_option('rmnlQuicktagSettings');
		if(count($o['buttons']) > 0){
			echo <<<EOT
				<script type="text/javascript">
					<!--
					if(wpaqToolbar = document.getElementById("ed_toolbar")){
						var wpaqNr, wpaqBut, wpaqStart, wpaqEnd;
EOT;
						for ($i = 0; $i < count($o['buttons']); $i++){
							$b = $o['buttons'][$i];
							$start = preg_replace('![\n\r]+!', "\\n", $b['start']);
							$start = str_replace("'", "\'", $start);
							$end = preg_replace('![\n\r]+!', "\\n", $b['end']);
							$end = str_replace("'", "\'", $end);
							echo <<<EOT
									wpaqStart = '{$start}';
									wpaqEnd = '{$end}';
									wpaqNr = edButtons.length;
									edButtons[wpaqNr] = new edButton('ed_wpaq{$i}','{$b['txt']}',wpaqStart, wpaqEnd,'');
									var wpaqBut = wpaqToolbar.lastChild;
									while (wpaqBut.nodeType != 1){
										wpaqBut = wpaqBut.previousSibling;
									}
									wpaqBut = wpaqBut.cloneNode(true);
									wpaqToolbar.appendChild(wpaqBut);
									wpaqBut.value = '{$b['text']}';
									wpaqBut.title = wpaqNr;
									wpaqBut.onclick = function () {edInsertTag(edCanvas, parseInt(this.title));}
									wpaqBut.id = "ed_wpaq{$i}";
EOT;
						}
				echo <<<EOT
					}

					//-->
				</script>
EOT;
		}
	} //End wpaq_addsome
} // End if

?>