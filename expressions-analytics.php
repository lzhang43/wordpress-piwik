<?php
/*
Name: Expressions Analytics
Description: WordPress plugin for Expressions analytics.
Author: Expressions Team, Alexander O'Mara
Version: 1.0
*/

//Check if inside WordPress.
if ( ! defined( 'ABSPATH' ) ) { exit(); }

class ExpressionsAnalytics {
	
	/**
	 * Piwik tracking code format.
	 * 
	 * The following variables are substituted into the string.
	 * - %1$s = The top domain to track.
	 * - %2$s = The domain for the Piwik tracker.
	 * - %3$u = The unique site id.
	 */
	const PIWIK_TRACKING_CODE = <<<'EOS'
<!-- Piwik -->
<script type="text/javascript">
var _paq = _paq || [];
_paq.push(["setDocumentTitle",document.domain+"/"+document.title]);
_paq.push(["setCookieDomain","%1$s"]);
_paq.push(["setDomains",["%1$s"]]);
_paq.push(["trackPageView"]);
_paq.push(["enableLinkTracking"]);
(function(d,t,u,g,s) {
u=(("https:"==d.location.protocol)?"https":"http")+"://%2$s/";
_paq.push(["setTrackerUrl",u+"piwik.php"]);
_paq.push(["setSiteId",%3$u]);
g=d.createElement(t);
s=d.getElementsByTagName(t)[0];
g.type="text/javascript";
g.defer=true;
g.async=true;
g.src=u+"piwik.js";
s.parentNode.insertBefore(g,s);
})(document,"script");
</script>
<noscript><img src="http://%2$s/piwik.php?idsite=%3$u&rec=1" style="border:0" alt="" /></noscript>
<!-- End Piwik Code -->
EOS;
	
	/**
	 * Google tracking code format.
	 * 
	 * TODO
	 */
	const GOOGLE_TRACKING_CODE = <<<'EOS'
TODO
EOS;

	public function __construct() {
		add_action( 'init', array($this, 'action_init') );
	}
	
	public function action_init() {
		//header('Content-Type: text/plain');
		//'its-suwi-dev.syr.edu', '*.localhost.syr.edu', 153
		//printf(ExpressionsAnalytics::PIWIK_TRACKING_CODE, '*.localhost.syr.edu', 'localhost', 1);
		//exit();
	}
}
new ExpressionsAnalytics();
