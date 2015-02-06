<?php

/**
 * This class responsible for reporting API (query) to remote SUWI server.
 *
 * @link       http://spiders.syr.edu
 * @since      2.0.0
 *
 * @package    expressions-analytics
 * @subpackage expressions-analytics/services
 * @author     Michael Zhang <lzhang43@syr.edu>
 */

class Expressions_Analytics_Report_Service {
	
	/**
	 * Generate the Google tracking code.
	 * 
	 * @param string $suwi_api_url 		Rest API url
	 * @param string $range 			Date range in comma seperated format
	 * @param string $period 			Query period (day, week, month, year)
	 * @param string $columns 			Columns to draw in comma seperated format
	 * 
	 * @return string The URL to generate the thumbnail.
	 */
	public function generate_report_thumbnail( $suwi_api_url, $range, $period, $site_id, $columns, $auth_token )
	{
		return $suwi_api_url."/index.php?date=".$range."&module=VisitsSummary&action=getEvolutionGraph&idSite="
				.$site_id."&period=".$period."&viewDataTable=sparkline&columns=".$columns."&token_auth=".$auth_token;
	}

}
