<% if Enabled || InPreview %>
	<% if not InPreview %>
		<h3>$Title.XML</h3>
		<% if Description %>
		<p>$Description.XML</p>
		<% end_if %>
	<% end_if %>
	<div class="chart" id="chart-$ID" style="$WidthHeightStyle.XML" data-chart-id="$ID" data-chart-source="$ChartSourceURL.XML"></div>
<% end_if %>
