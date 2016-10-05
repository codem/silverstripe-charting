<?php
namespace Codem\Charts;
class ChartPreviewField extends \FormField {

	private $chart;

	public function getTemplates() {
		return array('ChartPreviewField');
	}

	public function setChart($chart) {
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
