jQuery(document).ready(function ($) {
    if ((typeof WDIPMyFxBook != 'undefined') && !$.isEmptyObject(WDIPMyFxBook)) {
        var oneMonthPoint = 1000 * 3600 * 24 * 30;

        WDIPMyFxBook.each(function (id, opt) {
            var chart_options = getChartOptions(opt);
            var chart = Highcharts.chart(id, chart_options);

            var min = null,
                max = null,
                context = $('#' + id);

            $(opt.data).each(function (i, r) {
                min = min || r.x;
                max = max || r.x;

                min = Math.min(min, r.x);
                max = Math.max(max, r.x);
            });

            renderControlLabel(min, max, context, chart);

            $(".slider-control", context).slider({
                range: true,
                min: min,
                max: max,
                values: [min, max],
                slide: function (event, ui) {
                    renderControlLabel(ui.values[0], ui.values[1], context);

                    var dataRange = [];
                    $(opt.data).each(function (i, r) {
                        if (r.x >= ui.values[0] && r.x <= ui.values[1]) {
                            dataRange.push(r);
                        }
                    });

                    chart.series[0].setData(dataRange);
                }
            });

            $(".button-months", context).each(function () {
                $(this).click(function () {
                    $(".button-months", context).each(function () {
                        $(this).css("background-color", "rgba(68, 149, 204, 0.85)");
                    });

                    var role = $(this).attr('role'),
                        from = false,
                        to = max,
                        dataRange = [];

                    switch (role) {
                        case 'last-6-months':
                            from = to - (6 * oneMonthPoint);
                            break;
                        case 'last-12-months':
                            from = to - (12 * oneMonthPoint);
                    }
                    
                    $(opt.data).each(function (i, r) {
                        if (from !== false) {
                            if (r.x >= from && r.x <= to) {
                                dataRange.push(r);
                            }
                        } else {
                            dataRange.push(r);
                        }
                    });

                    chart.series[0].setData(dataRange);

                    $(this).css("background-color", "rgba(68, 149, 204, 1)");
                });
            })
        });
    }

    function getChartOptions(option) {
        $(option.data).each(function (i, r) {
            var dt = new Date(r.x);
            r.x = Date.UTC(dt.getFullYear(), dt.getMonth(), dt.getDate());
        });


        switch (option.type) {
            case 'get-data-daily':
                return {
                    lang: {
                        rangeSelectorZoom: ''
                    },
                    credits: {
                        enabled: false
                    },
                    chart: {
                        backgroundColor: option.bgcolor || null,
                        type: 'areaspline',
                        zoomType: 'x',
                        height: option.height || null,
                        width: option.width || null,
                        spacingBottom: 25
                    },
                    title: {
                        text: option.title || ''
                    },
                    subtitle: {
                        text: ((typeof option.filter != 'undefined') && option.filter == 1) ? sliderHTMLOwner() : '',
                        useHTML: true,
                        align: "center"
                    },
                    tooltip: {
                        valueSuffix: '%'
                    },
                    xAxis: {
                        tickmarkPlacement: 'on',
                        gridLineWidth: 1,
                        gridLineColor: option.gridcolor || '#7A7F87',
                        gridLineDashStyle: 'dot',
                        type: 'datetime',
                        tickInterval: 1000 * 3600 * 24,
                        labels: {
                            formatter: function () {
                                var dt = new Date(this.value),
                                    local = 'en-US';
                                return dt.toLocaleDateString(local, {month: 'short', year: '2-digit', day: 'numeric'});
                            }
                        }
                    },
                    yAxis: {
                        gridLineColor: option.gridcolor || '#7A7F87',
                        title: {
                            text: ''

                        },
                        labels: {
                            formatter: function () {
                                return this.value + '%';
                            }
                        }
                    },
                    legend: {
                        enabled: false
                    },
                    plotOptions: {
                        areaspline: {
                            fillColor: {
                                linearGradient: [0, 0, 0, 240],
                                stops: [
                                    [0, Highcharts.getOptions().colors[0]],
                                    [1, Highcharts.Color(Highcharts.getOptions().colors[0]).setOpacity(0).get('rgba')]
                                ]
                            },
                            lineWidth: 2,
                            states: {
                                hover: {
                                    lineWidth: 3
                                }
                            },
                            threshold: null
                        },
                        allowPointSelect: true

                    },
                    series: [
                        {
                            name: 'Growth',
                            color: Highcharts.getOptions().colors[0],
                            data: option.data,
                            turboThreshold: 0
                        }
                    ]
                };
                break;
            case 'get-daily-gain':
            case 'get-monthly-gain-loss':
                return {
                    credits: {
                        enabled: false
                    },
                    chart: {
                        backgroundColor: option.bgcolor || null,
                        type: 'column',
                        zoomType: 'x',
                        height: option.height || null,
                        width: option.width || null,
                        spacingBottom: 25
                    },
                    title: {
                        text: option.title || ''
                    },
                    subtitle: {
                        text: ((typeof option.filter != 'undefined') && option.filter == 1) ? buttonHTMLOwner() : '',
                        useHTML: true,
                        align: "right"
                    },
                    xAxis: {
                        tickmarkPlacement: 'on',
                        gridLineWidth: 1,
                        gridLineColor: option.gridcolor || '#7A7F87',
                        gridLineDashStyle: 'dot',
                        type: 'datetime',
                        tickInterval: oneMonthPoint, // 1 months
                        crosshair: true
                    },
                    yAxis: {
                        gridLineColor: option.gridcolor || '#7A7F87',
                        title: {text: ''},
                        labels: {
                            formatter: function () {
                                return this.value + '%';
                            }
                        }
                    },
                    legend: {
                        enabled: false
                    },
                    plotOptions: {
                        column: {
                            shadow: true,
                            borderRadius: 3,
                            borderWidth: 1
                        }
                    },
                    tooltip: {
                        valueSuffix: '%'
                    },
                    series: [
                        {
                            name: 'Growth',
                            data: option.data,
                            color: 'rgba(124, 181, 236, 0.7)',
                            negativeColor: 'rgba(255, 79, 79, 0.7)'
                        }
                    ]
                };
                break;
            default:
                return {};
        }
    }

    function sliderHTMLOwner() {
        return '<div class="chart-range-control">\
                    <div class="label-control left"></div>\
                    <div class="slider-control"></div>\
                    <div class="label-control right"></div>\
                </div>';
    }

    function buttonHTMLOwner() {
        return '<div class="chart-button-control">\
                    <button class="button-months" role="last-6-months" >Last 6 months</button>\
                    <button class="button-months" role="last-12-months" >Last 12 months</button>\
                    <button class="button-months" role="all-months">All months</button>\
                </div>';
    }

    function renderControlLabel(min, max, context, chart) {
        if($('.label-control', context).length){
            var minDate = new Date(min),
                maxDate = new Date(max),
                locale = "en-us",
                minMonth = minDate.toLocaleDateString(locale, {month: 'short', year: '2-digit', day: 'numeric'}),
                maxMonth = maxDate.toLocaleDateString(locale, {month: 'short', year: '2-digit', day: 'numeric'});

            $('.label-control.left', context).text(minMonth);
            $('.label-control.right', context).text(maxMonth);
            
            if(typeof chart != 'undefined'){
                chart.redraw();
            }
        }
    }
});
