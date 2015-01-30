<?php
/**
 * Plugin Name:       Expressions Analytics
 * Plugin URI:        https://github.com/su-its-op/expressions-analytics
 * Description:       A SUWI tracker analytics plugin for WordPress
 * Version:           2.0.0
 * Author:            Michael Zhang <lzhang43@syr.edi>
 * Author URI:        http://spiders.syr.edu
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/lzhang43/expressions-analytics
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) )
{
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-expressions-analytics-activator.php
 */
function activate_expressions_analytics()
{
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-expressions-analytics-activator.php';
	Expressions_Analytics_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-expressions-analytics-deactivator.php
 */
function deactivate_expressions_analytics()
{
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-expressions-analytics-deactivator.php';
	Expressions_Analytics_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_expressions_analytics' );
register_deactivation_hook( __FILE__, 'deactivate_expressions_analytics' );

/**
 * The core plugin class that is used to define internationalization,
 * dashboard-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-expressions-analytics.php';

/**
 * Begins execution of the plugin.
 *
 * @since    2.0.0
 */
function run_expressions_analytics()
{
	$plugin = new Expressions_Analytics();
	$plugin->run();
}

run_expressions_analytics();
