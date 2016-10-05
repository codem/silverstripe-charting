<?php
namespace Codem\Charts;
use Codem\Charts\ChartPreviewField as ChartPreviewField;
class Chart extends \DataObject {

	private $max_width = 512;

	private static $default_sort = "Sort";
	private static $singular_name = "Chart";
	private static $plural_name  = "Charts";

	private static $db = array(
		'Title' => 'Varchar(255)',
		'Description' => 'Text',
		'SourceURL' => 'Varchar(255)',
		'ChartType' => 'Enum(\'Pie,Bar,Line,Doughnut\',\'Line\')',
		'Width' => 'Int',
		'Height' => 'Int',
		'Enabled' => 'Boolean',
		'XAxisTitle' => 'Varchar(255)',
		'YAxisTitle' => 'Varchar(255)',
		'Sort' => 'Int'
	);

	private static $summary_fields = array(
		'ID' => '#',
		'Title' => 'Title',
		'Width' => 'Width',
		'Height' => 'Height',
		'SourceURL' => 'Source URL',
		'EnabledNice' => 'Enabled',
	);

	private static $defaults = array(
		'Enabled' => 0,
		'ChartType' => 'Line',
	);

	private static $has_one = array(
		'SourceFile' => 'Codem\Charts\ChartFile',
		'Author' => 'Member',
	);

	private static $belongs_many_many = array(
		'Pages' => 'SiteTree',// a chart can appear in multiple pages
	);

	public function EnabledNice() {
		return $this->Enabled == 1 ? "yes" : "no";
	}

	public function EncodedSourceURL() {
		return strip_tags($this->SourceURL);
	}

	/**
	 * @todo apply permissions
	 */
	public function canPreview($member) {
		return TRUE;
	}

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
		$field = \UploadField::create('SourceFile', 'Source File')
					->setAllowedExtensions(array('csv'))
					->setFolderName('ChartSourceFiles');
		$field->setAllowedMaxFileNumber(1);

		return $field;
	}

	public function getCmsFields() {
		$fields = parent::getCmsFields();
		$fields->removeByName('Sort');
		$fields->removeByName('Pages');
		$fields->addFieldToTab('Root.Main', $this->CsvUploadField(), 'SourceURL');
		if(!empty($this->ID)) {
			$this->setMaxWidth(512);
			$fields->addFieldToTab('Root.Main', ChartPreviewField::create('ChartPreview', 'Preview')->setChart( $this ) );
		}
		return $fields;
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

	public function getWidthHeightStyle() {
		return "width:" . ($this->Width > 0 ? ($this->Width . "px") : "100%") . ";"
				. "height:" . ($this->Height > 0 ? ($this->Height . "px") : "100%");
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