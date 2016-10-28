<?php
/**
 * @note handles requests referencing a chart
 */
use Codem\Charts\Chart as Chart;
use Codem\Charts\ChartConfiguration as ChartConfiguration;
class ChartView extends \Controller {

	private static $allowed_actions = array(
		'preview'
	);

	public function preview(SS_HTTPRequest $request) {
		$chart_id = $request->param('ID');
		$action = $request->param('Action');
		$config_id = $request->getVar('config');
		if(!$chart_id) {
			return "";
		}
		$member = \Member::currentUser();
		$chart = Chart::get()->filter('ID', $chart_id)->first();

		if(empty($chart->ID)) {
			return "";
		}
		if(!$chart->canPreview($member)) {
			return "";
		}


		// belongs to chart?
		$config = ChartConfiguration::get()->filter(array('ID' => $config_id, 'ChartID' => $chart_id))->first();
		if(empty($config->ID)) {
			return "";
		}

		// show even if not enabled
		$chart->setInPreview(TRUE);
		$chart->setAltConfig( $config );
		
		$charts = new ArrayList();
		$charts->push( $chart );
		$template_data = new ArrayData( array(
			'Chart' => $chart,
			'Charts' => $charts,
			'Title' => 'Chart preview',
		));
		return  $this->customise($template_data)
						->renderWith( array('ChartPreviewPage') );
	}

}
