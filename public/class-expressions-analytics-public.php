<?php

/**
 * The public-facing functionality (mostly the trackers) of the plugin.
 *
 * @link       http://spiders.syr.edu
 * @since      2.0.0
 *
 * @package    expressions-analytics
 * @subpackage expressions-analytics/public
 * @author     Michael Zhang <lzhang43@syr.edu>
 */

class Expressions_Analytics_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    2.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    2.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    2.0.0
	 * @var      string    $plugin_name       The name of the plugin.
	 * @var      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version )
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->setting_service = new Expressions_Analytics_Setting_Service;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    2.0.0
	 */
	public function enqueue_scripts()
	{
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/expressions-analytics-public.js', array( 'jquery' ), $this->version, false );
	}

	/**
	 * Insert all the tracking code.
	 */
	public function insert_tracking_code()
	{
		$settings = $this->settings_get();
		
		$piwik_global_tracking_domain = EXPANA_PIWIK_GLOBAL_TRACKING_DOMAIN;
		$piwik_rest_api = EXP_PIWIK_HOST;
		$piwik_global_tracking_id = EXPANA_PIWIK_GLOBAL_TRACKING_ID;
		
		//Piwik code for the current production level.
		$piwik_site_id = null;
		switch ( EXP_PRODUCTION_LEVEL ) {
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
		if ( is_int( $piwik_site_id ) ) {
			$site_domain = @parse_url( get_site_url(), PHP_URL_HOST );
			if ( ! empty( $site_domain ) ) {
				echo $this->tracking_code_piwik_start(
					'*.' . $site_domain,
					$piwik_rest_api,
					$piwik_site_id
				);

				//Global tracking Piwik.
				if (
					is_string( $piwik_global_tracking_domain ) && ! empty( $piwik_global_tracking_domain ) &&
					is_string( $piwik_rest_api ) && ! empty( $piwik_rest_api ) &&
					is_int( $piwik_global_tracking_id )
				) {
					echo $this->tracking_code_piwik_body(
						$piwik_global_tracking_domain,
						$piwik_rest_api,
						$piwik_global_tracking_id
					);
				}

				echo $this->tracking_code_piwik_body(
					'*.' . $site_domain,
					$piwik_rest_api,
					$piwik_site_id
				);

				echo $this->tracking_code_piwik_end();
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

}
