jQuery.ajax({
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
        jQuery.each(response.data, function(index, item) {
            jQuery("#report_content").append("<section><img src='" + item.thumbnail + "' /><span>" + item.description + "</span></section>");
        })
    }
});
