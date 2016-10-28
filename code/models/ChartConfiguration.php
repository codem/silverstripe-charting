<?php
namespace Codem\Charts;
use Codem\Charts\Chart as Chart;
/**
 * Resresents configuration for a chart
 */
class ChartConfiguration extends \DataObject {

	private $configuration, $chart;

	private static $default_sort = "IsDefault DESC, Sort ASC, Created DESC";

	private static $singular_name = "Chart configuration";
	private static $plural_name  = "Chart configurations";

	private static $has_one = array(
		'Chart' => 'Codem\Charts\Chart'
	);

	private static $db = array(
		'Sort' => 'Int',
		'IsDefault' => 'Boolean',
		'Width' => 'Int',
		'Height' => 'Int',
		'SupportMultipleDatasets' => 'Boolean',
		'IncludeTitleInChart' => 'Boolean',
		'Config' => 'Text'
	);

	private static $summary_fields = array(
		'ID' => '#',
		'Title' => 'Title',
		'IsDefaultNice' => 'Default?',
		'Width' => 'Width',
		'Height' => 'Height',
	);

	public function IsDefaultNice() {
		return $this->IsDefault == 1 ? "yes" : "no";
	}

	public function getTitle() {
		return ($this->IsDefault == 1 ? "Default " : "") . "Configuration with width={$this->Width}, height={$this->Height}";
	}

	/**
	 * @note retrieve instance of Chart  linked to this configuration
	 * @returns mixed
	 */
	public function FindChart() {
		if(!empty($this->chart)) {
			return $this->chart;
		}
		$this->chart = $this->Chart();
		if(empty($this->chart->ID) && !empty($this->ChartID)) {
			// for new records, not yet saved, use the ChartID set from the Chart::getCmsFields
			$this->chart = Chart::get()->filter('ID', $this->ChartID)->first();
		}
		return $this->chart;
	}

	private function populateConfig() {
		if(empty($this->configuration)) {
			$this->configuration = json_decode($this->Config, FALSE);
		}
		return $this->configuration;
	}

	public function getConfigValue($name) {
		$config = $this->populateConfig();
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
		$chart = $this->FindChart();
		if(!empty($chart->ID)) {
			// Update IsDefault, turn other defaults off
			if($this->IsDefault == 1) {
				\DB::query("UPDATE `Codem\Charts\ChartConfiguration` SET IsDefault=0 WHERE ChartID= '" . \Convert::raw2sql($this->ChartID) . "' AND ID <> '" . \Convert::raw2sql($this->ID) . "'");
			}
			// avoid chart->write() calling this again...
			\DB::query("UPDATE `Codem\Charts\Chart` SET ConfigurationCompleted=1 WHERE ID='" . \Convert::raw2sql($chart->ID) . "'");
		}
		return TRUE;
	}

	public function getCmsFields() {

		$fields = parent::getCmsFields();
		$fields->removeByName('Config');
		$fields->removeByName('ChartID');
		$fields->removeByName('Sort');

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
			$fields->removeByName('IncludeTitleInChart');
		}

		if($this->ID) {
			// preview chart with this config
			$this->Chart()->setAltConfig( $this );
			$fields->addFieldsToTab('Root.Main', array(
				\LiteralField::create('ChartPreviewWithConfigLiteral', '<p class="message">Preview of chart, with this configuration.</p>'),
				ChartPreviewField::create('ChartPreview', 'Preview')->setChart( $this->Chart() ),
			));
		}
		return $fields;
	}


	public function getConfigurationFormFields() {
		$fields = \FieldList::create();

		$chart = $this->FindChart();

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
				// which column for which axis?
				$fields->push( $this->getXAxisColumn($source) );
				$fields->push( $this->getYAxisColumn($source) );

				$fields->push( $this->getLineWidthField() );

				$fields->push( $this->getXAxisFormatField() );
				$fields->push( $this->getYAxisFormatField() );
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
				// Bubbles plot a point in X/Y with a label, marker size is the data column
				$fields->push( $this->getXAxisTitleField() );
				$fields->push( $this->getYAxisTitleField() );
				// the position of the markers on the chart
				$fields->push( $this->getXAxisColumn($source) );
				$fields->push( $this->getYAxisColumn($source) );
				// a label for each bubble
				$fields->push( $this->getLabelColumn($source) );
				// the data column represents the raw data
				$fields->push( $this->getDataColumn($source) );
				// the marker column represents the marker size in px
				$fields->push( $this->getMarkerColumn($source) );
				break;
			default:
				// Unsupported chart type
				break;
		}
		return $fields;
	}

	/**
	 * @returns TextField
	 * @note for relevant charts, returns the line width
	 */
	public function getLineWidthField() {
		return \TextField::create('ConfigData[LineWidth]', "Line Width", $this->getConfigValue('LineWidth'));
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
	 * @note for relevant charts (Bubble, marker plots), allows a marker size to be determined for each row
	 */
	public function getMarkerColumn($source) {
		return \DropdownField::create('ConfigData[MarkerColumn]', "Marker Size Column", $source, $this->getConfigValue('MarkerColumn'));
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
		$chart =  $this->FindChart();
		if(empty($chart->ID)) {
			throw new Exception("No chart provided for Script Configuration");
		}
		if(empty($chart->ChartType)) {
			throw new Exception("No chart type provided for Script Configuration");
		}

		$chart_id = $chart->ID;

		$title = "";
		$configurator = "Codem\\Charts\\" . $chart->ChartType;

		$script = "";
		switch($chart->ChartType) {
			case 'Pie':
			case 'Bar':
			case 'HorizontalBar':
			case 'Line':
			case 'Scatter':
			case 'TimeSeries':
			case 'Bubble':
				$config = new $configurator($this);
				$script = $config->ScriptValue();
				break;
			default:
				break;
		} //end switch

		// Script to render chart
		$script = "//TYPE:{$chart->ChartType}\ncht.add($chart_id, { $script } );";
		return $script;
	}
}
