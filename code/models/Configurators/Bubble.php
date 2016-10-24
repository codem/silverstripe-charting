<?php
namespace Codem\Charts;
use Codem\Charts\Configurator as Configurator;
/**
 * Bubble - unstable
 */
class Bubble extends Configurator {

	protected function getLayoutMarkerSize() {

		//$size = "rows.map( function(row) { return row['$dcolumn'] })"
		return array(
			'marker' => array( 'size' => '' )
		);
	}

	protected function getLayout() {
		// layout options
		$title = $this->getLayoutTitle();
		$margin = $this->getLayoutMargin();
		$legend = $this->getLayoutLegend();
		$marker_size = $this->getLayoutMarkerSize();

		return json_encode(array_merge(
			$title,
			$margin,
			$legend,
			$marker_size
		));
	}

	public function ScriptValue() {

		$xcolumn = $this->config->getConfigValue('XAxisColumn');
		$ycolumn = $this->config->getConfigValue('YAxisColumn');
		$dcolumn = $this->config->getConfigValue('DataColumn');
		$lcolumn = $this->config->getConfigValue('LabelColumn');
		$mcolumn = $this->config->getConfigValue('MarkerColumn');

		if(!$mcolumn) {
			$mcolumn = $dcolumn;
		}

		$layout = $this->getLayout();

		$script = <<<JAVASCRIPT
		trace : function(rows) {
			return [{
				mode : 'markers',
				type : 'scatter',
				marker : {
					sizemode: 'area',
					sizeref: 2e4,
					size : rows.map( function(row) { return Math.round(row['$mcolumn']); } )
				},
				x : rows.map( function(row) { return row['$xcolumn']; }),
				y : rows.map( function(row) { return row['$ycolumn']; })
			}];
		},
		layout : $layout
JAVASCRIPT;
		return $script;
	}

}
