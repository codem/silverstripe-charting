<script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
<script>
$('.chart[data-chart-id]').each(
	function() {
		try {
			var _chart = this;
			var src = $(this).attr('data-chart-source');
			if(!src) {
				throw 'No src available';
			}
			var xAxisTitle = $(this).attr('data-chart-xaxis');
			var yAxisTitle = $(this).attr('data-chart-yaxis');

			Plotly.d3.csv(src, function(rows){
				var column_names = Object.keys(rows[0]);
				console.log(column_names);
				var trace = {
					line : {
						width : 1
					},
					type : $(this).attr('data-chart-type'),
					mode : 'lines+markers',
					x : rows.map( function(row) {
							return row[column_names[1]]
						}),
					y : rows.map( function(row) {
							return row[column_names[2]]
						}),
				};

				var layout = {
					'title' : $(this).attr('data-chart-title'),
					'showlegend' : false,
					'margin' : { l:4, t:4, b:4, r:4 }
				};
				layout.yaxis = {
					'showgrid' : true
				};
				layout.xaxis = {
					'showgrid' : true
				};

				layout.yaxis.title = (yAxisTitle ? yAxisTitle : '');
				layout.xaxis.title = (xAxisTitle ? xAxisTitle : '');

				var opts = {
					'showLink' : false,
					'scrollZoom' : false
				};

				console.log('plotting....');
				Plotly.plot( _chart , [trace], layout, { showLink: false });

			});

		} catch (e) {
			console.log( $(this).attr('id') + ' failed...' );
			console.log(e)
		}
 	}
);
</script>
