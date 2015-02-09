jQuery(function ($) {

    $.ajax({
        url: "http://michael.dev/wordpress/wp-admin/admin-ajax.php",
        data: { action: "expana_ajax_report" },
        type: "POST",
        dataType: "json"
    }).success(function( response ) {
        if (response.meta.code !== 200) {
            alert("Error: " + response.meta.error_message);
        }
        else
        {
            $.each(response.data, function(index, item) {
                $("#report_content").append("<section><img src='" + item.thumbnail + "' /><span>" + item.description + "</span></section>");
            })
        }
    });

    $.ajax({
        url: "http://michael.dev/wordpress/wp-admin/admin-ajax.php",
        data: { action: "expana_ajax_visits_summary" },
        type: "POST",
        dataType: "JSON"
    }).success(function( response ) {

            $('#visits_summary').highcharts({

                title: {
                    text: "Visits Summary" //To disable the title, set the text to null
                },

                subtitle: {
                    text: "Expressions Analytics"
                },

                xAxis: {
                    tickInterval: 7 * 24 * 3600 * 1000, // one week
                    tickWidth: 0,
                    gridLineWidth: 1,
                    labels: {
                        align: 'left',
                        x: 3,
                        y: -3
                    }
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
                    y: 20,
                    floating: true,
                    borderWidth: 0
                },

                tooltip: {
                    shared: true,
                    crosshairs: true
                },

                series: [{
                    name: 'All visits',
                    lineWidth: 4,
                    marker: {
                        radius: 4
                    }
                }, {
                    name: 'New visitors'
                }]
            });
    });

});
