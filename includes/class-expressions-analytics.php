<?php

/**
 * This file defines the core plugin class of expressions analytics
 *
 * @link       http://spiders.syr.edu
 * @since      2.0.0
 *
 * @package    expressions-analytics
 * @subpackage expressions-analytics/includes
 * @author     Michael Zhang <lzhang43@syr.edu>
 */

class Expressions_Analytics {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    2.0.0
	 * @access   protected
	 * @var      Expressions_Analytics_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    2.0.0
	 * @access   protected
	 * @var      string    $expressions_analytics    The string used to uniquely identify this plugin.
	 */
	protected $expressions_analytics;

	/**
	 * The current version of the plugin.
	 *
	 * @since    2.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the Dashboard and
	 * the public-facing side of the site.
	 *
	 * @since    2.0.0
	 */
	public function __construct()
	{

		$this->plugin_name = 'expressions_analytics';
		$this->version = '2.0.0';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->set_constants();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Expressions_Analytics_Loader. Orchestrates the hooks of the plugin.
	 * - Expressions_Analytics_i18n. Defines internationalization functionality.
	 * - Expressions_Analytics_Admin. Defines all hooks for the dashboard.
	 * - Expressions_Analytics_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    2.0.0
	 * @access   private
	 */
	private function load_dependencies()
	{

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-expressions-analytics-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-expressions-analytics-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the WordPress Admin panel.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-expressions-analytics-admin.php';

		/**
		 * The class responsible for defining all actions related to the main SUWI dashboard.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-expressions-analytics-dashboard.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-expressions-analytics-public.php';

		/**
		 * The class responsible for handling all settings for the plugin
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'services/class-expressions-analytics-setting-service.php';

		/**
		 * The class responsible for handling all trackers behavior
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'services/class-expressions-analytics-tracker-service.php';

		/**
		 * The class responsible for handling reporting API queries to remote SUWI server
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'services/class-expressions-analytics-report-service.php';

		/**
		 * Loading composer
		 */
		$composer = plugin_dir_path( dirname(__FILE__) ) . 'vendor/autoload.php';

		if ( file_exists($composer) )
		{
			require_once $composer;
		}

		$this->loader = new Expressions_Analytics_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Expressions_Analytics_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    2.0.0
	 * @access   private
	 */
	private function set_locale()
	{

		$plugin_i18n = new Expressions_Analytics_i18n();
		$plugin_i18n->set_domain( $this->get_plugin_name() );

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the dashboard functionality
	 * of the plugin.
	 *
	 * @since    2.0.0
	 * @access   private
	 */
	private function define_admin_hooks()
	{

		$plugin_admin = new Expressions_Analytics_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_init', $plugin_admin, 'build_settings' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'build_admin_menu' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'build_dashboard' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		// Register AJAX POST interfaces
		$plugin_dashboard = new Expressions_Analytics_Dashboard( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_ajax_expana_ajax_site_info', $plugin_dashboard, 'expana_ajax_site_info' );
		$this->loader->add_action( 'wp_ajax_expana_ajax_report', $plugin_dashboard, 'expana_ajax_report' );
		$this->loader->add_action( 'wp_ajax_expana_ajax_visits_summary', $plugin_dashboard, 'expana_ajax_visits_summary' );
		$this->loader->add_action( 'wp_ajax_expana_ajax_live', $plugin_dashboard, 'expana_ajax_live' );
		$this->loader->add_action( 'wp_ajax_expana_ajax_visits_by_time', $plugin_dashboard, 'expana_ajax_visits_by_time' );
		$this->loader->add_action( 'wp_ajax_expana_ajax_resolutions', $plugin_dashboard, 'expana_ajax_resolutions' );
		$this->loader->add_action( 'wp_ajax_expana_ajax_os', $plugin_dashboard, 'expana_ajax_os' );
		$this->loader->add_action( 'wp_ajax_expana_ajax_browsers', $plugin_dashboard, 'expana_ajax_browsers' );
		$this->loader->add_action( 'wp_ajax_expana_change_date_range', $plugin_dashboard, 'expana_ajax_change_date_range' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    2.0.0
	 * @access   private
	 */
	private function define_public_hooks()
	{

		$plugin_public = new Expressions_Analytics_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_footer', $plugin_public, 'insert_tracking_code', 9999 );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    2.0.0
	 */
	public function run()
	{
		$this->loader->run();
	}

	/**
	 * Get the name of the plugin to uniquely identify it within the context of
	 * WordPress and to define i18n functionality.
	 *
	 * @since     2.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     2.0.0
	 * @return    Expressions_Analytics_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     2.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version()
	{
		return $this->version;
	}

	/**
	 * Retrieve or define constants of the plugin.
	 *
	 * @since     2.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function set_constants()
	{
		/**
		 * The rest API URL for global tracking in Piwik, minus the protocol.
		 *
		 * Define as non-string or empty to disable.
		 */
		if ( ! defined( 'EXP_PRODUCTION_LEVEL' ) )
		{
			define( 'EXP_PRODUCTION_LEVEL', null );
		}

		/**
		 * The rest API URL for global tracking in Piwik, minus the protocol.
		 *
		 * Define as non-string or empty to disable.
		 */
		if ( ! defined( 'EXP_PIWIK_HOST' ) )
		{
			define( 'EXP_PIWIK_HOST', null );
		}

		/**
		 * The protocol to access the Piwik API over.
		 *
		 * Define as non-string or empty to disable.
		 */
		if ( ! defined( 'EXP_PIWIK_PROTO' ) )
		{
			define( 'EXP_PIWIK_PROTO', null );
		}

		/**
		 * The site id for global tracking in Google.
		 *
		 * Define as non-string or empty to disable.
		 */
		if ( ! defined( 'EXPANA_GOOGLE_GLOBAL_TRACKING_ID' ) )
		{
			define( 'EXPANA_GOOGLE_GLOBAL_TRACKING_ID', null );
		}

		/**
		 * The namespace for global tracking in Google.
		 *
		 * Define as non-string or empty to disable.
		 */
		if ( ! defined( 'EXPANA_GOOGLE_GLOBAL_TRACKING_NAMESPACE' ) )
		{
			define( 'EXPANA_GOOGLE_GLOBAL_TRACKING_NAMESPACE', null );
		}

		/**
		 * The site id for global tracking in Piwik.
		 *
		 * Define as non-integer to disable.
		 */
		if ( ! defined( 'EXPANA_PIWIK_GLOBAL_TRACKING_ID' ) )
		{
			define( 'EXPANA_PIWIK_GLOBAL_TRACKING_ID', 1 );
		}

		/**
		 * The domain for global tracking in Piwik.
		 *
		 * Define as non-string or empty to disable.
		 */
		if ( ! defined( 'EXPANA_PIWIK_GLOBAL_TRACKING_DOMAIN' ) )
		{
			define( 'EXPANA_PIWIK_GLOBAL_TRACKING_DOMAIN', '*.syr.edu' );
		}

		/**
		 * Define the number of seconds to wait for remote API requests.
		 */
		if ( ! defined( 'EXPANA_EXTERNAL_API_TIMEOUT' ) )
		{
			define( 'EXPANA_EXTERNAL_API_TIMEOUT', 30 );
		}

		/**
		 * Define as true to disable remote API SSL verification.
		 */
		if ( ! defined( 'EXPANA_EXTERNAL_API_DISABLE_SSL_VERIFICATION' ) )
		{
			define( 'EXPANA_EXTERNAL_API_DISABLE_SSL_VERIFICATION', false );
		}

		/**
		 * Define the default time period
		 * 
		 * possible values: today, yesterday, last10, last30, lastweek, lastmonth;
		 */
		if ( ! defined( 'EXPANA_DEFAULT_TIME_PERIOD' ) )
		{
			define( 'EXPANA_DEFAULT_TIME_PERIOD', 'last30' );
		}
	}

}
