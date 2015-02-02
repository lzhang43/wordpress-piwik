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
	 * Define widgets
	 *
	 * @since 2.0.0
	 */
 	private $widgets = array(

				['report', 'Report'],

			);

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
	 		$this->expana_widgets_register( $widget[0], $widget[1] );
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
	 	echo "<div class='main'>Main</div>";
	 	echo "<div class='sub'>Sub</div>";
	 }

}
