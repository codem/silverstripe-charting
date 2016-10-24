<?php
namespace Codem\Charts;
use Codem\Charts\Configurator as Configurator;
use Codem\Charts\Line as Line;
class Scatter extends Line {

	protected $orientation = 'v';
	protected $line_width = 1;

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
