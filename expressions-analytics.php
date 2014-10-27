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
		add_action('admin_post_save_expana_dashboard', array(&$this, 'on_save_changes'));
	}

	/**
	 * Initialize the action hooks.
	 */
	public function add_actions() {
		//add_action( 'init', array( $this, 'action_init' ) );
		add_action( 'admin_init', array( $this, 'action_admin_init') );
		add_action( 'admin_menu', array( $this, 'build_dashboard') );
		//add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts' ) );
		//add_action( 'add_meta_boxes', array( $this, 'build_dashboard_metaboxes') );
		add_action( 'admin_menu', array( $this, 'action_admin_menu' ) );
		add_action( 'wp_footer', array( $this, 'action_print_tracking_code' ), 99999 );
		//add_action( 'add_meta_boxes', array( $this, 'expana_dashboard_boxes' ) );
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
		wp_register_script( 'expana_highcharts_data', plugins_url( 'js/modules/data.js', __FILE__ ) );
		wp_register_script( 'expana_highcharts_drilldown', plugins_url( 'js/modules/drilldown.js', __FILE__ ) );
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
			$date = 'last30';
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

				<div>
					<form action="" method="post">
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
									<!-- <option class="hide-if-no-js" value="lastyear" <?php if($_POST['expana-time-period']=="lastyear") echo("selected");?>>Last Year</option> -->
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
					</form>
				</div>

			<form action="admin-post.php" method="post">
				<?php wp_nonce_field('expana-metaboxes'); ?>
				<?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false ); ?>
				<?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false ); ?>
				<input type="hidden" name="action" value="save_expana_dashboard" />
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
			</form>
			
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

	function on_save_changes() {
		//user permission check
		if ( !current_user_can('manage_options') )
			wp_die( __('Cheatin&#8217; uh?') );
		//cross check the given referer
		check_admin_referer('expana-metaboxes');
		
		//process option saving
		
		//lets redirect the post request into get request (you may add additional params at the url, if you need to show save results
		wp_redirect($_POST['_wp_http_referer']);		
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
		wp_enqueue_script('expana_highcharts_data');
		wp_enqueue_script('expana_highcharts_drilldown');

		add_meta_box( 'expana_report', 'Report', array( $this, 'callback_dashboard_report' ), $this->pagehook, 'normal', 'core' );
		add_meta_box( 'expana_visit_length_of_visits', 'Visit Length of Visits', array( $this, 'callback_dashboard_length_of_visits'), $this->pagehook, 'normal', 'core' );
		add_meta_box( 'expana_visit_summary', 'Visit Summary', array( $this, 'callback_dashboard_visit_summary'), $this->pagehook, 'normal', 'core' );
		add_meta_box( 'expana_live', 'Live', array( $this, 'callback_dashboard_live'), $this->pagehook, 'normal', 'core' );
		add_meta_box( 'expana_visit_time', 'Visit Information Per LocalTime', array( $this, 'callback_dashboard_visit_time'), $this->pagehook, 'side', 'core' );
		add_meta_box( 'expana_devices', 'Device Types', array( $this, 'callback_dashboard_devices'), $this->pagehook, 'column3', 'core' );
		add_meta_box( 'expana_resolutions', 'Resolutions', array( $this, 'callback_dashboard_resolutions'), $this->pagehook, 'side', 'core' );
		add_meta_box( 'expana_browsers', 'Browser Version', array( $this, 'callback_dashboard_browsers'), $this->pagehook, 'side', 'core' );
		add_meta_box( 'expana_visitor_os', 'Visitor OS', array( $this, 'callback_dashboard_visitor_os'), $this->pagehook, 'side', 'core' );
		add_meta_box( 'expana_visitor_map_new', 'Visitor Map', array( $this, 'callback_dashboard_visitor_map_new'), $this->pagehook, 'column3', 'core' );
		add_meta_box( 'expana_referrers', 'Referrers', array( $this, 'callback_dashboard_referrers'), $this->pagehook, 'column3', 'core' );
		add_meta_box( 'expana_search_engines', 'Search Engines', array( $this, 'callback_dashboard_search_engines'), $this->pagehook, 'normal', 'core' );
		add_meta_box( 'expana_goals', 'Goals', array( $this, 'callback_dashboard_goals'), $this->pagehook, 'column3', 'core' );
		add_meta_box( 'expana_social_media_new', 'Social Media', array( $this, 'callback_dashboard_social_media_new'), $this->pagehook, 'side', 'core' );
		add_meta_box( 'expana_insights', 'Movers and Shakers', array( $this, 'callback_dashboard_insights'), $this->pagehook, 'column3', 'core' );
	}

	public function callback_dashboard_report()
	{
		$visits = $this->query_piwik_api(NULL, array(
			'token_auth'	=> $this->get_token_auth(),
			'idSite' 		=> $this->get_id_site(),
			'method'		=> 'VisitsSummary.getVisits'
			)); 

		$unique_visitors = $this->query_piwik_api(NULL, array(
			'token_auth'	=> $this->get_token_auth(),
			'idSite' 		=> $this->get_id_site(),
			'method'		=> 'VisitsSummary.getUniqueVisitors'
			)); 

		$actions = $this->query_piwik_api(NULL, array(
			'token_auth'	=> $this->get_token_auth(),
			'idSite' 		=> $this->get_id_site(),
			'method'		=> 'VisitsSummary.getActions'
			)); 

		$max_actions = $this->query_piwik_api(NULL, array(
			'token_auth'	=> $this->get_token_auth(),
			'idSite' 		=> $this->get_id_site(),
			'method'		=> 'VisitsSummary.getMaxActions'
			)); 

		$bounce_count = $this->query_piwik_api(NULL, array(
			'token_auth'	=> $this->get_token_auth(),
			'idSite' 		=> $this->get_id_site(),
			'method'		=> 'VisitsSummary.getBounceCount'
			)); 

		$visits_converted = $this->query_piwik_api(NULL, array(
			'token_auth'	=> $this->get_token_auth(),
			'idSite' 		=> $this->get_id_site(),
			'method'		=> 'VisitsSummary.getVisitsConverted'
			));

		$visits_length_pretty = $this->query_piwik_api(NULL, array(
			'token_auth'	=> $this->get_token_auth(),
			'idSite' 		=> $this->get_id_site(),
			'method'		=> 'VisitsSummary.getSumVisitsLengthPretty'
			));

		print_r (var_dump($visits)."<br />");
		print_r (var_dump($unique_visitors)."<br />");
		print_r (var_dump($actions)."<br />");
		print_r (var_dump($max_actions)."<br />");
		print_r (var_dump($bounce_count)."<br />");
		print_r (var_dump($visits_converted)."<br />");
		print_r (var_dump($visits_length_pretty)."<br />");
	}

	public function callback_dashboard_length_of_visits()
	{
		$piwik_response = $this->query_piwik_api(NULL, array(
			'token_auth'	=> $this->get_token_auth(),
			'idSite' 		=> $this->get_id_site(),
			'method'		=> 'VisitorInterest.getNumberOfVisitsPerVisitDuration'
			)); 

		if ($piwik_response['content'] !== '[]') {
		?>
			
		<div class="canvas-holder">
			<div id="visit_duration_chart" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
		</div>
		
		<script language="JavaScript">
            jQuery(document).ready(function($) {

				var visit_duration = jQuery.parseJSON('{"visit_duration_data": <?php echo $piwik_response['content']; ?> }');
				
				var visit_duration_label = [];
				var visit_duration_value = [];
				var visit_duration_data = [];
				var visit_duration_data_item = [];

				for (var i in visit_duration.visit_duration_data) {
					visit_duration_data_item.push(visit_duration.visit_duration_data[i].label);
					visit_duration_data_item.push(parseFloat(visit_duration.visit_duration_data[i].nb_visits));
					visit_duration_data.push(visit_duration_data_item);

					visit_duration_data_item = [];
				}

				console.log(visit_duration_data);

			    $('#visit_duration_chart').highcharts({
			        chart: {
			            type: 'column'
			        },
			        title: {
			            text: null
			        },
			        subtitle: {
			            text: null
			        },
			        xAxis: {
			            type: 'category',
			            labels: {
			                rotation: -45,
			                style: {
			                    fontSize: '13px',
			                    fontFamily: 'Verdana, sans-serif'
			                }
			            }
			        },
			        yAxis: {
			            min: 0,
			            title: {
			                text: 'Visits'
			            }
			        },
			        legend: {
			            enabled: false
			        },
			        tooltip: {
			            pointFormat: '{point.y:.0f} visits'
			        },
			        series: [{
			            name: 'visits',
			            data: visit_duration_data,
			            dataLabels: {
			                enabled: true,
			                rotation: -90,
			                color: '#FFFFFF',
			                align: 'right',
			                x: 4,
			                y: 10,
			                style: {
			                    fontSize: '13px',
			                    fontFamily: 'Verdana, sans-serif',
			                    textShadow: '0 0 3px black'
			                }
			            }
			        }]
			    });
            });
		</script>
	<?php }
		else { ?>

		<div class="canvas-holder">
			<div class="no-data">
				<span class="x-mark">
					<span class="line left"></span>
					<span class="line right"></span>
				</span>
			</div>

			<h2>No Data Available</h2>
			<p style="display: block;">Try another date range?</p>
		</div>

	<?php }
	}

	public function callback_dashboard_visit_time()
	{
		$piwik_response = $this->query_piwik_api(NULL, array(
			'token_auth'	=> $this->get_token_auth(),
			'idSite' 		=> $this->get_id_site(),
			'method'		=> 'VisitTime.getVisitInformationPerLocalTime'
			));

		if ($piwik_response['content'] !== '[]') {
		?>			
		<div class="canvas-holder">
			<div id="visit_time_chart" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
		</div>
		
		<script language="JavaScript">
            jQuery(document).ready(function($) {

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

				$('#visit_time_chart').highcharts({
				    title: {
				        text: null,
				        x: -20 //center
				    },
				    subtitle: {
				        text: null,
				        x: -20
				    },
				    xAxis: {
				        categories: visit_time_label
				    },
				    yAxis: {
				        title: {
				            text: 'Visits'
				        },
				        plotLines: [{
				            value: 0,
				            width: 1,
				            color: '#808080'
				        }]
				    },
				    legend: {
				        layout: 'horizontal',
				        align: 'center',
				        verticalAlign: 'bottom',
				        borderWidth: 0
				    },
				    series: [{
				        name: 'Visits',
				        data: visit_time_visits
				    }, {
				        name: 'Unique Visits',
				        data: visit_time_uniq_visitors
				    }]
				});
				});
		</script>
	<?php }
		else { ?>

		<div class="canvas-holder">
			<div class="no-data">
				<span class="x-mark">
					<span class="line left"></span>
					<span class="line right"></span>
				</span>
			</div>

			<h2>No Data Available</h2>
			<p style="display: block;">Try another date range?</p>
		</div>

	<?php }
	}

	public function callback_dashboard_devices()
	{
		$piwik_response = $this->query_piwik_api(NULL, array(
			'token_auth'	=> $this->get_token_auth(),
			'idSite' 		=> $this->get_id_site(),
			'method'		=> 'DevicesDetection.getType'
			));

		if ($piwik_response['content'] !== '[]') {
		?>

		<div class="canvas-holder">
			<div id="devices_chart" style="min-width: 300px; height: 400px; max-width: 600px; margin: 0 auto"></div>
		</div>

		<script language="JavaScript">
            jQuery(document).ready(function($) {
                $('#devices_chart').attr('width', $('#devices_chart').parent().width());

				var devices = jQuery.parseJSON('{"devices_data": <?php echo $piwik_response['content']; ?> }');
				
				var data = [];

				for (var i in devices.devices_data) {
					
					data_item = {};
					data_item.name = devices.devices_data[i].label;

					if (! devices.devices_data[i].nb_uniq_visitors)
					{
						data_item.y = devices.devices_data[i].sum_daily_nb_uniq_visitors;
					}
					else
					{
						data_item.y = devices.devices_data[i].nb_uniq_visitors;
					}

					data.push(data_item);
				}

				$(function () {
				    $('#devices_chart').highcharts({
				        chart: {
				            plotBackgroundColor: null,
				            plotBorderWidth: 0,
				            plotShadow: false
				        },
				        title: {
				            text: 'Device<br>types',
				            align: 'center',
				            verticalAlign: 'middle',
				            y: 50
				        },
				        tooltip: {
				            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
				        },
				        plotOptions: {
				            pie: {
				                dataLabels: {
				                    enabled: true,
				                    distance: -50,
				                    style: {
				                        fontWeight: 'bold',
				                        color: 'white',
				                        textShadow: '0px 1px 2px black'
				                    }
				                },
				                startAngle: -90,
				                endAngle: 90,
				                center: ['50%', '75%']
				            }
				        },
				        series: [{
				            type: 'pie',
				            name: 'Device',
				            innerSize: '50%',
				            data: data
				        }]
				    });
				});
            });
		</script>
	<?php }
		else { ?>

		<div class="canvas-holder">
			<div class="no-data">
				<span class="x-mark">
					<span class="line left"></span>
					<span class="line right"></span>
				</span>
			</div>

			<h2>No Data Available</h2>
			<p style="display: block;">Try another date range?</p>
		</div>

	<?php }
	}

	public function callback_dashboard_browsers()
	{
		$piwik_response = $this->query_piwik_api(NULL, array(
			'token_auth'	=> $this->get_token_auth(),
			'idSite' 		=> $this->get_id_site(),
			'method'		=> 'DevicesDetection.getBrowserVersions',
			'format'		=> 'Tsv'
			));

		$piwik_response2 = $this->query_piwik_api(NULL, array(
			'token_auth'	=> $this->get_token_auth(),
			'idSite' 		=> $this->get_id_site(),
			'method'		=> 'DevicesDetection.getBrowserVersions',
			));

		if ($piwik_response2['content'] !== '[]') {
		?>

		<div class="canvas-holder">
			<div id="browsers_chart" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
		</div>

		<script language="JavaScript">
			jQuery(document).ready(function($) {

			    Highcharts.data({
			        csv: document.getElementById('browser_verisions_tsv').innerHTML,
			        itemDelimiter: '\t',
			        parsed: function (columns) {

			            var brands = {},
			                brandsData = [],
			                versions = {},
			                drilldownSeries = [];

			            // Parse percentage strings
			            columns[1] = $.map(columns[1], function (value) {
			                return value;
			            });

			            $.each(columns[0], function (i, name) {
			                var brand,
			                    version;

			                if (i > 0) {

			                    // Remove special edition notes
			                    name = name.split(' -')[0];

			                    // Split into brand and version
			                    version = name.match(/([0-9]+[\.0-9x]*)/);
			                    if (version) {
			                        version = version[0];
			                    }
			                    brand = name.replace(version, '');

			                    // Create the main data
			                    if (!brands[brand]) {
			                        brands[brand] = columns[1][i];
			                    } else {
			                        brands[brand] += columns[1][i];
			                    }

			                    // Create the version data
			                    if (version !== null) {
			                        if (!versions[brand]) {
			                            versions[brand] = [];
			                        }
			                        versions[brand].push(['v' + version, columns[1][i]]);
			                    }
			                }

			            });

			            $.each(brands, function (name, y) {
			                brandsData.push({
			                    name: name,
			                    y: y,
			                    drilldown: versions[name] ? name : null
			                });
			            });
			            $.each(versions, function (key, value) {
			                drilldownSeries.push({
			                    name: key,
			                    id: key,
			                    data: value
			                });
			            });

			            // Create the chart
			            $('#browsers_chart').highcharts({
			                chart: {
			                    type: 'column'
			                },
			                title: {
			                    text: null
			                },
			                subtitle: {
			                    text: 'Click the columns to view versions'
			                },
			                xAxis: {
			                    type: 'category'
			                },
			                yAxis: {
			                    title: {
			                        text: 'Unique Visits'
			                    }
			                },
			                legend: {
			                    enabled: false
			                },
			                plotOptions: {
			                    series: {
			                        borderWidth: 0,
			                        dataLabels: {
			                            enabled: true,
			                            format: '{point.y:.0f}'
			                        }
			                    }
			                },

			                tooltip: {
			                    headerFormat: '<span style="font-size:11px">{series.name}</span><br>',
			                    pointFormat: '<span style="color:{point.color}">{point.name}</span>: <b>{point.y:.0f}</b><br/>'
			                },

			                series: [{
			                    name: 'Brands',
			                    colorByPoint: true,
			                    data: brandsData
			                }],
			                drilldown: {
			                    series: drilldownSeries
			                }
			            });
			        }
			    });
			});
		</script>

		<pre id="browser_verisions_tsv" style="display:none"><?php print_r($piwik_response['content']); ?></pre>

	<?php }
		else { ?>

		<div class="canvas-holder">
			<div class="no-data">
				<span class="x-mark">
					<span class="line left"></span>
					<span class="line right"></span>
				</span>
			</div>

			<h2>No Data Available</h2>
			<p style="display: block;">Try another date range?</p>
		</div>

	<?php }
	}

	public function callback_dashboard_resolutions()
	{
		$piwik_response = $this->query_piwik_api(NULL, array(
			'token_auth'	=> $this->get_token_auth(),
			'idSite' 		=> $this->get_id_site(),
			'method'		=> 'UserSettings.getResolution'
			));

		if ($piwik_response['content'] !== '[]') {
		?>

		<div class="canvas-holder">
			<div id="resolutions_chart" style="min-width: 310px; height: 400px; max-width: 600px; margin: 0 auto"></div>
		</div>

		<script language="JavaScript">
		    jQuery(document).ready(function($) {

				var resolutions = jQuery.parseJSON('{"resolutions_data": <?php echo $piwik_response['content']; ?> }');
				
				var data = [];
				var options = {
					segmentShowStroke : true,
					responsive : true,
				};

				for (var i in resolutions.resolutions_data) {

					if (i > 15) {
						break;
					}
					
					data_item = {};
					data_item.name = resolutions.resolutions_data[i].label;

					if (! resolutions.resolutions_data[i].nb_uniq_visitors)
					{
						data_item.y = resolutions.resolutions_data[i].sum_daily_nb_uniq_visitors;
					}
					else
					{
						data_item.y = resolutions.resolutions_data[i].nb_uniq_visitors;
					}

					data.push(data_item);
				}

		        // Build the chart
		        $('#resolutions_chart').highcharts({
		            chart: {
		                plotBackgroundColor: null,
		                plotBorderWidth: null,
		                plotShadow: false
		            },
		            title: {
		                text: null
		            },
		            tooltip: {
		                pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
		            },
		            plotOptions: {
		                pie: {
		                    allowPointSelect: true,
		                    cursor: 'pointer',
		                    dataLabels: {
		                        enabled: false
		                    },
		                    showInLegend: true
		                }
		            },
		            series: [{
		                type: 'pie',
		                name: 'Resolution',
		                data: data
		            }]
		        });
		    });
		</script>
	
	<?php }
		else { ?>

		<div class="canvas-holder">
			<div class="no-data">
				<span class="x-mark">
					<span class="line left"></span>
					<span class="line right"></span>
				</span>
			</div>

			<h2>No Data Available</h2>
			<p style="display: block;">Try another date range?</p>
		</div>

	<?php }
	}

	public function callback_dashboard_social_media()
	{
		$piwik_response = $this->query_piwik_api(NULL, array(
			'token_auth'	=> $this->get_token_auth(),
			'idSite' 		=> $this->get_id_site(),
			'method'		=> 'Referrers.getSocials'
			));

		if ($piwik_response['content'] !== '[]') {
		?>

		<div class="canvas-holder">
			<!-- Canvas removed -->
		</div>

		<script language="JavaScript">
            jQuery(document).ready(function($) {
                $('#social_media_chart').attr('width', $('#social_media_chart').parent().width());

				var social_media = jQuery.parseJSON('{"social_media_data": <?php echo $piwik_response['content']; ?> }');

				var data = [];

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
		else { ?>

		<div class="canvas-holder">
			<div class="no-data">
				<span class="x-mark">
					<span class="line left"></span>
					<span class="line right"></span>
				</span>
			</div>

			<h2>No Data Available</h2>
			<p style="display: block;">Try another date range?</p>
		</div>

	<?php }
	}

	public function callback_dashboard_social_media_new()
	{
		$piwik_response = $this->query_piwik_api(NULL, array(
			'token_auth'	=> $this->get_token_auth(),
			'idSite' 		=> $this->get_id_site(),
			'method'		=> 'Referrers.getSocials'
			));

		if ($piwik_response['content'] !== '[]') {
		?>

		<div class="canvas-holder">
			<div id="social_media_chart" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
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

			    $('#social_media_chart').highcharts({
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
			            name: 'Social Network Referrers',
			            data: data
			        }]
			    });
			});
		</script>
	
	<?php }
		else { ?>

		<div class="canvas-holder">
			<div class="no-data">
				<span class="x-mark">
					<span class="line left"></span>
					<span class="line right"></span>
				</span>
			</div>

			<h2>No Data Available</h2>
			<p style="display: block;">Try another date range?</p>
		</div>

	<?php }
	}

	public function callback_dashboard_visitor_map_new()
	{
		$piwik_response = $this->query_piwik_api(NULL, array(
			'token_auth'	=> $this->get_token_auth(),
			'idSite' 		=> $this->get_id_site(),
			'method'		=> 'UserCountry.getCountry'
			));

		if ($piwik_response['content'] !== '[]') {
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
							label.append(": " + data[code] + " visits");
						}
						else
						{
							label.append(": No visit");
						}
					}
				});

				jQuery('#vmap').attr('width', jQuery('#vmap').parent().width()).attr('height', jQuery('#vmap').parent().height());
			});
		</script>
		 
		<div id="vmap" style="height: 380px;"></div>

	<?php }
		else { ?>

		<div class="canvas-holder">
			<div class="no-data">
				<span class="x-mark">
					<span class="line left"></span>
					<span class="line right"></span>
				</span>
			</div>

			<h2>No Data Available</h2>
			<p style="display: block;">Try another date range?</p>
		</div>

	<?php }
	}

	public function callback_dashboard_visit_summary()
	{
		$piwik_response = $this->query_piwik_api(NULL, array(
			'token_auth'	=> $this->get_token_auth(),
			'idSite' 		=> $this->get_id_site(),
			'method'		=> 'VisitsSummary.get',
			'period'		=> 'day'
			)); 

		if ($piwik_response['content'] !== '[]') {
		?>
			
		<div class="canvas-holder">
			<div id="visit_summary_chart" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
		</div>
		
		<script language="JavaScript">
            jQuery(document).ready(function($) {

				var visit_summary = jQuery.parseJSON('{"visit_summary_data": <?php echo $piwik_response['content']; ?> }');
				
				var visit_summary_label = [];
				var visit_summary_uniq_visitors = [];
				var visit_summary_visits = [];

				console.log(visit_summary.visit_summary_data);

				for (var i in visit_summary.visit_summary_data) {

					visit_summary_label.push(i);
					
					if (visit_summary.visit_summary_data[i].nb_uniq_visitors > 0)
					{
						visit_summary_uniq_visitors.push(visit_summary.visit_summary_data[i].nb_uniq_visitors);
					}
					else
					{
						visit_summary_uniq_visitors.push(0);
					}

					if (visit_summary.visit_summary_data[i].nb_visits > 0)
					{
						visit_summary_visits.push(visit_summary.visit_summary_data[i].nb_visits);
					}
					else
					{
						visit_summary_visits.push(0);
					}
				}

				$('#visit_summary_chart').highcharts({
				    title: {
				        text: null,
				        x: -20 //center
				    },
				    subtitle: {
				        text: null,
				        x: -20
				    },
				    xAxis: {
				        categories: visit_summary_label,
				        type: 'datetime',
				        labels: {
				        	step: 14,
				        	enabled: false
				        }
				    },
				    yAxis: {
				        title: {
				            text: 'Visits'
				        },
				        plotLines: [{
				            value: 0,
				            width: 1,
				            color: '#808080'
				        }]
				    },
				    yAxis: {
				    	min: 0
				    },
				    legend: {
				        layout: 'horizontal',
				        align: 'center',
				        verticalAlign: 'bottom',
				        borderWidth: 0
				    },
				    series: [{
				        name: 'Visits',
				        data: visit_summary_visits
				    }, {
				        name: 'Unique Visits',
				        data: visit_summary_uniq_visitors
				    }]
				});
				});
		</script>
	<?php }
		else { ?>

		<div class="canvas-holder">
			<div class="no-data">
				<span class="x-mark">
					<span class="line left"></span>
					<span class="line right"></span>
				</span>
			</div>

			<h2>No Data Available</h2>
			<p style="display: block;">Try another date range?</p>
		</div>

	<?php } 
	}

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
	{
		$piwik_response = $this->query_piwik_api(NULL, array(
			'token_auth'	=> $this->get_token_auth(),
			'idSite' 		=> $this->get_id_site(),
			'method'		=> 'DevicesDetection.getOsVersions',
			'format'		=> 'Tsv'
			));

		$piwik_response2 = $this->query_piwik_api(NULL, array(
			'token_auth'	=> $this->get_token_auth(),
			'idSite' 		=> $this->get_id_site(),
			'method'		=> 'DevicesDetection.getOsVersions'
			));

		if ($piwik_response2['content'] !== '[]') {
		?>

		<div class="canvas-holder">
			<div id="os_version_chart" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
		</div>

		<script language="JavaScript">
			jQuery(document).ready(function($) {

			    Highcharts.data({
			        csv: document.getElementById('os_version_tsv').innerHTML,
			        itemDelimiter: '\t',
			        parsed: function (columns) {

			            var brands = {},
			                brandsData = [],
			                versions = {},
			                drilldownSeries = [];

			            // Parse percentage strings
			            columns[1] = $.map(columns[1], function (value) {
			                return value;
			            });

			            $.each(columns[0], function (i, name) {
			                var brand,
			                    version;

			                if (i > 0) {

			                    // Remove special edition notes
			                    name = name.split(' -')[0];

			                    // Split into brand and version
			                    version = name.match(/([0-9]+[\.0-9x]*)/);
			                    if (version) {
			                        version = version[0];
			                    }
			                    brand = name.replace(version, '');

			                    // Create the main data
			                    if (!brands[brand]) {
			                        brands[brand] = columns[1][i];
			                    } else {
			                        brands[brand] += columns[1][i];
			                    }

			                    // Create the version data
			                    if (version !== null) {
			                        if (!versions[brand]) {
			                            versions[brand] = [];
			                        }
			                        versions[brand].push(['v' + version, columns[1][i]]);
			                    }
			                }
			            });

			            $.each(brands, function (name, y) {
			                brandsData.push({
			                    name: name,
			                    y: y,
			                    drilldown: versions[name] ? name : null
			                });
			            });
			            $.each(versions, function (key, value) {
			                drilldownSeries.push({
			                    name: key,
			                    id: key,
			                    data: value
			                });
			            });

			            // Create the chart
			            $('#os_version_chart').highcharts({
			                chart: {
			                    type: 'column'
			                },
			                title: {
			                    text: null
			                },
			                subtitle: {
			                    text: 'Click the columns to view versions'
			                },
			                xAxis: {
			                    type: 'category'
			                },
			                yAxis: {
			                    title: {
			                        text: 'Unique Visits'
			                    }
			                },
			                legend: {
			                    enabled: false
			                },
			                plotOptions: {
			                    series: {
			                        borderWidth: 0,
			                        dataLabels: {
			                            enabled: true,
			                            format: '{point.y:.0f}'
			                        }
			                    }
			                },

			                tooltip: {
			                    headerFormat: '<span style="font-size:11px">{series.name}</span><br>',
			                    pointFormat: '<span style="color:{point.color}">{point.name}</span>: <b>{point.y:.0f}</b><br/>'
			                },

			                series: [{
			                    name: 'Brands',
			                    colorByPoint: true,
			                    data: brandsData
			                }],
			                drilldown: {
			                    series: drilldownSeries
			                }
			            });
			        }
			    });
			});
		</script>

		<pre id="os_version_tsv" style="display:none"><?php print_r($piwik_response['content']); ?></pre>

	<?php }
		else { ?>

		<div class="canvas-holder">
			<div class="no-data">
				<span class="x-mark">
					<span class="line left"></span>
					<span class="line right"></span>
				</span>
			</div>

			<h2>No Data Available</h2>
			<p style="display: block;">Try another date range?</p>
		</div>

	<?php }
	}

	public function callback_dashboard_referrers()
	{ ?>
		<iframe width="100%" height="830" src="<?php echo EXP_PIWIK_PROTO; ?>://<?php echo EXP_PIWIK_HOST; ?>/index.php?module=Widgetize&action=iframe&widget=1&moduleToWidgetize=Referrers&actionToWidgetize=getAll&idSite=<?php echo $this->get_id_site(); ?>&period=<?php echo $this->get_query_period(); ?>&date=<?php echo $this->get_query_date(); ?>&disableLink=1&widget=1&token_auth=<?php echo $this->get_token_auth(); ?>" scrolling="no" frameborder="0" marginheight="0" marginwidth="0"></iframe>
	<?php }

	public function callback_dashboard_search_engines()
	{
		$piwik_response = $this->query_piwik_api(NULL, array(
			'token_auth'	=> $this->get_token_auth(),
			'idSite' 		=> $this->get_id_site(),
			'method'		=> 'Referrers.getSearchEngines'
			));

		if ($piwik_response['content'] !== '[]') {
		?>

		<div class="canvas-holder">
			<div id="search_engine_chart" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
		</div>

		<script language="JavaScript">
			jQuery(document).ready(function($) {

				var search_engines = jQuery.parseJSON('{"search_engines_data": <?php echo $piwik_response['content']; ?> }');

				var data = [];

				for (var i in search_engines.search_engines_data) {
					data_item = {};
					data_item.name = search_engines.search_engines_data[i].label;

					if (! search_engines.search_engines_data[i].nb_uniq_visitors)
					{
						data_item.y = search_engines.search_engines_data[i].sum_daily_nb_uniq_visitors;
					}
					else
					{
						data_item.y = search_engines.search_engines_data[i].nb_uniq_visitors;
					}

					data.push(data_item);
				}

			    $('#search_engine_chart').highcharts({
			        chart: {
			            plotBackgroundColor: null,
			            plotBorderWidth: null,
			            plotShadow: false
			        },
			        title: {
			            text: null
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
			            name: 'Search engine share',
			            data: data
			        }]
			    });
			});
		</script>
	<?php }
		else { ?>

		<div class="canvas-holder">
			<div class="no-data">
				<span class="x-mark">
					<span class="line left"></span>
					<span class="line right"></span>
				</span>
			</div>

			<h2>No Data Available</h2>
			<p style="display: block;">Try another date range?</p>
		</div>

	<?php }
	}

	public function callback_dashboard_goals()
	{ ?>
		<iframe width="100%" height="400" src="<?php echo EXP_PIWIK_PROTO; ?>://<?php echo EXP_PIWIK_HOST; ?>/index.php?module=Widgetize&action=iframe&widget=1&moduleToWidgetize=Goals&actionToWidgetize=widgetGoalsOverview&idSite=<?php echo $this->get_id_site(); ?>&period=<?php echo $this->get_query_period(); ?>&date=<?php echo $this->get_query_date(); ?>&disableLink=1&widget=1&token_auth=<?php echo $this->get_token_auth(); ?>" scrolling="no" frameborder="0" marginheight="0" marginwidth="0"></iframe>
	<?php }

	public function callback_dashboard_insights()
	{ ?>
		<iframe width="100%" height="400" src="<?php echo EXP_PIWIK_PROTO; ?>://<?php echo EXP_PIWIK_HOST; ?>/index.php?module=Widgetize&action=iframe&widget=1&moduleToWidgetize=Insights&actionToWidgetize=getOverallMoversAndShakers&idSite=<?php echo $this->get_id_site(); ?>&period=<?php echo $this->get_query_period(); ?>&date=<?php echo $this->get_query_date(); ?>&disableLink=1&widget=1&token_auth=<?php echo $this->get_token_auth(); ?>" scrolling="no" frameborder="0" marginheight="0" marginwidth="0"></iframe>
	<?php }

}

new ExpressionsAnalytics();
