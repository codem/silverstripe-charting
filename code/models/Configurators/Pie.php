<?php
namespace Codem\Charts;
class Pie extends Configurator {

	public function ScriptValue() {

		$dcolumn = $this->config->getConfigValue('DataColumn');
		$lcolumn = $this->config->getConfigValue('LabelColumn');

		// layout options
		$title = $this->getLayoutTitle();
		$margin = $this->getLayoutMargin();
		$legend = $this->getLayoutLegend();

		$layout = json_encode(array_merge(
			$title,
			$margin,
			$legend
		));

		$script = <<<JAVASCRIPT
		trace : function(rows) {
			return [{
				type : 'pie',
				values : rows.map( function(row) { return row['$dcolumn'] }),
				labels : rows.map( function(row) { return row['$lcolumn'] })
				}];
		},
		layout : $layout
JAVASCRIPT;
		return $script;

	}


}
