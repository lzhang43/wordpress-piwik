(($) ->

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

$( 'date-range-filter' ).on 'click', (event) ->
    event.preventDefault()
    changeDateRange "custom"

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

) jQuery
