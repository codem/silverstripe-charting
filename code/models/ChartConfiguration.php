<?php
namespace Codem\Charts;
use Codem\Charts\Chart as Chart;
/**
 * Resresents configuration for a chart
 */
class ChartConfiguration extends \DataObject {

	private static $belongs_to  = array(
		'Chart' => 'Codem\Charts\Chart.Configuration'
	);

	private static $db = array(
		'Config' => 'Text'
	);

	/**
	 * Event handler called before writing to the database.
	 */
	public function onBeforeWrite()
	{
		parent::onBeforeWrite();

	}
}
?>
