<?php

/**
 * This class handles all the settings for the plugin.
 *
 * @link       http://spiders.syr.edu
 * @since      2.0.0
 *
 * @package    expressions-analytics
 * @subpackage expressions-analytics/services
 * @author     Michael Zhang <lzhang43@syr.edu>
 */

class Expressions_Analytics_Setting_Service {

	/**
	 * The settings data cache.
	 */
	private $settings_data = null;

	/**
	 * Admin panel settings page label.
	 */
	public $admin_panel_menu_label = 'Analytics';
	
	/**
	 * Admin panel settings page title.
	 */
	public $admin_panel_page_title = 'Expressions Analytics';
	
	/**
	 * Admin panel settings page slug.
	 */
	public $admin_panel_page_slug = 'expana';
	
	/**
	 * Admin settings name.
	 */
	public $settings_name = 'expana_settings';

	/**
	 * Admin panel settings field slug.
	 */
	public $admin_panel_settings_field_slug = 'expana-settings';
	
	/**
	 * Admin panel settings required privileges.
	 */
	public $admin_panel_settings_capability = 'manage_options';

	/**
	 * Dashboard page label.
	 */
	public $dashboard_menu_label = 'Analytics Dashboard';
	
	/**
	 * Dashboard page title.
	 */
	public $dashboard_page_title = 'Expressions Analytics Dashboard';
	
	/**
	 * Dashboard page slug.
	 */
	public $dashboard_page_slug = 'expana_dashboard';

	/**
	 * Dashboard required privileges.
	 */
	public $dashboard_capability = 'manage_options';

	/**
	 * The dashboard instance
	 */
	public $pagehook;

	/**
	 * The default settings data.
	 */
	private $settings_default = array(
		'piwik_auth_token_prod'  => '',
		'piwik_site_id_prod'     => null,
		'piwik_auth_token_dev'   => '',
		'piwik_site_id_dev'      => null,
		'piwik_auth_token_tst'   => '',
		'piwik_site_id_tst'      => null,
		'google_web_property_id' => '',
		'suwi_query_date'	 	 => 'last30',
		'suwi_query_period'		 => 'range'
	);

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    2.0.0
	 * @var      string    $plugin_name 	The name of this plugin.
	 * @var      string    $version			The version of this plugin.
	 */
	public function __construct( $plugin_name, $version )
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register option to store the last query date range
	 *
	 * @since 	2.0.0
	 */
	public function register_date_range_option()
	{
		// Check the sanitize function here
		register_setting( 'my_options_group', 'my_option_name', 'trim' );
	}

	/**
	 * Get the plugin settings.
	 * 
	 * @since   2.0.0
	 * @return array The settings.
	 */
	public function get_settings()
	{
		if ( ! is_array( $this->settings_data ) )
		{
			$this->settings_data = wp_parse_args(
				(array)get_option( $this->settings_name, array() ),
				$this->settings_default
			);
		}
		
		return $this->settings_data;
	}

	/**
	 * Build settings page
	 *
	 * @since    2.0.0
	 */
	public function build_settings()
	{
		$setting = $this->get_settings();

		//Register the plugin settings.
		register_setting(
			$this->admin_panel_settings_field_slug,
			$this->settings_name,
			array( $this, 'callback_settings_sanitize' )
		);

		//Add a section to the settings.
		//Piwik group.
		add_settings_section(
			$this->admin_panel_settings_field_slug . '-piwik',
			__( 'Piwik Analytics', 'expana' ),
			function(){
				?><p><?php echo __( 'Enter your Piwik Auto Token below to enable tracking. Current production level: <strong>' . EXP_PRODUCTION_LEVEL . '</strong>' , 'expana' ); ?></p><?php
			},
			$this->admin_panel_settings_field_slug
		);

		//Add a field to the section.
		//Piwik inputs.
		add_settings_field(
			'piwik_auth_token_prod',//Unique slug for field.
			__( 'Auth Token PROD' ),
			array( $this, 'callback_settings_section_field' ),
			$this->admin_panel_settings_field_slug,
			$this->admin_panel_settings_field_slug . '-piwik',
			array(
				'label_for'   => 'piwik_auth_token_prod',
				'input_type'  => 'text',
				'input_class' => 'regular-text code',
				'input_value' => $setting['piwik_auth_token_prod']
			)
		);

		add_settings_field(
			'piwik_auth_token_dev',//Unique slug for field.
			__( 'Auth Token DEV' ),
			array( $this, 'callback_settings_section_field' ),
			$this->admin_panel_settings_field_slug,
			$this->admin_panel_settings_field_slug . '-piwik',
			array(
				'label_for'   => 'piwik_auth_token_dev',
				'input_type'  => 'text',
				'input_class' => 'regular-text code',
				'input_value' => $setting['piwik_auth_token_dev']
			)
		);

		add_settings_field(
			'piwik_auth_token_tst',//Unique slug for field.
			__( 'Auth Token TST' ),
			array( $this, 'callback_settings_section_field' ),
			$this->admin_panel_settings_field_slug,
			$this->admin_panel_settings_field_slug . '-piwik',
			array(
				'label_for'   => 'piwik_auth_token_tst',
				'input_type'  => 'text',
				'input_class' => 'regular-text code',
				'input_value' => $setting['piwik_auth_token_tst']
			)
		);

		//Add a section to the settings.
		//Google group.
		add_settings_section(
			$this->admin_panel_settings_field_slug . '-google',
			__( 'Google Analytics', 'expana' ),
			function(){
				?><p><?php echo __( 'Enter your Google Web Property ID below to enable tracking.', 'expana' ); ?></p><?php
			},
			$this->admin_panel_settings_field_slug
		);

		//Add a field to the section.
		//Google inputs.
		 add_settings_field(
			'google_web_property_id',//Unique slug for field.
			__( 'Web Property ID' ),
			array( $this, 'callback_settings_section_field' ),
			$this->admin_panel_settings_field_slug,
			$this->admin_panel_settings_field_slug . '-google',
			array(
				'label_for'   => 'google_web_property_id',
				'input_type'  => 'text',
				'input_class' => 'regular-text code',
				'input_value' => $setting['google_web_property_id']
			)
		);
	}

	/**
	 * Admin panel settings page callback.
	 */
	public function callback_settings_page()
	{
		if ( ! current_user_can( $this->admin_panel_settings_capability ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		?><div class="wrap">
			<h2><?php echo __( $this->admin_panel_page_title, 'expana' ); ?></h2>
			<form action="options.php" method="post">
				<?php
					settings_fields( $this->admin_panel_settings_field_slug );
					do_settings_sections( $this->admin_panel_settings_field_slug );
				?>
				<p class="submit">
					<input type="submit" value="<?php esc_attr_e('Save Changes'); ?>" class="button button-primary" id="submit" name="submit" />
				</p>
			</form>
		</div><?php
	}

	/**
	 * Admin panel settings input callback.
	 * 
	 * @since 1.0.0
	 * @param array $args Data from add_settings_field.
	 */
	public function callback_settings_section_field( $args )
	{
		$args = wp_parse_args( $args, array(
			'label_for'   => '',
			'input_type'  => '',
			'input_class' => '',
			'input_value' => ''
		) );
		switch ( $args['input_type'] ) {
			case 'text':
				?><input <?php
					?>type="text" <?php
					?>id="<?php echo $args['label_for']; ?>" <?php
					?>class="<?php echo $args['input_class']; ?>" <?php
					?>name="<?php echo $this->settings_name; ?>[<?php echo $args['label_for']; ?>]" <?php
					?>value="<?php echo $args['input_value']; ?>" <?php
				?>/><?php
			break;
		}
	}

	/**
	 * Query the Piwik API for the site id associated with the URL and return the contents and success in an associative array.
	 * 
	 * @param string $resturl The URL to the REST API.
	 * @param array $restauth The Piwik auth token.
	 * 
	 * @return array The associative array.
	 */
	public function register_piwik_site( $resturl, $restauth )
	{
		$siteid = null;
		$error = null;

		$piwik_rest_api = EXP_PIWIK_HOST;
		$piwik_protocol = EXP_PIWIK_PROTO;
		$piwik_global_tracking_id = EXPANA_PIWIK_GLOBAL_TRACKING_ID;

		//@TODO: wrap the query function and move it to an helper class (maybe a service?)
		$client = new Guzzle\Http\Client();

		$url = $piwik_protocol . "://" . $piwik_rest_api . "/?module=API&format=JSON&token_auth=" . $restauth . "&method=SitesManager.getSitesIdFromSiteUrl&url=http://michael.dev/wordpress";
		
		$request = $client->get( $url );

		$response = $request->send();

		if( $response->getStatusCode() !== 200 )
		{
			$error = __( 'Error connecting to SUWI server', 'expana' );
		}

		$content = @json_decode( $response->getBody(), true );

		if ( ! is_array($content) )
		{
			$error = __( 'Piwik API returned an invalid response', 'expana' );
		}

		if ( empty( $content ) )
		{
			$error = __( 'No site associated with this URL under this auth token', 'expana' );
		}

		foreach ( $content as &$site ) {
			//Check the ID and make sure the ID is not the global one
			if ( isset( $site['idsite'] ) AND $site['idsite'] !== $piwik_global_tracking_id )
			{
				$siteid = (int) $site['idsite'];
				break;
			}
		}

		return $siteid === null ? array( 'result' => 'error', 'content' => $error ) : array( 'result' => 'success', 'content' => $siteid );
	}

	/**
	 * Sanitize and save the input.
	 * 
	 * @param array $input The updated settings.
	 * 
	 * @return string The sanitized settings.
	 */
	public function callback_settings_sanitize( $input = null )
	{
		//Get old settings.
		$settings = $this->get_settings();

		//Check the inputs
		if ( ! is_array( $input ) )
		{
			return $settings;
		}

		//Parse the inputs
		$input = wp_parse_args( $input, $this->settings_default );

		//Parse rest API url
		$rest_api_url = $this->parse_piwik_api_url();

		//If rest API url is empty (means API is not configured in wp-config.php), do nothing
		if ( ! $rest_api_url )
		{
			return $settings;
		}

		//Check if the current production level is valid
		if ( ! $this->validate_production_level(strtolower(EXP_PRODUCTION_LEVEL)) )
		{
			return $settings;
		}

		//Reset $piwik_error
		$piwik_error = null;

		//Sanitize the auth token
		$input_piwik_auth_token = htmlspecialchars( trim($input['piwik_auth_token_' . strtolower(EXP_PRODUCTION_LEVEL)]) );

		//Remove that piwik auth token if tempty
		if ( ! $input_piwik_auth_token )
		{
			$settings['piwik_auth_token_' . strtolower(EXP_PRODUCTION_LEVEL)] = null;
			$settings['piwik_site_id_' . strtolower(EXP_PRODUCTION_LEVEL)] = null;

			return $settings;
		}

		//Check for changes and currently unset.
		if ( $settings['piwik_auth_token_' . strtolower(EXP_PRODUCTION_LEVEL)] == $input_piwik_auth_token AND is_int( $settings['piwik_auth_token_' . strtolower(EXP_PRODUCTION_LEVEL)] ) )
		{
			return $settings;
		}

		//Retrive site info from Piwik and register the site
		$register = $this->register_piwik_site( $rest_api_url, $input_piwik_auth_token );

		//Check errors
		if ( $register['result'] == 'error' )
		{
			$piwik_error = $register['content'];
			$settings['piwik_site_id_' . strtolower(EXP_PRODUCTION_LEVEL)] = null;

			add_settings_error(
				$this->admin_panel_settings_field_slug . '-piwik-error',
				$this->admin_panel_settings_field_slug,
				__( 'Piwik Error:', 'expana' ) . '<br /><code>' . esc_html( $piwik_error ) . '</code>',
				'error'
			);
			
			return $settings;
		}

		//Finally, save settings if no error has been captured
		$settings['piwik_site_id_' . strtolower(EXP_PRODUCTION_LEVEL)] = $register['content'];

		$settings['piwik_auth_token_prod']  = htmlspecialchars( trim($input['piwik_auth_token_prod']) );
		$settings['piwik_auth_token_dev']   = htmlspecialchars( trim($input['piwik_auth_token_dev']) );
		$settings['piwik_auth_token_tst']   = htmlspecialchars( trim($input['piwik_auth_token_tst']) );
		$settings['google_web_property_id'] = htmlspecialchars( trim($input['google_web_property_id']) );

		return $settings;
		
	}


	/**
	 * Validate the production level value
	 * 
	 * @param string $production_level The current production level value.
	 * 
	 * @return boolean Whether or not the given value is valid
	 */
	public function validate_production_level( $production_level )
	{
		if ( ! in_array(strtolower($production_level), array( "dev", "tst", "prod" )) )
		{
			return false;
		}

		return true;
	}


	/**
	 * Parse Piwik REST API url
	 * 
	 * @return string|boolean REST API url OR False if empty or invalid
	 */
	public function parse_piwik_api_url()
	{
		//Retrive API configurations
		$piwik_rest_api = EXP_PIWIK_HOST;
		$piwik_protocol = EXP_PIWIK_PROTO;

		if ( empty( $piwik_rest_api ) OR empty( $piwik_protocol ) OR ! is_string( $piwik_rest_api ) OR ! is_string( $piwik_protocol ) )
		{
			return false;
		}

		return $piwik_protocol . '://' . $piwik_rest_api;
	}

	/**
	 * Build dashboard page
	 *
	 * @since    2.0.0
	 */
	public function build_dashboard()
	{
		if ( ! is_int( $this->get_site_id() ) )
		{
			return false;
		}

		$dashboard = new Expressions_Analytics_Dashboard($this->plugin_name, $this->version);

		$this->pagehook = add_dashboard_page(
			__( $this->dashboard_page_title, 'expana' ),
			__( $this->dashboard_menu_label, 'expana' ),
			$this->dashboard_capability,
			$this->dashboard_page_slug,
			array( $dashboard, 'expana_dashboard' )
		);

		add_action( 'load-'.$this->pagehook, array($dashboard, 'expana_widgets') );

		return $this->pagehook;

	}

	/**
	 * Get current production level
	 *
	 * @return 	string|boolean   $production_level or false, if the production level is invalid
	 * @since 	2.0.0
	 */
	public function get_production_level()
	{
		if ( $this->validate_production_level(EXP_PRODUCTION_LEVEL) )
		{
			return EXP_PRODUCTION_LEVEL;
		}

		return false;
	}

	/**
	 * Get piwik_site_id from piwik settings (not by remote piwik server)
	 *
	 * @since    2.0.0
	 */
	public function get_site_id()
	{

		$settings = $this->get_settings();

		$piwik_site_id = null;

		switch ( EXP_PRODUCTION_LEVEL )
		{
			case 'PROD':
				$piwik_site_id = $settings['piwik_site_id_prod'];
			break;
			case 'DEV':
				$piwik_site_id = $settings['piwik_site_id_dev'];
			break;
			case 'TST':
				$piwik_site_id = $settings['piwik_site_id_tst'];
			break;
		}

		return $piwik_site_id;
	}

	/**
	 * Get piwik_auth_token from piwik settings
	 *
	 * @return   string 	$piwik_auth_token
	 * @since    2.0.0
	 */
	public function get_auth_token()
	{

		$settings = $this->get_settings();

		$piwik_auth_token = null;

		switch ( EXP_PRODUCTION_LEVEL )
		{
			case 'PROD':
				$piwik_auth_token = $settings['piwik_auth_token_prod'];
			break;
			case 'TST':
				$piwik_auth_token = $settings['piwik_auth_token_tst'];
			break;
			case 'DEV':
				$piwik_auth_token = $settings['piwik_auth_token_dev'];
			break;
		}

		return $piwik_auth_token;
	}

}
