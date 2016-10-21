<div id="content" class="content" role="main">
        <div class="page-header">
        	<h1>
          		$Title
          	</h1>
        </div>
    <div class="typography-holder"><% include SubTitle %>
      <div class="typography">

        $Content

        <% if EnabledCharts %>
          <% if ChartListTitle %>
            <h3>Charts!</h3>
          <% end_if %>
          <% include ChartList %>
        <% else %>
          <h3>Zip!</h3>
          <div class="message">
            No charts on this page :frown:
          </div>
        <% end_if %>

      </div>
    </div>

</div>

<% include ChartScript %>
