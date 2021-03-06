<?php
namespace Codem\Charts;
use Codem\Charts\Configurator as Configurator;
class Bar extends Configurator {

	protected $orientation = 'v';

	public function ScriptValue() {

		$xcolumn = $this->config->getConfigValue('XAxisColumn');
		$ycolumn = $this->config->getConfigValue('YAxisColumn');

		// layout options
		$title = $this->getLayoutTitle();
		$margin = $this->getLayoutMargin();
		$legend = $this->getLayoutLegend();

		$layout = json_encode(array_merge(
			$title,
			$margin,
			$legend
		));

		$script = <<<SCRIPT
		trace : function(rows) {
			return [{
				type : 'bar',
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
