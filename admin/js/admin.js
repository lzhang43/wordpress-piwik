jQuery(function ($) {

    $.ajax({
        url: "http://michael.dev/wordpress/wp-admin/admin-ajax.php",
        data: { action: "expana_ajax_report" },
        type: "POST",
        dataType: "json"
    }).success(function( response ) {
        $('#loading_report').hide();
        $.each(response.data, function(index, item) {
            $("#report_content").append("<section><img src='" + item.thumbnail + "' /><span>" + item.description + "</span></section>");
        })
    });

    $.ajax({
        url: "http://michael.dev/wordpress/wp-admin/admin-ajax.php",
        data: { action: "expana_ajax_visits_summary" },
        type: "POST",
        dataType: "JSON"
    }).success(function( response ) {

            console.log( response );

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

            console.log(date);

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
    });

});
