(($) ->

   #global variables

    loading = '<div class="loading loading_redraw">' +
                        '<i class="fa fa-spinner fa-pulse"></i>' +
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

    # Append loading animation and no-data div
    $('#expana_dashboard .inside').prepend(loading, no_data);

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
  
) jQuery
