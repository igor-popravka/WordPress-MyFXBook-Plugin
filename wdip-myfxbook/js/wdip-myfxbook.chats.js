jQuery(document).ready(function ($) {
    if ((typeof WDIPMyFxBook != 'undefined') && !$.isEmptyObject(WDIPMyFxBook)) {
        WDIPMyFxBook.each(function (id, opt) {
            var chart = Highcharts.chart(id, getChartOptions(opt));

            var min = null,
                max = null,
                context = $('#' + id);

            $(opt.data).each(function (i, o) {
                min = min || o.x;
                max = max || o.x;

                min = Math.min(min, o.x);
                max = Math.max(max, o.x);
            });

            renderControlLabel(min, max, context);

            $(".slider-control", context).slider({
                range: true,
                min: min,
                max: max,
                values: [min, max],
                slide: function (event, ui) {
                    renderControlLabel(ui.values[0], ui.values[1], context);

                    var dataRange = [];
                    $(opt.data).each(function (i, o) {
                        if (o.x >= ui.values[0] && o.x <= ui.values[1]) {
                            dataRange.push(o);
                        }
                    });
                    chart.series[0].setData(dataRange);
                }
            });
        });
    }

    function getChartOptions(option) {
        $(option.data).each(function (i, r) {
            var dt = new Date(r.x);
            r.x = Date.UTC(dt.getFullYear(), dt.getMonth(), dt.getDate());
            option.data[i] = r;
        });

        switch (option.type) {
            case 'get-daily-gain':
                return {
                    lang: {
                        rangeSelectorZoom: ''
                    },
                    credits: {
                        enabled: true,
                        href: "https://www.myfxbook.com",
                        text: "Source: myfxbook.com",
                        position: {
                            y: -10
                        }
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
                        align: "left"
                    },
                    tooltip: {
                        valueSuffix: ' %'
                    },
                    xAxis: {
                        tickmarkPlacement: 'on',
                        gridLineWidth: 1,
                        gridLineColor: option.gridcolor || '#7A7F87',
                        gridLineDashStyle: 'dot',
                        type: 'datetime',
                        tickInterval: 1000 * 3600 * 24 * 30 // 1 months
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
                                linearGradient: [0, 0, 0, 300],
                                stops: [
                                    [0, Highcharts.getOptions().colors[0]],
                                    [1, Highcharts.Color(Highcharts.getOptions().colors[0]).setOpacity(0).get('rgba')]
                                ]
                            },
                            marker: {
                                radius: 4,
                                enabled: true
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
                            name: 'Profit',
                            color: Highcharts.getOptions().colors[0],
                            data: option.data
                        }
                    ]
                };
                break;
            case 'get-data-daily':
                return {
                    credits: {
                        enabled: true,
                        href: "https://www.myfxbook.com",
                        text: "Source: myfxbook.com",
                        position: {
                            y: -10
                        }
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
                        text: ((typeof option.filter != 'undefined') && option.filter == 1) ? sliderHTMLOwner() : '',
                        useHTML: true,
                        align: "left"
                    },
                    xAxis: {
                        tickmarkPlacement: 'on',
                        gridLineWidth: 1,
                        gridLineColor: option.gridcolor || '#7A7F87',
                        gridLineDashStyle: 'dot',
                        type: 'datetime',
                        tickInterval: 1000 * 3600 * 24 * 30, // 1 months
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
                    rangeSelector: {
                        selected: 1,
                        inputEnabled: false,
                        buttonTheme: {
                            width: 100,
                            height: 16,
                            fill: '#2B303A',
                            stroke: 'none',
                            'stroke-width': 0,
                            r: 5,
                            style: {
                                color: '#A0A0A0'
                            },
                            states: {
                                hover: {},
                                select: {
                                    fill: '#5B73A3',
                                    style: {
                                        color: 'white'
                                    }
                                }
                            }
                        },
                        buttons: [
                            {
                                type: 'month',
                                count: 6,
                                text: 'Last 6 months'
                            },
                            {
                                type: 'year',
                                count: 1,
                                text: 'Last 12 months'
                            },
                            {
                                type: 'all',
                                text: 'All'
                            }
                        ]
                    },
                    plotOptions: {
                        column: {
                            shadow: true,
                            color: 'rgba(124, 181, 236, 0.7)',
                            borderRadius: 3,
                            borderWidth: 0,
                            negativeColor: 'rgba(255, 79, 79, 0.7)'
                        }
                    },
                    series: [{
                        name: 'Profit',
                        data: option.data
                    }]
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

    function renderControlLabel(min, max, context) {
        var minDate = new Date(min),
            maxDate = new Date(max),
            locale = "en-us",
            minMonth = minDate.toLocaleString(locale, {month: "short"}),
            maxMonth = maxDate.toLocaleString(locale, {month: "short"});

        $('.label-control.left', context).text(minMonth + ', ' + minDate.getUTCFullYear());
        $('.label-control.right', context).text(maxMonth + ', ' + maxDate.getUTCFullYear());
    }
});
