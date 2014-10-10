<?php
/*
Plugin Name: Expressions Analytics
Description: WordPress plugin for Expressions analytics.
Author: Expressions Team, Alexander O'Mara
Version: 1.0
*/

/**
 * The rest API URL for global tracking in Piwik, minus the protocol.
 *
 * Define as non-string or empty to disable.
 */
if ( ! defined( 'EXP_PRODUCTION_LEVEL' ) ) {
	define( 'EXP_PRODUCTION_LEVEL', null );
}

/**
 * The rest API URL for global tracking in Piwik, minus the protocol.
 *
 * Define as non-string or empty to disable.
 */
if ( ! defined( 'EXP_PIWIK_HOST' ) ) {
	define( 'EXP_PIWIK_HOST', null );
}

/**
 * The protocol to access the Piwik API over.
 *
 * Define as non-string or empty to disable.
 */
if ( ! defined( 'EXP_PIWIK_PROTO' ) ) {
	define( 'EXP_PIWIK_PROTO', null );
}

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
	
	/**
	 * Admin panel settings page label.
	 */
	private $admin_panel_menu_label = 'Analytics';
	
	/**
	 * Admin panel settings page title.
	 */
	private $admin_panel_page_title = 'Expressions Analytics';
	
	/**
	 * Admin panel settings page slug.
	 */
	private $admin_panel_page_slug = 'expana';
	
	/**
	 * Admin panel settings field slug.
	 */
	private $admin_panel_settings_field_slug = 'expana-settings';
	
	/**
	 * Admin panel settings required privileges.
	 */
	private $admin_panel_settings_capability = 'manage_options';
	
	/**
	 * The settings option key.
	 */
	private $settings_name = 'expana_settings';
	
	/**
	 * The settings data cache.
	 */
	private $settings_data = null;

	/**
	 * Dashboard page label.
	 */
	private $dashboard_menu_label = 'Analytics Dashboard';
	
	/**
	 * Dashboard page title.
	 */
	private $dashboard_page_title = 'Expressions Analytics Dashboard';
	
	/**
	 * Dashboard page slug.
	 */
	private $dashboard_page_slug = 'expana_dashboard';

	/**
	 * Dashboard required privileges.
	 */
	private $dashboard_capability = 'manage_options';		
	
	/**
	 * The default settings data.
	 */
	private $settings_default = array(
		'piwik_auth_token_prod'  => '',
		'piwik_site_id_prod'     => null,
		'piwik_auth_token_dev'   => '',
		'piwik_site_id_dev'      => null,
		'google_web_property_id' => ''
	);
	
	/**
	 * Initializes the plugin.
	 */
	public function __construct() {
		$this->admin_panel_menu_label = __( $this->admin_panel_menu_label, 'expana' );
		$this->admin_panel_page_title = __( $this->admin_panel_page_title, 'expana' );
		
		$this->action_init();
		$this->add_actions();
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
	 * Support two columns.
	 * 
	 * @see http://www.code-styling.de/english/how-to-use-wordpress-metaboxes-at-own-plugins
	 */ 
	function on_screen_layout_columns($columns, $screen) {
		if ($screen == $this->pagehook) {
			$columns[$this->pagehook] = 3;
		}
		return $columns;
	}
	
	public function action_init() {
		add_filter( 'screen_layout_columns', array( $this, 'on_screen_layout_columns'), 10, 2 );
	}

	/**
	 * Initialize the action hooks.
	 */
	public function add_actions() {
		//add_action( 'init', array( $this, 'action_init' ) );
		add_action( 'admin_init', array( $this, 'action_admin_init') );
		add_action( 'admin_menu', array( $this, 'build_dashboard') );
		//add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts' ) );
		add_action( 'add_meta_boxes', array( $this, 'build_dashboard_metaboxes') );
		add_action( 'admin_menu', array( $this, 'action_admin_menu' ) );
		add_action( 'wp_footer', array( $this, 'action_print_tracking_code' ), 99999 );
		add_action( 'add_meta_boxes', array( $this, 'expana_dashboard_boxes' ) );
	}
	
	/**
	 * Get the plugin settings..
	 * 
	 * @return array The settings.
	 */
	public function settings_get() {
		if ( ! is_array( $this->settings_data ) ) {
			$this->settings_data = wp_parse_args( (array)get_option( $this->settings_name, array() ), $this->settings_default );
		}
		return $this->settings_data;
	}
	
	/**
	 * Initialize the plugin settings.
	 */
	public function action_admin_init() {
		$setting = $this->settings_get();
		
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
				?><p><?php echo __( 'Enter your Piwik Auto Token below to enable tracking.', 'expana' ); ?></p><?php
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

		//Register scripts and stylesa
		//wp_register_script( 'expana_d3js', 'http://d3js.org/d3.v3.min.js' );
        wp_register_script( 'expana_chartjs', plugins_url( 'js/chart.min.js', __FILE__ ) );
        wp_register_script( 'expana_jqvmap', plugins_url( 'js/jquery.vmap.js', __FILE__ ) );
        wp_register_script( 'expana_jqvmap_world', plugins_url( 'js/maps/jquery.vmap.world.js', __FILE__ ) );
		wp_register_style( 'jquery-ui_style', 'http://ajax.aspnetcdn.com/ajax/jquery.ui/1.10.4/themes/smoothness/jquery-ui.css' );
		wp_register_style( 'expana_jqvmap_style', plugins_url( 'css/jqvmap.css', __FILE__ ) );
		wp_register_style( 'expana_style', plugins_url( 'style.css', __FILE__ ) );
		wp_register_script( 'expana_highcharts', plugins_url( 'js/highcharts.js', __FILE__ ) );
		wp_register_script( 'expana_highcharts_exporting', plugins_url( 'js/modules/exporting.js', __FILE__ ) );
	}
	
	/**
	 * Query the Piwik API for the site id associated with the URL and return the contents and success in an associative array.
	 * 
	 * @param string $resturl The URL to the REST API.
	 * @param array $restauth The Piwik auth token.
	 * 
	 * @return array The associative array.
	 */
	public function piwik_api_get_site_id_from_site_url( $resturl, $restauth ) {
		$siteid = null;
		$error = null;
		//Query the REST API.
		$req = $this->query_piwik_api(
			$resturl,
			array(
				'token_auth' => $restauth,
				'method'     => 'SitesManager.getSitesIdFromSiteUrl',
				'url'        => get_site_url()
			)
		);

		//Check success.
		if ( $req['result'] === 'success' && ! empty( $req['content'] ) ) {
			//Decode the JSON content.
			$content = @json_decode( $req['content'], true );
			if ( is_array( $content ) ) {
				//If JSON result is not error.
				if ( ! ( isset( $content['result'] ) && $content['result'] === 'error' ) ) {
					if ( ! empty( $content ) ) {
						//Loop over the sites.
						foreach ( $content as &$site ) {
							//Check the ID.
							if ( isset( $site['idsite'] ) ) {
								$idsite = (int)$site['idsite'];
								//Make sure the ID is not the global one.
								if ( $idsite !== EXPANA_PIWIK_GLOBAL_TRACKING_ID ) {
									$siteid = $idsite;
									break;
								}
							}
						}
						unset( $site );
					}
					if ( $siteid === null ) {
						$error = __( 'No site associated with this URL under this auth token', 'expana' );
					}
				} else {
					$error = __( 'Piwik API error', 'expana' );
				}
			} else {
				$error = __( 'Piwik API returned an invalid response', 'expana' );
			}
		} else {
			$error = __( 'Failed to connect to the Piwik API', 'expana' );
		}
		return $siteid === null ? array( 'result' => 'error', 'content' => $error ) : array( 'result' => 'success', 'content' => $siteid );
	}
	
	/**
	 * Sanitize the input.
	 * 
	 * @param array $input The updated settings.
	 * 
	 * @return string The sanitized settings.
	 */
	public function callback_settings_sanitize( $input = null ) {
		//Get old settings.
		$settings = $this->settings_get();
		if ( is_array( $input ) ) {
			//Parse the input
			$input = wp_parse_args( $input, $this->settings_default );
			
			//Variable that are used a lot.
			$input_piwik_auth_token_prod = trim( $input['piwik_auth_token_prod'] );
			$input_piwik_auth_token_dev  = trim( $input['piwik_auth_token_dev'] );
			
			//Check if the API is configured.
			$piwik_rest_api = EXP_PIWIK_HOST;
			$piwik_protocol = EXP_PIWIK_PROTO;
			if ( is_string( $piwik_rest_api ) && ! empty( $piwik_rest_api ) && is_string( $piwik_protocol ) && ! empty( $piwik_protocol ) ) {
				$rest_api_url = $piwik_protocol . '://' . $piwik_rest_api;
				$piwik_error = null;
				//Only use the current production level.
				switch ( EXP_PRODUCTION_LEVEL ) {
					case 'PROD':
						if ( $input_piwik_auth_token_prod ) {
							//Check for changes or currently unset.
							if ( $settings['piwik_auth_token_prod'] !== $input_piwik_auth_token_prod || ! is_int( $settings['piwik_site_id_prod'] ) ) {
								$res = $this->piwik_api_get_site_id_from_site_url( $rest_api_url, $input_piwik_auth_token_prod );
								if ( $res['result'] === 'success' ) {
									$settings['piwik_site_id_prod'] = $res['content'];
								} else {
									$piwik_error = $res['content'];
									$settings['piwik_site_id_prod'] = null;
								}
							}
						} else {
							$settings['piwik_site_id_prod'] = null;
						}
					break;
					case 'DEV':
						if ( $input_piwik_auth_token_dev ) {
							//Check for changes or currently unset.
							if ( $settings['piwik_auth_token_dev'] !== $input_piwik_auth_token_dev || ! is_int( $settings['piwik_site_id_dev'] ) ) {
								$res = $this->piwik_api_get_site_id_from_site_url( $rest_api_url, $input_piwik_auth_token_dev );
								if ( $res['result'] === 'success' ) {
									$settings['piwik_site_id_dev'] = $res['content'];
								} else {
									$piwik_error = $res['content'];
									$settings['piwik_site_id_dev'] = null;
								}
							}
						} else {
							$settings['piwik_site_id_dev'] = null;
						}
					break;
				}
				if ( $piwik_error ) {
					add_settings_error(
						$this->admin_panel_settings_field_slug . '-piwik-error',
						$this->admin_panel_settings_field_slug,
						__( 'Piwik Error:', 'expana' ) . '<br /><code>' . esc_html( $piwik_error ) . '</code>',
						'error'
					);
				}
			}
			
			$settings['piwik_auth_token_prod']  = $input_piwik_auth_token_prod;
			$settings['piwik_auth_token_dev']   = $input_piwik_auth_token_dev;
			$settings['google_web_property_id'] = trim( $input['google_web_property_id'] );
		}
		return $settings;
	}
	
	/**
	 * Admin panel settings input callback.
	 * 
	 * @param array $args Data from add_settings_field.
	 */
	public function callback_settings_section_field( $args ) {
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
				<p class="submit">
					<input type="submit" value="<?php esc_attr_e('Save Changes'); ?>" class="button button-primary" id="submit" name="submit" />
				</p>
			</form>
		</div><?php
	}
	
	/**
	 * Action callback to print all the tracking code.
	 */
	public function action_print_tracking_code() {
		$settings = $this->settings_get();
		
		$piwik_global_tracking_domain = EXPANA_PIWIK_GLOBAL_TRACKING_DOMAIN;
		$piwik_rest_api = EXP_PIWIK_HOST;
		$piwik_global_tracking_id = EXPANA_PIWIK_GLOBAL_TRACKING_ID;
		//Global tracking Piwik.
		if (
			is_string( $piwik_global_tracking_domain ) && ! empty( $piwik_global_tracking_domain ) &&
			is_string( $piwik_rest_api ) && ! empty( $piwik_rest_api ) &&
			is_int( $piwik_global_tracking_id )
		) {
			echo $this->tracking_code_piwik(
				$piwik_global_tracking_domain,
				$piwik_rest_api,
				$piwik_global_tracking_id
			);
		}
		
		//Piwik code for the current production level.
		$piwik_site_id = null;
		switch ( EXP_PRODUCTION_LEVEL ) {
			case 'PROD':
				$piwik_site_id = $settings['piwik_site_id_prod'];
			break;
			case 'DEV':
				$piwik_site_id = $settings['piwik_site_id_dev'];
			break;
		}
		if ( is_int( $piwik_site_id ) ) {
			$site_domain = @parse_url( get_site_url(), PHP_URL_HOST );
			if ( ! empty( $site_domain ) ) {
				echo $this->tracking_code_piwik(
					'*.' . $site_domain,
					$piwik_rest_api,
					$piwik_site_id
				);
			}
		}
				
		//Google tracking.
		$ga_accounts = array();
		
		//Add user tracking to the list.
		if ( ! empty( $settings['google_web_property_id'] ) ) {
			$ga_accounts[$settings['google_web_property_id']] = array(
				'namespace' => ''
			);
		}
		
		//Add global tracking to the list.
		$google_global_tracking_id = EXPANA_GOOGLE_GLOBAL_TRACKING_ID;
		if ( is_string( $google_global_tracking_id ) && ! empty( $google_global_tracking_id ) ) {
			$ga_accounts[$google_global_tracking_id] = array(
				'namespace' => EXPANA_GOOGLE_GLOBAL_TRACKING_NAMESPACE
			);
		}
		
		//Output the tracking code.
		if ( ! empty( $ga_accounts ) ) {
			echo $this->tracking_code_google( $ga_accounts );
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
	 * Validate date and time in all formats
	 *
	 * @return bool
	 */
	public function validate_date($date, $format = 'Y-m-d')
	{
		$d = DateTime::createFromFormat($format, $date);
		return $d AND $d->format($format) == $date;
	}

	/**
	 * Query the Piwik API with the specified parameters and return the contents in an associative array.
	 * 
	 * @param string $restapi The URL to the REST API.
	 * @param array $query An associative array of query parameters.
	 * 
	 * @return array The associative array.
	 */
	public function query_piwik_api( $restapi, $query ) {

		$piwik_rest_api = EXP_PIWIK_HOST;
		$piwik_protocol = EXP_PIWIK_PROTO;

		if ( is_string( $piwik_rest_api ) AND ! empty( $piwik_rest_api ) AND is_string( $piwik_protocol ) AND ! empty( $piwik_protocol ) ) {
			$restapi = $piwik_protocol . '://' . $piwik_rest_api;
		}
		else
		{
			$error = __( 'Piwik API error', 'expana' );
			return $siteid === null ? array( 'result' => 'error', 'content' => $error ) : array( 'result' => 'success', 'content' => $siteid );
		}

		if ( is_string( $_POST['expana-time-period'] ) )
		{
			if ( $this->validate_date( $_POST['expana-from-date'] ) AND $this->validate_date( $_POST['expana-to-date'] ) )
			{
				$time_period = 'daterange';
				$from_date = $_POST['expana-from-date'];
				$to_date =  $_POST['expana-to-date'];
			}
			else
			{
				$time_period = sanitize_text_field( $_POST['expana-time-period'] );
			}
		}
		else
		{
			$time_period = 'last30';
		}

		if ( $time_period == 'lastyear' )
		{
			$date = 'previous1year';
			$period = 'range';
		}
		elseif ( $time_period == 'lastmonth' )
		{
			$date = 'previous1month';
			$period = 'range';
		}
		elseif ( $time_period == 'lastweek' )
		{
			$date = 'previous1week';
			$period = 'range';
		}
		elseif ( $time_period == 'last10' )
		{
			$date = 'last10day';
			$period = 'range';
		}
		elseif ( $time_period == 'last30' )
		{
			$date = 'last30day';
			$period = 'range';
		}
		elseif ( $time_period == 'daterange' )
		{
			$date = $from_date . ',' . $to_date;
			$period = 'range';
		}
		else
		{
			$date = $time_period;
			$period = 'day';
		}

		return $this->remote_request( rtrim( $restapi, '/' ) . '/?' . http_build_query( wp_parse_args( $query, array(
			'date'		 => $date,
			'period'	 => $period,
			'module'     => 'API',
			'format'     => 'JSON'
		) ) ) );
	}

	/**
	 * Return period argument for Piwik iFrame widgets
	 *
	 * @return string
	 */
	public function get_query_period() {
		if ( is_string( $_POST['expana-time-period'] ) )
		{

			if ( $this->validate_date( $_POST['expana-from-date'] ) AND $this->validate_date( $_POST['expana-to-date'] ) )
			{
				//Custom date range
				$time_period = 'daterange';
			}
			else
			{
				//Time period presets
				$time_period = sanitize_text_field( $_POST['expana-time-period'] );
			}
		}
		else
		{
			//No POST request. Default option is last 30 days.
			$time_period = 'last30';
		}

		if ( $time_period == 'lastyear' OR $time_period == 'lastmonth' OR $time_period == 'lastweek' OR $time_period == 'last10' OR $time_period == 'last30' OR $time_period == 'daterange')
		{
			$period = 'range';
		}
		else
		{
			$period = 'day';
		}

		return $period;
	}

	/**
	 * Return date range argument for Piwik iFrame widgets
	 * DON'T CALL THIS FUNCTION IN query_piwik_api(). Their date formats are different.
	 *
	 * @return string
	 */
	public function get_query_date() {
		if ( is_string( $_POST['expana-time-period'] ) )
		{
			if ( $this->validate_date( $_POST['expana-from-date'] ) AND $this->validate_date( $_POST['expana-to-date'] ) )
			{
				$time_period = 'daterange';
				$from_date = $_POST['expana-from-date'];
				$to_date =  $_POST['expana-to-date'];
			}
			else
			{
				$time_period = sanitize_text_field( $_POST['expana-time-period'] );
			}
		}
		else
		{
			$time_period = 'last30';
		}

		if ( $time_period == 'lastyear' )
		{
			$date = 'previous1year';
		}
		elseif ( $time_period == 'lastmonth' )
		{
			$date = 'previous1month';
		}
		elseif ( $time_period == 'lastweek' )
		{
			$date = 'previous1week';
		}
		elseif ( $time_period == 'last10' )
		{
			$date = 'last10';
		}
		elseif ( $time_period == 'last30' )
		{
			$date = 'last30';
		}
		elseif ( $time_period == 'daterange' )
		{
			$date = $from_date . ',' . $to_date;
		}
		else
		{
			$date = $time_period;
		}

		return $date;
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
				'content' => __( 'Invalid URL', 'expana' )
			);
		}
		if ( function_exists( 'curl_init' ) ) {
			//Init CURL.
			$ctx = curl_init( $url );
			//Check success.
			if ( ! $ctx ) {
				return array(
					'result'  => 'error',
					'content' => __( 'Failed to initialize CURL', 'expana' )
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
					'content' => __( 'Remote fopen request failed', 'expana' )
				);
			}
		}
		//Return failure.
		return array(
			'result'  => 'error',
			'content' => __( 'CURL and remote fopen are disabled', 'expana' )
		);
	}

	/**
	 * Get associated Piwik site id
	 */
	public function getPiwikSiteId() {
		$settings = $this->settings_get();

		$piwik_site_id = null;
		switch ( EXP_PRODUCTION_LEVEL ) {
			case 'PROD':
				$piwik_site_id = $settings['piwik_site_id_prod'];
			break;
			case 'DEV':
				$piwik_site_id = $settings['piwik_site_id_dev'];
			break;
		}

		return $piwik_site_id;
	}

	/**
	* Get token_auth
	*/
	public function get_token_auth() {
		$settings = $this->settings_get();

		$piwik_token_auth = null;
		switch ( EXP_PRODUCTION_LEVEL ) {
			case 'PROD':
				$piwik_token_auth = $settings['piwik_auth_token_prod'];
			break;
			case 'DEV':
				$piwik_token_auth = $settings['piwik_auth_token_dev'];
			break;
		}

		return $piwik_token_auth;
	}

	/**
	* Get idSite
	*/
	public function get_id_site() {
		$settings = $this->settings_get();

		$piwik_site_id = null;
		switch ( EXP_PRODUCTION_LEVEL ) {
			case 'PROD':
				$piwik_site_id = $settings['piwik_site_id_prod'];
			break;
			case 'DEV':
				$piwik_site_id = $settings['piwik_site_id_dev'];
			break;
		}

		return $piwik_site_id;
	}

	/**
	 * Build dashboard page
	 */
	public function build_dashboard() {
		if ( is_int( $this->get_id_site() ) ) {
			$this->pagehook = add_dashboard_page(
				__( $this->dashboard_page_title, 'expana' ),
				__( $this->dashboard_menu_label, 'expana' ),
				$this->dashboard_capability,
				$this->dashboard_page_slug,
				array( $this, 'callback_dashboard_page' )
			);

			add_action( 'load-'.$this->pagehook, array($this, 'load_dashboard') );
		}
	}

	/**
	 * Make Piwik API data available to JavaScript
	 *
	 */
	public function localize_piwik_data() {
		$piwik_response = $this->query_piwik_api(NULL, array(
			'token_auth'	=> $this->get_token_auth(),
			'idSite' 		=> $this->get_id_site(),
			'method'		=> 'VisitorInterest.getNumberOfVisitsPerVisitDuration'
			));
	}

	/**
	 * Dashboard page callback.
	 */
	public function callback_dashboard_page() {

		if ( ! current_user_can( $this->dashboard_capability ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		global $screen_layout_columns;

		if (empty($screen_layout_columns)) {
			$screen_layout_columns = 3;
		}

		?>
		<script type="text/javascript">
		jQuery(document).ready( function($) {
			if ( jQuery( window ).width() <= 800 )
			{
				jQuery( "#dashboard-widgets" ).removeClass( "columns-3" ).removeClass( "columns-2" ).removeClass( "has-right-sidebar" );
			}
			else if ( jQuery( window ).width() > 800 && jQuery( window ).width() <= 1500 )
			{
				jQuery( "#dashboard-widgets" ).removeClass( "columns-3" ).addClass( "columns-2" ).removeClass( "has-right-sidebar" );
			}
			else
			{
				jQuery( "#dashboard-widgets" ).removeClass( "columns-2" ).addClass( "columns-3" ).addClass( "has-right-sidebar" );
			}
		});
		</script>

		<div id="expana_dashboard" class="wrap">
			<h2><?php echo __( $this->dashboard_page_title, 'expana' ); ?></h2>
			
			<form action="" method="post">
				<?php wp_nonce_field( 'expana_dashboard' ); ?>
				<?php wp_nonce_field( 'closed_postboxes', 'closed_postboxes_nonce', false ); ?>
				<?php wp_nonce_field( 'metabox_order', 'metabox_order_nonce', false ); ?>
				<input type="hidden" name="action" value="save_expana_dashboard" />
				<div>
					<div class="tablenav top">
						<div class="alignleft actions">
							<label class="screen-reader-text" for="expana-time-period">Select Time Period</label>
							<select id="expana-time-period" name="expana-time-period">
								<option selected="selected" value="-1">Time Period</option>
								<option class="hide-if-no-js" value="today" <?php if($_POST['expana-time-period']=="today") echo("selected");?>>Today</option>
								<option class="hide-if-no-js" value="yesterday" <?php if($_POST['expana-time-period']=="yesterday") echo("selected");?>>Yesterday</option>
								<option class="hide-if-no-js" value="last10" <?php if($_POST['expana-time-period']=="last10") echo("selected");?>>Last 10 Days</option>
								<option class="hide-if-no-js" value="last30" <?php if($_POST['expana-time-period']=="last30") echo("selected");?>>Last 30 Days</option>
								<option class="hide-if-no-js" value="lastweek" <?php if($_POST['expana-time-period']=="lastweek") echo("selected");?>>Last Week</option>
								<option class="hide-if-no-js" value="lastmonth" <?php if($_POST['expana-time-period']=="lastmonth") echo("selected");?>>Last Month</option>
								<option class="hide-if-no-js" value="lastyear" <?php if($_POST['expana-time-period']=="lastyear") echo("selected");?>>Last Year</option>
								<option class="hide-if-no-js" value="daterange" <?php if($_POST['expana-time-period']=="daterange") echo("selected");?>>Custom Date Range</option>
							</select>

							<label class="screen-reader-text" for="expana-from-date">From</label>
							<input type="text" class="expana-datepicker" id="expana-from-date" name="expana-from-date" placeholder="From" value="<?php if ($this->validate_date( $_POST['expana-from-date'] )) echo $_POST['expana-from-date']; ?>" />

							<label class="screen-reader-text" for="expana-to-date">To</label>
							<input type="text" class="expana-datepicker" id="expana-to-date" name="expana-to-date" placeholder="To" value="<?php if ($this->validate_date( $_POST['expana-to-date'] )) echo $_POST['expana-to-date']; ?>" />

							<input type="submit" value="Apply" class="button action" id="doaction" name="">
						</div>
					<br class="clear">
					</div>
				</div>
			</form>

			<div id="dashboard-widgets" class="metabox-holder columns-<?php echo $screen_layout_columns; ?><?php echo 2 <= $screen_layout_columns?' has-right-sidebar':''; ?>">
				<div id='postbox-container-1' class='postbox-container'>
					<?php $meta_boxes = do_meta_boxes($this->pagehook, 'normal', null); ?>	
				</div>
				
				<div id='postbox-container-2' class='postbox-container'>
					<?php do_meta_boxes($this->pagehook, 'side', null); ?>
				</div>
				
				<div id='postbox-container-3' class='postbox-container'>
					<?php do_meta_boxes($this->pagehook, 'column3', null); ?>
				</div>
			</div>
			
		</div>

		<script type="text/javascript">
			//<![CDATA[
			jQuery(document).ready( function($) {
				// close postboxes that should be closed
				$('.if-js-closed').removeClass('if-js-closed').addClass('closed');
				// postboxes setup
				postboxes.add_postbox_toggles('<?php echo $this->pagehook; ?>');
			});
			//]]>
		</script>

		<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery( "#expana-from-date" ).datepicker({
					dateFormat: 'yy-mm-dd',
					changeMonth: true,
					changeYear: true,
					maxDate: 'D',
					onClose: function( selectedDate ) {
						jQuery( "#expana-to-date" ).datepicker( "option", "minDate", selectedDate );
						jQuery( "#expana-time-period>option[value='daterange']" ).prop( 'selected', true );
					}
				});

				jQuery( "#expana-to-date" ).datepicker({
					dateFormat: 'yy-mm-dd',
					changeMonth: true,
					changeYear: true,
					maxDate: 'D',
					onClose: function( selectedDate ) {
						jQuery( "#expana-from-date" ).datepicker( "option", "maxDate", selectedDate );
						jQuery( "#expana-time-period>option[value='daterange']" ).prop( 'selected', true );
					}
				});

				jQuery( "#expana-time-period>option[value='today']" ).click(function() {
					jQuery( "#expana-from-date" ).val( jQuery.datepicker.formatDate('yy-mm-dd', new Date()) );
					jQuery( "#expana-to-date" ).val( jQuery.datepicker.formatDate('yy-mm-dd', new Date()) );
				});

				jQuery( "#expana-time-period>option[value='yesterday']" ).click(function() {
					jQuery( "#expana-from-date" ).val( jQuery.datepicker.formatDate('yy-mm-dd', (function(d){ d.setDate(d.getDate()-1); return d})(new Date)) );
					jQuery( "#expana-to-date" ).val( jQuery.datepicker.formatDate('yy-mm-dd', (function(d){ d.setDate(d.getDate()-1); return d})(new Date)) );
				});

				jQuery( "#expana-time-period>option[value='last10']" ).click(function() {
					jQuery( "#expana-from-date" ).val( jQuery.datepicker.formatDate('yy-mm-dd', (function(d){ d.setDate(d.getDate()-9); return d})(new Date)) );
					jQuery( "#expana-to-date" ).val( jQuery.datepicker.formatDate('yy-mm-dd', new Date()) );
				});

				jQuery( "#expana-time-period>option[value='last30']" ).click(function() {
					jQuery( "#expana-from-date" ).val( jQuery.datepicker.formatDate('yy-mm-dd', (function(d){ d.setDate(d.getDate()-29); return d})(new Date)) );
					jQuery( "#expana-to-date" ).val( jQuery.datepicker.formatDate('yy-mm-dd', new Date()) );
				});

				jQuery( "#expana-time-period>option[value='lastweek']" ).click(function() {
					jQuery( "#expana-from-date" ).val( jQuery.datepicker.formatDate('yy-mm-dd', (function(d){ d.setDate(d.getDate()-d.getDay()-7); return d})(new Date)) );
					jQuery( "#expana-to-date" ).val( jQuery.datepicker.formatDate('yy-mm-dd', (function(d){ d.setDate(d.getDate()-d.getDay()-1); return d})(new Date)) );
				});

				jQuery( "#expana-time-period>option[value='lastmonth']" ).click(function() {
					jQuery( "#expana-from-date" ).val( jQuery.datepicker.formatDate('yy-mm-dd', (function(d){ d.setMonth(d.getMonth()-1); d.setDate(1); return d})(new Date)) );
					jQuery( "#expana-to-date" ).val( jQuery.datepicker.formatDate('yy-mm-dd', (function(d){ d.setMonth(d.getMonth()); d.setDate(0); return d})(new Date)) );
				});

				jQuery( "#expana-time-period>option[value='lastyear']" ).click(function() {
					jQuery( "#expana-from-date" ).val( jQuery.datepicker.formatDate('yy-mm-dd', (function(d){ d.setFullYear(d.getFullYear()-1); d.setMonth(0); d.setDate(1); return d})(new Date)) );
					jQuery( "#expana-to-date" ).val( jQuery.datepicker.formatDate('yy-mm-dd', (function(d){ d.setFullYear(d.getFullYear()); d.setMonth(0); d.setDate(0); return d})(new Date)) );
				});
			});

			jQuery( window ).resize(function() {

				if ( jQuery( window ).width() <= 800 )
				{
					jQuery( "#dashboard-widgets" ).removeClass( "columns-3" ).removeClass( "columns-2" ).removeClass( "has-right-sidebar" );
				}
				else if ( jQuery( window ).width() > 800 && jQuery( window ).width() <= 1500 )
				{
					jQuery( "#dashboard-widgets" ).removeClass( "columns-3" ).addClass( "columns-2" ).addClass( "has-right-sidebar" );
				}
				else
				{
					jQuery( "#dashboard-widgets" ).removeClass( "columns-2" ).addClass( "columns-3" ).addClass( "has-right-sidebar" );
				}
			}); 
		</script>

		<?php
	}

	public function load_dashboard() {
		wp_enqueue_script('common');
		wp_enqueue_script('wp-lists');
		wp_enqueue_script('postbox');
		wp_enqueue_script('expana_chartjs');
		wp_enqueue_style('expana_style');
		wp_enqueue_style('expana_jqvmap_style');
		wp_enqueue_style('jquery-ui_style');
		wp_enqueue_script('expana_jqvmap');
		wp_enqueue_script('expana_jqvmap_world');
		wp_enqueue_script('jquery-ui-datepicker');
		wp_enqueue_script('expana_highcharts');
		wp_enqueue_script('expana_highcharts_exporting');

		add_meta_box( 'expana_visit_length_of_visits', 'Visit Length of Visits (Chart.js)', array( $this, 'callback_dashboard_length_of_visits'), $this->pagehook, 'normal', 'core' );
		add_meta_box( 'expana_visit_summary', 'Visit Summary', array( $this, 'callback_dashboard_visit_summary'), $this->pagehook, 'normal', 'core' );
		add_meta_box( 'expana_live', 'Live', array( $this, 'callback_dashboard_live'), $this->pagehook, 'normal', 'core' );
		add_meta_box( 'expana_visit_time', 'Visit Information Per LocalTime (Chart.js)', array( $this, 'callback_dashboard_visit_time'), $this->pagehook, 'side', 'core' );
		add_meta_box( 'expana_devices', 'Device Types (Chart.js)', array( $this, 'callback_dashboard_devices'), $this->pagehook, 'column3', 'core' );
		add_meta_box( 'expana_resolutions', 'Resolutions (Chart.js)', array( $this, 'callback_dashboard_resolutions'), $this->pagehook, 'side', 'core' );
		add_meta_box( 'expana_browsers', 'Browser Families (Chart.js)', array( $this, 'callback_dashboard_browsers'), $this->pagehook, 'normal', 'core' );
		add_meta_box( 'expana_visitor_map', 'Visitor Map', array( $this, 'callback_dashboard_visitor_map'), $this->pagehook, 'side', 'core' );
		add_meta_box( 'expana_visitor_browser', 'Browser Version', array( $this, 'callback_dashboard_visitor_browser'), $this->pagehook, 'side', 'core' );
		add_meta_box( 'expana_visitor_os', 'Visitor OS', array( $this, 'callback_dashboard_visitor_os'), $this->pagehook, 'side', 'core' );
		add_meta_box( 'expana_visitor_map_new', 'Visitor Map (jQuery Vector Maps)', array( $this, 'callback_dashboard_visitor_map_new'), $this->pagehook, 'column3', 'core' );
		add_meta_box( 'expana_referrers', 'Referrers', array( $this, 'callback_dashboard_referrers'), $this->pagehook, 'column3', 'core' );
		add_meta_box( 'expana_search_engines', 'Search Engines', array( $this, 'callback_dashboard_search_engines'), $this->pagehook, 'normal', 'core' );
		add_meta_box( 'expana_goals', 'Goals', array( $this, 'callback_dashboard_goals'), $this->pagehook, 'column3', 'core' );
		add_meta_box( 'expana_social_media', 'Social Media', array( $this, 'callback_dashboard_social_media'), $this->pagehook, 'column3', 'core' );
		add_meta_box( 'expana_social_media_new', 'Social Media (Rendered with Highcharts)', array( $this, 'callback_dashboard_social_media_new'), $this->pagehook, 'side', 'core' );
	}

	public function callback_dashboard_length_of_visits()
	{
		$piwik_response = $this->query_piwik_api(NULL, array(
			'token_auth'	=> $this->get_token_auth(),
			'idSite' 		=> $this->get_id_site(),
			'method'		=> 'VisitorInterest.getNumberOfVisitsPerVisitDuration'
			)); 

			?>
			
		<div class="canvas-holder">
			<canvas id="visit_duration_chart" height="400"></canvas>
		</div>

		<p align="center"><span class="label dataset-2">Unique Visitors</span></p>
		
		<script language="JavaScript">
            jQuery(document).ready(function($) {
                $('#visit_duration_chart').attr('width', $('#visit_duration_chart').parent().width());

				var visit_duration = jQuery.parseJSON('{"visit_duration_data": <?php echo $piwik_response['content']; ?> }');
				
				var visit_duration_label = [];
				var visit_duration_value = [];

				for (var i in visit_duration.visit_duration_data) {
					visit_duration_label.push(visit_duration.visit_duration_data[i].label);
					visit_duration_value.push(visit_duration.visit_duration_data[i].nb_visits);
				}

				var data = {
                    labels : visit_duration_label,
                    datasets : [
                        {
                            label: "Unique Visitors",
                            fillColor: "rgba(151,187,205,0.2)",
                            strokeColor: "rgba(151,187,205,1)",
                            pointColor: "rgba(151,187,205,1)",
                            pointStrokeColor: "#fff",
                            pointHighlightFill: "#fff",
                            pointHighlightStroke: "rgba(151,187,205,1)",
                            data: visit_duration_value
                        }
                    ]
				};

				var options = {
					scaleShowGridLines : true,
					responsive : true,
				};

                var visit_duration_chart = new Chart(document.getElementById("visit_duration_chart").getContext("2d")).Bar(data, options);
            });
		</script>
	<?php
	}

	public function callback_dashboard_visit_time()
	{
		$piwik_response = $this->query_piwik_api(NULL, array(
			'token_auth'	=> $this->get_token_auth(),
			'idSite' 		=> $this->get_id_site(),
			'method'		=> 'VisitTime.getVisitInformationPerLocalTime'
			)); 

			?>
			
		<div class="canvas-holder">
			<canvas id="visit_time_chart" width="400" height="400"></canvas>
		</div>

		<p align="center"><span class="label dataset-2">Unique Visitors</span> &nbsp; <span class="label dataset-1">Visitor</span></p>
		
		<script language="JavaScript">
            jQuery(document).ready(function($) {
                $('#visit_time_chart').attr('width', $('#visit_time_chart').parent().width());

				var visit_time = jQuery.parseJSON('{"visit_time_data": <?php echo $piwik_response['content']; ?> }');
				
				var visit_time_label = [];
				var visit_time_uniq_visitors = [];
				var visit_time_visits = [];

				for (var i in visit_time.visit_time_data) {
					visit_time_label.push(visit_time.visit_time_data[i].label);
					if (! visit_time.visit_time_data[i].nb_uniq_visitors)
					{
						visit_time_uniq_visitors.push(visit_time.visit_time_data[i].sum_daily_nb_uniq_visitors);
					}
					else
					{
						visit_time_uniq_visitors.push(visit_time.visit_time_data[i].nb_uniq_visitors);
					}
					
					visit_time_visits.push(visit_time.visit_time_data[i].nb_visits);
				}

				var data = {
					labels : visit_time_label,
					datasets : [
						{
							label: "Visits",
							fillColor: "rgba(220,220,220,0.2)",
							strokeColor: "rgba(220,220,220,1)",
							pointColor: "rgba(220,220,220,1)",
							pointStrokeColor: "#fff",
							pointHighlightFill: "#fff",
							pointHighlightStroke: "rgba(220,220,220,1)",
							data: visit_time_visits
						},
						{
							label: "Unique Visits",
							fillColor: "rgba(151,187,205,0.2)",
							strokeColor: "rgba(151,187,205,1)",
							pointColor: "rgba(151,187,205,1)",
							pointStrokeColor: "#fff",
							pointHighlightFill: "#fff",
							pointHighlightStroke: "rgba(151,187,205,1)",
							data: visit_time_uniq_visitors
						}
					]
				};

				var options = {
					scaleShowGridLines : true,
					responsive : true,
				};

                new Chart(document.getElementById("visit_time_chart").getContext("2d")).Line(data, options);
            });
		</script>
	<?php
	}

	public function callback_dashboard_devices()
	{
		$piwik_response = $this->query_piwik_api(NULL, array(
			'token_auth'	=> $this->get_token_auth(),
			'idSite' 		=> $this->get_id_site(),
			'method'		=> 'DevicesDetection.getType'
			));
		?>

		<div class="canvas-holder">
			<canvas id="devices_chart" width="400" height="400"></canvas>
		</div>

		<script language="JavaScript">
            jQuery(document).ready(function($) {
                $('#devices_chart').attr('width', $('#devices_chart').parent().width());

				var devices = jQuery.parseJSON('{"devices_data": <?php echo $piwik_response['content']; ?> }');
				
				var data = [];
				var options = {
					segmentShowStroke : true,
					responsive : true,
				};

				var color = ["#e51c23", "#e91e63", "#9c27b0", "#673ab7", "#3f51b5", "#5677fc", "#03a9f4", "#00bcd4", "#009688", "#259b24", "#8bc34a", "#cddc39", "#ffeb3b", "#ffc107", "#ff9800", "#ff5722", "#795548", "#9e9e9e", "#607d8b"];
				var highlight = ["#e84e40", "#ec407a", "#ab47bc", "#7e57c2", "#5c6bc0", "#738ffe", "#29b6f6", "#26c6da", "#26a69a", "#2baf2b", "#9ccc65", "#d4e157", "#ffee58", "#ffca28", "#ffa726", "#ff7043", "#8d6e63", "#bdbdbd", "#78909c"];

				for (var i in devices.devices_data) {
					
					data_item = {};
					data_item.label = devices.devices_data[i].label;

					if (! devices.devices_data[i].nb_uniq_visitors)
					{
						data_item.value = devices.devices_data[i].sum_daily_nb_uniq_visitors;
					}
					else
					{
						data_item.value = devices.devices_data[i].nb_uniq_visitors;
					}
					
					data_item.color = color[i];
					data_item.highlight = highlight[i];

					data.push(data_item);
				}

                new Chart(document.getElementById("devices_chart").getContext("2d")).Doughnut(data, options);
            });
		</script>
	<?php }

	public function callback_dashboard_browsers()
	{
		$piwik_response = $this->query_piwik_api(NULL, array(
			'token_auth'	=> $this->get_token_auth(),
			'idSite' 		=> $this->get_id_site(),
			'method'		=> 'DevicesDetection.getBrowserFamilies'
			));
		?>

		<div class="canvas-holder">
			<canvas id="browsers_chart" width="400" height="400"></canvas>
		</div>
	
		<script language="JavaScript">
            jQuery(document).ready(function($) {
                $('#browsers_chart').attr('width', $('#browsers_chart').parent().width());

				var browsers = jQuery.parseJSON('{"browsers_data": <?php echo $piwik_response['content']; ?> }');
				
				var data = [];
				var options = {
					responsive : true,
				};

				var color = ["#e51c23", "#e91e63", "#9c27b0", "#673ab7", "#3f51b5", "#5677fc", "#03a9f4", "#00bcd4", "#009688", "#259b24", "#8bc34a", "#cddc39", "#ffeb3b", "#ffc107", "#ff9800", "#ff5722", "#795548", "#9e9e9e", "#607d8b"];
				var highlight = ["#e84e40", "#ec407a", "#ab47bc", "#7e57c2", "#5c6bc0", "#738ffe", "#29b6f6", "#26c6da", "#26a69a", "#2baf2b", "#9ccc65", "#d4e157", "#ffee58", "#ffca28", "#ffa726", "#ff7043", "#8d6e63", "#bdbdbd", "#78909c"];

				for (var i in browsers.browsers_data) {
					
					data_item = {};
					data_item.label = browsers.browsers_data[i].label;

					if (! browsers.browsers_data[i].nb_uniq_visitors)
					{
						data_item.value = browsers.browsers_data[i].sum_daily_nb_uniq_visitors;
					}
					else
					{
						data_item.value = browsers.browsers_data[i].nb_uniq_visitors;
					}
					
					data_item.color = color[i];
					data_item.highlight = highlight[i];

					data.push(data_item);
				}

                new Chart(document.getElementById("browsers_chart").getContext("2d")).PolarArea(data, options);
            });
		</script>
	<?php }

	public function callback_dashboard_resolutions()
	{
		$piwik_response = $this->query_piwik_api(NULL, array(
			'token_auth'	=> $this->get_token_auth(),
			'idSite' 		=> $this->get_id_site(),
			'method'		=> 'UserSettings.getResolution'
			));
		?>

		<div class="canvas-holder">
			<canvas id="resolutions_chart" width="400" height="400"></canvas>
		</div>

		<script language="JavaScript">
            jQuery(document).ready(function($) {
                $('#resolutions_chart').attr('width', $('#resolutions_chart').parent().width());

				var resolutions = jQuery.parseJSON('{"resolutions_data": <?php echo $piwik_response['content']; ?> }');

				var data = [];
				var options = {
					responsive : true,
				};

				var color = ["#e51c23", "#e91e63", "#9c27b0", "#673ab7", "#3f51b5", "#5677fc", "#03a9f4", "#00bcd4", "#009688", "#259b24", "#8bc34a", "#cddc39", "#ffeb3b", "#ffc107", "#ff9800", "#ff5722", "#795548", "#9e9e9e", "#607d8b"];
				var highlight = ["#e84e40", "#ec407a", "#ab47bc", "#7e57c2", "#5c6bc0", "#738ffe", "#29b6f6", "#26c6da", "#26a69a", "#2baf2b", "#9ccc65", "#d4e157", "#ffee58", "#ffca28", "#ffa726", "#ff7043", "#8d6e63", "#bdbdbd", "#78909c"];

				for (var i in resolutions.resolutions_data) {

					if (i > 18) {
						break;
					}

					data_item = {};
					data_item.label = resolutions.resolutions_data[i].label;

					if (! resolutions.resolutions_data[i].nb_uniq_visitors)
					{
						data_item.value = resolutions.resolutions_data[i].sum_daily_nb_uniq_visitors;
					}
					else
					{
						data_item.value = resolutions.resolutions_data[i].nb_uniq_visitors;
					}
					
					data_item.color = color[i];
					data_item.highlight = highlight[i];

					data.push(data_item);
				}



                new Chart(document.getElementById("resolutions_chart").getContext("2d")).Doughnut(data, options);
            });
		</script>
	
	<?php }

	public function callback_dashboard_social_media()
	{
		$piwik_response = $this->query_piwik_api(NULL, array(
			'token_auth'	=> $this->get_token_auth(),
			'idSite' 		=> $this->get_id_site(),
			'method'		=> 'Referrers.getSocials'
			));
		?>

		<div class="canvas-holder">
			<canvas id="social_media_chart" width="400" height="400"></canvas>
		</div>

		<script language="JavaScript">
            jQuery(document).ready(function($) {
                $('#social_media_chart').attr('width', $('#social_media_chart').parent().width());

				var social_media = jQuery.parseJSON('{"social_media_data": <?php echo $piwik_response['content']; ?> }');

				var data = [];
				var options = {
					responsive : true,
				};

				var color = ["#e51c23", "#e91e63", "#9c27b0", "#673ab7", "#3f51b5", "#5677fc", "#03a9f4", "#00bcd4", "#009688", "#259b24", "#8bc34a", "#cddc39", "#ffeb3b", "#ffc107", "#ff9800", "#ff5722", "#795548", "#9e9e9e", "#607d8b"];
				var highlight = ["#e84e40", "#ec407a", "#ab47bc", "#7e57c2", "#5c6bc0", "#738ffe", "#29b6f6", "#26c6da", "#26a69a", "#2baf2b", "#9ccc65", "#d4e157", "#ffee58", "#ffca28", "#ffa726", "#ff7043", "#8d6e63", "#bdbdbd", "#78909c"];

				for (var i in social_media.social_media_data) {

					if (i > 18) {
						break;
					}

					data_item = {};
					data_item.label = social_media.social_media_data[i].label;

					if (! social_media.social_media_data[i].nb_uniq_visitors)
					{
						data_item.value = social_media.social_media_data[i].sum_daily_nb_uniq_visitors;
					}
					else
					{
						data_item.value = social_media.social_media_data[i].nb_uniq_visitors;
					}
					
					data_item.color = color[i];
					data_item.highlight = highlight[i];

					data.push(data_item);
				}



                new Chart(document.getElementById("social_media_chart").getContext("2d")).Doughnut(data, options);
            });
		</script>
	
	<?php }

	public function callback_dashboard_social_media_new()
	{
		$piwik_response = $this->query_piwik_api(NULL, array(
			'token_auth'	=> $this->get_token_auth(),
			'idSite' 		=> $this->get_id_site(),
			'method'		=> 'Referrers.getSocials'
			));

		print_r ($piwik_response['content']);
		?>

		<div class="canvas-holder">
			<div id="container" style="min-width: 310px; height: 400px; max-width: 600px; margin: 0 auto"></div>
		</div>

		<script type="text/javascript">
			jQuery(function ($) {
				var social_media = jQuery.parseJSON('{"social_media_data": <?php echo $piwik_response['content']; ?> }');

				var data = [];

				for (var i in social_media.social_media_data) {
					data_item = {};
					data_item.name = social_media.social_media_data[i].label;

					if (! social_media.social_media_data[i].nb_uniq_visitors)
					{
						data_item.y = social_media.social_media_data[i].sum_daily_nb_uniq_visitors;
					}
					else
					{
						data_item.y = social_media.social_media_data[i].nb_uniq_visitors;
					}

					data.push(data_item);
				}

				console.log(data);


			    $('#container').highcharts({
			        chart: {
			            plotBackgroundColor: null,
			            plotBorderWidth: null,
			            plotShadow: false
			        },
			        title: {
			            text: null,
			        },
			        tooltip: {
			            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
			        },
			        plotOptions: {
			            pie: {
			                allowPointSelect: true,
			                cursor: 'pointer',
			                dataLabels: {
			                    enabled: true,
			                    format: '<b>{point.name}</b>: {point.percentage:.1f} %',
			                    style: {
			                        color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
			                    }
			                }
			            }
			        },
			        series: [{
			            type: 'pie',
			            name: 'Browser share',
			            data: data,
			        }]
			    });
			});
		</script>
	
	<?php }

	public function callback_dashboard_visitor_map_new()
	{
		$piwik_response = $this->query_piwik_api(NULL, array(
			'token_auth'	=> $this->get_token_auth(),
			'idSite' 		=> $this->get_id_site(),
			'method'		=> 'UserCountry.getCountry'
			));
		?>

		<script>
			var visitor_data_piwik = jQuery.parseJSON('{"visitor_data": <?php echo $piwik_response['content']; ?> }');

			var data = {};

			for (var i in visitor_data_piwik.visitor_data) {
				country_code = visitor_data_piwik.visitor_data[i].code;
				data[country_code] = visitor_data_piwik.visitor_data[i].nb_visits;
			}

			jQuery(document).ready(function() {
				jQuery('#vmap').vectorMap({
					map: 'world_en',
					backgroundColor: null,
					values: data,
					selectedColor: '#c2d6e0',
					scaleColors: ['#dcdcdc', '#97bbcd'],
					onLabelShow: function(element, label, code)
					{
						if(data[code])
						{
							var message = " Visits: " + data[code];
						}
						else
						{
							var message = "No Visit"
						}
						
						label.text(message);
					}
				});

				jQuery('#vmap').attr('width', jQuery('#vmap').parent().width()).attr('height', jQuery('#vmap').parent().height());
			});
		</script>
		 
		<div id="vmap" style="height: 380px;"></div>

	<?php
	}

	public function callback_dashboard_visit_summary()
	{ ?>
		<iframe id="dashboard_visit_summary" width="100%" height="900" src="<?php echo EXP_PIWIK_PROTO; ?>://<?php echo EXP_PIWIK_HOST; ?>/index.php?module=Widgetize&action=iframe&widget=1&moduleToWidgetize=VisitsSummary&actionToWidgetize=index&idSite=<?php echo $this->get_id_site(); ?>&period=<?php echo $this->get_query_period(); ?>&date=<?php echo $this->get_query_date(); ?>&disableLink=1&widget=1&token_auth=<?php echo $this->get_token_auth(); ?>" scrolling="no" frameborder="0" marginheight="0" marginwidth="0"></iframe>
	<?php }

	public function callback_dashboard_live()
	{ ?>
		<iframe width="100%" height="350" src="<?php echo EXP_PIWIK_PROTO; ?>://<?php echo EXP_PIWIK_HOST; ?>/index.php?module=Widgetize&action=iframe&widget=1&moduleToWidgetize=Live&actionToWidgetize=getSimpleLastVisitCount&idSite=<?php echo $this->get_id_site(); ?>&period=<?php echo $this->get_query_period(); ?>&date=<?php echo $this->get_query_date(); ?>&disableLink=1&widget=1&token_auth=<?php echo $this->get_token_auth(); ?>" scrolling="no" frameborder="0" marginheight="0" marginwidth="0"></iframe>
	<?php }

	public function callback_dashboard_visitor_map()
	{ ?>
		<iframe width="100%" height="400" src="<?php echo EXP_PIWIK_PROTO; ?>://<?php echo EXP_PIWIK_HOST; ?>/index.php?module=Widgetize&action=iframe&widget=1&moduleToWidgetize=UserCountryMap&actionToWidgetize=visitorMap&idSite=<?php echo $this->get_id_site(); ?>&period=<?php echo $this->get_query_period(); ?>&date=<?php echo $this->get_query_date(); ?>&disableLink=1&widget=1&token_auth=<?php echo $this->get_token_auth(); ?>" scrolling="no" frameborder="0" marginheight="0" marginwidth="0"></iframe>	
	<?php }

	public function callback_dashboard_visitor_browser()
	{ ?>
		<iframe width="100%" height="350" src="<?php echo EXP_PIWIK_PROTO; ?>://<?php echo EXP_PIWIK_HOST; ?>/index.php?module=Widgetize&action=iframe&widget=1&moduleToWidgetize=UserSettings&actionToWidgetize=getBrowser&idSite=<?php echo $this->get_id_site(); ?>&period=<?php echo $this->get_query_period(); ?>&date=<?php echo $this->get_query_date(); ?>&disableLink=1&widget=1&token_auth=<?php echo $this->get_token_auth(); ?>" scrolling="no" frameborder="0" marginheight="0" marginwidth="0"></iframe>
	<?php }

	public function callback_dashboard_visitor_os()
	{ ?>
		<iframe width="100%" height="510" src="<?php echo EXP_PIWIK_PROTO; ?>://<?php echo EXP_PIWIK_HOST; ?>/index.php?module=Widgetize&action=iframe&widget=1&moduleToWidgetize=DevicesDetection&actionToWidgetize=getOsVersions&idSite=<?php echo $this->get_id_site(); ?>&period=<?php echo $this->get_query_period(); ?>&date=<?php echo $this->get_query_date(); ?>&disableLink=1&widget=1&token_auth=<?php echo $this->get_token_auth(); ?>" scrolling="no" frameborder="0" marginheight="0" marginwidth="0"></iframe>
	<?php }

	public function callback_dashboard_referrers()
	{ ?>
		<iframe width="100%" height="830" src="<?php echo EXP_PIWIK_PROTO; ?>://<?php echo EXP_PIWIK_HOST; ?>/index.php?module=Widgetize&action=iframe&widget=1&moduleToWidgetize=Referrers&actionToWidgetize=getAll&idSite=<?php echo $this->get_id_site(); ?>&period=<?php echo $this->get_query_period(); ?>&date=<?php echo $this->get_query_date(); ?>&disableLink=1&widget=1&token_auth=<?php echo $this->get_token_auth(); ?>" scrolling="no" frameborder="0" marginheight="0" marginwidth="0"></iframe>
	<?php }

	public function callback_dashboard_search_engines()
	{ ?>
		<iframe width="100%" height="380" src="<?php echo EXP_PIWIK_PROTO; ?>://<?php echo EXP_PIWIK_HOST; ?>/index.php?module=Widgetize&action=iframe&widget=1&moduleToWidgetize=Referrers&actionToWidgetize=getSearchEngines&idSite=<?php echo $this->get_id_site(); ?>&period=<?php echo $this->get_query_period(); ?>&date=<?php echo $this->get_query_date(); ?>&disableLink=1&widget=1&token_auth=<?php echo $this->get_token_auth(); ?>" scrolling="yes" frameborder="0" marginheight="0" marginwidth="0"></iframe>
	<?php }

	public function callback_dashboard_goals()
	{ ?>
		<iframe width="100%" height="400" src="<?php echo EXP_PIWIK_PROTO; ?>://<?php echo EXP_PIWIK_HOST; ?>/index.php?module=Widgetize&action=iframe&widget=1&moduleToWidgetize=Goals&actionToWidgetize=widgetGoalsOverview&idSite=<?php echo $this->get_id_site(); ?>&period=<?php echo $this->get_query_period(); ?>&date=<?php echo $this->get_query_date(); ?>&disableLink=1&widget=1&token_auth=<?php echo $this->get_token_auth(); ?>" scrolling="no" frameborder="0" marginheight="0" marginwidth="0"></iframe>
	<?php }

}

new ExpressionsAnalytics();
