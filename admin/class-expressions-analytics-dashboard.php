<?php

/**
 * The dashboard-specific functionality of the plugin.
 *
 * @link       http://spiders.syr.edu
 * @since      2.0.0
 *
 * @package    expressions-analytics
 * @subpackage expressions-analytics/admin
 * @author     Michael Zhang <lzhang43@syr.edu>
 */

class Expressions_Analytics_Dashboard {

	private $piwik;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    2.0.0
	 * @var      string    $plugin_name 	The name of this plugin.
	 * @var      string    $version			The version of this plugin.
	 */
	public function __construct()
	{

		$this->setting_service = new Expressions_Analytics_Setting_Service;
		$this->report_service  = new Expressions_Analytics_Report_Service;
		$this->suwi = $this->initPiwik();

	}

	/**
	 * Define widgets
	 *
	 * @since 2.0.0
	 */
 	private $widgets = array(

				['report', 'Report', null, null],
				['widget1', 'Widget 1', 'normal', 'default'],
				['widget2', 'Widget 2', 'side', 'default'],
				['widget3', 'Widget 3', 'column3', 'default'],
				['widget4', 'Widget 4', 'normal', 'default'],
				['widget5', 'Widget 5', 'side', 'default'],
				['widget6', 'Widget 6', 'column3', 'default'],
				['widget7', 'Widget 7', 'normal', 'default'],
				['widget8', 'Widget 8', 'side', 'default'],

			);

	/**
	 * Initialize Piwik class
	 *
	 * @since 2.0.0
	 */
	private function initPiwik()
	{
		$this->piwik = new Piwik($this->setting_service->parse_piwik_api_url(), $this->setting_service->get_auth_token(), $this->setting_service->get_site_id());

	 	$this->piwik->setRange('2015-1-6', Piwik::DATE_YESTERDAY); //All data from the first to the last date
	 	$this->piwik->setPeriod(Piwik::PERIOD_DAY);
	 	$this->piwik->setFormat(Piwik::FORMAT_JSON);
	 	$this->piwik->setLanguage('en');

	 	return $this->piwik;
	}

	/**
	 * Displays the dashboard.
	 *
	 * @since 2.0.0
	 */
	public function expana_dashboard()
	{
		$screen = get_current_screen();

		$columns = absint( $screen->get_columns() );
		$columns_css = null;

		if ( $columns )
		{
			$columns_css = " columns-$columns";
		}

		include (plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/expressions-analytics-admin-dashboard.php');

		wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
		wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );

	}

	/**
	 * List all widgets.
	 *
	 * @since 2.0.0
	 */
	 public function expana_widgets()
	 {
	 	foreach ($this->widgets as $widget)
	 	{
	 		$this->expana_widgets_register( $widget[0], $widget[1], $widget[2], $widget[3] );
	 	}
	 }

	/**
	 * Actual method that registers widgets.
	 *
	 * @param $id 		Unique ID for the widget
	 * @param $name 	Display name for the widget
	 * @param $column 	The location of the widget ('normal', 'side', 'column3', or 'column4')
	 * @param $priority The priority of the widget ('high', 'core', 'default' or 'low')
	 * @since 2.0.0
	 */
	 private function expana_widgets_register( $id, $name, $column = 'normal', $priority = 'core' )
	 {
	 	$setting_service = new Expressions_Analytics_Setting_Service;

	 	add_meta_box ( 'expana_'.$id, $name, array( $this, 'expana_widgets_callback_'.$id), $setting_service->pagehook, $column, $priority );
	 }

	/**
	 * Dashboard Widget: Report
	 *
	 * @since 2.0.0
	 */
	 public function expana_widgets_callback_report()
	 {
	 	include (plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/widget_report.php');
	 }

	/**
	 * Dashboard Widget: Widget 1
	 *
	 * @since 2.0.0
	 */
	 public function expana_widgets_callback_widget1()
	 {
	 	echo "<div class='main'>Main</div>";
	 	echo "<div class='sub'>Sub</div>";
	 }

	/**
	 * Dashboard Widget: Widget 2
	 *
	 * @since 2.0.0
	 */
	 public function expana_widgets_callback_widget2()
	 {
	 	echo "<div class='main'>Main</div>";
	 	echo "<div class='sub'>Sub</div>";
	 }

	/**
	 * Dashboard Widget: Widget 3
	 *
	 * @since 2.0.0
	 */
	 public function expana_widgets_callback_widget3()
	 {
	 	echo "<div class='main'>Main</div>";
	 	echo "<div class='sub'>Sub</div>";
	 }

	/**
	 * Dashboard Widget: Widget 4
	 *
	 * @since 2.0.0
	 */
	 public function expana_widgets_callback_widget4()
	 {
	 	echo "<div class='main'>Main</div>";
	 	echo "<div class='sub'>Sub</div>";
	 }

	/**
	 * Dashboard Widget: Widget 5
	 *
	 * @since 2.0.0
	 */
	 public function expana_widgets_callback_widget5()
	 {
	 	echo "<div class='main'>Main</div>";
	 	echo "<div class='sub'>Sub</div>";
	 }

	/**
	 * Dashboard Widget: Widget 6
	 *
	 * @since 2.0.0
	 */
	 public function expana_widgets_callback_widget6()
	 {
	 	echo "<div class='main'>Main</div>";
	 	echo "<div class='sub'>Sub</div>";
	 }

	/**
	 * Dashboard Widget: Widget 7
	 *
	 * @since 2.0.0
	 */
	 public function expana_widgets_callback_widget7()
	 {
	 	echo "<div class='main'>Main</div>";
	 	echo "<div class='sub'>Sub</div>";
	 }

	/**
	 * Dashboard Widget: Widget 8
	 *
	 * @since 2.0.0
	 */
	 public function expana_widgets_callback_widget8()
	 {
	 	echo "<div class='main'>Main</div>";
	 	echo "<div class='sub'>Sub</div>";
	 }

}
