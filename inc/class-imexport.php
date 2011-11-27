<?php
/**
 * AddQuicktag - Settings
 * @license GPLv3
 * @package AddQuicktag
 * @subpackage AddQuicktag Settings
 */

if ( ! function_exists( 'add_action' ) ) {
	echo "Hi there!  I'm just a part of plugin, not much I can do when called directly.";
	exit;
}

class Add_Quicktag_Im_Export extends Add_Quicktag_Settings {
	
	static private $classobj = NULL;
	
	/**
	 * Handler for the action 'init'. Instantiates this class.
	 * 
	 * @access  public
	 * @since   2.0.0
	 * @return  $classobj
	 */
	public function get_object() {
		
		if ( NULL === self :: $classobj ) {
			self :: $classobj = new self;
		}
		
		return self :: $classobj;
	}
	
	/**
	 * Constructor, init on defined hooks of WP and include second class
	 * 
	 * @access  public
	 * @since   0.0.2
	 * @uses    register_activation_hook, register_uninstall_hook, add_action
	 * @return  void
	 */
	public function __construct() {
		
		add_action( 'admin_head', array( $this, 'on_admin_head' ) );
		
		add_action( 'addquicktag_settings_page', array( $this, 'get_im_export_part' ) );
	}
	
	public function on_admin_head() {
		if ( isset( $_GET['addquicktag_download'] ) )
		$this -> export_xml();
	}
	
	public function get_im_export_part() {
		?>
		<h3><?php _e( 'Im- & Export', parent :: get_textdomain() ); ?></h3>
		<form method="get" action="">
			<p class="submit">
				<input type="submit" name="submit" value="<?php _e( 'Download Export File', parent :: get_textdomain() ); ?> &raquo;" />
				<input type="hidden" name="addquicktag_download" value="true" />
			</p>
		</form>
		<?php
	}
	
	public function export_xml() {
		
		$filename = 'addquicktag.' . date('Y-m-d') . '.xml';
		
		header( 'Content-Description: File Transfer' );
		header( "Content-Disposition: attachment; filename=$filename" );
		header( 'Content-type: text/xml; charset=' . get_option('blog_charset'), TRUE );
		
		if ( is_plugin_active_for_network( $this -> plugin ) )
			$result = get_site_option( $this -> option_string );
		else
			$result = get_option( $this -> option_string );
		echo $result.'<br>';
		if ( $result ) {
			$XMLDoc = new SimpleXMLElement("<?xml version='1.0' standalone='yes'?><addquicktag></addquicktag>");
			
			while( $dbrow = mysql_fetch_object($result) ) {
				$xmlrow = $XMLDoc -> addChild('row');
				
				foreach($dbrow as $Spalte => $Wert)
					$xmlrow -> $Spalte = $Wert;
			}
			
			echo $XMLDoc -> asXML();
		}
	}
	
} // end class
