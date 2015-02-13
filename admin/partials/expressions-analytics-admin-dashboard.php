<?php
/**
* Provide dashboard view for the plugin
*
 * @link       http://spiders.syr.edu
 * @since      2.0.0
 *
 * @package    expressions-analytics
 * @subpackage expressions-analytics/admin/partials
 * @author     Michael Zhang <lzhang43@syr.edu>
*/
?>
<div class="wrap">

	<h2>Expressions Analytics Dashboard</h2>

	<p class="expana-meta-info">
		Version: <?php echo $this->version; ?> | 
		Production Level: <?php echo $this->setting_service->get_production_level(); ?> | 
		SUWI Server: <?php echo $this->setting_service->parse_piwik_api_url(); ?> | 
		Site ID: <?php echo $this->setting_service->get_site_id(); ?> | 
		Current Date Range: <span id="date_range">Last 30 days</span><?php //@TODO: output date range info here ?>
	</p>

	<div id="welcome-panel" class="welcome-panel">
		<div class="welcome-panel-content">
			<div class="welcome-panel-column-container">

					<div class="welcome-panel-column">
						<h4><?php _e( 'Report' ); ?></h4>
						<div id="loading_report" class="loading">
							<i class="fa fa-cog fa-spin"></i>
						</div>
						<div id="report_content"></div>
					</div><!-- /.welcome-panel-column -->

					<div class="welcome-panel-column">
						<h4><?php _e( 'Visitors' ); ?></h4>
						<div id="loading_visits_summary" class="loading">
							<i class="fa fa-cog fa-spin"></i>
						</div>
						<div id="visits_summary" style="height: 400px;"></div>
						<div id="visits_datatable"></div>
					</div><!-- /.welcome-panel-column -->

			</div><!-- /.welcome-panel-column-container -->
		</div><!-- /.welcome-panel-content -->
	</div><!-- /#welcome-panel -->

	<div id="dashboard-date-range">
		<div aria-label="Dashboard Date Range" role="group" class="date-range-selectors">
			<button id="expana_last90" class="date-range-button" type="button">Last 90 days</button>
			<button id="expana_last30" class="date-range-button current" type="button">Last 30 days</button>
			<button id="expana_last7" class="date-range-button" type="button">Last 7 days</button>
			<button id="expana_yesterday" class="date-range-button" type="button">Yesterday</button>
			<button id="expana_custom" class="date-range-button" type="button">Custom</button>
		</div>

		<div class="date-range-inputs">
			<form class="form-inline">
				<div class="form-group">
					<label class="sr-only" for="date-from">From</label>
					<div class="input-group">
						<div class="input-group-addon"><i class="fa fa-calendar"></i></div>
						<input type="date" class="form-control" id="expana-from-date" placeholder="From">
					</div>
				</div>
				<div class="form-group">
					<label class="sr-only" for="date-to">To</label>
					<div class="input-group">
						<div class="input-group-addon"><i class="fa fa-calendar"></i></div>
						<input type="date" class="form-control" id="expana-to-date" placeholder="To">
					</div>
				</div>
				<button type="submit" id="date-range-filter" class="date-range-button">Filter</button>
			</form>
		</div>
	</div>

	<div id="dashboard-widgets-wrap">
		<div id="dashboard-widgets" class="metabox-holder<?php echo $columns_css; ?>">

			<div id="postbox-container-1" class="postbox-container">
			<?php do_meta_boxes( $screen->id, 'normal', '' ); ?>
			</div>

			<div id="postbox-container-2" class="postbox-container">
			<?php do_meta_boxes( $screen->id, 'side', '' ); ?>
			</div>

			<div id="postbox-container-3" class="postbox-container">
			<?php do_meta_boxes( $screen->id, 'column3', '' ); ?>
			</div>

			<div id="postbox-container-4" class="postbox-container">
			<?php do_meta_boxes( $screen->id, 'column4', '' ); ?>
			</div>

		</div><!-- /#dashboard-widgets -->
	</div><!-- /#dashboard-widgets-wrap -->

</div><!-- /.wrap -->
