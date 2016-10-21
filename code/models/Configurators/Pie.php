<?php
namespace Codem\Charts;
class Pie {

	private $config;

	public function __construct($config) {
		$this->config = $config;
	}

	public function ScriptValue($chart) {

		extract($this->config->ScriptValues($chart));

		$script = <<<SCRIPT
		configuration.trace = function(rows) {
			return [{
				type : 'pie',
				values : rows.map( function(row) { return row['$dcolumn'] }),
				labels : rows.map( function(row) { return row['$lcolumn'] })
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
