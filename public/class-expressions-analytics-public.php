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
		$this->tracker_service = new Expressions_Analytics_Tracker_Service;

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
	 *
	 * @since 	 2.0.0
	 */
	public function insert_tracking_code()
	{
		$settings = $this->setting_service->get_settings();

		if ( empty($settings) )
		{
			return false;
		}

		return $this->tracker_service->print_tracking_code( $settings );
	}

}
