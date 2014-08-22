<?php
/*
Name: Expressions Analytics
Description: WordPress plugin for Expressions analytics.
Author: Expressions Team, Alexander O'Mara
Version: 1.0
*/

/**
 * The site id for global tracking in Google.
 *
 * Define as non-string or empty to disable.
 */
if ( ! defined( 'EXPANA_GOOGLE_GLOBAL_TRACKING_ID' ) ) {
	define( 'EXPANA_GOOGLE_GLOBAL_TRACKING_ID', null );
}

/**
 * The namespace for global tracking in Google.
 *
 * Define as non-string or empty to disable.
 */
if ( ! defined( 'EXPANA_GOOGLE_GLOBAL_TRACKING_NAMESPACE' ) ) {
	define( 'EXPANA_GOOGLE_GLOBAL_TRACKING_NAMESPACE', null );
}

/**
 * The site id for global tracking in Piwik.
 * 
 * Define as non-integer to disable.
 */
if ( ! defined( 'EXPANA_PIWIK_GLOBAL_TRACKING_ID' ) ) {
	define( 'EXPANA_PIWIK_GLOBAL_TRACKING_ID', 1 );
}

/**
 * The domain for global tracking in Piwik.
 *
 * Define as non-string or empty to disable.
 */
if ( ! defined( 'EXPANA_PIWIK_GLOBAL_TRACKING_DOMAIN' ) ) {
	define( 'EXPANA_PIWIK_GLOBAL_TRACKING_DOMAIN', '*.syr.edu' );
}

/**
 * The rest API URL for global tracking in Piwik, minus the protocol.
 *
 * Define as non-string or empty to disable.
 */
if ( ! defined( 'EXPANA_PIWIK_GLOBAL_TRACKING_REST_API' ) ) {
	define( 'EXPANA_PIWIK_GLOBAL_TRACKING_REST_API', null );//TODO
}

/**
 * Define the number of seconds to wait for remote API requests.
 */
if ( ! defined( 'EXPANA_EXTERNAL_API_TIMEOUT' ) ) {
	define( 'EXPANA_EXTERNAL_API_TIMEOUT', 30 );
}

/**
 * Define as true to disable remote API SSL verification.
 */
if ( ! defined( 'EXPANA_EXTERNAL_API_DISABLE_SSL_VERIFICATION' ) ) {
	define( 'EXPANA_EXTERNAL_API_DISABLE_SSL_VERIFICATION', false );
}

//Check if inside WordPress.
if ( ! defined( 'ABSPATH' ) ) { exit(); }

class ExpressionsAnalytics {
	
	/**
	 * Piwik tracking code format.
	 * 
	 * The following variables are substituted into the string.
	 * - %1$s = The top domain to track.
	 * - %2$s = The REST API base for the Piwik tracker.
	 * - %3$u = The unique site id.
	 */
	const TRACKING_CODE_PIWIK = <<<'EOS'
<!-- Piwik -->
<script type="text/javascript">
var _paq=_paq||[];
_paq.push(["setDocumentTitle",document.domain+"/"+document.title]);
_paq.push(["setCookieDomain","%1$s"]);
_paq.push(["setDomains",["%1$s"]]);
_paq.push(["trackPageView"]);
_paq.push(["enableLinkTracking"]);
(function(d,t,u,g,s) {
u=("https:"==d.location.protocol?"https":"http")+"://%2$s/";
_paq.push(["setTrackerUrl",u+"piwik.php"]);
_paq.push(["setSiteId",%3$u]);
g=d.createElement(t);
s=d.getElementsByTagName(t)[0];
g.type="text/javascript";
g.defer=true;
g.async=true;
g.src=u+"piwik.js";
s.parentNode.insertBefore(g,s);
})(document,"script");
</script>
<noscript><img src="//%2$s/piwik.php?idsite=%3$u&rec=1" style="border:0" alt="" /></noscript>
<!-- End Piwik Code -->

EOS;
	
	/**
	 * Google tracking code format.
	 * 
	 * The following variables are substituted into the string.
	 * - %1$s = The tracking settings code.
	 */
	const TRACKING_CODE_GOOGLE = <<<'EOS'
<script type="text/javascript">
var _gaq=_gaq||[];
%1$s(function() {
var ga=document.createElement('script');
ga.type='text/javascript';
ga.async=true;
ga.src=('https:'==document.location.protocol?'https://ssl':'http://www')+'.google-analytics.com/ga.js';
var s=document.getElementsByTagName('script')[0];
s.parentNode.insertBefore(ga,s);
})();
</script>

EOS;
	
	/**
	 * Google tracking API call.
	 * 
	 * The following variables are substituted into the string.
	 * - %1$s = The API call arguments.
	 */
	const TRACKING_CODE_GOOGLE_API_CALL = <<<'EOS'
_gaq.push(%1$s);

EOS;
	
	private $admin_panel_menu_label = 'Analytics';
	private $admin_panel_page_title = 'Expressions Analytics';
	private $admin_panel_page_slug = 'expressions-analytics';
	private $admin_panel_settings_field_slug = 'expressions-analytics-settings';
	private $admin_panel_settings_capability = 'manage_options';
	
	private $settings_name = 'expressions_analytics_settings';
	private $settings = null;
	
	public function __construct() {
		$this->add_actions();
		//header('Content-Type: text/plain');
		//var_dump($this->query_piwik_api(array('method'=>'SitesManager.getSitesIdFromSiteUrl')));
		//exit();
	}
	
	/**
	 * Generate the Piwik tracking code.
	 * 
	 * @param string $track_domain The domain to track.
	 * @param string $rest_api The rest API URL, minus the protocol.
	 * @param string $site_id The unique site id assigned by Piwik.
	 * 
	 * @return string The Piwik tracking code.
	 */
	public function tracking_code_piwik( $track_domain, $rest_api, $site_id ) {
		return sprintf( self::TRACKING_CODE_PIWIK, $track_domain, $rest_api, $site_id );
	}
	
	/**
	 * Generate the Google tracking code.
	 * 
	 * @param array $accounts The accounts to track.
	 * 
	 * @return string The Google tracking code.
	 */
	public function tracking_code_google( $accounts ) {
		$api_calls_str = '';
		if ( is_array( $accounts ) ) {
			foreach ( $accounts as $account=>&$tracking ) {
				$ns = isset( $tracking['namespace'] ) && is_string( $tracking['namespace'] ) && ! empty( $tracking['namespace'] ) ? $tracking['namespace'] . '.' : '';
				$api_calls_str .= $this->tracking_code_google_api_call( array( $ns . '_setAccount', $account ) );
				$api_calls_str .= $this->tracking_code_google_api_call( array( $ns . '_trackPageview' ) );
			}
			unset( $tracking );
		}
		return empty( $api_calls_str ) ? '' : sprintf( self::TRACKING_CODE_GOOGLE, $api_calls_str );
	}
	
	/**
	 * Generate the Google API call.
	 * 
	 * @param mixed $call The API call parameter.
	 * 
	 * @return string The API call JS string.
	 */
	public function tracking_code_google_api_call( $call ) {
		return sprintf( self::TRACKING_CODE_GOOGLE_API_CALL, json_encode( $call ) );
	}
	
	/**
	 * Initialize the action hooks.
	 */
	public function add_actions() {
		add_action( 'init',                  array( $this, 'action_init'                  )        );
		add_action( 'admin_init',            array( $this, 'action_admin_init'            )        );
		add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts' )        );
		add_action( 'admin_menu',            array( $this, 'action_admin_menu'            )        );
		add_action( 'wp_footer',             array( $this, 'action_print_tracking_code'   ), 99999 );
	}
	
	public function action_init() {
		
	}
	
	public function action_admin_init() {
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
			__( 'Piwik Settings', 'expana' ),
			function(){
				?><p><?php echo __( 'TODO Piwik settings description.', 'expana' ); ?></p><?php
			},
			$this->admin_panel_settings_field_slug
		);
		//Add a field to the section.
		//Piwik inputs.
		add_settings_field(
			'piwik',//A unique slug for this settings field, otherwise apparently unused.
			__( 'Rest API URL' ),
			array( $this, 'callback_settings_section_field' ),
			$this->admin_panel_settings_field_slug,
			$this->admin_panel_settings_field_slug . '-piwik',
			array(
				'label_for' => 'piwik_rest_api',
				'input_type' => 'text',
				'input_class' => 'regular-text code'
			)
		);
		//Add a section to the settings.
		//Google group.
		add_settings_section(
			$this->admin_panel_settings_field_slug . '-google',
			__( 'Google Settings', 'expana' ),
			function(){
				?><p><?php echo __( 'TODO Google settings description.', 'expana' ); ?></p><?php
			},
			$this->admin_panel_settings_field_slug
		);
		//Add a field to the section.
		//Google inputs.
		add_settings_field(
			'piwik',//A unique slug for this settings field, otherwise apparently unused.
			__( 'Web Property ID' ),
			array( $this, 'callback_settings_section_field' ),
			$this->admin_panel_settings_field_slug,
			$this->admin_panel_settings_field_slug . '-google',
			array(
				'label_for' => 'google_id',
				'input_type' => 'text',
				'input_class' => 'regular-text code'
			)
		);
	}
	
	public function callback_settings_sanitize() {
		//TODO
	}
	
	/**
	 * Admin panel settings input callback.
	 * 
	 * @param array $args Data from add_settings_field.
	 */
	public function callback_settings_section_field( $args ) {
		//TODO: Be sure to use label_for for the input element ID.
		$args = wp_parse_args( $args, array(
			'label_for' => '',
			'input_type' => '',
			'input_class' => ''
		) );
		switch ( $args['input_type'] ) {
			case 'text':
				?><input id="<?php echo $args['label_for']; ?>" class="<?php echo $args['input_class']; ?>" type="text" /><?php
			break;
		}
	}
	
	/**
	 * Admin panel script enqueue callback.
	 * 
	 * @param string $hook The WordPress unique page slug.
	 */
	public function action_admin_enqueue_scripts( $hook ) {
		if ( $hook === 'settings_page_' . $this->admin_panel_page_slug ) {
			
		}
	}
	
	/**
	 * Add to admin panel menu.
	 */
	public function action_admin_menu() {
		add_options_page(
			__( $this->admin_panel_page_title, 'expana' ),
			__( $this->admin_panel_menu_label, 'expana' ),
			$this->admin_panel_settings_capability,
			$this->admin_panel_page_slug,
			array( $this, 'callback_settings_page' )
		);
	}
	
	/**
	 * Admin panel settings page callback.
	 */
	public function callback_settings_page() {
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
			</form>
		</div><?php
	}
	
	/**
	 * Action callback to print all the tracking code.
	 */
	public function action_print_tracking_code() {
		//Global tracking Piwik.
		if (
			is_string( EXPANA_PIWIK_GLOBAL_TRACKING_DOMAIN ) && ! empty( EXPANA_PIWIK_GLOBAL_TRACKING_DOMAIN ) &&
			is_string( EXPANA_PIWIK_GLOBAL_TRACKING_REST_API ) && ! empty( EXPANA_PIWIK_GLOBAL_TRACKING_REST_API ) &&
			is_int( EXPANA_PIWIK_GLOBAL_TRACKING_ID )
		) {
			echo $this->tracking_code_piwik(
				EXPANA_PIWIK_GLOBAL_TRACKING_DOMAIN,
				EXPANA_PIWIK_GLOBAL_TRACKING_REST_API,
				EXPANA_PIWIK_GLOBAL_TRACKING_ID
			);
		}
		
		//TODO: User defined Piwik.
		$ga_accounts = array();
		//Add global tracking to the list.
		if ( is_string( EXPANA_GOOGLE_GLOBAL_TRACKING_ID ) && ! empty( EXPANA_GOOGLE_GLOBAL_TRACKING_ID ) ) {
			$ga_accounts[EXPANA_GOOGLE_GLOBAL_TRACKING_ID] = array(
				'namespace' => EXPANA_GOOGLE_GLOBAL_TRACKING_NAMESPACE
			);
		}
		
		//TODO: Add site tracking to the list.
		$ga_accounts['TEST-USER'] = array(
			'namespace' => ''
		);
		echo $this->tracking_code_google( $ga_accounts );
	}
	
	/**
	 * Get all saved settings, or a specified one, optionally defaulting to a provided default.
	 * 
	 * @param string $setting The setting to fetch.
	 * @param mixed $default The default value to return.
	 * 
	 * @return mixed The settings or the specified setting.
	 */
	public function settings_get( $setting = null, $default = null ) {
		//Lazy pull the settings, defaulting to an empty array.
		if ( ! is_array( $this->settings ) ) {
			$this->settings = get_option( $this->settings_name, null );
			if ( ! is_array( $this->settings ) ) {
				$this->settings = array();
			}
		}
		//Check if for a specific setting.
		if ( $setting !== null ) {
			//Return default if the property does not exist.
			return array_key_exists( $this->settings, $setting ) ? $this->settings[$setting] : $default;
		}
		//Return all the settings.
		return ( empty( $this->settings ) && $default !== null ) ? $default : $this->settings;
	}
	
	/**
	 * Update settings.
	 * 
	 * @param mixed $settings An associative array of setting to save.
	 * @param bool $replace_all If true, replaces all settings with the new settings, else merges the settings.
	 */
	public function settings_set( $settings, $replace_all = false ) {
		//Check that settings are an array.
		if ( is_array( $settings ) ) {
			$changed = false;
			//Check if should overwrite all settings or simple merge them.
			if ( $replace_all ) {
				$this->settings = $settings;
				$changed = true;
			} else {
				foreach ( $settings as $k=>&$v ) {
					if ( array_key_exists( $this->settings, $k ) && $this->settings[$k] !== $v ) {
						//Set the key value, without using the reference.
						$this->settings[$k] = $settings[$k];
						$changed = true;
					}
				}
				unset( $v );
			}
			//If the settings have changed, save them to the database.
			if ( $changed ) {
				update_option( $this->settings_name, $this->settings );
			}
		}
	}
	
	/**
	 * Delete the specified setting or delete all settings if none are specified.
	 * 
	 * @param string $setting A specific setting to delete.
	 */
	public function settings_delete( $setting = null ) {
		//Check if deleting a specific setting.
		if ( $setting !== null ) {
			//If there to delete, remove it and save.
			if ( property_exists( $this->settings, $setting ) ) {
				unset( $this->settings[$setting] );
				update_option( $this->settings_name, $this->settings );
			}
		}
		//If deleting all, then remove the option completely.
		delete_option( $this->settings_name );
	}
	
	/**
	 * Query the configured Piwik API with the specified parameters and return the contents in an associative array.
	 * 
	 * @param array $query An associative array of query parameters.
	 * 
	 * @return array The associative array.
	 */
	public function query_piwik_api( $query ) {
		$api_path = 'http://' . EXPANA_PIWIK_GLOBAL_TRACKING_REST_API;//TODO
		$query_args = wp_parse_args( $query, array(
			'module'     => 'API',
			'format'     => 'JSON',
			'url'        => get_site_url(),
			'token_auth' => TMP_AUTHTOKEN//TODO
		) );
		$url = rtrim( $api_path, '/' ) . '/?' . http_build_query( $query_args );
		return $this->remote_request( $url );
	}
	
	/**
	 * Fetch an external URL and return the contents and success in an associative array.
	 * 
	 * @param string $url The URL to fetch.
	 * 
	 * @return array The associative array.
	 */
	public function remote_request( $url ) {
		if ( filter_var( $url, FILTER_VALIDATE_URL ) === false ) {
			return array(
				'result'  => 'error',
				'content' => 'Invalid URL'
			);
		}
		if ( function_exists( 'curl_init' ) ) {
			//Init CURL.
			$ctx = curl_init( $url );
			//Check success.
			if ( ! $ctx ) {
				return array(
					'result'  => 'error',
					'content' => 'Failed to initialize CURL'
				);
			}
			//Return string.
			curl_setopt( $ctx, CURLOPT_RETURNTRANSFER, true );
			//Suppress headers.
			curl_setopt( $ctx, CURLOPT_HEADER, false );
			//Verify SSL certificates.
			curl_setopt( $ctx, CURLOPT_SSL_VERIFYPEER, EXPANA_EXTERNAL_API_DISABLE_SSL_VERIFICATION !== true );
			//Set user agent if readable, else rely on the default.
			$php_user_agent = @ini_get( 'user_agent' );
			if ( ! empty( $php_user_agent ) ) {
				curl_setopt( $ctx, CURLOPT_USERAGENT, $php_user_agent );
			}
			//Set timeout.
			curl_setopt( $ctx, CURLOPT_TIMEOUT, EXPANA_EXTERNAL_API_TIMEOUT );
			//Send request.
			$response = curl_exec( $ctx );
			//Grab any error message.
			$curl_error = curl_error( $ctx );
			//Close connection.
			curl_close( $ctx );
			//Check response.
			if ( is_string( $response ) ) {
				return array(
					'result'  => 'success',
					'content' => $response
				);
			} else {
				return array(
					'result'  => 'error',
					'content' => $curl_error
				);
			}
		}
		elseif ( @ini_get( 'allow_url_fopen' ) && function_exists( 'stream_context_create' ) ) {
			//Create stream.
			$ctx = stream_context_create( array(
				'http' => array(
					'timeout' => EXPANA_EXTERNAL_API_TIMEOUT
				)
			) );
			//Send request.
			$response = @file_get_contents( $url, false, $ctx );
			//Check response.
			if ( is_string( $response ) ) {
				return array(
					'result'  => 'success',
					'content' => $response
				);
			} else {
				return array(
					'result'  => 'error',
					'content' => 'Remote fopen request failed'
				);
			}
		}
		//Return failure.
		return array(
			'result'  => 'error',
			'content' => 'CURL and remote fopen are disabled'
		);
	}
}
new ExpressionsAnalytics();
