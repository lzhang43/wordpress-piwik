(($) ->
    site_url = ''

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
        $( '#expana_dashboard .inside' ).prepend(loading, no_data)

        # hide everything that's not currently in use or is still loading
        $( '.date-range-inputs' ).hide()
        $( '#live' ).hide()
        $( '.no_data' ).hide()

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

            date = []
            nb_actions = []
            nb_actions_per_visit = []
            nb_uniq_visitors = []
            nb_users = []
            nb_visits = []
            nb_visits_converted = []

            $.each response, (i, day) ->
                date.push(i)
                $.each categories, (j, category) ->
                    if day[category]
                        eval(category).push(day[category])
                    else
                        eval(category).push(0)

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
        .success (response) ->
            $( "#live_visitor_counter" ).text(response[0].visitors)
            $( "#live_visits" ).text(response[0].visits)
            $( "#live_actions" ).text(response[0].actions)
            $( "#live_converted" ).text(response[0].visitsConverted)
            $( "#live" ).show()
            $( "#expana_live .loading" ).hide().removeClass('loading_redraw')

        # Schedule a repeat
        setTimeout(init_live, 1000 * 15) #15 seconds = 1000 ms * 10 seconds

    # Define Visits By Time widget initialization
    init_visits_by_time = ->
        $.ajax
            url: "admin-ajax.php"
            data: { action: "expana_ajax_visits_by_time" }
            type: "POST"
            dataType: "JSON"
        .success (response) ->
            if ( $.isEmptyObject response ) # Check if the response is empty
                $('#expana_visits_by_time .loading').hide() # Hide the loading animation
                $('#expana_visits_by_time .no_data').show()
                return false # Exit

            # Define a list of series that will be included in the chart
            categories = ['label', 'nb_actions', 'nb_visits', 'sum_daily_nb_uniq_visitors']

            label = []
            nb_actions = []
            nb_visits = []
            sum_daily_nb_uniq_visitors = []

            $.each response, (i, hour) ->
                $.each categories, (i, category) ->
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

    # Define Resolutions widget initialization
    init_resolutions = ->
        $.ajax
            url: "admin-ajax.php"
            data: { action: "expana_ajax_resolutions" }
            type: "POST"
            dataType: "JSON"
        .success (response) ->
            if $.isEmptyObject response # Check if the response is empty
                $('#expana_resolutions .loading').hide()
                $('#expana_resolutions .no_data').show()
                return false

            data = []
            $.each response, (i, resolution) ->
                if i > 15
                    return false # only output 15 most popular resolutions

                entry = {}
                entry.name = resolution.label

                if not resolution.nb_uniq_visitors
                    entry.y = resolution.sum_daily_nb_uniq_visitors
                else
                    entry.y = resolution.nb_uniq_visitors

                if i is 0 
                    entry.sliced = true
                    entry.selected = true

                data.push(entry)

            $( "#resolutions" ).highcharts
                chart:
                    plotBackgroundColor: null
                    plotBorderWidth: null
                    plotShadow: false
                title:
                    text: null
                tooltip:
                    pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
                plotOptions:
                    pie:
                        allowPointSelect: true
                        cursor: 'pointer'
                        dataLabels:
                            enabled: false
                        showInLegend: true
                series: [{
                    type: 'pie'
                    name: 'Resolutions'
                    data: data
                }]

            $( "#expana_resolutions .loading" ).hide()

    # Define OS widget initialization
    init_os = ->
        $.ajax
            url: "admin-ajax.php"
            data: { action: "expana_ajax_os" }
            type: "POST"
            dataType: "JSON"
        .success (response) ->
            if $.isEmptyObject response
                $('#expana_os .loading').hide()
                $('#expana_os .no_data').show()
                return false

            brands = {}
            brandsData = []
            versions = {}
            drilldownSeries = []
            knownBrands = [ 'Windows Phone', 'Windows', 'iOS', 'Mac', 'Android', 'Ubuntu', 'BlackBerry OS', 'Symbian OS', 'Chrome OS', 'PlayStation' ]

            $.each response, (i, os) -> # forEach OS entires returned by Piwik API
                    os.name = os.label.split(' -')[0] # Remove special edition notes
                    $.each knownBrands, (j, knownBrand) -> # Split into brand and version. First check if os.name matches knownBrands
                        isKnown = os.name.indexOf knownBrand # indexOf() method returns the first index at which a given element can be found in the array, or -1
                        if isKnown > -1 # Check if os.name matches any of the known brands
                            os.brand = knownBrand # If matches, store the brand name
                            os.version = os.name.replace(os.brand, '').trim() # Remove brand name from os.name, so the rest of the string is its version info
                            if not os.version # Sometimes, there's nothing left. e.g. Ubuntu
                                os.version = 'Unknown Version'
                            return false # break the loop

                    if not os.brand # No matches in known brand, we have to use regular exp.
                        version = os.name.match(/([0-9]+[\.0-9x]*)/) # Assume os.version matches /([0-9]+[\.0-9x]*)/
                        if version
                            os.version = version[0].trim() # Obtain the first element in the array returned by os.name.match
                        os.brand = os.name.replace(os.version, '').trim()

                    if not brands[os.brand] # Create the main data
                        if os.sum_daily_nb_uniq_visitors > 0
                            brands[os.brand] = os.sum_daily_nb_uniq_visitors
                        else
                            brands[os.brand] = os.nb_uniq_visitors
                    else
                        if os.sum_daily_nb_uniq_visitors > 0
                            brands[os.brand] += os.sum_daily_nb_uniq_visitors
                        else
                            brands[os.brand] += os.nb_uniq_visitors

                    if os.version # Create the version data
                        if not versions[os.brand]
                            versions[os.brand] = []
                        versions[os.brand].push [os.version, os.sum_daily_nb_uniq_visitors]

            i = 0
            $.each brands, (name, y) -> # Build bransData and drilldownSeries for HighCharts
                brandsData.push
                    name: name
                    y: y
                    drilldown: if versions[name] then name else null
                ++i
                if i >= 10 # Only output the first 10 brands to avoid data overlap
                    return false

            $.each versions, (key, value) ->
                drilldownSeries.push
                    name: key
                    id: key
                    data: value

            $( "#os" ).highcharts
                chart:
                    type: 'column'
                    marginTop: 40
                title:
                    text: null
                xAxis:
                    type: 'category'
                yAxis:
                    title:
                        text: 'Unique Visitors'
                legend:
                    enabled: false
                plotOptions:
                    series:
                        borderWidth: 0
                        dataLabels:
                            enabled: true
                            format: '{point.y:.0f}'
                tooltip:
                    headerFormat: '<span style="font-size:11px">{series.name}</span><br>',
                    pointFormat: '<span style="color:{point.color}">{point.name}</span>: <b>{point.y:.0f}</b><br/>'
                series:[
                    name: 'Brands'
                    colorByPoint: true
                    data: brandsData
                ]
                drilldown:
                    series: drilldownSeries

            $('#expana_os .loading').hide()

    # Define Browsers widget initialization
    init_browsers = ->
        $.ajax
            url: "admin-ajax.php",
            data: { action: "expana_ajax_browsers" },
            type: "POST",
            dataType: "JSON"
        .success (response) ->
            if $.isEmptyObject response
                $( "#expana_browsers .loading" ).hide()
                $( "#expana_browsers .no_data" ).show()
                return false

            brands = {}
            brandsData = []
            versions = {}
            drilldownSeries = []
            knownBrands = [ 'Chrome', 'Firefox', 'Opera', 'Safari' ]

            $.each response, (i, browser) -> # forEach browser entires returned by Piwik API
                    browser.name = browser.label.split(' -')[0] # Remove special edition notes
                    $.each knownBrands, (j, knownBrand) -> # Split into brand and version. First check if browser.name matches knownBrands
                        isKnown = browser.name.indexOf knownBrand # indexOf() method returns the first index at which a given element can be found in the array, or -1
                        if isKnown > -1 # Check if browser.name matches any of the known brands
                            browser.brand = knownBrand # If matches, store the brand name
                            browser.version = browser.name.replace(browser.brand, '').trim() # Remove brand name from browser.name, so the rest of the string is its version info
                            if not browser.version # Sometimes, there's nothing left. e.g. Chrome
                                browser.version = 'Unknown Version'
                            return false # break the loop
                    
                    if not browser.brand # No matches in known brand, we have to use regular exp.
                        version = browser.name.match(/([0-9]+[\.0-9x]*)/) # Assume browser.version matches /([0-9]+[\.0-9x]*)/
                        if version
                            browser.version = version[0].trim() # Obtain the first element in the array returned by browser.name.match
                        browser.brand = browser.name.replace(browser.version, '').trim()

                    if not brands[browser.brand] # Create the main data
                        if browser.sum_daily_nb_uniq_visitors > 0
                            brands[browser.brand] = browser.sum_daily_nb_uniq_visitors
                        else
                            brands[browser.brand] = browser.nb_uniq_visitors
                    else
                        if browser.sum_daily_nb_uniq_visitors > 0
                            brands[browser.brand] += browser.sum_daily_nb_uniq_visitors
                        else
                            brands[browser.brand] += browser.nb_uniq_visitors

                    if browser.version # Create the version data
                        if not versions[browser.brand]
                            versions[browser.brand] = []
                        versions[browser.brand].push([browser.version, browser.sum_daily_nb_uniq_visitors])

            i = 0

            $.each brands, (name, y) -> # Build bransData and drilldownSeries for HighCharts
                brandsData.push
                    name: name
                    y: y
                    drilldown: if versions[name] then name else null
                ++i
                if i >= 10 # Only output the first 10 brands to avoid data overlap
                    return false

            $.each versions, (key, value) ->
                drilldownSeries.push
                    name: key
                    id: key
                    data: value

            $( "#browsers" ).highcharts
                chart:
                    type: 'column'
                    marginTop: 40
                title:
                    text: null
                xAxis:
                    type: 'category'
                yAxis:
                    title:
                        text: 'Unique Visitors'
                legend:
                    enabled: false
                plotOptions:
                    series:
                        borderWidth: 0
                        dataLabels:
                            enabled: true
                            format: '{point.y:.0f}'
                tooltip:
                    headerFormat: '<span style="font-size:11px">{series.name}</span><br>',
                    pointFormat: '<span style="color:{point.color}">{point.name}</span>: <b>{point.y:.0f}</b><br/>'
                series: [{
                    name: 'Brands'
                    colorByPoint: true
                    data: brandsData
                }]
                drilldown:
                    series: drilldownSeries

            $('#expana_browsers .loading').hide()

    init_visits_map_us = ->
        $.ajax
            url: "admin-ajax.php"
            data: { action: "expana_ajax_maps_us" }
            type: "POST"
            dataType: "JSON"
        .success (response) ->
            if $.isEmptyObject response
                $('#expana_map_us .loading').hide()
                $('#expana_map_us .no_data').show()
                return false

            statesData = []

            $.each response, (i, item) ->
                name = item.label.split(',')[0].trim()
                if name isnt 'Unknown'
                    statesData.push
                        name: name
                        value: item.sum_daily_nb_uniq_visitors

            mapData = Highcharts.geojson(Highcharts.maps['countries/us/us-all'])
            $( "#map_us" ).highcharts 'Map', # Initiate the chart
                title:
                    text : null
                legend:
                    align: 'right'
                    floating: true
                    title:
                        text: 'Unique Visitors'
                mapNavigation:
                    enabled: true
                    buttonOptions:
                        align: 'left'
                        floating: true
                        verticalAlign: 'top'
                    enableMouseWheelZoom: false
                colorAxis: {}
                series: [
                    data: statesData
                    mapData: mapData
                    joinBy: ['name', 'name']
                    name: 'Unique Visitors'
                    states:
                        hover:
                            color: '#A9FF96'
                    dataLabels:
                        enabled: true
                        format: '{point.properties.postal-code}'
                        color: '#ffffff'
                ]

            $('#expana_map_us .loading').hide()

    init_visits_map_world = ->
            $.ajax
                url: "admin-ajax.php"
                data: { action: "expana_ajax_maps_world" }
                type: "POST"
                dataType: "JSON"
            .success (response) ->
                if $.isEmptyObject response
                    $('#expana_map_world .loading').hide()
                    $('#expana_map_world .no_data').show()
                    return false

                countriesData = []

                $.each response, (i, item) ->
                    name = item.label
                    if name is "United States"
                        name = "United States of America" # GeoJson uses the name "United States of America"
                    countriesData.push
                        name: name
                        value: item.sum_daily_nb_uniq_visitors

                mapData = Highcharts.maps['custom/world']

                $( "#map_world" ).highcharts 'Map', 
                    title:
                        text: null
                    legend:
                        align: 'right'
                        floating: true
                        title:
                            text: 'Unique Visitors'
                    mapNavigation:
                        enabled: true
                        buttonOptions:
                            align: 'left'
                            floating: true
                            verticalAlign: 'top'
                        enableMouseWheelZoom: false
                    colorAxis: {}
                    series : [
                        data : countriesData
                        mapData: mapData
                        joinBy: ['name', 'name']
                        name: 'Unique Visitors'
                        states:
                            hover:
                                color: '#A9FF96'
                        ]

                $('#expana_map_world .loading').hide()

    init_device_type = ->
        $.ajax
            url: "admin-ajax.php"
            data: { action: "expana_ajax_device_type" }
            type: "POST"
            dataType: "JSON"
        .success (response) ->
            if $.isEmptyObject response 
                $('#expana_device_type .loading').hide()
                $('#expana_device_type .no_data').show()
                return false

            data = []
            $.each response, (i, type) ->
                entry = {}
                entry.name = type.label
                entry.y = type.nb_visits
                if i is 0
                    entry.sliced = true
                    entry.selected = true
                data.push(entry)

            $( "#device_type" ).highcharts
                chart:
                    plotBackgroundColor: null
                    plotBorderWidth: null
                    plotShadow: false
                title:
                    text: null
                tooltip:
                    pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
                plotOptions:
                    pie:
                        allowPointSelect: true
                        cursor: 'pointer'
                        dataLabels:
                            enabled: true
                            format: '<b>{point.name}</b>: {point.percentage:.1f} %'
                            style:
                                color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                series: [
                    type: 'pie'
                    name: 'Device type'
                    data: data
                ]

            $('#expana_device_type .loading').hide()

    init_top_pages = ->
        $( "popular_pages" ).hide() # Hide the table
        $( "popular_pages table tbody" ).empty() # Empty exsiting entries

        $.ajax
            url: "admin-ajax.php"
            data: { action: "expana_ajax_top_pages" }
            type: "POST"
            dataType: "JSON"
        .success (response) ->
            if $.isEmptyObject response # Check if the response is empty
                $( "#expana_top_pages .loading" ).hide()
                $( "#expana_top_pages .no_data" ).show()
                return false

            $.each response, (i, entry) ->
                $( "#popular_pages table > tbody:last" ).append "<tr><td>#{entry.label}<a href=#{site_url}/#{entry.label} target='_blank'> <i class='fa fa-external-link'></i></a></td><td>#{entry.nb_hits}</td><td>#{entry.nb_visits}</td><td>#{entry.avg_time_on_page}s</td></tr>"
                if i > 20
                    return false

            $( "#expana_top_pages .loading" ).hide()
            $( "popular_pages" ).show()

    init_referrers = ->
        $( "#referrers" ).hide() # Hide the table
        $('#referrers table tbody').empty() # Empty exsiting entries
        $.ajax
            url: "admin-ajax.php"
            data: { action: "expana_ajax_referrers" }
            type: "POST"
            dataType: "JSON"
        .success (response) ->
            $.each response, (i, referrer) ->
                $( "#referrers table > tbody:last" ).append "<tr><td>(#{referrer.referer_type}) #{referrer.label}</td><td>#{referrer.nb_visits}</td><td>#{referrer.nb_actions}</td></tr>"
                if ( i > 20 )
                    return false

            if $.isEmptyObject response
                $('#expana_referrers .loading').hide()
                $('#expana_referrers .no_data').show()
                return false

            $('#expana_referrers .loading').hide()
            $('#referrers').show()

    init_seo_rankings = ->
        $.ajax
            url: "admin-ajax.php"
            data:
                action: "expana_ajax_seo_rankings",
                url: site_url
            type: "POST"
            dataType: "JSON"
        .success (response) ->
            $( "#expana_seo_rankings #seo_rankings > h4" ).append " <span>#{site_url}</span>"
            $.each response, (i, entry) ->
                if not entry.rank
                    entry.rank = 0
                if not entry.rank_suffix
                    entry.rank_suffix = ""
                icon = switch entry.id
                    when "google-index", "pagerank" then "file-text-o"
                    when "bing-index"               then "angle-double-right" # Sadly, font-awesome doesn't provide icon-bing
                    when "alexa"                    then "angle-double-right"
                    when "domain-age"               then "file"
                    when "external-backlinks"       then "link"
                    when "referrer-domains"         then "globe"
                    else "angle-double-right"
                $( "#expana_seo_rankings #seo_ranking_list" ).append "<ul id='seo_ranking_#{entry.id}'><i class='fa fa-#{icon}'></i><span class='seo_ranking_label'>#{entry.label}</span> <span class='seo_ranking'>#{entry.rank} #{entry.rank_suffix}</span>"
            $( "#expana_seo_rankings .loading" ).hide().removeClass "loading_redraw"

    custom_selector = ->
        $( "date-range-filter" ).on "click", (event) ->
            event.preventDefault()
            changeDateRange "custom"

    changeDateRange = (range) ->
        if range is "custom"
            dates = validateDateRange()
        if dates is false
            return false

        $( ".no_data" ).hide() # Hide no_data div (if any)
        $( ".loading_redraw" ).show() # Display loading animation
        $('#visits_by_time').empty() # Destory Charts that need to be redrawed
        $('#os').empty()
        $('#resolutions').empty()
        $('#browsers').empty()
        $('#map_us').empty()
        $('#map_world').empty()
        $('#device_type').empty()
        
        $.ajax # AJAX POST request to set new date range
            url: "admin-ajax.php"
            data:
                action: "expana_change_date_range"
                range: range
                dates: dates
            type: "POST"
            dataType: "JSON"
        .success (response) ->
            init_visits_by_time() # Redraw charts
            init_os()
            init_resolutions()
            init_browsers()
            init_visits_map_us()
            init_visits_map_world()
            init_device_type()
            init_top_pages()

            $( ".date-range-selectors button.date-range-button" ).prop "disabled", false #Enable buttons (loading animation will be hide by init_charts function upon completion)

    validateDateRange = ->
        from_date = $( "#expana-from-date" ).datepicker "getDate"
        to_date = $( "#expana-to-date" ).datepicker "getDate"

        if not from_date or not to_date
            alert "Invalid dates"
            return false

        $.datepicker.formatDate('yy-mm-dd', from_date) + "," + $.datepicker.formatDate('yy-mm-dd', to_date)

    maintainenceMode = ->
        notice =    """

                    """

    init_widgets = ->
        init_browsers()
        init_device_type()
        init_live()
        init_os()
        init_referrers()
        init_report()
        init_resolutions()
        init_seo_rankings()
        init_top_pages()
        init_visits_by_time()
        init_visits_map_us()
        init_visits_map_world()
        init_visits_summary()

    # Initialize
    custom_selector()
    load_elements()
    load_site_info().success ->
        init_widgets() # Defer widgets initialization
    return

) jQuery
