<?php

/**
 * This class prints tracking code for the plugin.
 *
 * @link       http://spiders.syr.edu
 * @since      2.0.0
 *
 * @package    expressions-analytics
 * @subpackage expressions-analytics/services
 * @author     Michael Zhang <lzhang43@syr.edu>
 */

class Expressions_Analytics_Tracker_Service {

	/**
	 * Piwik tracking code format.
	 * 
	 * The following variables are substituted into the string.
	 * - %1$s = The top domain to track.
	 * - %2$s = The REST API base for the Piwik tracker.
	 * - %3$u = The unique site id.
	 */
	const TRACKING_CODE_PIWIK_START = <<<'EOS'
<!-- Piwik -->
<script type="text/javascript">
(function(d,t,u,g,s) {
u=("https:"==d.location.protocol?"https":"http")+"://%2$s/";
g=d.createElement(t);
s=d.getElementsByTagName(t)[0];
g.type="text/javascript";
g.defer=true;
g.async=true;
g.src=u+"piwik.js";
s.parentNode.insertBefore(g,s);
})(document,"script");

window.piwikAsyncInit = function () {
try {

EOS;

	const TRACKING_CODE_PIWIK_BODY = <<<'EOS'
var piwikTracker%3$u = Piwik.getTracker((("https:" == document.location.protocol) ? "https://%2$s/" : "http://%2$s/") + "piwik.php", %3$u);
piwikTracker%3$u.trackPageView();
piwikTracker%3$u.enableLinkTracking();

EOS;

	const TRACKING_CODE_PIWIK_END = <<<'EOS'
} catch( err ) {}
}
</script>
<!-- End Piwik Code -->

EOS;
	
	/**
	 * Google tracking code format.
	 * 
	 * The following variables are substituted into the string.
	 * - %1$s = The tracking settings code.
	 */
	const TRACKING_CODE_GOOGLE = <<<'EOS'
<script type="text/javascript">
var _gaq=_gaq||[];
%1$s(function() {
var ga=document.createElement('script');
ga.type='text/javascript';
ga.async=true;
ga.src=('https:'==document.location.protocol?'https://ssl':'http://www')+'.google-analytics.com/ga.js';
var s=document.getElementsByTagName('script')[0];
s.parentNode.insertBefore(ga,s);
})();
</script>

EOS;
	
	/**
	 * Google tracking API call.
	 * 
	 * The following variables are substituted into the string.
	 * - %1$s = The API call arguments.
	 */
	const TRACKING_CODE_GOOGLE_API_CALL = <<<'EOS'
_gaq.push(%1$s);

EOS;

	/**
	 * Generate the Piwik tracking code.
	 * 
	 * @param string $track_domain The domain to track.
	 * @param string $rest_api The rest API URL, minus the protocol.
	 * @param string $site_id The unique site id assigned by Piwik.
	 * 
	 * @return string The Piwik tracking code.
	 */
	private function tracking_code_piwik_start( $track_domain, $rest_api, $site_id ) {
		return sprintf( self::TRACKING_CODE_PIWIK_START, $track_domain, $rest_api, $site_id );
	}

	private function tracking_code_piwik_body( $track_domain, $rest_api, $site_id ) {
		return sprintf( self::TRACKING_CODE_PIWIK_BODY, $track_domain, $rest_api, $site_id );
	}

	private function tracking_code_piwik_end() {
		return self::TRACKING_CODE_PIWIK_END;
	}
	
	/**
	 * Generate the Google tracking code.
	 * 
	 * @param array $accounts The accounts to track.
	 * 
	 * @return string The Google tracking code.
	 */
	private function tracking_code_google( $accounts ) {
		$api_calls_str = '';
		if ( is_array( $accounts ) ) {
			foreach ( $accounts as $account=>&$tracking ) {
				$ns = isset( $tracking['namespace'] ) && is_string( $tracking['namespace'] ) && ! empty( $tracking['namespace'] ) ? $tracking['namespace'] . '.' : '';
				$api_calls_str .= $this->tracking_code_google_api_call( array( $ns . '_setAccount', $account ) );
				$api_calls_str .= $this->tracking_code_google_api_call( array( $ns . '_trackPageview' ) );
			}
			unset( $tracking );
		}
		return empty( $api_calls_str ) ? '' : sprintf( self::TRACKING_CODE_GOOGLE, $api_calls_str );
	}
	
	/**
	 * Generate the Google API call.
	 * 
	 * @param mixed $call The API call parameter.
	 * 
	 * @return string The API call JS string.
	 */
	private function tracking_code_google_api_call( $call ) {
		return sprintf( self::TRACKING_CODE_GOOGLE_API_CALL, json_encode( $call ) );
	}

	/**
	 * Action callback to print all the tracking code.
	 *
	 * @since 	 2.0.0
	 */
	public function print_tracking_code( $settings ) {

		$piwik_global_tracking_domain = EXPANA_PIWIK_GLOBAL_TRACKING_DOMAIN;
		$piwik_rest_api = EXP_PIWIK_HOST;
		$piwik_global_tracking_id = EXPANA_PIWIK_GLOBAL_TRACKING_ID;
		
		//Piwik code for the current production level.
		$piwik_site_id = null;
		switch ( EXP_PRODUCTION_LEVEL ) {
			case 'PROD':
				$piwik_site_id = $settings['piwik_site_id_prod'];
			break;
			case 'DEV':
				$piwik_site_id = $settings['piwik_site_id_dev'];
			break;
			case 'TST':
				$piwik_site_id = $settings['piwik_site_id_tst'];
			break;
		}
		if ( is_int( $piwik_site_id ) ) {
			$site_domain = @parse_url( get_site_url(), PHP_URL_HOST );
			if ( ! empty( $site_domain ) ) {
				echo $this->tracking_code_piwik_start(
					'*.' . $site_domain,
					$piwik_rest_api,
					$piwik_site_id
				);

				//Global tracking Piwik.
				if (
					is_string( $piwik_global_tracking_domain ) && ! empty( $piwik_global_tracking_domain ) &&
					is_string( $piwik_rest_api ) && ! empty( $piwik_rest_api ) &&
					is_int( $piwik_global_tracking_id )
				) {
					echo $this->tracking_code_piwik_body(
						$piwik_global_tracking_domain,
						$piwik_rest_api,
						$piwik_global_tracking_id
					);
				}

				echo $this->tracking_code_piwik_body(
					'*.' . $site_domain,
					$piwik_rest_api,
					$piwik_site_id
				);

				echo $this->tracking_code_piwik_end();
			}
		}
				
		//Google tracking.
		$ga_accounts = array();
		
		//Add user tracking to the list.
		if ( ! empty( $settings['google_web_property_id'] ) )
		{
			$ga_accounts[$settings['google_web_property_id']] = array(
				'namespace' => ''
			);
		}
		
		//Add global tracking to the list.
		$google_global_tracking_id = EXPANA_GOOGLE_GLOBAL_TRACKING_ID;
		
		if ( is_string( $google_global_tracking_id ) && ! empty( $google_global_tracking_id ) )
		{
			$ga_accounts[$google_global_tracking_id] = array(
				'namespace' => EXPANA_GOOGLE_GLOBAL_TRACKING_NAMESPACE
			);
		}
		
		//Output the tracking code.
		if ( ! empty( $ga_accounts ) )
		{
			echo $this->tracking_code_google( $ga_accounts );
		}
	}

}
