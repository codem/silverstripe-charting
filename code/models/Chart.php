<?php
namespace Codem\Charts;
use Codem\Charts\ChartPreviewField as ChartPreviewField;
use Codem\Charts\ChartConfiguration as ChartConfiguration;
class Chart extends \DataObject {

	private $max_width = 512;
	private $in_preview = FALSE;

	private static $default_sort = "Sort";
	private static $singular_name = "Chart";
	private static $plural_name  = "Charts";

	private static $db = array(
		'Enabled' => 'Boolean',
		'Title' => 'Varchar(255)',
		'Description' => 'Text',
		'SourceURL' => 'Varchar(255)',
		'ChartType' => 'Enum(\'Pie,Bar,HorizontalBar,Line,Scatter,TimeSeries\',\'Line\')',
		'ConfigurationCompleted' => 'Boolean',// can only display if configuration completed
		'Sort' => 'Int'
	);

	private static $summary_fields = array(
		'ID' => '#',
		'Title' => 'Title',
		'ChartType' => 'Type',
		'ChartSource' => 'Source',
		'ChartSourceURL' => 'Source URL',
		'EnabledNice' => 'Enabled',
	);

	private static $defaults = array(
		'Enabled' => 0,
		'ChartType' => 'Line',
	);

	private static $has_one = array(
		'SourceFile' => 'Codem\Charts\ChartFile',
		'Author' => 'Member',// TODO
		'Configuration' => 'Codem\Charts\ChartConfiguration'
	);

	private static $belongs_many_many = array(
		'Pages' => 'SiteTree',// a chart can appear in multiple pages
	);

	public function EnabledNice() {
		return $this->Enabled == 1 ? "yes" : "no";
	}

	/**
	 * @note remove cruft from an uploaded file URL
	 * @todo check against CSV imports, special characters and the like
	 */
	public function EncodedSourceURL() {
		return strip_tags($this->SourceURL);
	}

	public function setInPreview($in) {
		$this->in_preview = $in;
	}

	public function InPreview() {
		return $this->in_preview;
	}

	/**
	 * @todo apply permissions
	 */
	public function canPreview($member) {
		return TRUE;
	}

	/**
	 * @returns string
	 * @note return where the source is coming from
	 */
	public function ChartSource() {
		$file = $this->SourceFile();
		if(!empty($file->ID) && ($file instanceof ChartFile)) {
			return "Uploaded file";
		} else if($this->SourceURL) {
			return "Remote URL";
		} else {
			return "";
		}
	}

	/**
	 * @returns string
	 * @note determine the chart source URL, local file is taken first if it exists
	 */
	public function ChartSourceURL() {
		$file = $this->SourceFile();
		if(!empty($file->ID) && ($file instanceof ChartFile)) {
			return $file->AbsoluteLink();
		} else if($this->SourceURL) {
			return $this->EncodedSourceURL();
		} else {
			return "";
		}
	}

	/**
	 * PreviewURL
	 * @note is used as an iframe src URL to preview charts in CMS/admin
	 */
	public function PreviewURL() {
		return \Controller::join_links( \Director::baseURL(),  'chartview', 'preview', $this->ID);
	}

	private function CsvUploadField() {
		$field = \UploadField::create('SourceFile', 'Source File');
		$field->setAllowedExtensions(array('csv'));
		$field->setFoldername('Uploads/ChartSourceFiles');
		$field->setDisplayFoldername('Uploads/ChartSourceFiles');
		$field->setAllowedMaxFileNumber(1);
		return $field;
	}

	/**
	 * Event handler called before writing to the database.
	 */
	public function onBeforeWrite()
	{
		parent::onBeforeWrite();
		if(empty($this->ID) && empty($this->AuthorID)) {
			$member = Member::currentUser();
			if(!empty($member->ID)) {
				$this->AuthorID = $member->ID;
			}
		}

		// create a configuration if one has not been created already - allows for better relation management
		if(empty($this->ConfigurationID)) {
			// config is not complete
			$this->ConfigurationCompleted = 0;
			// create a new configuration for this chart, probably on first save
			$configuration = new ChartConfiguration();
			$configuration_id = $configuration->write();
			if(!empty($configuration_id)) {
				$this->ConfigurationID = $configuration_id;
			}
		}

		// if config is not completed, the chart cannot be enabled
		if(empty($this->ConfigurationCompleted) || $this->ConfigurationCompleted != 1) {
			$this->Enabled = 0;
		}
		return TRUE;
	}

	public function getCmsFields() {

		$fields = parent::getCmsFields();
		$fields->removeByName('Sort');
		$fields->removeByName('Pages');
		$fields->removeByName('ConfigurationCompleted');
		$fields->removeByName('ConfigurationID');

		$fields->addFieldToTab('Root.Main', $this->CsvUploadField(), 'SourceURL');
		$fields->addFieldToTab('Root.Main', \DropdownField::create('AuthorID', 'Author', \Member::get()->map('ID','Title')), 'Description');

		if(!empty($this->ID)) {

			// TODO add some information here about the chart requiring configuration (if not yet configured)
			$fields->addFieldToTab(
				'Root.Main',
				\CheckboxField::create(
					'Enabled',
					'Display chart on website'
				),
				'Title'
			);

			// in the admin, set a max width for the preview, in line with other fields
			$this->setMaxWidth(512);
			$fields->addFieldToTab('Root.Main', ChartPreviewField::create('ChartPreview', 'Preview')->setChart( $this ) );

			// provide a configuration form based on the file uploaded and the type selected
			$configuration = $this->Configuration();
			// basic title for config
			$fields->addFieldToTab('Root.Configuration', \ReadOnlyField::create('CurrentConfiguration', 'Configuration', $configuration->getTitle()));
			$configuration_button = \HasOneButtonField::create('Configuration', 'Configuration', $this);
			$configuration_button_config = $configuration_button->getConfig();
			$edit_button = $configuration_button_config->getComponentByType('GridFieldHasOneEditButton');
			$add_button = $configuration_button_config->getComponentByType('GridFieldAddNewButton');
			$edit_button->setButtonName('Edit Configuration');
			$add_button->setButtonName('Add new configuration');
			if($this->ConfigurationCompleted == 1) {
				$add_button->setButtonName('Edit configuration');
			}
			$fields->addFieldToTab('Root.Configuration', $configuration_button);

		} else {
			$fields->removeByName('Enabled');
		}
		return $fields;
	}

	public function getSourceHeadings() {
		$url = $this->ChartSourceURL();
		if(!$url) {
			return FALSE;
		}

		try {
			$ch = curl_init();
			$timeout = 5;
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			// Because some hosts sniff user agents like wget and curl
			curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.143 Safari/537.36");
			$data = curl_exec($ch);

			curl_close($ch);
			$tmpfile = tmpfile();
			fwrite($tmpfile, $data);
			rewind($tmpfile);

			$row = fgetcsv($tmpfile, 0, ",", "\"");
			fclose($tmpfile);//rm the tmpfile

			$row = (!empty($row) ? $row : array());
			$source = array();
			foreach($row as $value) {
				$source[$value] = $value;
			}
			return $source;

		} catch (Exception $e) {

		}

		return FALSE;
	}

	/**
	 * forTemplate
	 * @note renderWith ChartDisplay when calling Chart
	 */
	public function forTemplate() {
		return $this->renderWidth('ChartDisplay');
	}

	public function getMaxWidth() {
		return $this->max_width;
	}

	public function setMaxWidth($max) {
		$this->max_width = $max;
	}

	public function getDefaultHeight() {
		return 300;
	}

	public function getWidthHeightStyle() {
		$config = $this->Configuration();
		if(empty($config->ID)) {
			return "";
		}
		$width = $config->Width;
		$height = $config->Height;
		return "width:" . ($width > 0 ? ($width . "px") : "100%") . ";"
				. "height:" . ($height > 0 ? ($height . "px") : "100%") . ";";
	}


	// ---- SHORTCODE-ABLE --- //

	public function getShortcodeFields() {
		return \FieldList::create(
					\DropdownField::create(
						'Style',
						'Chart Style',
						array('Width','Height','XAxisTitle','YAxisTitle')
					)
		);
	}

	// could maybe do it by authored graphs first?
	public function getShortcodableRecords() {
		return Chart::get()->sort('Sort ASC')->map('ID', 'Title');
	}

	public function getShortCodePlaceHolder($attributes) {
		// in a short code, set max = width
		$this->setMaxWidth($this->Width);
		$data = array(
			'Chart' => $this
		);
		return  $this->customise( new ArrayData($data) )->renderWith('ChartPreviewField');
	}

	/**
     * Parse the shortcode and render as a string, probably with a template
     * @param array $attributes the list of attributes of the shortcode
     * @param string $content the shortcode content
     * @param ShortcodeParser $parser the ShortcodeParser instance
     * @param string $shortcode the raw shortcode being parsed
     * @return String
     **/
    public static function parse_shortcode($attributes, $content, $parser, $shortcode)
    {
        // check the gallery exists
        if (isset($attributes['id']) && $gallery = Chart::get()->byID($attributes['id'])) {
            // collect custom attributes from the shortcode popup form
			if(!empty($chart->ID)) {
				// just render it into ChartDisplay
				return $chart->forTemplate();
			}
        }

		return "";
    }
}
