jQuery(function ($) {

    // hide everything that's not currently in use or is still loading
    $(".date-range-inputs").hide();
    $("#live").hide();

    // handle onclick event for date range selectors
    $( ".date-range-selectors button.date-range-button" ).on("click", function() {
        $(".date-range-selectors button.date-range-button").removeClass("current");
        $(this).addClass("current");

        $("#date_range").text( $(this).text() );

        if( $(this).attr("id") == "expana_custom" )
        {
            $(".date-range-inputs").show();
        }
        else
        {
            $(".date-range-inputs").hide();
        }
    });

    $("#date-range-filter").on("click", function(e) {
        e.preventDefault();
    });

    // Initialize datepickers
    $( "#expana-from-date" ).datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true,
        maxDate: 'D',
        constrainInput: true
    });

    $( "#expana-to-date" ).datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true,
        constrainInput: true
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
        $('#loading_report').hide();
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
                name: 'Actions',
                data: nb_actions
            }, {
                name: 'Actions per visit',
                data: nb_actions_per_visit
            }, {
                name: 'Unique Visitors',
                data: nb_uniq_visitors
            }, {
                name: 'Visits',
                data: nb_visits
            }, {
                name: 'Visits Converted',
                data: nb_visits_converted
            }]

        });

        $('#loading_visits_summary').hide();

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
            $("#loading_live").hide();
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

            $('#loading_visits_by_time').hide();

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

            $('#loading_resolutions').hide();
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
                        brands[os.brand] = os.sum_daily_nb_uniq_visitors;
                    } else {
                        brands[os.brand] += os.sum_daily_nb_uniq_visitors;
                    }

                    // Create the version data
                    if (os.version) {
                        if (! versions[os.brand]) {
                            versions[os.brand] = [];
                        }
                        versions[os.brand].push([os.version, os.sum_daily_nb_uniq_visitors]);
                    }
            });

            // Build bransData and drilldownSeries for HighCharts
            $.each(brands, function (name, y) {
                brandsData.push({
                    name: name,
                    y: y,
                    drilldown: versions[name] ? name : null
                });
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

            $('#loading_os').hide();
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
                        brands[browser.brand] = browser.sum_daily_nb_uniq_visitors;
                    } else {
                        brands[browser.brand] += browser.sum_daily_nb_uniq_visitors;
                    }

                    // Create the version data
                    if (browser.version) {
                        if (! versions[browser.brand]) {
                            versions[browser.brand] = [];
                        }
                        versions[browser.brand].push([browser.version, browser.sum_daily_nb_uniq_visitors]);
                    }
            });

            // Build bransData and drilldownSeries for HighCharts
            $.each(brands, function (name, y) {
                brandsData.push({
                    name: name,
                    y: y,
                    drilldown: versions[name] ? name : null
                });
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

            $('#loading_browsers').hide();
        });
    }

    // Initilize OS chart
    init_browsers();
});
