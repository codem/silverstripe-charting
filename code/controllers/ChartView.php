<?php
/**
 * @note handles requests referencing a chart
 */
use Codem\Charts\Chart as Chart;
class ChartView extends \Controller {

	private static $allowed_actions = array(
		'preview'
	);

	public function preview(SS_HTTPRequest $request) {
		$chart_id = $request->param('ID');
		$action = $request->param('Action');
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

		$template_data = new ArrayData( array(
			'Chart' => $chart,
			'Title' => 'Chart preview',
		));
		return  $this->customise($template_data)
						->renderWith( array('ChartPreviewPage') );
	}

}
