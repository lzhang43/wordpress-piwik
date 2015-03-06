(($) ->

site_url

load_elements = ->
    # Elements to be appended to eahc .inside div
    loading =   """
                <div class="loading loading_redraw">
                    <i class="fa fa-spinner fa-pulse"></i>
                </div>
                """

    no_data =   """
                <div class="no_data">
                    <div class="no_data_cross">
                        <span class="x_mark">
                        <span class="line left"></span>
                        <span class="line right"></span>
                        </span>
                    </div>
                    <h2>No Data Available</h2>
                    <p>Try another date range?</p>
                </div>
                """

    # Append loading animation and no-data div
    $( '#expana_dashboard .inside' ).prepend(loading, no_data);

    # hide everything that's not currently in use or is still loading
    $( '.date-range-inputs' ).hide();
    $( '#live' ).hide();
    $( '.no_data' ).hide();

    # handle onclick event for date range selectors
    $( '.date-range-selectors button.date-range-button' ).on 'click', ->

        # Disable all buttons
        $( ".date-range-selectors button.date-range-button" ).prop 'disabled', true

        # Remove "current" class from all buttons
        $( '.date-range-selectors button.date-range-button' ).removeClass 'current'

        # Add "current" class to the button being clicked
        $(@).addClass 'current'

        # Change #date_range information (only for display purposes)
        $( '#date_range' ).text( $(@).text() )

        # Check if the button being clicked is for "custom date range"
        if $(@).data( 'range' ) is "custom"
            # Dispaly custom date range input fields. Do nothing, waiting for query dates
            $( '.date-range-inputs' ).show
            # Also enable buttons in case the user want to go back
            $( ".date-range-selectors button.date-range-button" ).prop 'disabled', false
        else
            # Not custom date range, hide input fields and start changing date range
            $( '.date-range-inputs' ).hide
            changeDateRange $(this).data( 'range' )

load_site_info = ->
    $.ajax
        url: "admin-ajax.php"
        data: { action: "expana_ajax_site_info" }
        type: "POST"
        dataType: "json"
    .success (response) ->
        $( "#created_at" ).text response[0].ts_created
        $( ".time_zone" ).text response[0].timezone
        site_url = response[0].main_url

        # Initialize datepickers
        $( "#expana-from-date" ).datepicker
            dateFormat: 'yy-mm-dd'
            changeMonth: true
            changeYear: true
            minDate: response[0].ts_created
            maxDate: 'D'
            constrainInput: true
            onSelect: (selectedDate) ->
                $( "#expana-to-date" ).datepicker( "option", "minDate", selectedDate )

        $( "#expana-to-date" ).datepicker
            dateFormat: 'yy-mm-dd'
            changeMonth: true
            changeYear: true
            minDate: response[0].ts_created
            maxDate: 'D'
            constrainInput: true
            onSelect: (selectedDate) ->
                $( "#expana-from-date" ).datepicker( "option", "maxDate", selectedDate )

        # Add class "current"
        $.ajax
            url: "admin-ajax.php"
            data: { action: "expana_ajax_get_date" }
            type: "POST"
            dataType: "json"
        .success (response) ->
            if response is "last90" or response is "last30" or response is "last7" or response is "yesterday"
                $( ".date-range-button[data-range=" + response + "]" ).addClass "current"
            else
                $( "#expana_custom" ).addClass "current"
                $( ".date-range-inputs" ).show()
                dates = response.split ","
                $( "#expana-from-date" ).datepicker "setDate", dates[0]
                $( "#expana-to-date" ).datepicker "setDate", dates[1]

init_report = ->
    $.ajax
        url: "admin-ajax.php"
        data: { action: "expana_ajax_report" }
        type: "POST"
        dataType: "json"
    .success (response) ->
        $( "#expana_report .loading" ).hide()
        $.each response.data, (index, item) ->
            $( "#report_content" ).append "<section><img src='#{item.thumbnail}' /><span>#{item.description}</span></section>"

init_visits_summary = ->
    $.ajax
        url: "admin-ajax.php"
        data: { action: "expana_ajax_visits_summary" }
        type: "POST"
        dataType: "JSON"
    .success (response) ->
        # Define a list of series that will be included in the chart
        categories = ['nb_actions', 'nb_actions_per_visit', 'nb_uniq_visitors', 'nb_users', 'nb_visits', 'nb_visits_converted']

        $.each response, (i, day) ->
            date.push(i)
            $.each categories (j, category) ->
                if day[category]
                    eval(category).push(day[category]);
                else
                    eval(category).push(0);

        $( "#visits_summary" ).highcharts
            title:
                text: null #To disable the title, set the text to null
            chart:
                marginTop: 50
            xAxis:
                categories: date
                tickInterval: 3
            yAxis: [{ # left y axis
                    title:
                        text: null
                    labels:
                        align: 'left'
                        x: 3
                        y: 16
                        format: '{value:.,0f}'
                    showFirstLabel: false
                    },
                    { # right y axis
                        linkedTo: 0
                        gridLineWidth: 0
                        opposite: true
                        title:
                            text: null
                        labels:
                            align: 'right'
                            x: -3
                            y: 16
                            format: '{value:.,0f}'
                        showFirstLabel: false
                    }]
            legend:
                align: 'left'
                verticalAlign: 'top'
                y: 0
                floating: true
                borderWidth: 0
            tooltip:
                shared: true
                crosshairs: true
            series: [{
                        name: 'Visits'
                        data: nb_visits
                    }, {
                        name: 'Unique Visitors'
                        data: nb_uniq_visitors
                    }, {
                        name: 'Visits Converted'
                        data: nb_visits_converted
                    }, {
                        name: 'Actions'
                        data: nb_actions
                        visible: false
                    }, {
                        name: 'Actions per visit'
                        data: nb_actions_per_visit
                        visible: false
                    }]

        $( "#expana_visits_summary .loading" ).hide()

# Define Live widget initialization & set refresh interval
init_live = ->
    $.ajax
        url: "admin-ajax.php"
        data: { action: "expana_ajax_live" }
        type: "POST"
        dataType: "JSON"
    .success response ->
        $( "#live_visitor_counter" ).text(response[0].visitors)
        $( "#live_visits" ).text(response[0].visits)
        $( "#live_actions" ).text(response[0].actions)
        $( "#live_converted" ).text(response[0].visitsConverted)
        $( "#live" ).show()
        $( "#expana_live .loading" ).hide().removeClass('loading_redraw')
   # Schedule a repeat
   setTimeout(init_live, 1000 * 15); #15 seconds = 1000 ms * 10 seconds

# Define Visits By Time widget initialization
init_visits_by_time = ->
    $.ajax
        url: "admin-ajax.php"
        data: { action: "expana_ajax_visits_by_time" }
        type: "POST"
        dataType: "JSON"
    .success response ->
        if ( $.isEmptyObject response ) # Check if the response is empty
            $('#expana_visits_by_time .loading').hide() # Hide the loading animation
            $('#expana_visits_by_time .no_data').show()
            return false; # Exit

        # Define a list of series that will be included in the chart
        categories = ['label', 'nb_actions', 'nb_visits', 'sum_daily_nb_uniq_visitors'];

        $.each response (i, hour) ->
            $.each categories (i, category) ->
                if hour[category]
                    eval(category).push(hour[category])
                else
                    eval(category).push(0)

        # Draw the chart
        $('#visits_by_time').highcharts
            chart:
                type: 'bar'
                marginTop: 50
            title:
                text: null
            xAxis:
                categories: label
                title:
                    text: null
            yAxis:
                min: 0
                title:
                    text: 'Visits'
                    align: 'high'
                labels:
                    overflow: 'justify'
            plotOptions:
                bar:
                    dataLabels:
                        enabled: true
            legend:
                layout: 'horizontal'
                align: 'left'
                floating: true
                verticalAlign: 'top'
            tooltip:
                shared: true
            credits:
                enabled: false
            series: [{
                        name: 'Actions'
                        data: nb_actions
                    }, {
                        name: 'Visits'
                        data: nb_visits
                    }, {
                        name: 'Unique Visitors'
                        data: sum_daily_nb_uniq_visitors
                    }]

        $('#expana_visits_by_time .loading').hide()

custom_selector = ->
$( 'date-range-filter' ).on 'click', (event) ->
    event.preventDefault()
    changeDateRange "custom"

) jQuery
