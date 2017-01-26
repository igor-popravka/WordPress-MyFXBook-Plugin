jQuery(document).ready(function ($) {
    $.fn.FXCalculator = function (options) {
        var context = this,
            opt = $.extend({
                fee: ['0.00'],
                accID: null,
                saveData: function () {
                    var post_data = context.data('post_data'),
                        data = {};
                    if (!post_data) {
                        post_data = {};
                    }
                    data[$(this).attr('name')] = $(this).val();
                    context.data('post_data', $.extend(post_data, data));
                }
            }, options);

        $('.show-graph', context).button().on('click', function () {
            if(context.data('series_data')){
                var el = $('<div>').css({display: "none"}).appendTo(context),
                    chart = Highcharts.chart(el[0], opt.chart_options),
                    series = context.data('series_data');

                chart.xAxis[0].setCategories(series.categories);
                chart.series[0].setData(series.total_amount_data);
                chart.series[1].setData(series.gain_amount_data);
                chart.series[2].setData(series.fee_amount_data);

                el.dialog({
                    title: "Calculation result into graph",
                    width: opt.chart_options.chart.width + 50
                });
            }
        });

        $('.wdip-menu', context).height(
            $('.show-graph', context).height()
        );

        $('.wdip-data-amount input', context).on('change', opt.saveData);
        $('.wdip-data-date input', context).datepicker({
            dateFormat: "yy-mm-dd",
            changeMonth: true,
            changeYear: true
        }).on('change', opt.saveData);
        $('.wdip-data-fee select', context).html((function (opt) {
            var selOpt = "";
            $(opt.fee).each(function (n, item) {
                selOpt += "<option value=\"" + parseFloat(item / 100).toFixed(2) + "\">" + item + "%</option>\n";
            });
            return selOpt;
        })(opt)).on('change', opt.saveData);
        context.data('post_data', {
            fee: $('.wdip-data-fee select', context).find('option:selected').attr('value')
        });

        $('form', context).submit(function (e) {
            e.preventDefault();
            $.post(opt.url, $.extend({
                action: 'wdip-calculate',
                id: opt.accID
            }, context.data('post_data')), function (result) {
                var series = null;
                if (result.success) {
                    for (var name in result.data) {
                        $(".wdip-result span[name='wdip_" + name + "']", context).text(result.data[name]);
                    }

                    if (result.data.series.total_amount_data.length ||
                        result.data.series.fee_amount_data.length ||
                        result.data.series.gain_amount_data.length
                    ) {
                        series = result.data.series
                    }
                } else {
                    $(".wdip-result span", context).each(function () {
                        $(this).text('');
                    });
                }
                context.data('series_data', series);
            });
            return false;
        });
    }
});