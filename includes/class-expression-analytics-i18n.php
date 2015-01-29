<?php

/**
 * This file defines the internationalization functionality
 *
 * Loads and defines the internationalization files for expression analytics
 * so that it is ready for translation.
 *
 * @link       http://spiders.syr.edu
 * @since      2.0.0
 *
 * @package    expression-analytics
 * @subpackage expression-analytics/includes
 * @author     Michael Zhang <lzhang43@syr.edu>
 */

class Expression_Analytics_i18n {

	/**
	 * The domain specified for this plugin.
	 *
	 * @since    2.0.0
	 * @access   private
	 * @var      string    $domain    The domain identifier for this plugin.
	 */
	private $domain;

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    2.0.0
	 */
	public function load_plugin_textdomain()
	{

		load_plugin_textdomain(
			$this->domain,
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}

	/**
	 * Set the domain equal to that of the specified domain.
	 *
	 * @since    2.0.0
	 * @param    string    $domain    The domain that represents the locale of this plugin.
	 */
	public function set_domain( $domain )
	{
		$this->domain = $domain;
	}

}
