<?php

$db_gala_name = "ndsccouk_swim_gala";

// start the session when worpress is initializing
add_action('init', 'myStartSession', 1);
function myStartSession() {
    if(!session_id()) {
        session_start();
    }
}



// swimming gala entry pages
// gala details are loaded from the db and the user is allowed to search for the
// swimmer to enter. 
// Clicking on the user displays a list of events to enter

// get the gala info from the database
// and save it in the session
function gala_entry_start_func ($atts) {
    extract( shortcode_atts( array('gala_index' => '1'), $atts ) );
    //echo "Gala index is $gala_index\n";
    $gala_details = load_gala((int)$gala_index);
    
    if ($gala_details == NULL) {
        return "Unable to find an active Gala to enter";
    }
    
    //Save gala details to session
    $_SESSION['gala_details']=$gala_details;
    
    ob_start();
    echo "<h2>{$gala_details['gala_name']}</h2>" ;
    //echo $_SESSION['gala_index'];
    //echo $_SESSION['gala_name'];
    printf ("<p>Events cost &pound;%.2f each to enter</p>", $gala_details['event_cost_pence'] / 100);
    printf ("<p>Closing date is midnight on %s</p>", date('l jS \of F',$gala_details['closing_date']));
    printf ("<p>%s</p>", $gala_details['description']);
    $output = ob_get_contents();
    ob_end_clean();
    return $output;

}
add_shortcode( 'GALAENTRYSTART', 'gala_entry_start_func' );


// Find the swimmer to enter
// function to display search dialog for swimers
// Includes div for Ajax to place matching swimmers
function ajaxndsc_showswimmersearch (){
	ob_start();
	echo '<form id="personalbest" onSubmit="return ajaxndscgala_swimmersearch()">'. PHP_EOL;
	echo 'Swimmer Name:<input type="text" name="NameSearch"/>'. PHP_EOL;
	echo '<input type="submit" value="Search">'. PHP_EOL;
	echo '</form>'. PHP_EOL;
	// result area
	echo '<p><div id="showswimmersearchresult">Type part of the swimmer name you are searching for then click Search</div>'. PHP_EOL;
	echo '<p><div id="showswimmerresults"></div>'. PHP_EOL;
	
	
	$output = ob_get_contents();
	ob_end_clean();
	return $output;	
}
function ajaxshowgala_shortcode_function( $atts ){
    return ajaxndsc_showswimmersearch();
}
add_shortcode( 'AJAXGALAENTRY', 'ajaxshowgala_shortcode_function' );

// ajax handler to display the swimers search result
function ajaxndscgala_swimmersearch_ajaxhandler() {
	$form_data = $_POST['form_data'];
	parse_str ($form_data);
	//$results = "Stroke:$Stroke<p>Distance:$Distance<p>". PHP_EOL;
	$results = display_gala_swimmersearch_func($NameSearch);
	die($results);
}


// Gala entry
// Return HTML to display a table of matching swimmers
 function display_gala_swimmersearch_func ($typedName)
 {
 	// don't search for very short strings
 	if (strlen($typedName) < 3) {
 		return "Search name is too short, please be more specific";
 	}
 	//search for names which match the provided name with wild cards before and after
 	$searchString = '%'.$typedName .'%';
        $results = searchForSwimmer($searchString);

        if (count($results) < 1) {
            return "No swimmers matched that search, please try again";
        }
        else if (count($results) > 10) {
            return "Too many swimmers matched that search, please be more specific";
        } 
        
        ob_start();
        echo 'Select the swimmer you wish enter<br>'. PHP_EOL;
        foreach ($results as $asaNo => $athleteName) {
                $argument = "'$athleteName'";
                echo '<a href="fetch_swimmer?id=', $asaNo, '">', $athleteName, ' - ', $asaNo, '</a></br>'. PHP_EOL;
        }

        $output .= ob_get_contents();
        ob_end_clean();
        return $output;
}


 // register swimmer search handler with Wordpress
add_action( 'wp_ajax_nopriv_ajaxndscgala_swimmersearch_ajaxhandler', 'ajaxndscgala_swimmersearch_ajaxhandler' );
add_action( 'wp_ajax_ajaxndscgala_swimmersearch_ajaxhandler', 'ajaxndscgala_swimmersearch_ajaxhandler' ); 

// queue the js scripts so they are included on the page
function ajaxndscgala_enqueuescripts() {
	wp_enqueue_script('ajaxndscgala', AJAXNDSCRESULTSURL.'/js/ajaxndscgala.js', array('jquery'));
	wp_localize_script( 'ajaxndscgala', 'ajaxndscgalaajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
}
add_action('wp_enqueue_scripts', ajaxndscgala_enqueuescripts);


function gala_entry_eventspage_func($atts) {
    $athleteHtml = gala_entry_retrieve_athelete($atts);
    $eventsHtml = gala_entry_displayevents();
    return $athleteHtml . $eventsHtml;
}
add_shortcode( 'gala_entry_eventspage', 'gala_entry_eventspage_func' );

// retrieve athlete data and store in session
// display athlete name
// gets the chosen athlete
function gala_entry_retrieve_athelete ($atts) {
    if (!array_key_exists("id", $_GET)) {
            return 'Somthing went wrong, No swimmer ID was provided';
    }
    //$swimmerID = $_GET["id"];
    $swimmerID = filter_input(INPUT_GET, "id", FILTER_SANITIZE_STRING);
    // Get athlete data from database
    $swimmerDetails = fetchSwimmer($swimmerID);

    // check for an errror response
    if (array_key_exists('error', $swimmerDetails))
    {
        return $swimmerDetails['error'] . "<br>";
    }
    // add the swimmers details to the sessiom
    $_SESSION['swimmer_details'] = $swimmerDetails;
    
    return "Name: " . $_SESSION['swimmer_details']['swimmer_name'] .  " - ASA No: " . $swimmerID . " - Squad: " . $_SESSION['swimmer_details']['athlete_squad'] . "<br><br>". PHP_EOL;
}

function gala_entry_displayevents() {
    if (!array_key_exists("id", $_GET)) {
        return '';
    }
	// get events from the dataabse that match the gala and gender of swimmer
    $events = fetchEvents($_SESSION['gala_details']['gala_index'], $_SESSION['swimmer_details']['gender']);
	
    // display a form that will allow user to select events and enter time
    ob_start();
    echo 'Enter a time for events you wish to enter like 1:23.45 or 57.89<br>';
    echo 'If you dont have a time enter NT<br>';
    echo 'Leave the time blank to not enter that event<br><br>' . PHP_EOL;
    echo '<form action="\gala-entry-3" method="post"><table>';
    echo '<tr><th>Event Number</th>';
    echo '<th>Event Name</th>';
    echo '<th>Entered time</th></tr>';
    $event_names = array();
    foreach ($events as $event) {
            echo '<tr>';
            echo '<td>' . $event['number'] . '<input type="hidden" name="eventnos[]" value="' . $event['number'] . '"></td>' . PHP_EOL;
            echo '<td>' . $event['name'] . '</td>' . PHP_EOL;
            $event_names[] = $event['name'];
            echo '<td><input type="text" name="entered_times[]" maxlength="8" size="8" maxlength="8" ';
            //echo 'pattern="^([1-2]?[0-9]:)?[0-5][0-9]\.[0-9]{2}$|NT|nt" title="time like 1:23.45 or 57.89 or NT or blank if not entering"';
            echo '></td></tr>'. PHP_EOL;
    }
    echo '</table><input type="submit" value="Submit"></form>'; 
    $_SESSION['eventnames'] = $event_names;
    $output = ob_get_contents();
    ob_end_clean();
    return $output;		
}

// display the entered events with the entry time
// if any times are formated badly prevent confirmation
// 
function gala_entry_eventsconfirm_func($atts) {
    $entryDetails = array();
    
    // check all the arrays are populated
    $entryDetails['times'] = cleanEntryTimes($_POST['entered_times']) ;
    $eventCount = count($entryDetails['times']);
    $entryDetails['eventno'] = $_POST['eventnos'];
    if ($eventCount != count ($entryDetails['eventno'])) { return 'System error, please try again'; }
    $entryDetails['eventname'] = $_SESSION['eventnames'];
    if ($eventCount != count ($entryDetails['eventname'])) { return 'System error, please try again'; }
    
    

    $indx = 0;
    $entryCount = 0;
    $submitDisabled = "";
    ob_start();
    echo 'Summary of entry:';
    echo '<table>';
    while ($indx < $eventCount) {
        // only display entered events
        if ($entryDetails['times'][$indx] !=  "NOENTRY" ) {
            $entryCount++;
            echo '<tr>';
            echo '<td>' . $entryDetails['eventno'][$indx] . '</td>';
            echo '<td>' . $entryDetails['eventname'][$indx] . '</td>';
            echo '<td>' . $entryDetails['times'][$indx] . '</td>';
            echo '</tr>';
            if ($entryDetails['times'][$indx] ==  "Invalid" ) {
                $submitDisabled = "Disabled";
            }
        }
        $indx++;
    }
    echo '</table>';
    if ($entryCount > 0) {

        if ($submitDisabled == "Disabled") {
            echo 'You have entered one or more times in an invalid format<br>';
            echo 'Please use seconds and two decimals e.g. 12.34 ';
            echo 'or minutes, seconds and decimals e.g. 1:23.45<br>';
            echo 'Please go back and correct your entry<p>';
        }
        else {
            printf ('You have entered %d events<br>', $entryCount);
            echo 'The cost of entry is: ' . entryCost($entryCount) . '<br>';
            echo 'If you are happy with the entry click the confirm button below. ';
            echo 'Otherwise go back to make any corrections<br>';
        }
    }
    else {
        echo 'You have not entered any events, please go back and enter a time (or NT) for one or more events<p>';
        $submitDisabled = "Disabled";
    }
    echo '<p>';
    echo '<form action="gala-entry-4" method="get">';
    //printf('<input type="image" name="payment" value="GoCardless" src="https://s3-eu-west-1.amazonaws.com/gocardless/images/public/buttons/updated/pay-with-gc-small.png" alt="Submit" %s><br>', $submitDisabled);
    printf('<input type="submit" value="Cheque" name="payment" %s></form>', $submitDisabled);
    $output = ob_get_contents();
    ob_end_clean();
    // store the entry details in the session if valid
    if ($submitDisabled != "Disabled") {
        $_SESSION['entryDetails'] = $entryDetails;
    }
    return $output;	
}
add_shortcode( 'gala_entry_eventsconfirm', 'gala_entry_eventsconfirm_func' );

function cleanEntryTimes ($entryTimes)
{
    $pattern = '/^([1-2]?[0-9]:)?[0-5][0-9]\.[0-9]{2}$/';

    foreach ($entryTimes as $rawEntryTime)
    {
        $rawEntryTime = trim($rawEntryTime);

        // if the user typed anything
        if (strlen($rawEntryTime) > 0) {
            // NT means no entry time
            if (strcasecmp ($rawEntryTime, "NT") == 0) {
                $clean_entrytimes[] =  "No Time";
            }
            // aything else check it matches a valid input format
            else {
                // if valid, put time in the clean array
                if (preg_match ($pattern, $rawEntryTime)) {
                    $clean_entrytimes[] =  $rawEntryTime;
                }
                // not valid format
                else {
                    $clean_entrytimes[] =  "Invalid";
                }
            }
        }
        // nothing typed so no entry
        else {
            $clean_entrytimes[] = "NOENTRY";
        }
    }
    return $clean_entrytimes;
}


// calculate and return the cost for the number of entries
// store the cost in the session
function entryCost($numEvents) {
    $entryCost = ($numEvents * $_SESSION['gala_details']['event_cost_pence']) / 100;
    $_SESSION['total_entry_cost'] = $entryCost;
    return sprintf('£%.2f', $entryCost);
}

// save the entry in the database
// display the payment options
function gala_entry_save_entry() {
    $_SESSION['entry_index'] = saveEntry($_SESSION['gala_details'], $_SESSION['swimmer_details'], $_SESSION['total_entry_cost'], $_SESSION['entryDetails']);
}

// user has confirmed their entry and selected payment type
// handle the entry depending on the payment tye selected
function gala_entry_take_payment_func() {
    
    gala_entry_save_entry();
    $paymentMethod = filter_input(INPUT_GET, 'payment', FILTER_SANITIZE_STRING);
    switch ($paymentMethod) {
        case 'Cheque':
            echo 'You chose cheque!';

            break;

        default:
            break;
    }
}
add_shortcode( 'gala_entry_take_payment', 'gala_entry_take_payment_func' );