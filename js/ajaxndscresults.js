//$("#toptimes").submit(ajaxndscresults_toptimes(event));

// Called when the top times form is submitted
function ajaxndscresults_toptimes() {

	var form_data = jQuery( "#toptimes" ).serialize();
	if (typeof(_gaq) !== 'undefined') { 
	    _gaq.push(['_trackEvent', 'results', 'view', 'toptimes']);
	}
    jQuery.ajax({
        type: 'POST',
        url: ajaxndscresultsajax.ajaxurl,
        data: {
            action: 'ajaxndscresults_ajaxhandler',
            form_data: form_data
        },
        success: function(data, textStatus, XMLHttpRequest) {
            var loadpostresult = '#showtoptimesresult';
            jQuery(loadpostresult).html('');
            jQuery(loadpostresult).append(data);
        },
        error: function(MLHttpRequest, textStatus, errorThrown) {
            alert(errorThrown);
        }
    });
	return false; // prevent further bubbling of event
}

// Called when the swimmer search form is submitted
function ajaxndscresults_swimmersearch() {

	var form_data = jQuery( "#personalbest" ).serialize();
	if (typeof(_gaq) !== 'undefined') { 
		var nameSearch = jQuery('input[name=NameSearch]').val();
		_gaq.push(['_trackEvent', 'results', 'search', nameSearch]);
	}
    jQuery.ajax({
        type: 'POST',
        url: ajaxndscresultsajax.ajaxurl,
        data: {
            action: 'ajaxndscswimmersearch_ajaxhandler',
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

// Called when the swimmer name is selected
// Requests the results table and writes it to the page
function ajaxndscresults_swimmerresults(swimmerName) {

	var form_data = jQuery( "#personalbest" ).serialize();
	if (typeof(_gaq) !== 'undefined') { 
	    _gaq.push(['_trackEvent', 'results', 'view', swimmerName]);
	}
    jQuery.ajax({
        type: 'POST',
        url: ajaxndscresultsajax.ajaxurl,
        data: {
            action: 'ajaxndscswimmerresults_ajaxhandler',
            swimmerName: swimmerName
        },
        success: function(data, textStatus, XMLHttpRequest) {
            var loadpostresult = '#showswimmerresults';
            jQuery(loadpostresult).html('');
            jQuery(loadpostresult).append(data);
        },
        error: function(MLHttpRequest, textStatus, errorThrown) {
            alert(errorThrown);
        }
    });
	return false; // prevent further bubbling of event
}


// When the stroke is changed limit which distances are available
// Frestyle only for 800 and 1500
//$("#stroke").change(function () {
function topTimes_strokeChange() {
    var stroke = jQuery("#stroke").val();
    var formStrokes = ["Back", "Breast", "Fly"];
    var formDistances = ["12U_50", "13O_50", "12U_100", "13O_100", "200"];
    var IMDistances = ["12U_100", "13O_100", "200", "200"];
    var medleyDistances = ["12U_100", "13O_100", "200", "400"];

    // for each distance
    jQuery("#distance option").each(function () {
        var $thisOption = jQuery(this);
        if (jQuery.inArray(stroke, formStrokes) != -1) { // if its a form stroke
            if (jQuery.inArray($thisOption.val(), formDistances) == -1) { // and not a form distance
                $thisOption.attr("disabled", "disabled"); // disable the distance
                if (jQuery(this).is(':selected')) { // if selected, change the selection
                    jQuery("#distance option[value='12U_50']").attr('selected', 'selected');
                }
            } else {
                $thisOption.removeAttr('disabled');
            }
        } else if (stroke == "Free") { // freestyle so all distances available
            $thisOption.removeAttr('disabled');
        } else if (stroke == "IM") { // Individual medley
            if (jQuery.inArray($thisOption.val(), medleyDistances) == -1) { // and not medley distance
                $thisOption.attr("disabled", "disabled"); // disable the distance
                if (jQuery(this).is(':selected')) { // if selected, change the selection
                    jQuery("#distance option[value='200']").attr('selected', 'selected');
                }
            } else {
                $thisOption.removeAttr('disabled');
            }
        }
    });
}