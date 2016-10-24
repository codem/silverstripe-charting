<?php
namespace Codem\Charts;
use Codem\Charts\Configurator as Configurator;
use Codem\Charts\Bar as Bar;
class Line extends Bar {

	protected $orientation = 'v';
	protected $line_width = 1;

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

	protected function getLayout() {
		// layout options
		$title = $this->getLayoutTitle();
		$margin = $this->getLayoutMargin();
		$legend = $this->getLayoutLegend();
		$xaxis = $this->getLayoutXAxis();
		$yaxis = $this->getLayoutYAxis();

		return json_encode(array_merge(
			$title,
			$margin,
			$legend,
			$xaxis,
			$yaxis
		));
	}

	public function ScriptValue() {

		$xcolumn = $this->config->getConfigValue('XAxisColumn');
		$ycolumn = $this->config->getConfigValue('YAxisColumn');
		$mode = $this->config->getConfigValue('Mode');// lines markers etc
		$layout = $this->getLayout();

		$line_width = round($this->config->getConfigValue('LineWidth'));
		if($line_width <= 0) {
			$line_width = 1;
		}

		$script = <<<SCRIPT
		trace : function(rows) {
			return [{
				mode : '$mode',
				type : 'scatter',
				line : {
					width: $line_width
			  },
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
