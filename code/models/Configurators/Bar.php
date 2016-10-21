<?php
namespace Codem\Charts;
class Bar {

	public function __construct($config) {
		$this->config = $config;
	}

	public function ScriptValue($chart) {

		extract($this->config->ScriptValues($chart));

		$script = <<<SCRIPT
		configuration.trace = function(rows) {
			return [{
			type : 'bar',
			x : rows.map( function(row) { return row['$xcolumn'] }),
			y : rows.map( function(row) { return row['$ycolumn'] })
			}];
		};
		configuration.layout = {
			$layout_title
			showlegend : false,
			$layout_margin
		};
SCRIPT;
		return $script;
	}

}
