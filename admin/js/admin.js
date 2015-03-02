jQuery(function ($) {

    var loading = '<div class="loading loading_redraw">' +
                        '<i class="fa fa-cog fa-spin"></i>' +
                  '</div>';

        no_data = '<div class="no_data">' + 
                        '<div class="no_data_cross">' +
                            '<span class="x_mark">' +
                                '<span class="line left"></span>' +
                                '<span class="line right"></span>' +
                            '</span>' +
                        '</div>' +

                        '<h2>No Data Available</h2>' +
                        '<p>Try another date range?</p>' +
                    '</div>';

        site_url = '';

    // Append loading animation and no-data div
    $("#expana_dashboard .inside").prepend(loading, no_data);

    // hide everything that's not currently in use or is still loading
    $(".date-range-inputs").hide();
    $("#live").hide();
    $(".no_data").hide();

    // handle onclick event for date range selectors
    $( ".date-range-selectors button.date-range-button" ).on("click", function() {

        // Disable all buttons
        $( ".date-range-selectors button.date-range-button" ).prop("disabled", true);

        // Remove "current" class from all buttons
        $(".date-range-selectors button.date-range-button").removeClass("current");

        // Add "current" class to the button being clicked
        $(this).addClass("current");

        // Change #date_range information (only for display purposes)
        $("#date_range").text( $(this).text() );

        // Check if the button being clicked is for "custom date range"
        if( $(this).data("range") == "custom" )
        {
            // Dispaly custom date range input fields. Do nothing, waiting for query dates
            $(".date-range-inputs").show();
            // Also enable buttons in case the user want to go back
            $( ".date-range-selectors button.date-range-button" ).prop("disabled", false);
        }
        else
        {
            // Not custom date range, hide input fields and start changing date range
            $(".date-range-inputs").hide();
            changeDateRange( $(this).data("range") );
        }
    });

    $("#date-range-filter").on("click", function(e) {
        e.preventDefault();

        changeDateRange( "custom" );
    });

    // Loading website info
    function load_site_info() {
        $.ajax({
            url: "admin-ajax.php",
            data: { action: "expana_ajax_site_info" },
            type: "POST",
            dataType: "json"
        }).success(function( response ) {
            $("#created_at").text(response[0].ts_created);
            $(".time_zone").text(response[0].timezone);
            site_url = response[0].main_url;

            // Initialize datepickers
            $( "#expana-from-date" ).datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true,
                minDate: response[0].ts_created,
                maxDate: 'D',
                constrainInput: true,
                onSelect: function( selectedDate ) {
                    jQuery( "#expana-to-date" ).datepicker( "option", "minDate", selectedDate );
                }
            });

            $( "#expana-to-date" ).datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true,
                minDate: response[0].ts_created,
                maxDate: 'D',
                constrainInput: true,
                onSelect: function( selectedDate ) {
                    jQuery( "#expana-from-date" ).datepicker( "option", "maxDate", selectedDate );
                }
            });

            // Add class "current"
            $.ajax({
                url: "admin-ajax.php",
                data: { action: "expana_ajax_get_date" },
                type: "POST",
                dataType: "json"
            }).success(function( response ) {
                if (response == "last90" || response == "last30" || response == "last7" || response == "yesterday")
                {
                    $(".date-range-button[data-range=" + response + "]").addClass("current");
                }
                else
                {
                    $("#expana_custom").addClass("current");
                    $(".date-range-inputs").show();
                    var dates = response.split(",");
                    $("#expana-from-date").datepicker("setDate", dates[0]);
                    $("#expana-to-date").datepicker("setDate", dates[1]);
                }
            })
        })
    }

    load_site_info();

    // Generate report
    $.ajax({
        url: "admin-ajax.php",
        data: { action: "expana_ajax_report" },
        type: "POST",
        dataType: "json"
    }).success(function( response ) {
        $('#expana_report .loading').hide();
        $.each(response.data, function(index, item) {
            $("#report_content").append("<section><img src='" + item.thumbnail + "' /><span>" + item.description + "</span></section>");
        })
    });

    // Initialize Visits Summary widget
    $.ajax({
        url: "admin-ajax.php",
        data: { action: "expana_ajax_visits_summary" },
        type: "POST",
        dataType: "JSON"
    }).success(function( response ) {

        // Define a list of series that will be included in the chart
        categories = ['nb_actions', 'nb_actions_per_visit', 'nb_uniq_visitors', 'nb_users', 'nb_visits', 'nb_visits_converted'];

        var date = [];
        var nb_actions = [];
        var nb_actions_per_visit = [];
        var nb_uniq_visitors = [];
        var nb_users = [];
        var nb_visits = [];
        var nb_visits_converted = [];

        $.each(response, function (i, day) {
            date.push(i);

            $.each(categories, function (i, category)
            {
                if (day[category])
                {
                    eval(category).push(day[category]);
                }
                else
                {
                    eval(category).push(0);
                }
            });
        });

        $('#visits_summary').highcharts({

            title: {
                text: null //To disable the title, set the text to null
            },

            chart: {
                marginTop: 50
            },

            xAxis: {
                categories: date,
                tickInterval: 3
            },

            yAxis: [{ // left y axis
                title: {
                    text: null
                },
                labels: {
                    align: 'left',
                    x: 3,
                    y: 16,
                    format: '{value:.,0f}'
                },
                showFirstLabel: false
            }, { // right y axis
                linkedTo: 0,
                gridLineWidth: 0,
                opposite: true,
                title: {
                    text: null
                },
                labels: {
                    align: 'right',
                    x: -3,
                    y: 16,
                    format: '{value:.,0f}'
                },
                showFirstLabel: false
            }],

            legend: {
                align: 'left',
                verticalAlign: 'top',
                y: 0,
                floating: true,
                borderWidth: 0
            },

            tooltip: {
                shared: true,
                crosshairs: true
            },

            series: [{
                name: 'Visits',
                data: nb_visits
            }, {
                name: 'Unique Visitors',
                data: nb_uniq_visitors
            }, {
                name: 'Visits Converted',
                data: nb_visits_converted
            }, {
                name: 'Actions',
                data: nb_actions,
                visible: false
            }, {
                name: 'Actions per visit',
                data: nb_actions_per_visit,
                visible: false
            }]

        });

        $('#expana_visits_summary .loading').hide();

    });

    // Define Live widget initialization & set refresh interval
    function init_live() {
        $.ajax({
            url: "admin-ajax.php",
            data: { action: "expana_ajax_live" },
            type: "POST",
            dataType: "JSON"
        }).success(function( response ) {
            $("#live_visitor_counter").text(response[0].visitors);
            $("#live_visits").text(response[0].visits);
            $("#live_actions").text(response[0].actions);
            $("#live_converted").text(response[0].visitsConverted);
            $("#live").show();
            $("#expana_live .loading").hide().removeClass('loading_redraw');
        });

       // Schedule a repeat
       setTimeout(init_live, 1000 * 15); //15 seconds = 1000 ms * 10 seconds
    }

    // Initialize Live widget
    init_live();


    // Define Visits By Time widget initialization
    function init_visits_by_time() {
        $.ajax({
            url: "admin-ajax.php",
            data: { action: "expana_ajax_visits_by_time" },
            type: "POST",
            dataType: "JSON"
        }).success(function( response ) {

            // Check if the response is empty
            if ( $.isEmptyObject(response) )
            {
                // Hide the loading animation
                $('#expana_visits_by_time .loading').hide();
                
                $('#expana_visits_by_time .no_data').show();

                // Exit
                return false;
            }

            // Define a list of series that will be included in the chart
            categories = ['label', 'nb_actions', 'nb_visits', 'sum_daily_nb_uniq_visitors'];

            var label = [];
            var nb_actions = [];
            var nb_visits = [];
            var sum_daily_nb_uniq_visitors = [];

            $.each(response, function (i, hour) {
                $.each(categories, function (i, category)
                {
                    if (hour[category])
                    {
                        eval(category).push(hour[category]);
                    }
                    else
                    {
                        eval(category).push(0);
                    }
                });
            });

            // Draw the chart
            $('#visits_by_time').highcharts({
                chart: {
                    type: 'bar',
                    marginTop: 50
                },
                title: {
                    text: null
                },
                xAxis: {
                    categories: label,
                    title: {
                        text: null
                    }
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: 'Visits',
                        align: 'high'
                    },
                    labels: {
                        overflow: 'justify'
                    }
                },
                plotOptions: {
                    bar: {
                        dataLabels: {
                            enabled: true
                        }
                    }
                },
                legend: {
                    layout: 'horizontal',
                    align: 'left',
                    floating: true,
                    verticalAlign: 'top'
                },
                tooltip: {
                    shared: true
                },
                credits: {
                    enabled: false
                },
                series: [{
                    name: 'Actions',
                    data: nb_actions
                }, {
                    name: 'Visits',
                    data: nb_visits
                }, {
                    name: 'Unique Visitors',
                    data: sum_daily_nb_uniq_visitors
                }]
            });

            $('#expana_visits_by_time .loading').hide();

        }); // success
    }

    // Initilize Visits By Time chart
    init_visits_by_time();


    // Define Resolutions widget initialization
    function init_resolutions() {
        $.ajax({
            url: "admin-ajax.php",
            data: { action: "expana_ajax_resolutions" },
            type: "POST",
            dataType: "JSON"
        }).success(function( response ) {

            // Check if the response is empty
            if ( $.isEmptyObject(response) )
            {
                // Hide the loading animation
                $('#expana_resolutions .loading').hide();
                
                $('#expana_resolutions .no_data').show();

                // Exit
                return false;
            }

            data = [];

            $.each(response, function (i, resolution) {

                if (i > 15)
                {
                    return false; //only output 15 most popular resolutions
                }

                entry = {};
                entry.name = resolution.label;

                if (! resolution.nb_uniq_visitors)
                {
                    entry.y = resolution.sum_daily_nb_uniq_visitors;
                }
                else
                {
                    entry.y = resolution.nb_uniq_visitors;
                }

                if ( i == 0 )
                {
                    entry.sliced = true;
                    entry.selected = true;
                }

                data.push(entry);
            });

            $('#resolutions').highcharts({
                chart: {
                    plotBackgroundColor: null,
                    plotBorderWidth: null,
                    plotShadow: false
                },
                title: {
                    text: null
                },
                tooltip: {
                    pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
                },
                plotOptions: {
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: false
                        },
                        showInLegend: true
                    }
                },
                series: [{
                    type: 'pie',
                    name: 'Resolutions',
                    data: data
                }]
            });

            $('#expana_resolutions .loading').hide();
        });
    }

    // Initilize Resolutions chart
    init_resolutions();

    // Define OS widget initialization
    function init_os() {
        $.ajax({
            url: "admin-ajax.php",
            data: { action: "expana_ajax_os" },
            type: "POST",
            dataType: "JSON"
        }).success(function( response ) {

            // Check if the response is empty
            if ( $.isEmptyObject(response) )
            {
                // Hide the loading animation
                $('#expana_os .loading').hide();
                
                $('#expana_os .no_data').show();

                // Exit
                return false;
            }

            var brands = {},
                brandsData = [],
                versions = {},
                drilldownSeries = [];
                knownBrands = [ 'Windows Phone', 'Windows', 'iOS', 'Mac', 'Android', 'Ubuntu', 'BlackBerry OS', 'Symbian OS', 'Chrome OS', 'PlayStation' ];

            // forEach OS entires returned by Piwik API
            $.each(response, function (i, os) {

                    // Remove special edition notes
                    os.name = os.label.split(' -')[0];

                    // Split into brand and version. First check if os.name matches knownBrands
                    $.each(knownBrands, function (j, knownBrand) {

                        // indexOf() method returns the first index at which a given element can be found in the array, or -1
                        isKnown = os.name.indexOf(knownBrand);

                        // Check if os.name matches any of the known brands
                        if (isKnown > -1)
                        {
                            // If matches, store the brand name
                            os.brand = knownBrand;

                            // Remove brand name from os.name, so the rest of the string is its version info
                            os.version = os.name.replace(os.brand, '').trim();

                            // Sometimes, there's nothing left. e.g. Ubuntu
                            if(os.version == '')
                            {
                                os.version = 'Unknown Version';
                            }

                            // break the loop
                            return false;
                        }

                    });

                    // No matches in known brand, we have to use regular exp.
                    if (! os.brand)
                    {
                        // Assume os.version matches /([0-9]+[\.0-9x]*)/
                        version = os.name.match(/([0-9]+[\.0-9x]*)/);

                        // Obtain the first element in the array returned by os.name.match
                        if (version) {
                            os.version = version[0].trim();
                        }

                        os.brand = os.name.replace(os.version, '').trim();
                    }

                    // Create the main data
                    if (! brands[os.brand]) {
                        if(os.sum_daily_nb_uniq_visitors > 0) {
                            brands[os.brand] = os.sum_daily_nb_uniq_visitors;
                        } else {
                            brands[os.brand] = os.nb_uniq_visitors;
                        }
                    } else {
                        if(os.sum_daily_nb_uniq_visitors > 0) {
                            brands[os.brand] += os.sum_daily_nb_uniq_visitors;
                        } else {
                            brands[os.brand] += os.nb_uniq_visitors;
                        }
                    }

                    // Create the version data
                    if (os.version) {
                        if (! versions[os.brand]) {
                            versions[os.brand] = [];
                        }
                        versions[os.brand].push([os.version, os.sum_daily_nb_uniq_visitors]);
                    }
            });

            var i = 0;

            // Build bransData and drilldownSeries for HighCharts
            $.each(brands, function (name, y) {
                brandsData.push({
                    name: name,
                    y: y,
                    drilldown: versions[name] ? name : null
                });

                ++i;

                // Only output the first 10 brands to avoid data overlap
                if(i >=10)
                {
                    return false;
                }
            });

            $.each(versions, function (key, value) {
                drilldownSeries.push({
                    name: key,
                    id: key,
                    data: value
                });
            });

            $('#os').highcharts({
                chart: {
                    type: 'column',
                    marginTop: 40
                },
                title: {
                    text: null
                },
                xAxis: {
                    type: 'category'
                },
                yAxis: {
                    title: {
                        text: 'Unique Visitors'
                    }
                },
                legend: {
                    enabled: false
                },
                plotOptions: {
                    series: {
                        borderWidth: 0,
                        dataLabels: {
                            enabled: true,
                            format: '{point.y:.0f}'
                        }
                    }
                },

                tooltip: {
                    headerFormat: '<span style="font-size:11px">{series.name}</span><br>',
                    pointFormat: '<span style="color:{point.color}">{point.name}</span>: <b>{point.y:.0f}</b><br/>'
                },

                series: [{
                    name: 'Brands',
                    colorByPoint: true,
                    data: brandsData
                }],
                drilldown: {
                    series: drilldownSeries
                }
            });

            $('#expana_os .loading').hide();
        });
    }

    // Initilize OS chart
    init_os();


    // Define Browsers widget initialization
    function init_browsers() {
        $.ajax({
            url: "admin-ajax.php",
            data: { action: "expana_ajax_browsers" },
            type: "POST",
            dataType: "JSON"
        }).success(function( response ) {

            // Check if the response is empty
            if ( $.isEmptyObject(response) )
            {
                // Hide the loading animation
                $('#expana_browsers .loading').hide();
                
                $('#expana_browsers .no_data').show();

                // Exit
                return false;
            }

            var brands = {},
                brandsData = [],
                versions = {},
                drilldownSeries = [];
                knownBrands = [ 'Chrome', 'Firefox', 'Opera', 'Safari' ];

            // forEach browser entires returned by Piwik API
            $.each(response, function (i, browser) {

                    // Remove special edition notes
                    browser.name = browser.label.split(' -')[0];

                    // Split into brand and version. First check if browser.name matches knownBrands
                    $.each(knownBrands, function (j, knownBrand) {

                        // indexOf() method returns the first index at which a given element can be found in the array, or -1
                        isKnown = browser.name.indexOf(knownBrand);

                        // Check if browser.name matches any of the known brands
                        if (isKnown > -1)
                        {
                            // If matches, store the brand name
                            browser.brand = knownBrand;

                            // Remove brand name from browser.name, so the rest of the string is its version info
                            browser.version = browser.name.replace(browser.brand, '').trim();

                            // Sometimes, there's nothing left. e.g. Chrome
                            if(browser.version == '')
                            {
                                browser.version = 'Unknown Version';
                            }

                            // break the loop
                            return false;
                        }

                    });

                    // No matches in known brand, we have to use regular exp.
                    if (! browser.brand)
                    {
                        // Assume browser.version matches /([0-9]+[\.0-9x]*)/
                        version = browser.name.match(/([0-9]+[\.0-9x]*)/);

                        // Obtain the first element in the array returned by browser.name.match
                        if (version) {
                            browser.version = version[0].trim();
                        }

                        browser.brand = browser.name.replace(browser.version, '').trim();
                    }

                    // Create the main data
                    if (! brands[browser.brand]) {
                        if(browser.sum_daily_nb_uniq_visitors > 0) {
                            brands[browser.brand] = browser.sum_daily_nb_uniq_visitors;
                        } else {
                            brands[browser.brand] = browser.nb_uniq_visitors;
                        }
                    } else {
                        if(browser.sum_daily_nb_uniq_visitors > 0) {
                            brands[browser.brand] += browser.sum_daily_nb_uniq_visitors;
                        } else {
                            brands[browser.brand] += browser.nb_uniq_visitors;
                        }
                    }

                    // Create the version data
                    if (browser.version) {
                        if (! versions[browser.brand]) {
                            versions[browser.brand] = [];
                        }
                        versions[browser.brand].push([browser.version, browser.sum_daily_nb_uniq_visitors]);
                    }
            });

            var i = 0;

            // Build bransData and drilldownSeries for HighCharts
            $.each(brands, function (name, y) {
                brandsData.push({
                    name: name,
                    y: y,
                    drilldown: versions[name] ? name : null
                });


                ++i;

                // Only output the first 10 brands to avoid data overlap
                if(i >= 10)
                {
                    return false;
                }
            });

            $.each(versions, function (key, value) {
                drilldownSeries.push({
                    name: key,
                    id: key,
                    data: value
                });
            });

            $('#browsers').highcharts({
                chart: {
                    type: 'column',
                    marginTop: 40
                },
                title: {
                    text: null
                },
                xAxis: {
                    type: 'category'
                },
                yAxis: {
                    title: {
                        text: 'Unique Visitors'
                    }
                },
                legend: {
                    enabled: false
                },
                plotOptions: {
                    series: {
                        borderWidth: 0,
                        dataLabels: {
                            enabled: true,
                            format: '{point.y:.0f}'
                        }
                    }
                },

                tooltip: {
                    headerFormat: '<span style="font-size:11px">{series.name}</span><br>',
                    pointFormat: '<span style="color:{point.color}">{point.name}</span>: <b>{point.y:.0f}</b><br/>'
                },

                series: [{
                    name: 'Brands',
                    colorByPoint: true,
                    data: brandsData
                }],
                drilldown: {
                    series: drilldownSeries
                }
            });

            $('#expana_browsers .loading').hide();
        });
    }

    // Initilize OS chart
    init_browsers();

    function init_visits_map_us() {

        $.ajax({
            url: "admin-ajax.php",
            data: { action: "expana_ajax_maps_us" },
            type: "POST",
            dataType: "JSON"
        }).success(function( response ) {

            // Check if the response is empty
            if ( $.isEmptyObject(response) )
            {
                // Hide the loading animation
                $('#expana_map_us .loading').hide();
                
                $('#expana_map_us .no_data').show();

                // Exit
                return false;
            }

            var statesData = [];

            $.each(response, function(i, item) {

                name = item.label.split(',')[0].trim();

                if (name !== 'Unknown')
                {
                    statesData.push({
                        name: name,
                        value: item.sum_daily_nb_uniq_visitors
                    });
                }

            });

           var mapData = Highcharts.geojson(Highcharts.maps['countries/us/us-all']);

            // Initiate the chart
            $('#map_us').highcharts('Map', {

                title : {
                    text : null
                },

                legend: {
                    align: 'right',
                    floating: true,
                    title: {
                        text: 'Unique Visitors'
                    }
                },

                mapNavigation: {
                    enabled: true,
                    buttonOptions: {
                        align: 'left',
                        floating: true,
                        verticalAlign: 'top'
                    },
                    enableMouseWheelZoom: false
                },

                colorAxis: {
                },

                series : [{
                    data : statesData,
                    mapData: mapData,
                    joinBy: ['name', 'name'],
                    name: 'Unique Visitors',
                    states: {
                        hover: {
                            color: '#A9FF96'
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        format: '{point.properties.postal-code}',
                        color: '#ffffff'
                    }
                }]
            });

            $('#expana_map_us .loading').hide();

        });
        
    }

    init_visits_map_us();

    function init_visits_map_world() {

        $.ajax({
            url: "admin-ajax.php",
            data: { action: "expana_ajax_maps_world" },
            type: "POST",
            dataType: "JSON"
        }).success(function( response ) {

            // Check if the response is empty
            if ( $.isEmptyObject(response) )
            {
                // Hide the loading animation
                $('#expana_map_world .loading').hide();
                
                $('#expana_map_world .no_data').show();

                // Exit
                return false;
            }

            var countriesData = [];

            $.each(response, function(i, item) {

                name = item.label;

                // Check if it's the United States
                if(name == "United States")
                {
                    // GeoJson uses the name "United States of America"
                    name = "United States of America";
                }

                countriesData.push({
                    name: name,
                    value: item.sum_daily_nb_uniq_visitors
                });

            });

            var mapData = Highcharts.maps['custom/world'];

            // Initiate the chart
            $('#map_world').highcharts('Map', {

                title : {
                    text : null
                },

                legend: {
                    align: 'right',
                    floating: true,
                    title: {
                        text: 'Unique Visitors'
                    }
                },

                mapNavigation: {
                    enabled: true,
                    buttonOptions: {
                        align: 'left',
                        floating: true,
                        verticalAlign: 'top'
                    },
                    enableMouseWheelZoom: false
                },

                colorAxis: {
                },

                series : [{
                    data : countriesData,
                    mapData: mapData,
                    joinBy: ['name', 'name'],
                    name: 'Unique Visitors',
                    states: {
                        hover: {
                            color: '#A9FF96'
                        }
                    }
                }]
            });

            $('#expana_map_world .loading').hide();

        });
    }

    init_visits_map_world();

    function init_device_type() {

        $.ajax({
            url: "admin-ajax.php",
            data: { action: "expana_ajax_device_type" },
            type: "POST",
            dataType: "JSON"
        }).success(function( response ) {

            // Check if the response is empty
            if ( $.isEmptyObject(response) )
            {
                // Hide the loading animation
                $('#expana_device_type .loading').hide();
                
                $('#expana_device_type .no_data').show();

                // Exit
                return false;
            }

            data = [];

            $.each(response, function (i, type) {

                entry = {};
                entry.name = type.label;
                entry.y = type.nb_visits;

                if ( i == 0 )
                {
                    entry.sliced = true;
                    entry.selected = true;
                }

                data.push(entry);
            });

            $('#device_type').highcharts({
                chart: {
                    plotBackgroundColor: null,
                    plotBorderWidth: null,
                    plotShadow: false
                },
                title: {
                    text: null
                },
                tooltip: {
                    pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
                },
                plotOptions: {
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: true,
                            format: '<b>{point.name}</b>: {point.percentage:.1f} %',
                            style: {
                                color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                            }
                        }
                    }
                },
                series: [{
                    type: 'pie',
                    name: 'Device type',
                    data: data
                }]
            });

            $('#expana_device_type .loading').hide();
        });
    }

    init_device_type();

    function init_top_pages() {

        // Hide the table
        $('#popular_pages').hide();

        // Empty exsiting entries
        $('#popular_pages table tbody').empty();

        $.ajax({
            url: "admin-ajax.php",
            data: { action: "expana_ajax_top_pages" },
            type: "POST",
            dataType: "JSON"
        }).success(function( response ) {

            $.each(response, function (i, entry) {

                console.log(entry);

                $('#popular_pages table > tbody:last').append('<tr><td>' + entry.label + ' <a href=' + site_url + '/' + entry.label + ' target="_blank"><i class="fa fa-external-link"></i></a></td><td>' + entry.nb_hits + '</td><td>' + entry.nb_visits + '</td><td>' + entry.avg_time_on_page + 's</td></tr>');

                if ( i > 20 )
                {
                    return false;
                }

            });

            // Check if the response is empty
            if ( $.isEmptyObject(response) )
            {
                // Hide the loading animation
                $('#expana_top_pages .loading').hide();
                
                $('#expana_top_pages .no_data').show();

                // Exit
                return false;
            }

            $('#expana_top_pages .loading').hide();
            $('#popular_pages').show();

        });
    }

    init_top_pages();

    function changeDateRange( range ) {

        var dates = null;

        if (range == "custom")
        {
            dates = validateDateRange();
        }

        if(dates === false)
        {
            return false;
        }

        // Hide no_data div (if any)
        $( ".no_data" ).hide();

        // Display loading animation
        $( ".loading_redraw" ).show();

        // Destory Charts that will be redrawed
        $('#visits_by_time').empty();
        $('#os').empty();
        $('#resolutions').empty();
        $('#browsers').empty();
        $('#map_us').empty();
        $('#map_world').empty();
        $('#device_type').empty();

        // AJAX POST request to set new date range
        $.ajax({
            url: "admin-ajax.php",
            data: { action: "expana_change_date_range",
                    range: range,
                    dates: dates },
            type: "POST",
            dataType: "JSON"
        }).success(function( response ) {

            // Redraw these charts
            init_visits_by_time();
            init_os();
            init_resolutions();
            init_browsers();
            init_visits_map_us();
            init_visits_map_world();
            init_device_type();
            init_top_pages();

            // Enable buttons (loading animation will be hide by init_charts function upon completion)
            $( ".date-range-selectors button.date-range-button" ).prop("disabled", false);
        });
    }

    function validateDateRange() {

        var from_date = $("#expana-from-date").datepicker("getDate");
            to_date = $("#expana-to-date").datepicker("getDate");

        if( ! from_date || ! to_date )
        {
            alert("Invalid dates");
            return false;
        }

        return $.datepicker.formatDate('yy-mm-dd', from_date) + "," + $.datepicker.formatDate('yy-mm-dd', to_date);
    }

});
