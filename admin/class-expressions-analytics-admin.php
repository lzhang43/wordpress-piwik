<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://spiders.syr.edu
 * @since      2.0.0
 *
 * @package    expressions-analytics
 * @subpackage expressions-analytics/admin
 * @author     Michael Zhang <lzhang43@syr.edu>
 */

class Expressions_Analytics_Admin {

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

	private $setting_service;

	private $dashboard;
	
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    2.0.0
	 * @var      string    $plugin_name       The name of this plugin.
	 * @var      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version )
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->setting_service = new Expressions_Analytics_Setting_Service( $this->plugin_name, $this->version );

	}

	/**
	 * Register the stylesheets for the Dashboard.
	 *
	 * @since    2.0.0
	 */
	public function enqueue_styles()
	{
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/admin.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'jquery-ui_style', plugin_dir_url( __FILE__ ) . 'js/jquery-ui/themes/smoothness/jquery-ui.min.css', array(), $this->version, 'all' );
		wp_enqueue_style('dashicons');
	}

	/**
	 * Register the JavaScript for the dashboard.
	 *
	 * @since    2.0.0
	 */
	public function enqueue_scripts()
	{
		// Built-in WordPress libraries
		wp_enqueue_script( 'common' );
		wp_enqueue_script( 'postbox' );
		wp_enqueue_script( 'dashboard' );

		// 3rd-party libraries
		wp_enqueue_script( 'highcharts', plugin_dir_url( __FILE__ ) . 'js/highcharts/highcharts.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'highcharts-data', plugin_dir_url( __FILE__ ) . 'js/highcharts/modules/data.js', array( 'highcharts' ), $this->version, false );
		wp_enqueue_script( 'highcharts-exporting', plugin_dir_url( __FILE__ ) . 'js/highcharts/modules/exporting.js', array( 'highcharts' ), $this->version, false );
		wp_enqueue_script( 'highcharts-drilldown', plugin_dir_url( __FILE__ ) . 'js/highcharts/modules/drilldown.js', array( 'highcharts' ), $this->version, false );
		wp_enqueue_script( 'jquery-ui', plugin_dir_url( __FILE__ ) . 'js/jquery-ui/jquery-ui.min.js', array( 'jquery' ), $this->version, false );

		// application JS files
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/admin.js', array( 'highcharts' ), $this->version, true );
	}

	/**
	 * Add to admin panel menu.
	 *
	 * @since 2.0.0
	 */
	public function build_admin_menu()
	{
		add_options_page(
			__( $this->setting_service->admin_panel_page_title, 'expana' ),
			__( $this->setting_service->admin_panel_menu_label, 'expana' ),
			$this->setting_service->admin_panel_settings_capability,
			$this->setting_service->admin_panel_page_slug,
			array( $this->setting_service, 'callback_settings_page' )
		);
	}

	/**
	 * Build settings page
	 *
	 * @since    2.0.0
	 */
	public function build_settings()
	{
		return $this->setting_service->build_settings();
	}

	/**
	 * Build dashboard page
	 *
	 * @since    2.0.0
	 */
	public function build_dashboard()
	{
		return $this->setting_service->build_dashboard();
	}

}
