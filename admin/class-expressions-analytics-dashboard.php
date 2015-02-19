<?php

use \VisualAppeal\Piwik as Piwik;

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

	private $suwi;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    2.0.0
	 * @var      string    $plugin_name 	The name of this plugin.
	 * @var      string    $version			The version of this plugin.
	 */
	public function __construct( $plugin_name, $version )
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		$this->setting_service = new Expressions_Analytics_Setting_Service( $this->plugin_name, $this->version );
		$this->report_service  = new Expressions_Analytics_Report_Service( $this->plugin_name, $this->version );
		$this->suwi = $this->initPiwik();

	}

	/**
	 * Define widgets
	 *
	 * @since 2.0.0
	 */
 	private $widgets = array(

 				//Thanks to PHP 5.3, we can't use [] here
				array('live', 'Live', 'normal', 'default'),
				array('visits_by_time', 'Visits By Time', 'side', 'default'),
				array('resolutions', 'Resolutions', 'column3', 'default'),
				array('os', 'Operating Systems', 'normal', 'default'),
				array('browsers', 'Browsers', 'side', 'default'),
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

	 	$this->piwik->setRange($this->generateDate(), Piwik::DATE_YESTERDAY); //All data from the first to the last date
	 	$this->piwik->setPeriod(Piwik::PERIOD_DAY);
	 	$this->piwik->setFormat(Piwik::FORMAT_JSON);
	 	$this->piwik->setLanguage('en');

	 	return $this->piwik;
	}

	/**
	 * Date generator
	 *
	 * @since 2.0.0
	 */
	private function generateDate( $date = '' )
	{
		return date('Y-m-d', strtotime('-30 days'));
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
	 	$setting_service = new Expressions_Analytics_Setting_Service( $this->plugin_name, $this->version );

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
	 			
	 			'meta' => array('code' => 200),

	 			'data' => array(

		 			array('thumbnail' => $this->report_service->generate_report_thumbnail( $this->setting_service->parse_piwik_api_url(), $this->suwi->getRange(), $this->suwi->getPeriod(), $this->suwi->getSiteId(), "nb_visits,nb_uniq_visitors", $this->suwi->getToken() ),
	 				 'description' => "<strong>" . $this->suwi->getVisits() . '</strong> visits, <strong>' . $this->suwi->getUniqueVisitors() . '</strong> unique visitors'),
		 			
		 			array('thumbnail' => $this->report_service->generate_report_thumbnail( $this->setting_service->parse_piwik_api_url(), $this->suwi->getRange(), $this->suwi->getPeriod(), $this->suwi->getSiteId(), "avg_time_on_site", $this->suwi->getToken() ),
	 				 'description' => "<strong>" . $this->suwi->getVisitsSummary(null, 'avg_time_on_site') . 's</strong> average visit duration'),
	 				
	 				array('thumbnail' => $this->report_service->generate_report_thumbnail( $this->setting_service->parse_piwik_api_url(), $this->suwi->getRange(), $this->suwi->getPeriod(), $this->suwi->getSiteId(), "bounce_rate", $this->suwi->getToken() ),
	 				 'description' => "<strong>" . $this->suwi->getVisitsSummary(null, 'bounce_rate') . '</strong> visits have bounced (left the website after one page)'),
		 			
		 			array('thumbnail' => $this->report_service->generate_report_thumbnail( $this->setting_service->parse_piwik_api_url(), $this->suwi->getRange(), $this->suwi->getPeriod(), $this->suwi->getSiteId(), "nb_actions_per_visit", $this->suwi->getToken() ),
	 				 'description' => "<strong>" . $this->suwi->getVisitsSummary(null, 'nb_actions_per_visit') . '</strong> actions per visit'),
		 			
		 			array('thumbnail' => $this->report_service->generate_report_thumbnail( $this->setting_service->parse_piwik_api_url(), $this->suwi->getRange(), $this->suwi->getPeriod(), $this->suwi->getSiteId(), "avg_time_generation", $this->suwi->getToken() ),
	 				 'description' => "<strong>" . $this->suwi->getActions(null, 'avg_generation_time') . 's</strong> average generation time'),
	 				
	 				array('thumbnail' => $this->report_service->generate_report_thumbnail( $this->setting_service->parse_piwik_api_url(), $this->suwi->getRange(), $this->suwi->getPeriod(), $this->suwi->getSiteId(), "nb_pageviews, nb_uniq_pageviews", $this->suwi->getToken() ),
	 				 'description' => "<strong>" . $this->suwi->getApi(null, 'nb_pageviews') . '</strong> pageviews, <strong>' . $this->suwi->getApi(null, 'nb_uniq_pageviews') . '</strong> unique pageviews'),
	 				
	 				array('thumbnail' => $this->report_service->generate_report_thumbnail( $this->setting_service->parse_piwik_api_url(), $this->suwi->getRange(), $this->suwi->getPeriod(), $this->suwi->getSiteId(), "nb_searches, nb_keywords", $this->suwi->getToken() ),
	 				 'description' => "<strong>" . $this->suwi->getApi(null, 'nb_searches') . '</strong> total searches on your website, <strong>' . $this->suwi->getApi(null, 'nb_keywords') . '</strong> unique keywords'),
	 				
	 				array('thumbnail' => $this->report_service->generate_report_thumbnail( $this->setting_service->parse_piwik_api_url(), $this->suwi->getRange(), $this->suwi->getPeriod(), $this->suwi->getSiteId(), "nb_downloads, nb_uniq_downloads", $this->suwi->getToken() ),
	 				 'description' => "<strong>" . $this->suwi->getApi(null, 'nb_downloads') . '</strong> downloads, <strong>' . $this->suwi->getApi(null, 'nb_uniq_downloads') . '</strong> unique downloads'),
	 				
	 				array('thumbnail' => $this->report_service->generate_report_thumbnail( $this->setting_service->parse_piwik_api_url(), $this->suwi->getRange(), $this->suwi->getPeriod(), $this->suwi->getSiteId(), "nb_outlinks, nb_uniq_outlinks", $this->suwi->getToken() ),
	 				 'description' => "<strong>" . $this->suwi->getApi(null, 'nb_outlinks') . '</strong> outlinks, <strong>'. $this->suwi->getApi(null, 'nb_uniq_outlinks') . '</strong> unique outlink'),
	 				
		 			array('thumbnail' => $this->report_service->generate_report_thumbnail( $this->setting_service->parse_piwik_api_url(), $this->suwi->getRange(), $this->suwi->getPeriod(), $this->suwi->getSiteId(), "max_actions", $this->suwi->getToken() ),
	 				 'description' => "<strong>" . $this->suwi->getVisitsSummary(null, 'max_actions') . '</strong> max actions in one visit'),
	 			)
	 		);

			//change period back to day
			$this->suwi->setPeriod(Piwik::PERIOD_DAY);
	
		wp_send_json($json_data);
	 }

	 /**
	  * An AJAX POST interface for pulling all data under VisitsSummary module
	  *
	  * @return $json_data 		Site information, including timezone, created date, and etc.
	  */
	 public function expana_ajax_site_info()
	 {
	 	wp_send_json($this->suwi->getSiteInformation());
	 }


	 /**
	  * An AJAX POST interface for pulling all data under VisitsSummary module
	  *
	  * @return $json_data 		Core web analytics metrics (visits, unique visitors, count of actions (page views & downloads & clicks on outlinks), time on site, bounces and converted visits. 
	  */
	 public function expana_ajax_visits_summary()
	 {
	 	$this->suwi->setPeriod(Piwik::PERIOD_DAY);

	 	wp_send_json($this->suwi->getVisitsSummary());
	 }

	 /**
	  * An AJAX POST interface for pulling real time visits counters
	  *
	  * @return $json_data 		The Live API returns visit level information about visitors.
	  * @since  2.0.0
	  */
	 public function expana_ajax_live()
	 {
	 	wp_send_json($this->suwi->getCounters(30));
	 }

	 /**
	  * An AJAX POST interface for pulling visits data by hours
	  *
	  * @return $json_data 		The Live API returns reports by Hour (Server time), and by Hour Local Time of visitors.
	  * @since  2.0.0
	  */
	 public function expana_ajax_visits_by_time()
	 {
	 	$this->suwi->setPeriod(Piwik::PERIOD_RANGE);

	 	$return = $this->suwi->getVisitServerTime();

	 	$this->suwi->setPeriod(Piwik::PERIOD_DAY);

	 	wp_send_json($return);
	 }

	 /**
	  * An AJAX POST interface for pulling resolutions data
	  *
	  * @return $json_data
	  * @since  2.0.0
	  */
	 public function expana_ajax_resolutions()
	 {
	 	$this->suwi->setPeriod(Piwik::PERIOD_RANGE);

	 	$return = $this->suwi->getResolution();

	 	$this->suwi->setPeriod(Piwik::PERIOD_DAY);

	 	wp_send_json($return);
	 }

	 /**
	  * An AJAX POST interface for pulling Operating systems data
	  *
	  * @return $json_data
	  * @since  2.0.0
	  */
	 public function expana_ajax_os()
	 {
	 	$this->suwi->setPeriod(Piwik::PERIOD_RANGE);

	 	$return = $this->suwi->getOsVersions();

	 	$this->suwi->setPeriod(Piwik::PERIOD_DAY);

	 	wp_send_json($return);
	 }

	 /**
	  * An AJAX POST interface for pulling browsers data
	  *
	  * @return $json_data
	  * @since  2.0.0
	  */
	 public function expana_ajax_browsers()
	 {
	 	$this->suwi->setPeriod(Piwik::PERIOD_RANGE);

	 	$return = $this->suwi->getBrowserVersions();

	 	$this->suwi->setPeriod(Piwik::PERIOD_DAY);

	 	wp_send_json($return);
	 }
	 
	/**
	 * Dashboard Widget: Live
	 *
	 * @since 2.0.0
	 */
	 public function expana_widgets_callback_live()
	 {
	 	require("partials/widget_live.php");
	 }

	/**
	 * Dashboard Widget: Visits By Time
	 *
	 * @since 2.0.0
	 */
	 public function expana_widgets_callback_visits_by_time()
	 {
	 	require("partials/widget_visits_by_time.php");
	 }

	/**
	 * Dashboard Widget: Resolutions
	 *
	 * @since 2.0.0
	 */
	 public function expana_widgets_callback_resolutions()
	 {
	 	require("partials/widget_resolutions.php");
	 }

	/**
	 * Dashboard Widget: OS
	 *
	 * @since 2.0.0
	 */
	 public function expana_widgets_callback_os()
	 {
	 	require("partials/widget_os.php");
	 }

	/**
	 * Dashboard Widget: Browsers
	 *
	 * @since 2.0.0
	 */
	 public function expana_widgets_callback_browsers()
	 {
	 	require("partials/widget_browsers.php");
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
