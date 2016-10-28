<?php
namespace Codem\Charts;
use Codem\Charts\Chart as Chart;
use Codem\Charts\ChartConfiguration as ChartConfiguration;

class ChartPreviewField extends \FormField {

	private $chart, $chart_configuration;

	public function getTemplates() {
		return array('ChartPreviewField');
	}

	public function setChart(Chart $chart) {
		$this->chart = $chart;
		return $this;
	}

	/**
	 * Returns the form field.
	 *
	 * @param array $properties
	 *
	 * @return string
	 */
	public function Field($properties = array()) {
		$properties['Chart'] = $this->chart;
		return parent::Field($properties);
	}

}
