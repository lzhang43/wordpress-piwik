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

            console.log(response);

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

});
