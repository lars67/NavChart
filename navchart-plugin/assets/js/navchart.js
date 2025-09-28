(function($) {
    'use strict';

    $(document).ready(function() {
        console.log('NavChart: Starting initialization - FIXED VERSION');
        $('.navchart-container').each(function() {
            initNavChart($(this));
        });
    });

    function initNavChart($container) {
        var chartId = $container.attr('id');
        var settings = JSON.parse($container.attr('data-settings') || '{}');
        
        // Default settings
        settings.title = settings.title || 'Robofunds Global A/S';
        settings.yLabel = settings.yLabel || 'FinalNav';
        settings.smoothing = settings.smoothing || 'none';
        settings.animation = settings.animation !== false;
        settings.height = settings.height || 400;

        // Set container height
        $container.css('height', settings.height + 'px');

        // Show loading message
        $container.html('<div style="text-align: center; padding: 50px; color: #666; font-size: 16px;">Loading Excel data...</div>');

        console.log('NavChart: Requesting Excel data');
    
        // AJAX request for Excel data
        var ajaxData = {
            action: 'navchart_data',
            nonce: navchart_ajax.nonce,
            smoothing: settings.smoothing,
            smoothing_factor: settings.smoothing_factor || 3
        };
        
        // Use custom date range if enabled, otherwise use days_back
        if (settings.use_custom_range && (settings.start_date || settings.end_date)) {
            ajaxData.start_date = settings.start_date || '';
            ajaxData.end_date = settings.end_date || '';
            ajaxData.days_back = 0; // Disable days_back when using custom range
        } else {
            ajaxData.days_back = settings.days_back || 365; // Show all available data
            ajaxData.start_date = '';
            ajaxData.end_date = '';
        }
        
        console.log('NavChart: AJAX parameters:', ajaxData);
        
        $.ajax({
            url: navchart_ajax.ajax_url,
            type: 'POST',
            data: ajaxData,
            dataType: 'json',
            success: function(response) {
                console.log('NavChart: AJAX response received:', response);
                
                if (response.success && response.data && response.data.dates && response.data.values) {
                    console.log('NavChart: SUCCESS - Excel data loaded');
                    console.log('- Data points:', response.data.dates.length);
                    console.log('- Date range:', response.data.dates[0], 'to', response.data.dates[response.data.dates.length-1]);
                    console.log('- Value range:', Math.min.apply(Math, response.data.values), 'to', Math.max.apply(Math, response.data.values));
                    renderChart($container, response.data, settings);
                } else {
                    console.error('NavChart: Invalid Excel data response:', response);
                    showError($container, response.data ? response.data.message : 'Invalid Excel data response');
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                console.error('NavChart: AJAX error:', textStatus, errorThrown);
                showError($container, 'Failed to load Excel data: ' + textStatus + ' - ' + errorThrown);
            }
        });
    }

    function renderChart($container, data, settings) {
        console.log('NavChart: Rendering chart with X-AXIS LABEL FIX');
        
        var chart = echarts.init(document.getElementById($container.attr('id')));

        // X-AXIS LABEL FIX - Calculate proper interval to prevent overlapping
        var totalPoints = data.dates.length;
        var targetLabels = 6; // Show maximum 6 labels for readability
        var skipInterval = Math.max(1, Math.floor(totalPoints / targetLabels));
        
        console.log('NavChart: X-axis fix - Total points:', totalPoints, 'Skip interval:', skipInterval);

        var option = {
            title: {
                text: settings.title,
                left: 'center',
                top: '20px',
                textStyle: {
                    fontSize: 14,
                    fontWeight: '600',
                    color: '#333333',
                    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
                }
            },
            tooltip: {
                trigger: 'axis',
                backgroundColor: 'rgba(255, 255, 255, 0.95)',
                borderColor: '#333333',
                borderWidth: 1,
                textStyle: {
                    color: '#333333',
                    fontSize: 12
                },
                formatter: function(params) {
                    var param = params[0];
                    return '<strong>' + param.name + '</strong><br/>' +
                           settings.yLabel + ': <span style="color: #FFD700; font-weight: bold;">' + 
                           param.value.toLocaleString() + '</span>';
                }
            },
            grid: {
                left: '80px',
                right: '50px',
                top: '80px',
                bottom: '100px', // More space for rotated labels
                borderWidth: 2,
                borderColor: '#000000',
                backgroundColor: 'rgba(168, 181, 160, 0.3)', // Light sage green inner area
                show: true
            },
            xAxis: {
                type: 'category',
                data: data.dates,
                axisLine: {
                    lineStyle: {
                        color: '#000000',
                        width: 2
                    }
                },
                axisTick: {
                    lineStyle: {
                        color: '#000000',
                        width: 1
                    },
                    length: 6
                },
                axisLabel: {
                    color: '#333333',
                    fontSize: 10,
                    fontWeight: '500',
                    rotate: 45, // Rotate labels to prevent overlap
                    margin: 15,
                    interval: skipInterval - 1, // MAIN FIX: Use calculated skip interval
                    formatter: function(value, index) {
                        // Format dates to show month/year for better readability
                        try {
                            var date = new Date(value);
                            if (isNaN(date.getTime())) {
                                return value; // Return original if date parsing fails
                            }
                            var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                                        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                            return months[date.getMonth()] + ' ' + date.getFullYear();
                        } catch (e) {
                            return value;
                        }
                    }
                },
                splitLine: {
                    show: true,
                    lineStyle: {
                        color: 'rgba(255, 255, 255, 0.4)', // Semi-transparent white grid lines
                        width: 1,
                        type: 'solid'
                    }
                }
            },
            yAxis: {
                type: 'value',
                name: settings.yLabel,
                nameLocation: 'middle',
                nameGap: 50,
                min: settings.yMin || 90000,  // Configurable minimum
                max: settings.yMax || 140000, // Configurable maximum
                nameTextStyle: {
                    color: '#333333',
                    fontSize: 12,
                    fontWeight: '600'
                },
                axisLine: {
                    lineStyle: {
                        color: '#000000',
                        width: 2
                    }
                },
                axisTick: {
                    lineStyle: {
                        color: '#000000',
                        width: 1
                    }
                },
                axisLabel: {
                    color: '#333333',
                    fontSize: 11,
                    formatter: function(value) {
                        return value.toLocaleString();
                    }
                },
                splitLine: {
                    show: true,
                    lineStyle: {
                        color: 'rgba(255, 255, 255, 0.4)', // Semi-transparent white grid lines
                        width: 1,
                        type: 'solid'
                    }
                }
            },
            series: [{
                name: settings.yLabel,
                type: 'line',
                data: data.values,
                smooth: settings.smoothing !== 'none',
                symbol: 'none',
                lineStyle: {
                    width: 2,
                    color: '#FFD700' // Bright yellow line
                },
                itemStyle: {
                    color: '#FFD700'
                },
                emphasis: {
                    lineStyle: {
                        width: 3
                    }
                },
                animation: settings.animation,
                animationDuration: 1500,
                animationEasing: 'cubicOut'
            }],
            backgroundColor: 'rgba(122, 155, 122, 0.8)', // Darker sage green outer background
            textStyle: {
                fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
            }
        };

        chart.setOption(option);
        
        // Handle window resize
        $(window).resize(function() {
            chart.resize();
        });
        
        console.log('NavChart: Chart rendered successfully with X-AXIS FIX');
    }

    function showError($container, message) {
        console.error('NavChart: Showing error:', message);
        $container.html(
            '<div style="text-align: center; padding: 50px; color: #cc0000; border: 2px solid #cc0000; background: #ffe6e6; border-radius: 5px; margin: 20px;">' +
            '<h3 style="color: #cc0000; margin-top: 0;">NavChart Error</h3>' +
            '<p style="margin-bottom: 0;">' + message + '</p>' +
            '<p style="font-size: 12px; color: #666; margin-top: 10px;">Please check the Excel file path and content in NavChart settings.</p>' +
            '</div>'
        );
    }

})(jQuery);
