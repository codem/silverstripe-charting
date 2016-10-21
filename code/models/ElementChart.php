<?php
use Codem\Charts\Chart as Chart;
/**
 * ElementChart is an Elemental Element for Charting
 */
if(class_exists('BaseElement')) {
	/**
	 * @note Elemental element for a chart
	 */
	class ElementChart extends BaseElement {

		private static $has_one = array(
			'Chart' => 'Codem\Charts\Chart',
		);

		private function ChartList() {
			return Chart::get()->sort('Created DESC');
		}

		public function getCmsFields() {
			$fields = parent::getCmsFields();
			$fields->addFieldToTab('Root.Main', new \DropDownField('ChartID', 'Chart', $this->ChartList()->map('ID', 'Title')));
			return $fields;
		}
	}
}
