<!doctype html>
<html>
	<head>
		<title>$Title.XML</title>
		<style>
		html, body {
			padding :0;
			margin : 0;
		}
		.chart {
			height : 200px;/* default */
			width : 100%;
			background : #fff;
			border : none;
		}
		</style>
	</head>
	<body>
		<% if Chart %>
			<% with Chart %>
				<% include ChartElement %>
			<% end_with %>
		<% end_if %>
	</body>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
	<% include ChartScript %>
</html>
