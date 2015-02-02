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
	 * Displays the dashboard.
	 *
	 * @since 2.0.0
	 */
	 public function expana_widgets()
	 {
	 	$setting_service = new Expressions_Analytics_Setting_Service;

		add_meta_box( 'expana_report', 'Report', array( $this, 'callback_dashboard_report' ), $setting_service->pagehook, 'normal', 'core' );
	 }

	 public function callback_dashboard_report()
	 {
	 	echo "hello";
	 }

}
