<?php
namespace Codem\Charts;
use Codem\Charts\Chart as Chart;
use Codem\Charts\ChartConfiguration as ChartConfiguration;
/**
 * Provides configuration handling for the different chart types
 * the idea here is to decouple generic chart configuration +  cms fields from chart display and 3rd party chart libraries
 */
abstract class Configurator {
	protected $config, $chart;

	public function __construct(ChartConfiguration $config) {
		$this->config = $config;
		$this->chart = $config->FindChart();
	}

	abstract public function ScriptValue();

	protected function getLayoutTitle() {
		$title = $this->chart->Title;
		if(!$this->config->IncludeTitleInChart) {
			// if an empty title is specified, the layout box is still shown and takes up space
			$layout_title = array();
		} else {
			$layout_title = array(
				'title' => $title,
			);
		}
		return $layout_title;
	}

	protected function getLayoutMargin() {
		$margin = array(
			'margin' => array(
				'l' => 60,
				'b' => 60,
				'r' => 60,
				't' => 60,
			)
		);
		return $margin;
	}

	protected function getLayoutLegend() {
		return array(
			'showlegend' => FALSE
		);
	}

}
