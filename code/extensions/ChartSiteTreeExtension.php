<?php
/**
 *  Extension for linking charts <--> pages
 */
class ChartSiteTreeExtension extends \DataExtension {

	/**
	 * Anything in the SiteTree can have some charts
	 */
	private static $many_many = array(
		'Charts' => 'Codem\Charts\Chart',
	);

	public function EnabledCharts() {
		return $this->owner->Charts()->filter('Enabled', 1)->sort('Sort ASC');
	}

	public function updateCmsFields(Fieldlist $fields) {
		$chart_gridfield_config = GridFieldConfig_RecordEditor::create($this->owner->stat('page_length'))
						->removeComponentsByType('GridFieldPrintButton');
		$chart_gridfield = GridField::create(
			'Charts',
			FALSE,
			$this->owner->Charts()->sort('Sort ASC'),
			$chart_gridfield_config
		);

		if(class_exists('GridFieldOrderableRows')) {
			//sorting
			$chart_gridfield_config->addComponent( new \GridFieldOrderableRows('Sort') );
		}
		$fields->addFieldToTab('Root.Charts', $chart_gridfield);

	}
}
