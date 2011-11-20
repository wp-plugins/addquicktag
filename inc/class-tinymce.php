<?php
/**
 * AddQuicktag - to TinyMCE Editor
 * @license GPLv3
 * @package AddQuicktag
 * @subpackage AddQuicktag 2 TinyMce
 */

class Add_Quicktag_2_TinyMce extends Add_Quicktag {
	
	static private $classobj = NULL;
	
	static private $option_string      = 'rmnlQuicktagSettings_tmce';
	
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
	 * @uses    add_action
	 * @return  void
	 */
	public function __construct() {
		
		add_filter( 'mce_external_plugins', array( $this, 'add_externel_buttons' ) );
		add_filter( 'mce_buttons_2',        array( $this, 'extend_editor_buttons' ), 10, 2 );
	}
	
	public function add_externel_buttons( $plugins ) {
		if ( FALSE == is_array($plugins) )
			$plugins = array();

		$url = WP_PLUGIN_URL . '/addquicktags_to_the_wysiwyg-editor/aqtwe_tinymce3plus_combobox/editor_plugin.js';
		$plugin_array = array_merge( $plugins, array( self :: $option_string => $url ) );

		return $plugin_array;
	}
	
	public function extend_editor_buttons( $buttons, $editor_id ) {
		
		return array_merge( array( self :: $option_string ), $buttons );
	}
	
} // end class
