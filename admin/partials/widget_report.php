<div class="main">
	<section>
		<div>
			<img src="<?php echo $this->report_service->generate_report_thumbnail( $this->setting_service->parse_piwik_api_url(), $this->suwi->getRange(), $this->suwi->getPeriod(), $this->suwi->getSiteId(), "nb_visits,nb_uniq_visitors" ); ?>" />
			<span>2 visits, 2 unique visitors</span>
		</div>
	</section>

	<section>
		<div>
			<img src="<?php echo $this->report_service->generate_report_thumbnail( $this->setting_service->parse_piwik_api_url(), $this->suwi->getRange(), $this->suwi->getPeriod(), $this->suwi->getSiteId(), "avg_time_on_site" ); ?>" />
			<span>2s average visit duration</span>
		</div>
	</section>

	<section>
		<div>
			<img src="<?php echo $this->report_service->generate_report_thumbnail( $this->setting_service->parse_piwik_api_url(), $this->suwi->getRange(), $this->suwi->getPeriod(), $this->suwi->getSiteId(), "bounce_rate" ); ?>" />
			<span>0% visits have bounced (left the website after one page)</span>
		</div>
	</section>

	<section>
		<div>
			<img src="<?php echo $this->report_service->generate_report_thumbnail( $this->setting_service->parse_piwik_api_url(), $this->suwi->getRange(), $this->suwi->getPeriod(), $this->suwi->getSiteId(), "nb_actions_per_visit" ); ?>" />
			<span>3.5 actions (page views, downloads, outlinks and internal site searches) per visit</span>
		</div>
	</section>

	<section>
		<div>
			<img src="<?php echo $this->report_service->generate_report_thumbnail( $this->setting_service->parse_piwik_api_url(), $this->suwi->getRange(), $this->suwi->getPeriod(), $this->suwi->getSiteId(), "avg_time_generation" ); ?>" />
			<span>1.02s average generation time</span>
		</div>
	</section>

	<section>
		<div>
			<img src="<?php echo $this->report_service->generate_report_thumbnail( $this->setting_service->parse_piwik_api_url(), $this->suwi->getRange(), $this->suwi->getPeriod(), $this->suwi->getSiteId(), "nb_pageviews,nb_uniq_pageviews" ); ?>" />
			<span>7 pageviews, 2 unique pageviews</span>
		</div>
	</section>

	<section>
		<div>
			<img src="<?php echo $this->report_service->generate_report_thumbnail( $this->setting_service->parse_piwik_api_url(), $this->suwi->getRange(), $this->suwi->getPeriod(), $this->suwi->getSiteId(), "nb_searches,nb_keywords" ); ?>" />
			<span>0 total searches on your website, 0 unique keywords</span>
		</div>
	</section>

	<section>
		<div>
			<img src="<?php echo $this->report_service->generate_report_thumbnail( $this->setting_service->parse_piwik_api_url(), $this->suwi->getRange(), $this->suwi->getPeriod(), $this->suwi->getSiteId(), "nb_downloads, nb_uniq_downloads" ); ?>" />
			<span>0 downloads, 0 unique downloads</span>
		</div>
	</section>

	<section>
		<div>
			<img src="<?php echo $this->report_service->generate_report_thumbnail( $this->setting_service->parse_piwik_api_url(), $this->suwi->getRange(), $this->suwi->getPeriod(), $this->suwi->getSiteId(), "nb_outlinks, nb_uniq_outlinks" ); ?>" />
			<span>0 outlinks, 0 unique outlinks</span>
		</div>
	</section>

	<section>
		<div>
			<img src="<?php echo $this->report_service->generate_report_thumbnail( $this->setting_service->parse_piwik_api_url(), $this->suwi->getRange(), $this->suwi->getPeriod(), $this->suwi->getSiteId(), "max_actions" ); ?>" />
			<span>4 max actions in one visit</span>
		</div>
	</section>
</div>
<div class="sub">Sub</div>
