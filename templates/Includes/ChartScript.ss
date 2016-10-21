<% if Charts %>
<script>
	var Charts = function() {};
	Charts.prototype = {
		items : [],
		init : function() {},
		add : function(key, configuration) {
			this.items[key] = configuration;
		},
		get : function(key) {
			return this.items[key];
		}
	};
	var cht = new Charts();
	<% loop Charts %>
		$Configuration.Script

		cht.add('$ID', configuration);
	<% end_loop %>

</script>
<script>

	$(document).ready(function($) {

		$('.chart[data-chart-id]').each(
			function() {
				try {

					var _chart = this;

					var id = $(this).attr('data-chart-id');

					console.log(id);

					var src = $(this).attr('data-chart-source');
					if(!src) {
						throw 'No src available';
					}
					var config = cht.get(id);
					Plotly.d3.csv(src, function(rows) {
						var data = config.trace(rows);
						var layout = config.layout;
						var opts = {
							'showLink' : false,
							'scrollZoom' : false
						};
						Plotly.plot( _chart , data, layout, opts);

					});
				} catch (e) {
					console.log( $(this).attr('id') + ' failed...' );
					console.log(e)
				}

			}
		);
	});
</script>
<script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
<% end_if %>
