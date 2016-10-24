<?php
namespace Codem\Charts;
use Codem\Charts\Configurator as Configurator;
use Codem\Charts\Line as Line;
/**
 * TimeSeries
 * @note like a Line Plot but when one or more axes are in a date/time format
 */
class TimeSeries extends Line {

	protected $orientation = 'v';
	protected $line_width = 1;

	protected $default_axis_format = '%B, %Y',// Month, Year

	protected function getLayoutXAxis() {
		return array(
			'xaxis' => array(
				'title' => $this->config->getConfigValue('XAxisTitle'),
				'tickformat' => $this->config->getConfigValue('XAxisFormat'),
			)
		);
	}

	protected function getLayoutYAxis() {
		return array(
			'yaxis' => array(
				'title' => $this->config->getConfigValue('YAxisTitle'),
				'tickformat' => $this->config->getConfigValue('YAxisFormat'),
			)
		);
	}

	public function ScriptValue() {

		$xcolumn = $this->config->getConfigValue('XAxisColumn');
		$ycolumn = $this->config->getConfigValue('YAxisColumn');
		$mode = $this->config->getConfigValue('Mode');// lines markers etc
		$layout = $this->getLayout();

		$script = <<<SCRIPT
		trace : function(rows) {
			return [{
				mode : '$mode',
				type : 'scatter',
				x : rows.map( function(row) { return row['$xcolumn'] }),
				y : rows.map( function(row) { return row['$ycolumn'] }),
				orientation : '$this->orientation'
			}];
		},
		layout : $layout
SCRIPT;
		return $script;
	}

}
