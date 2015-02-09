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

 				//Thanks to PHP 5.3, we can't use [] here
				array('report', 'Report', null, null),
				array('widget1', 'Widget 1', 'normal', 'default'),
				array('widget2', 'Widget 2', 'side', 'default'),
				array('widget3', 'Widget 3', 'column3', 'default'),
				array('widget4', 'Widget 4', 'normal', 'default'),
				array('widget5', 'Widget 5', 'side', 'default'),
				array('widget6', 'Widget 6', 'column3', 'default'),
				array('widget7', 'Widget 7', 'normal', 'default'),
				array('widget8', 'Widget 8', 'side', 'default'),

			);

	/**
	 * Initialize Piwik class
	 *
	 * @since 2.0.0
	 */
	private function initPiwik()
	{
		$this->piwik = new Piwik($this->setting_service->parse_piwik_api_url(), $this->setting_service->get_auth_token(), $this->setting_service->get_site_id());

	 	$this->piwik->setRange('2015-01-06', Piwik::DATE_YESTERDAY); //All data from the first to the last date
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
	  * An AJAX POST interface for pulling report summary
	  *
	  * @return $json_data 		Report summary figures and thumbnails
	  */
	 public function expana_ajax_report()
	 {

	 	//temproraily changed period to range
	 	$this->suwi->setPeriod(Piwik::PERIOD_RANGE);

	 	$json_data = array(
	 			
	 			'meta' => ['code' => 200],

	 			'data' => [

		 			['thumbnail' => $this->report_service->generate_report_thumbnail( $this->setting_service->parse_piwik_api_url(), $this->suwi->getRange(), $this->suwi->getPeriod(), $this->suwi->getSiteId(), "nb_visits,nb_uniq_visitors", $this->suwi->getToken() ),
	 				 'description' => $this->suwi->getVisits() . ' visits, ' . $this->suwi->getUniqueVisitors() . ' unique visitors'],
		 			
		 			['thumbnail' => $this->report_service->generate_report_thumbnail( $this->setting_service->parse_piwik_api_url(), $this->suwi->getRange(), $this->suwi->getPeriod(), $this->suwi->getSiteId(), "avg_time_on_site", $this->suwi->getToken() ),
	 				 'description' => $this->suwi->getVisitsSummary(null, 'avg_time_on_site') . 's average visit duration'],
	 				
	 				['thumbnail' => $this->report_service->generate_report_thumbnail( $this->setting_service->parse_piwik_api_url(), $this->suwi->getRange(), $this->suwi->getPeriod(), $this->suwi->getSiteId(), "bounce_rate", $this->suwi->getToken() ),
	 				 'description' => $this->suwi->getVisitsSummary(null, 'bounce_rate') . ' visits have bounced (left the website after one page)'],
		 			
		 			['thumbnail' => $this->report_service->generate_report_thumbnail( $this->setting_service->parse_piwik_api_url(), $this->suwi->getRange(), $this->suwi->getPeriod(), $this->suwi->getSiteId(), "nb_actions_per_visit", $this->suwi->getToken() ),
	 				 'description' => $this->suwi->getVisitsSummary(null, 'nb_actions') . ' actions per visit'],
		 			
		 			['thumbnail' => $this->report_service->generate_report_thumbnail( $this->setting_service->parse_piwik_api_url(), $this->suwi->getRange(), $this->suwi->getPeriod(), $this->suwi->getSiteId(), "avg_time_generation", $this->suwi->getToken() ),
	 				 'description' => $this->suwi->getActions(null, 'avg_time_generation') . 's average generation time'],
	 				
	 				['thumbnail' => $this->report_service->generate_report_thumbnail( $this->setting_service->parse_piwik_api_url(), $this->suwi->getRange(), $this->suwi->getPeriod(), $this->suwi->getSiteId(), "nb_pageviews, nb_uniq_pageviews", $this->suwi->getToken() ),
	 				 'description' => $this->suwi->getActions(null, 'nb_pageviews') . ' pageviews, ' . $this->suwi->getActions(null, 'nb_uniq_pageviews') . ' unique pageviews'],
	 				
	 				['thumbnail' => $this->report_service->generate_report_thumbnail( $this->setting_service->parse_piwik_api_url(), $this->suwi->getRange(), $this->suwi->getPeriod(), $this->suwi->getSiteId(), "nb_searches, nb_keywords", $this->suwi->getToken() ),
	 				 'description' => $this->suwi->getActions(null, 'nb_searches') . ' total searches on your website, ' . $this->suwi->getActions(null, 'nb_keywords') . ' unique keywords'],
	 				
	 				['thumbnail' => $this->report_service->generate_report_thumbnail( $this->setting_service->parse_piwik_api_url(), $this->suwi->getRange(), $this->suwi->getPeriod(), $this->suwi->getSiteId(), "nb_downloads, nb_uniq_downloads", $this->suwi->getToken() ),
	 				 'description' => $this->suwi->getActions(null, 'nb_downloads') . ' downloads, ' . $this->suwi->getActions(null, 'nb_uniq_downloads') . ' unique downloads'],
	 				
	 				['thumbnail' => $this->report_service->generate_report_thumbnail( $this->setting_service->parse_piwik_api_url(), $this->suwi->getRange(), $this->suwi->getPeriod(), $this->suwi->getSiteId(), "nb_outlinks, nb_uniq_outlinks", $this->suwi->getToken() ),
	 				 'description' => $this->suwi->getActions(null, 'nb_outlinks') . ' outlinks, '. $this->suwi->getActions(null, 'nb_uniq_outlinks') . ' unique outlink'],
	 				
		 			['thumbnail' => $this->report_service->generate_report_thumbnail( $this->setting_service->parse_piwik_api_url(), $this->suwi->getRange(), $this->suwi->getPeriod(), $this->suwi->getSiteId(), "max_actions", $this->suwi->getToken() ),
	 				 'description' => $this->suwi->getVisitsSummary(null, 'max_actions') . ' max actions in one visit'],
	 			]
	 		);

			//change period back to day
			$this->suwi->setPeriod(Piwik::PERIOD_DAY);
	
		wp_send_json($json_data);
	 }

	 /**
	  * An AJAX POST interface for pulling all data under VisitsSummary module
	  *
	  * @return $json_data 		Core web analytics metrics (visits, unique visitors, count of actions (page views & downloads & clicks on outlinks), time on site, bounces and converted visits. 
	  */
	 public function expana_ajax_visits_summary()
	 {
	 	wp_send_json($this->suwi->getVisitsSummary());
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
