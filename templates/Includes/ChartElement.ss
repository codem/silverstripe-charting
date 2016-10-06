<% if Enabled || InPreview %>
	<h3>$Title.XML</h3>
	<% if Description %>
	<p>$Description.XML</p>
	<% end_if %>
	<div class="chart" id="chart-$ID" style="$WidthHeightStyle.XML" data-chart-id="$ID" data-chart-source="$ChartSourceURL.XML"></div>
<% end_if %>
