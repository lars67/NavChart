(function($) {
    'use strict';

    console.log('NavChart JS: Script loaded and ready.');

    $(document).ready(function() {
        console.log('NavChart JS: Document ready, looking for containers.');
        var $containers = $('.navchart-container');
        console.log('NavChart JS: Found ' + $containers.length + ' containers.');
        $containers.each(function() {
            initNavChart($(this));
        });
    });

    function initNavChart($container) {
        var chartId = $container.attr('id');
        var settings = $container.data('settings') || {};
        var chart = echarts.init(document.getElementById(chartId));

        // Default settings.
        settings.title = settings.title || 'Rotbund Global NAV';
        settings.yLabel = settings.yLabel || 'FinalNav';
        settings.smoothing = settings.smoothing || 'none';
        settings.animation = settings.animation !== false;

        // Fetch data.
        console.log( 'NavChart JS: Fetching data with settings:', settings );
    
        $.post(navchart_ajax.ajax_url, {
            action: 'navchart_data',
            nonce: navchart_ajax.nonce,
            days_back: settings.days_back || 30,
            smoothing: settings.smoothing,
            start_date: settings.start_date || '',
            end_date: settings.end_date || ''
        }, function(response) {
            console.log( 'NavChart JS: AJAX response:', response );
    
            if (response.success) {
                console.log( 'NavChart JS: Success, data points:', response.data.dates.length );
                var option = {
                    title: {
                        text: settings.title
                    },
                    tooltip: {
                        trigger: 'axis'
                    },
                    legend: {
                        data: ['FinalNav']
                    },
                    xAxis: {
                        type: 'category',
                        data: response.data.dates
                    },
                    yAxis: {
                        type: 'value',
                        name: settings.yLabel
                    },
                    grid: {
                        borderWidth: 0
                    },
                    dataZoom: [],
                    series: [{
                        name: 'FinalNav',
                        type: 'line',
                        data: response.data.values,
                        smooth: settings.smoothing !== 'none',
                        animation: settings.animation,
                        animationDuration: 1000,
                        animationEasing: settings.animType || 'linear',
                        lineStyle: {
                            width: 2,
                            color: '#FFD700'
                        },
                        itemStyle: {
                            color: '#FFD700'
                        },
                        markPoint: {
                            data: []
                        }
                    }],
                    visualMap: {
                        show: false,
                        dimension: 1,
                        min: Math.min.apply(null, response.data.values),
                        max: Math.max.apply(null, response.data.values),
                        inRange: {
                            color: ['#FFD700', '#FFFFE0']
                        }
                    },
                    backgroundColor: '#90EE90'
                };
    
                chart.setOption(option);
                console.log( 'NavChart JS: Chart rendered.' );
            } else {
                console.error( 'NavChart JS: AJAX error:', response.data );
                $container.html('<p>Error loading chart data: ' + (response.data || 'Unknown error') + '</p>');
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error( 'NavChart JS: AJAX fail:', textStatus, errorThrown, jqXHR );
            $container.html('<p>Failed to load chart: ' + textStatus + '</p>');
        });
    }

})(jQuery);