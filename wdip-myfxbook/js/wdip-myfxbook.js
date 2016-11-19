jQuery(document).ready(function ($) {
    google.charts.load('current', {'packages': ['corechart', 'bar', 'controls']});
    google.charts.setOnLoadCallback(drawChart);

    function drawChart() {
        if (!$.isEmptyObject(wdip_myfxbook_options)) {
            $(wdip_myfxbook_options).each(function (i, opt) {
                drawDashboard(opt);
            });
        }

        function drawDashboard(opt) {
            var data = new google.visualization.DataTable(opt.data($));
            // Create a dashboard.
            var dashboard = new google.visualization.Dashboard($('dashboard-' + opt.id));
            // Create a range slider, passing some options
            var control = new google.visualization.ControlWrapper({
                controlType: 'DateRangeFilter',
                containerId: 'filter-' + opt.id,
                options: {
                    filterColumnIndex: 0,
                    ui: {
                        format: {pattern: "MMM dd, yy"}
                    }
                }
            });

            // Create a pie chart, passing some options
            var chart = new google.visualization.ChartWrapper({
                chartType: opt.chart,
                containerId: 'chart-' + opt.id,
                options: opt.options
            });
            dashboard.bind(control, chart);
            // Draw the dashboard.
            dashboard.draw(data);
        }
    }
});
