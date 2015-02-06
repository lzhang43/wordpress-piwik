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

	<div id="welcome-panel" class="welcome-panel">
		<div class="welcome-panel-content">
			<div class="welcome-panel-column-container">

					<div class="welcome-panel-column">
						<h4><?php _e( 'Report' ); ?></h4>
						<div id="report-content">
							
						</div>
					</div><!-- /.welcome-panel-column -->

					<div class="welcome-panel-column">
						<h4><?php _e( 'Visitors' ); ?></h4>
					</div><!-- /.welcome-panel-column -->

			</div><!-- /.welcome-panel-column-container -->
		</div><!-- /.welcome-panel-content -->
	</div><!-- /#welcome-panel -->

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
