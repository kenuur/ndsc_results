// Called when the swimmer search form is submitted
function ajaxndscgala_swimmersearch() {

	var form_data = jQuery( "#personalbest" ).serialize();
	if (typeof(_gaq) !== 'undefined') { 
		var nameSearch = jQuery('input[name=NameSearch]').val();
		_gaq.push(['_trackEvent', 'results', 'search', nameSearch]);
	}
    jQuery.ajax({
        type: 'POST',
        url: ajaxndscresultsajax.ajaxurl,
        data: {
            action: 'ajaxndscgala_swimmersearch_ajaxhandler',
            form_data: form_data
        },
        success: function(data, textStatus, XMLHttpRequest) {
            var loadpostresult = '#showswimmersearchresult';
            jQuery(loadpostresult).html('');
            jQuery(loadpostresult).append(data);
            loadpostresult = '#showswimmerresults';
            jQuery(loadpostresult).html('');
        },
        error: function(MLHttpRequest, textStatus, errorThrown) {
            alert(errorThrown);
        }
    });
	return false; // prevent further bubbling of event
}
