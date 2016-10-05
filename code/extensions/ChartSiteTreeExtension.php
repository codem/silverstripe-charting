<?php
/**
 *  Extension for linking charts <--> pages
 */
class ChartSiteTreeExtension extends \DataExtension {

	private static $many_many = array(
		'Charts' => 'Codem\Charts\Chart',
	);

	public function updateCmsFields(Fieldlist $fields) {
		$chart_gridfield_config = GridFieldConfig_RecordEditor::create($this->owner->stat('page_length'))
						->removeComponentsByType('GridFieldPrintButton');
		$chart_gridfield = GridField::create(
			'Charts',
			FALSE,
			$this->owner->Charts()->sort('Sort ASC'),
			$chart_gridfield_config
		);
		$fields->addFieldToTab('Root.Charts', $chart_gridfield);

	}
}
