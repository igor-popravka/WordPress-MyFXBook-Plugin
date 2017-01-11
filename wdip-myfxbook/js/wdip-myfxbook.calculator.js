jQuery(document).ready(function ($) {
    var context = $('div[id^="wdip-calculator-"]'),
        series = null,
        options = context.data('options'),
        chart = Highcharts.chart($('#wdip-graph-wrap .wdip-graph', context)[0], options);

    $(".wdip-field[name='wdip_start_date']", context).datepicker({
        dateFormat: "yy-mm-dd",
        changeMonth: true,
        changeYear: true
    });

    $(".wdip-calculator", context).height($(".wdip-data-wrap", context).height() + 20);
    chart.setSize(579, $(".wdip-data-wrap", context).height());

    $('.wdip-button.wdip-calculate', context).click(function () {
        $.post("<?= admin_url('admin-ajax.php'); ?>", {
            action: 'wdip-calculate',
            amount: $(".wdip-field[name='wdip_amount']", context).val(),
            start: $(".wdip-field[name='wdip_start_date']", context).val(),
            fee: $(".wdip-field[name='wdip_performance_fee']", context).val(),
            id: $(".wdip-field[name='wdip_id']", context).val()
        }, function (result) {
            series = {categories: [], data: []};
            if (result.success) {
                for (var name in result.data) {
                    $(".wdip-result .wdip-field[name='wdip_" + name + "']", context).text(result.data[name]);
                }

                if (result.data.series.total_amount_data.length ||
                    result.data.series.fee_amount_data.length ||
                    result.data.series.gain_amount_data.length
                ) {
                    series = result.data.series
                } else {
                    series = null;
                }
            } else {
                $(".wdip-result .wdip-field", context).each(function () {
                    $(this).text('');
                });
            }

            if (series) {
                chart.xAxis[0].setCategories(series.categories);
                chart.series[0].setData(series.total_amount_data);
                chart.series[1].setData(series.fee_amount_data);
                chart.series[2].setData(series.gain_amount_data);
                $('#wdip-graph-wrap', context).dialog();
            }
        });
    });

    /*$('.wdip-menu .wdip-button', context).click(function () {
     if ($('.wdip-graph-wrap', context).is(':hidden') && series) {
     $('.wdip-graph-wrap', context).animate({width: "show"}, 1000);
     } else {
     $('.wdip-graph-wrap', context).animate({width: "hide"}, 1000);
     }
     });*/
});
