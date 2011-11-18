<?php
/**
 * AddQuicktag - Settings
 * @license GPLv3
 * @package AddQuicktag
 * @subpackage AddQuicktag Settings
 */

class Add_Quicktag_Settings extends Add_Quicktag {
	
	static private $classobj = NULL;
	// string for translation
	static public $textdomain;
	// string for options in table options
	static private $option_string;
	// string for plugin file
	static private $plugin;
	
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
	 * Construvtor, init on defined hooks of WP and include second class
	 * 
	 * @access  public
	 * @since   0.0.2
	 * @uses    register_activation_hook, register_uninstall_hook, add_action
	 * @return  void
	 */
	public function __construct() {
		
		// textdomain from parent class
		$this -> textdomain    = parent :: get_textdomain();
		$this -> option_string = parent :: get_option_string();
		$this -> plugin        = parent :: get_plugin_string();
		
		register_uninstall_hook( __FILE__, array( 'Add_Quicktag_Settings', 'unregister_settings' ) );
		// settings for an active multisite
		if ( is_plugin_active_for_network( $this -> plugin ) ) {
			add_action( 'network_admin_menu', array( $this, 'add_settings_page' ) );
		} else {
			add_action( 'admin_menu',         array( $this, 'add_settings_page' ) );
		}
		add_action( 'admin_init',             array( $this, 'register_settings' ) );
	}
	
	
	/**
	 * Return Textdomain string
	 * 
	 * @access  public
	 * @since   2.0.0
	 * @return  string
	 */
	public function get_textdomain() {
		
		return $this -> textdomain;
	}
	
	
	/**
	 * Add settings link on plugins.php in backend
	 * 
	 * @uses   plugin_basename
	 * @access public
	 * @param  array $links, string $file
	 * @since  2.0.0
	 * @return string $links
	 */
	public function plugin_action_links( $links, $file ) {
		
		if ( plugin_basename( dirname(__FILE__).'/addquicktag.php' ) == $file  )
			$links[] = '<a href="options-general.php?page='. $this -> option_string . '_group">' . __('Settings') . '</a>';
		
		return $links;
	}
	
	
	/**
	 * Add settings page in WP backend
	 * 
	 * @uses   add_options_page
	 * @access public
	 * @since  2.0.0
	 * @return void
	 */
	public function add_settings_page () {
		
		if ( is_plugin_active_for_network( $this -> plugin ) ) {
			add_submenu_page(
				'settings.php',
				parent :: get_plugin_data( 'Name' ) . ' ' . __( 'Settings', $this -> get_textdomain() ),
				parent :: get_plugin_data( 'Name' ),
				'manage_options',
				plugin_basename(__FILE__),
				array( $this, 'get_network_settings_page' )
			);
		} else {
			add_options_page(
				parent :: get_plugin_data( 'Name' ) . ' ' . __( 'Settings', $this -> get_textdomain() ),
				parent :: get_plugin_data( 'Name' ),
				'manage_options',
				plugin_basename(__FILE__),
				array( $this, 'get_settings_page' )
			);
			add_action( 'contextual_help', array( $this, 'contextual_help' ), 10, 3 );
		}
	}
	
	/**
	 * Return form and markup on settings page
	 * 
	 * @uses settings_fields, normalize_whitespace
	 * @access public	
	 * @since 0.0.2
	 * @return void
	 */
	public function get_settings_page () {
		
		screen_icon('options-general'); ?>
		<div class="wrap">
		<h2><?php echo parent :: get_plugin_data( 'Name' ); ?></h2>
		<h3><?php _e('Add or delete Quicktag buttons', $this -> get_textdomain() ); ?></h3>
		
		<form method="post" action="options.php">
			<?php
			settings_fields( $this -> option_string . '_group' );
			$options = get_option( $this -> option_string );
			// sort array by order value
			$tmp = Array();
			foreach( $options['buttons'] as $order ) {
				$tmp[] = &$order['order'];
			}
			array_multisort( $tmp, SORT_ASC, $options['buttons'] );
			?>
			
			<table class="widefat">
				<tr>
					<th class="row-title"><?php _e('Button Label*', $this -> get_textdomain() ); ?></th>
					<th class="row-title"><?php _e('Title Attribute', $this -> get_textdomain() ); ?></th>
					<th class="row-title"><?php _e('Start Tag(s)*', $this -> get_textdomain() ); ?></th>
					<th class="row-title"><?php _e('End Tag(s)', $this -> get_textdomain() ); ?></th>
					<th class="row-title" style="width:5%;"><?php _e('Access Key', $this -> get_textdomain() ); ?></th>
					<th class="row-title" style="width:5%;"><?php _e('Order', $this -> get_textdomain() ); ?></th>
				</tr>
				<?php
				if ( empty($options['buttons']) )
					$options['buttons'] = array();
				$class = '';
				for ( $i = 0; $i < count( $options['buttons'] ); $i++ ) {
					$class = ( ' class="alternate"' == $class ) ? '' : ' class="alternate"';
					$b           = $options['buttons'][$i];
					$b['text']   = htmlentities( stripslashes($b['text']), ENT_COMPAT, get_option('blog_charset') );
					$b['title']  = htmlentities( stripslashes($b['title']), ENT_COMPAT, get_option('blog_charset') );
					$b['start']  = htmlentities( $b['start'], ENT_COMPAT, get_option('blog_charset') );
					$b['end']    = htmlentities( $b['end'], ENT_COMPAT, get_option('blog_charset') );
					if ( ! isset( $b['access'] ) )
						$b['access'] = '';
					$b['access'] = htmlentities( $b['access'], ENT_COMPAT, get_option('blog_charset') );
					if ( ! isset( $b['order'] ) )
						$b['order'] = 0;
					$b['order'] = intval( $b['order'] );
					$nr          = $i + 1;
				echo '
				<tr>
					<td><input type="text" name="' . $this -> option_string . '[buttons][' . $i 
					. '][text]" value="' . $b['text'] . '" style="width: 95%;" /></td>
					<td><input type="text" name="' . $this -> option_string . '[buttons][' . $i . '][title]" value="' 
					. $b['title'] . '" style="width: 95%;" /></td>
					<td><textarea class="code" name="' . $this -> option_string . '[buttons][' . $i 
					. '][start]" rows="2" cols="25" style="width: 95%;">' . $b['start'] . '</textarea></td>
					<td><textarea class="code" name="' . $this -> option_string . '[buttons][' . $i 
					. '][end]" rows="2" cols="25" style="width: 95%;">' . $b['end'] . '</textarea></td>
					<td><input type="text" name="' . $this -> option_string . '[buttons][' . $i 
					. '][access]" value="' . $b['access'] . '" style="width: 95%;" /></td>
					<td><input type="text" name="' . $this -> option_string . '[buttons][' . $i 
					. '][order]" value="' . $b['order'] . '" style="width: 95%;" /></td>
				</tr>
				';
				}
				?>
				<tr>
					<td><input type="text" name="<?php echo $this -> option_string; ?>[buttons][<?php echo $i; ?>][text]" value="" style="width: 95%;" /></td>
					<td><input type="text" name="<?php echo $this -> option_string; ?>[buttons][<?php echo $i; ?>][title]" value="" style="width: 95%;" /></td>
					<td><textarea class="code" name="<?php echo $this -> option_string; ?>[buttons][<?php echo $i; ?>][start]" rows="2" cols="25" style="width: 95%;"></textarea></td>
					<td><textarea class="code" name="<?php echo $this -> option_string; ?>[buttons][<?php echo $i; ?>][end]" rows="2" cols="25" style="width: 95%;"></textarea></td>
					<td><input type="text" name="<?php echo $this -> option_string; ?>[buttons][<?php echo $i; ?>][access]" value="" class="code" style="width: 95%;" /></td>
					<td><input type="text" name="<?php echo $this -> option_string; ?>[buttons][<?php echo $i; ?>][order]" value="" style="width: 95%;" /></td>
				</tr>
			</table>
			
			<p><?php _e( 'Fill in the fields below to add or edit the quicktags. Fields with * are required. To delete a tag simply empty all fields.', $this -> get_textdomain() ); ?></p>
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
			</p>
	
		</form>
		
		<?php $this -> get_plugin_infos(); ?>
		</div>
		<?php
	}
	
	public function get_plugin_infos() {
		?>
		<h3><?php _e( 'Like this plugin?', $this -> get_textdomain() ); ?></h3>
		<p><?php _e( 'Here\'s how you can give back:', $this -> get_textdomain() ); ?></p>
		<ul>
			<li><a href="http://wordpress.org/extend/plugins/addquicktag/" title="<?php esc_attr_e( 'The Plugin on the WordPress plugin repository', $this -> get_textdomain() ); ?>"><?php _e( 'Give the plugin a good rating.', $this -> get_textdomain() ); ?></a></li>
			<li><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=6069955" title="<?php esc_attr_e( 'Donate via PayPal', $this -> get_textdomain() ); ?>"><?php _e( 'Donate a few euros.', $this -> get_textdomain() ); ?></a></li>
			<li><a href="http://www.amazon.de/gp/registry/3NTOGEK181L23/ref=wl_s_3" title="<?php esc_attr_e( 'Frank Bültge\'s Amazon Wish List', $this -> get_textdomain() ); ?>"><?php _e( 'Get me something from my wish list.', $this -> get_textdomain() ); ?></a></li>
		</ul>
		
		<h3><?php _e( 'About this plugin', $this -> get_textdomain() ); ?></h3>
		<p>
			<strong><?php _e( 'Version:', $this -> get_textdomain() ); ?></strong>
			<?php echo parent :: get_plugin_data( 'Version' ); ?>
		</p>
		<p>
			<strong><?php _e( 'Description:', $this -> get_textdomain() ); ?></strong>
			<?php echo parent :: get_plugin_data( 'Description' ); ?>
		</p>
		<?php
	}
	
	/**
	 * ToDo: build settigns page with network options for network, with raw for active in blog x
	 */
	public function get_network_settings_page() {
		
		screen_icon('options-general'); ?>
		<div class="wrap">
		<h2><?php echo Add_Quicktag :: get_plugin_data( 'Name' ); ?></h2>
		
		<form method="post" action="settings.php">
			<h3><?php _e('Add or delete Quicktag buttons', $this -> get_textdomain() ); ?></h3>
			<p>ToDo for next release.<br />Build settigns page with network options for network, with raw for active in blog x</p>
			<?php wp_nonce_field( 'siteoptions' ); ?>
		</form>
		
		<?php $this -> get_plugin_infos(); ?>
		
		</div>
		<?php
	}
	
	/**
	 * Validate settings for options
	 * 
	 * @uses   normalize_whitespace
	 * @access public
	 * @param  array $value
	 * @since  2.0.0
	 * @return string $value
	 */
	public function validate_settings( $value ) {
		
		$buttons = array();
		for ( $i = 0; $i < count( $value['buttons']); $i++ ) {
				$b = $value['buttons'][$i];
				if ($b['text']  != '' && $b['start'] != '') {
					$b['text']   = esc_html( $b['text'] );
					$b['title']  = esc_html( $b['title'] );
					$b['start']  = stripslashes( $b['start'] );
					$b['end']    = stripslashes( $b['end'] );
					$b['access'] = esc_html( $b['access'] );
					$b['order']  = intval( $b['order'] );
					$buttons[]   = $b;
				}
		}
		$value['buttons'] = $buttons;
		
		return $value;
	}
	
	/**
	 * Register settings for options
	 * 
	 * @uses    register_setting
	 * @access  public
	 * @since   2.0.0
	 * @return  void
	 */
	public function register_settings() {
		
		register_setting( $this -> option_string . '_group', $this -> option_string, array( $this, 'validate_settings' ) );
	}
	
	/**
	 * Unregister and delete settings; clean database
	 * 
	 * @uses    unregister_setting, delete_option
	 * @access  public
	 * @since   0.0.2
	 * @return  void
	 */
	public function unregister_settings() {
		
		unregister_setting( $this -> option_string . '_group', $this -> option_string );
		delete_option( $this -> option_string );
	}
	
	/**
	 * Add help text
	 * 
	 * @uses    normalize_whitespace
	 * @param   string $contextual_help
	 * @param   string $screen_id
	 * @param   string $screen
	 * @since   2.0.0
	 * @return  string $contextual_help
	 */
	public function contextual_help( $contextual_help, $screen_id, $screen ) {
			
		if ( 'settings_page_' . $this -> option_string . '_group' !== $screen_id )
			return $contextual_help;
			
		$contextual_help = 
			'<p>' . __( '' ) . '</p>';
			
		return normalize_whitespace( $contextual_help );
	}
	
}
?>