<?php
/**
 * Plugin Name: AddQuicktag
 * Plugin URI:  http://bueltge.de/wp-addquicktags-de-plugin/120/
 * Text Domain: addquicktag
 * Domain Path: /languages
 * Description: Allows you to easily add custom Quicktags to the editor.
 * Version:	 2.0.0 Alpha
 * Author:	  Frank Bültge
 * Author URI: http://bueltge.de
 * License:	GPLv3
 */

/**
License:
==============================================================================
Copyright 2011 Frank Bültge  (email : frank@bueltge.de)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

Requirements:
==============================================================================
This plugin requires WordPress >= 3.3 and tested with PHP Interpreter >= 5.3
*/

/**
 * 
 */
class Add_Quicktag {
	
	static private $classobj;
	
	static private $option_string	  = 'rmnlQuicktagSettings';
	
	static private $admin_pages_for_js = array( 'post.php', 'post-new.php', );
	
	static private $plugin;
	
	function __construct() {
		// get string of plugin
		self :: $plugin = plugin_basename( dirname(__FILE__) . '/addquicktag.php' );
		
		// on uninstall remove capability from roles
		register_uninstall_hook( __FILE__, array('Add_Quicktag', 'uninstall' ) );
		// in deactivate delete all settings in database
		register_deactivation_hook( __FILE__, array('Add_Quicktag', 'uninstall' ) );
		
		// load translation files
		add_action( 'admin_init', array( $this, 'localize_plugin' ) );
		
		require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'inc/class.settings.php';
		$add_quicktag_settings = Add_Quicktag_Settings :: get_object();
		
		add_action( 'wp_print_scripts', array( $this, 'print_scripts' ) );
		
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts') );
		// add settings link
		add_filter( 'plugin_action_links',   array( $this, 'plugin_action_links' ), 10, 2 );
	}
	
	public function uninstall() {
		
		delete_option( self :: $option_string );
	}
	
	public function print_scripts() {
		
		$options = get_option( self :: $option_string );
		// sort array by order value
		$tmp = Array();
		foreach( $options['buttons'] as $order ) {
			$tmp[] = $order['order'];
		}
		array_multisort( $tmp, SORT_ASC, $options['buttons'] );
		?>
		<script type="text/javascript">
			var addquicktag_tags = <?php echo json_encode( $options ); ?>;
		</script>
		<?php
	}
	
	/**
	 * Enqueue Scripts for plugin
	 * 
	 * @param   $where  string
	 * @since   2.0.0
	 * @access  public
	 * @return  void
	 */
	public function admin_enqueue_scripts ( $where ) {
		
		if ( ! in_array( $where, self :: $admin_pages_for_js ) )
			return;
		
		$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';
		
		wp_enqueue_script(
			self :: get_textdomain() . '_script', 
			plugins_url( '/js/add-quicktags' . $suffix. '.js', __FILE__ ), 	
			array( 'jquery', 'quicktags' ),
			'',
			TRUE
		);
		
		// Alternative to JSON function
		// wp_localize_script( self :: get_textdomain() . '_script', 'addquicktag_tags', get_option( self :: $option_string ) );
	}
	
	/**
	 * Handler for the action 'init'. Instantiates this class.
	 *
	 * @since   2.0.0
	 * @access  public
	 * @return  $classobj
	 */
	public function get_object () {
		
		if ( NULL === self :: $classobj ) {
			self :: $classobj = new self;
		}
	
		return self :: $classobj;
	}
	
	/**
	 * Localize_plugin function.
	 *
	 * @uses	load_plugin_textdomain, plugin_basename
	 * @access  public
	 * @since   2.0.0
	 * @return  void
	 */
	public function localize_plugin() {
		
		load_plugin_textdomain( $this -> get_textdomain(), FALSE, dirname( plugin_basename(__FILE__) ) . '/languages' );
	}
	
	/**
	 * return plugin comment data
	 * 
	 * @since  2.0.0
	 * @access public
	 * @param  $value string, default = 'TextDomain'
	 *		 Name, PluginURI, Version, Description, Author, AuthorURI, TextDomain, DomainPath, Network, Title
	 * @return string
	 */
	public function get_plugin_data( $value = 'TextDomain' ) {
		
		if ( ! function_exists( 'get_plugin_data' ) )
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		
		$plugin_data  = get_plugin_data( __FILE__ );
		$plugin_value = $plugin_data[$value];
		
		return $plugin_value;
	}
	
	public function get_plugin_string() {
		
		return self :: $plugin;
	}
	
	/**
	 * Retrun textdomain string
	 * 
	 * @since   2.0.0
	 * @access  public
	 * @return  string
	 */
	public function get_textdomain() {
		
		return self :: get_plugin_data( 'TextDomain' );
	}
	
	public function get_option_string() {
		
		return self :: $option_string;
	}
	
	/**
	 * Add settings link on plugins.php in backend
	 * 
	 * @uses	plugin_basename
	 * @access  public
	 * @param   array $links, string $file
	 * @since   2.0.0
	 * @return  string $links
	 */
	public function plugin_action_links( $links, $file ) {
		
		if ( plugin_basename( dirname(__FILE__).'/addquicktag.php' ) === $file ) {
			$links[] = '<a href="options-general.php?page=addquicktag/inc/class.settings.php">' . __('Settings') . '</a>';
		}
	
		return $links;
	}
	
} // end class

if ( function_exists( 'add_action' ) && class_exists( 'Add_Quicktag' ) ) {
	add_action( 'plugins_loaded', array( 'Add_Quicktag', 'get_object' ) );
} else {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}
