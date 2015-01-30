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
	 * The settings option key.
	 */
	private $settings_name = 'expana_settings';

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
		'google_web_property_id' => ''
	);

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

}
