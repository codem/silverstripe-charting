<?php
namespace Codem\Charts;
use Codem\Charts\Chart as Chart;
/**
 * Resresents configuration for a chart
 */
class ChartConfiguration extends \DataObject {

	private $configuration;

	private static $default_sort = "ID";
	private static $singular_name = "Chart configuration";
	private static $plural_name  = "Configuration of charts";

	private static $belongs_to  = array(
		'Chart' => 'Codem\Charts\Chart.Configuration'
	);

	private static $db = array(
		'Width' => 'Int',
		'Height' => 'Int',
		'SupportMultipleDatasets' => 'Boolean',
		'IncludeTitleInChart' => 'Boolean',
		'Config' => 'Text'
	);

	public function getTitle() {
		return $this->ID;
	}

	private function grabConfig() {
		if(empty($this->configuration)) {
			$this->configuration = json_decode($this->Config, FALSE);
		}
		return $this->configuration;
	}

	public function getConfigValue($name) {
		$config = $this->grabConfig();
		return (isset($config->$name) ? $config->$name : NULL);
	}

	public function onBeforeWrite() {
		parent::onBeforeWrite();

		$controller = \Controller::curr();
		$request = $controller->getRequest();
		$post = $request->postVar('ConfigData');
		$this->Config = json_encode($post);
		return TRUE;
	}

	/**
	 * Event handler called before writing to the database.
	 */
	public function onAfterWrite()
	{
		parent::onAfterWrite();
		$chart = $this->Chart();
		if(!empty($chart->ID)) {
			// avoid chart->write() calling this again...
			\DB::query("UPDATE `Codem\Charts\Chart` SET ConfigurationCompleted=1 WHERE ID='" . \Convert::raw2sql($chart->ID) . "'");
		}
		return TRUE;
	}

	public function getCmsFields() {

		$fields = parent::getCmsFields();
		$fields->removeByName('Config');

		try {
			$fieldlist = $this->getConfigurationFormFields();
			$fields->addFieldToTab("Root.Main", \HeaderField::create('GeneralHeading', "General Chart settings", 3), "Width" );
			foreach($fieldlist as $field) {
				$fields->addFieldToTab("Root.Main", $field);
			}
		} catch (\Exception $e) {
			$fields->addFieldToTab("Root.Main", \LiteralField::create('ChartConfigurationError', "<p class=\"message bad\">" . $e->getMessage() . "</p>") );
			$fields->removeByName('Width');
			$fields->removeByName('Height');
			$fields->removeByName('SupportMultipleDatasets');
		}

		return $fields;
	}


	public function getConfigurationFormFields() {
		$fields = \FieldList::create();
		$chart = $this->Chart();

		if(empty($chart->ID)) {
			throw new \Exception("The chart does not exist, cannot configure it!");
		}

		$source = $chart->getSourceHeadings();

		if(empty($source)) {
			// no data :(
			throw new \Exception("No source data found, cannot configure. Possibly an error fetching the CSV source data.");
		}

		$fields->push( \HeaderField::create('ChartConfigurationHeading]', "Configuration for chart", 3) );
		$fields->push( \LiteralField::create('ChartConfigurationHelper', "<p class=\"message\">There are " . count($source) . " columns in the source file</p>") );

		switch($chart->ChartType) {
			case 'Pie':
				// a pie chart has labels in one column and data in another
				$fields->push( $this->getLabelColumn($source) );
				$fields->push( $this->getDataColumn($source) );
				break;
			case 'Bar':
				// a bar chart has x/y axis fields and formats
				$fields->push( $this->getXAxisTitleField() );
				$fields->push( $this->getYAxisTitleField() );
				$fields->push( $this->getXAxisFormatField() );
				$fields->push( $this->getYAxisFormatField() );
				// which column for which axis?
				$fields->push( $this->getXAxisColumn($source) );
				$fields->push( $this->getYAxisColumn($source) );
				break;
			case 'HorizontalBar':
				// like a bar, but the other way ;)
				$fields->push( $this->getModeField() );
				$fields->push( $this->getXAxisTitleField() );
				$fields->push( $this->getYAxisTitleField() );
				$fields->push( $this->getXAxisFormatField() );
				$fields->push( $this->getYAxisFormatField() );
				// which column for which axis?
				$fields->push( $this->getXAxisColumn($source) );
				$fields->push( $this->getYAxisColumn($source) );
				break;
			case 'Line':
				// like a bar but with lines connecting points
				$fields->push( $this->getModeField() );
				$fields->push( $this->getXAxisTitleField() );
				$fields->push( $this->getYAxisTitleField() );
				$fields->push( $this->getXAxisFormatField() );
				$fields->push( $this->getYAxisFormatField() );
				// which column for which axis?
				$fields->push( $this->getXAxisColumn($source) );
				$fields->push( $this->getYAxisColumn($source) );
				break;
			case 'Scatter':
			case 'TimeSeries':
				$fields->push( $this->getModeField() );
				$fields->push( $this->getXAxisTitleField() );
				$fields->push( $this->getYAxisTitleField() );
				$fields->push( $this->getXAxisFormatField() );
				$fields->push( $this->getYAxisFormatField() );
				// which column for which axis?
				$fields->push( $this->getXAxisColumn($source) );
				$fields->push( $this->getYAxisColumn($source) );
				break;
			case 'Bubble':
				// bubbles have 3 data columns - an  x/y pos +  marker size
				// Bubble charts have the mode of 'markers' always
				$fields->push( $this->getXAxisTitleField() );
				$fields->push( $this->getYAxisTitleField() );
				// the position of the markers on the chart
				$fields->push( $this->getXAxisColumn($source) );
				$fields->push( $this->getYAxisColumn($source) );
				// the data column represents the marker size
				$fields->push( $this->getDataColumn($source) );
				break;
			default:
				// Unsupported chart type
				break;
		}
		return $fields;
	}

	/**
	 * @returns DropdownField
	 * @note returns a field allowing the mode for a supporting chart type to be selected
	 */
	public function getModeField() {
		$source = array(
			'markers' => 'Markers',
			'lines' => 'Lines',
			'lines+markers' => 'Lines & Markers',
		);
		return \DropdownField::create('ConfigData[Mode]', "Mode", $source, $this->getConfigValue('Mode'));
	}

	/**
	 * @returns DropdownField
	 * @note for relevant charts (Pie), allows a label column to be selected from the source
	 */
	public function getLabelColumn($source) {
		return \DropdownField::create('ConfigData[LabelColumn]', "Label Column", $source, $this->getConfigValue('XAxisColumn'));
	}

	/**
	 * @returns DropdownField
	 * @note for relevant charts (Pie), allows a data column to be selected from the source. For Pie charts this represents the size of the wedge.
	 * 			For Bubble charts, the data column represents the marker size, the coords are found in XAxisColumn and YAxisColumn
	 */
	public function getDataColumn($source) {
		return \DropdownField::create('ConfigData[DataColumn]', "Data Column", $source, $this->getConfigValue('DataColumn'));
	}

	/**
	 * @returns DropdownField
	 * @note for relevant charts, returns the data that should be represented on the X-Axis of a chart
	 */
	public function getXAxisColumn($source) {
		return \DropdownField::create('ConfigData[XAxisColumn]', "X-Axis Column", $source, $this->getConfigValue('XAxisColumn'));
	}

	/**
	 * @returns DropdownField
	 * @note for relevant charts, returns the data that should be represented on the Y-Axis of a chart
	 */
	public function getYAxisColumn($source) {
		return \DropdownField::create('ConfigData[YAxisColumn]', "Y-Axis Column", $source, $this->getConfigValue('YAxisColumn'));
	}

	/**
	 * @returns TextField
	 * @note for relevant charts, returns the title for the X Axis
	 */
	public function getXAxisTitleField() {
		return \TextField::create('ConfigData[XAxisTitle]', "X-Axis Title", $this->getConfigValue('XAxisTitle'));
	}

	/**
	 * @returns TextField
	 * @note for relevant charts, returns the title for the Y Axis
	 */
	public function getYAxisTitleField() {
		return \TextField::create('ConfigData[YAxisTitle]', "Y-Axis Title", $this->getConfigValue('YAxisTitle'));
	}

	/**
	 * @returns TextField
	 * @note for relevant charts, returns the format for data on the X Axis
	 */
	public function getXAxisFormatField() {
		return \TextField::create('ConfigData[XAxisFormat]', "X-Axis Format", $this->getConfigValue('XAxisFormat'));
	}

	/**
	 * @returns TextField
	 * @note for relevant charts, returns the format for data on the Y Axis
	 */
	public function getYAxisFormatField() {
		return \TextField::create('ConfigData[YAxisFormat]', "Y-Axis Format", $this->getConfigValue('YAxisFormat'));
	}


	/**
	 * Script() renders the saved configuration into something Plotly understands
	 * @returns string
	 */
	public function Script() {

		$chart =  $this->Chart();

		$height = (int)$this->Height;
		$width = (int)$this->Width;

		$title = addslashes($chart->Title);
		$mode = addslashes($this->getConfigValue('Mode'));

		$xcolumn = addslashes($this->getConfigValue('XAxisColumn'));
		$ycolumn = addslashes($this->getConfigValue('YAxisColumn'));

		$xtitle = addslashes($this->getConfigValue('XAxisTitle'));
		$ytitle = addslashes($this->getConfigValue('YAxisTitle'));

		$xformat = addslashes($this->getConfigValue('XAxisFormat'));
		$yformat = addslashes($this->getConfigValue('YAxisFormat'));

		$dcolumn = addslashes($this->getConfigValue('DataColumn'));
		$lcolumn = addslashes($this->getConfigValue('LabelColumn'));

		$layout_margin = "margin : { l: 60, b: 60, r: 60, t: 60 }";
		if(!$this->IncludeTitleInChart) {
			// if an empty title is specified, the layout box is still shown and takes up space
			$layout_title = "";
		} else {
			$layout_title = "title : '{$title}',";
		}

		$script = <<<SCRIPT

var configuration = {};
// TYPE:{$chart->ChartType}

SCRIPT;

		switch($chart->ChartType) {
			case 'Pie':
				$script .= <<<SCRIPT
configuration.trace = function(rows) {
	return {
		type : 'pie',
		values : rows.map( function(row) { return row['$dcolumn'] }),
		labels : rows.map( function(row) { return row['$lcolumn'] })
	};
};
configuration.layout = {
	$layout_title
	showlegend : false,
	$layout_margin
};
SCRIPT;
				break;
			case 'Bar':
			$script .= <<<SCRIPT
configuration.trace = function(rows) {
return {
	type : 'bar',
	x : rows.map( function(row) { return row['$xcolumn'] }),
	y : rows.map( function(row) { return row['$ycolumn'] })
};
};
configuration.layout = {
$layout_title
showlegend : false,
$layout_margin
};
SCRIPT;
				break;
			case 'HorizontalBar':

				break;
			case 'Line':
				$script .= <<<SCRIPT
configuration.trace = function(rows) {
	return {
		mode : '$mode',
		type : 'scatter',
		x : rows.map( function(row) { return row['$xcolumn'] }),
		y : rows.map( function(row) { return row['$ycolumn'] })
	};
};
configuration.layout = {
	line : { 'width' : 1 },
	yaxis : { title : '$ytitle', tickformat : '$yformat' },
	xaxis : { title : '$xtitle', tickformat : '$xformat' },
	$layout_title,
	showlegend : false,
	$layout_margin
};
SCRIPT;
				break;
			case 'Scatter':
			case 'TimeSeries':
				$script .= <<<SCRIPT
configuration.trace = function(rows) {
	return {
		mode : '$mode',
		type : 'scatter',
		x : rows.map( function(row) { return row['$xcolumn'] }),
		y : rows.map( function(row) { return row['$ycolumn'] })
	};
};
configuration.layout = {
	$layout_title,
	showlegend : false,
	$layout_margin
};
SCRIPT;
				break;
			case 'Bubble':
				$script .= <<<SCRIPT
configuration.trace = function(rows) {
	return {
		mode : 'markers',
		x : rows.map( function(row) { return row['$xcolumn'] }),
		y : rows.map( function(row) { return row['$ycolumn'] }),
		marker: {
			size : rows.map( function(row) { return row['$dcolumn'] })
		}
	};
};
configuration.layout = {
	$layout_title,
	showlegend : false,
	$layout_margin
};
SCRIPT;
				break;
			default:
				break;
		}

		return $script;
	}
}
?>
