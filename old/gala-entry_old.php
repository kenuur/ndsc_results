<?php

require_once 'gala_routines.php';


// get the gala info from the database
// and save it in the session
// 
function gala_entry_start_func ($atts) {
	$mysqli = openNDSC();

	extract( shortcode_atts( array('gala_index' => '1'), $atts ) );

	$sql = "SELECT `name`, `index`, `active`, `event_cost_pence`, `entry_surcharge_pence` FROM `gala_list` WHERE `index` = {$gala_index}";
	$res = getResults ($mysqli, $sql);

	// save it in the session
	$row = $res->fetch_assoc();
	extract($row);
	$_SESSION['gala_name'] = $name;
	$_SESSION['gala_live'] = $active;
	$_SESSION['event_cost_pence'] = $event_cost_pence;
	$_SESSION['entry_surcharge_pence'] = $entry_surcharge_pence;
	$_SESSION['gala_index'] = $gala_index;
	
	ob_start();
	echo "<h2>{$_SESSION['gala_name']}</h2>" ;
	printf ("<p>Events cost &pound;%.2f each to enter</p>", $event_cost_pence / 100);
	$output = ob_get_contents();
	ob_end_clean();
	return $output;
}
add_shortcode( 'gala_entry_start', 'gala_entry_start_func' );

// Searhes the database for the swimmer
// Gets the id of the swimmer from form GET 
function gala_entry_find_swimmer_func ($atts) {
	$mysqli = openNDSC();

	if (!array_key_exists("id", $_GET)) {
		$_SESSION['submit_disabled'] = TRUE;
		return 'No swimmer ID provided';
	}
	$sql = "SELECT Last, First, ID_NO FROM `athlete` WHERE `Inactive`=0 AND SubGr='SW' AND (Last='{$_GET["id"]}' OR ID_NO='{$_GET["id"]}') ORDER BY Last ASC";

	$res = getResults ($mysqli, $sql);

	$_SESSION['submit_disabled'] = FALSE;
	ob_start();
	if ($res->num_rows == 0) {
		echo "No swimmers surname or ASA number matched that search: <b>{$_GET["id"]}</b>";
		echo "<br>Please check spelling. The whole surname or ASA number is required<br>";
		$_SESSION['submit_disabled'] = TRUE;
	}
	else {
		echo "<p>Please select your swimmer from this list or go back to change your search</p>";
		while ($row = $res->fetch_assoc()) {
			extract($row);
			echo '<input type="radio" name="swimmer" value="'. $ID_NO . '" required>'.$First. ' ' .$Last. ' - '. $ID_NO . "</input></BR>\n";
		}
	}	
	$output = ob_get_contents();
	ob_end_clean();
	return $output;
}
add_shortcode( 'gala_entry_find_swimmer', 'gala_entry_find_swimmer_func' );

// Return 'disabled' so form progression can be prevented
// Uses session submit_disabled flag
function gala_entry_submit_disabled_func ($atts) {
	if (!array_key_exists("submit_disabled", $_SESSION)) {
		return '';
	}
	if ($_SESSION['submit_disabled'] == TRUE) {
		return 'disabled';
	}
	else {
		return '';
	}
}
add_shortcode( 'gala_entry_submit_disabled', 'gala_entry_submit_disabled_func' );

// retrieve athlete data and store in session
// display athlete name
// gets the chosen athlete 
function gala_entry_retrieve_athelete_func ($atts) {
	if (!array_key_exists("swimmer", $_GET)) {
		$_SESSION['submit_disabled'] = TRUE;
		return 'Somthing went wrong, No swimmer ID provided';
	}

	// Get athlete data from database
	$mysqli = openNDSC();
	$sql = "SELECT Last, First, ID_NO, Sex, Birth, `Group` FROM `athlete` WHERE `Inactive`=0 AND SubGr='SW' AND (ID_NO='". $_GET["swimmer"] ."') ORDER BY Last ASC";
	$res = getResults ($mysqli, $sql);

	if ($res->num_rows == 0) {
		return "Somthing went wrong, couldn't find swimmer: ". $_GET["swimmer"];
	}
	if ($res->num_rows > 1) {
		return "Somthing went wrong, more than one swimmer with that ID: ". $_GET["swimmer"];
	}

	$row = $res->fetch_assoc();
	extract($row);
	// setup the session variables
	$_SESSION['swimmer_name']= $First .' '. $Last;
	$_SESSION['swimmer_ID']=$ID_NO;
	$_SESSION['athlete_group']=$Group;
	$_SESSION['gender']=$Sex;
	
	return "Name: {$_SESSION['swimmer_name']} <br>ID: $ID_NO <br>Squad: $Group <br>". PHP_EOL;
}
add_shortcode( 'gala_entry_retrieve_athelete', 'gala_entry_retrieve_athelete_func' );

// get the gala events from the database
// display them as table cells
function gala_entry_retreive_events_func ($atts) {
	if (!array_key_exists("swimmer", $_GET)) {
		$_SESSION['submit_disabled'] = TRUE;
		return 'Somthing went wrong, No swimmer ID provided';
	}

	// get the gala events from the database, filter by squad
	$mysqli = openNDSC();
	$sql = "SELECT * FROM `gala_events` WHERE gender='{$_SESSION['gender']}' AND gala='{$_SESSION['gala_index']}'";
	if ('CR' == $_SESSION['athlete_group']) {
		$squad = " AND event_type = 'cruiser'";
	}
	else if ('SP' == $_SESSION['athlete_group']) {
		$squad = " AND event_type = 'sprinter'";
	}
	else {
		$squad = " AND event_type = 'agegroup'";
	}
	$sql .= $squad;

	$res = getResults ($mysqli, $sql);
	
	ob_start();
	while ($row = $res->fetch_assoc()) {
		extract($row);
		echo '<tr>';
		echo '<td><input type="checkbox" name="events[]" value="'. $event_number .'"></td>';
		echo "<td>$event_number</td>";
		echo '<td>'. ($gender == 'M' ? 'Boys ' : 'Girls ') . $event_name .'</td>';	
		echo '</tr>'. PHP_EOL;
		$_SESSION['submit_disabled'] = FALSE;
	}
	$output = ob_get_contents();
	ob_end_clean();
	return $output;	
}
add_shortcode( 'gala_entry_retreive_events', 'gala_entry_retreive_events_func' );

// return the swimmer name and ID to display at the top of the forms
// retrieved from Session so retrieve_athlete must be done first
function gala_entry_swimmer_header_func ($atts) {
	return "Name: {$_SESSION['swimmer_name']} <br>ID: {$_SESSION['swimmer_ID']}<br>Squad: {$_SESSION['athlete_group']}<br>". PHP_EOL;
}
add_shortcode( 'gala_entry_swimmer_header', 'gala_entry_swimmer_header_func' );

// display a table of the selected events with text boxes for seed time entry
// selected event numbers in the url
function gala_entry_retrieve_selected_events_func ($atts) {
	//get a list of event numbers
	$events = $_GET['events'];
	if(empty($events))
	{
		$_SESSION['submit_disabled'] = TRUE;
		return("You didn't select any events, please go back and select one or more events.");
	}
	else
	{
		$N = count($events);
		$event_list = "";
		for($i=0; $i < $N; $i++)
		{
			$event_list .= "'" .$events[$i]. "',"; 
		}
		$event_list = rtrim($event_list, ",");
		$_SESSION['submit_disabled'] = FALSE;

	}
	
	$mysqli = openNDSC();
	// make a query to get the events that were selected
	$sql = "SELECT * FROM `gala_events` WHERE event_number IN ($event_list)";
	$res = getResults ($mysqli, $sql);

	// Display the events selected on the previous page in the form
	// Add a text box for entry of times
	$event_numbers = array();
	$event_descriptions = array();

	ob_start();
	
	while ($row = $res->fetch_assoc()) {
		extract($row);
		echo '<tr>';
		echo "<td> $event_number </td>";
		echo '<td>'. ($gender == 'M' ? 'Boys ' : 'Girls ') . $event_name .'</td>';	
		$event_numbers[] = $event_number;
		$event_descriptions[] = ($gender == 'M' ? 'Boys ' : 'Girls '). $event_name;
		echo '<td><input type="text" name="entered_times[]" maxlength="8" size="8" maxlength="8" ';
		echo 'pattern="^([1-2]?[0-9]:)?[0-5][0-9]\.[0-9]{2}$|NT|nt" title="time like 1:23.45 or 57.89 or NT or blank"';
		echo '></td></tr>'. PHP_EOL;
	}
	$_SESSION['event_numbers'] = $event_numbers;
	$_SESSION['event_descriptions'] = $event_descriptions;
	
	$output = ob_get_contents();
	ob_end_clean();
	return $output;	
}
add_shortcode( 'gala_entry_retrieve_selected_events', 'gala_entry_retrieve_selected_events_func' );

// display the entered times for confirmation
function gala_entry_display_entries_func ($atts) {
	// entered times are on URL
	// other details in the session
	$event_numbers = $_SESSION['event_numbers'];
	$event_descriptions = $_SESSION['event_descriptions'];
	$entered_times = $_GET['entered_times'];
	$_SESSION['submit_disabled'] = TRUE;
	// check the number of events/times are correct
	if(empty($event_numbers))
	{
		return("Didn't get any events");
	}
	if(empty($entered_times))
	{
		return("Didn't get any times");
	}
	$num_events = count($event_numbers);
	$num_times = count($entered_times);
	if($num_events != $num_times)
	{
		return("Error with returned data");
	}
	
	// Process each entry and check 
	$enableSubmit = true;
	$clean_entrytimes = array();
	ob_start();
	for($i=0; $i < $num_events; $i++)
	{
		echo "<tr><td>$event_numbers[$i] </td>";
		echo "<td>$event_descriptions[$i] </td>";
		$entrytime = handleEntryTime( $entered_times[$i] );

		if ($entrytime == null) {
			$enableSubmit = false;
			echo "<td>Time format incorrect</td></tr>";	
		}
		else {
			echo "<td>$entrytime </td></tr>";
		}
		$clean_entrytimes[] = $entrytime;	
	}
	// enable the submit button if all ok
	if ($enableSubmit) {
		$_SESSION['submit_disabled'] = FALSE;
	}
	// store the processed times in the session
	$_SESSION['clean_entrytimes'] = $clean_entrytimes;
	$_SESSION['num_events'] = $num_times;
	
	$output = ob_get_contents();
	ob_end_clean();
	return $output;	
}
add_shortcode( 'gala_entry_display_entries', 'gala_entry_display_entries_func' );

// display a sumary of the entry
// calculate the cost
function gala_entry_display_summary_func ($atts) {
	ob_start();
	if ($_SESSION['submit_disabled']) { 
		echo "Time must be in one of the following formats<br>".PHP_EOL;
		echo "12.34, 1:23.45, 12:34.56, blank or NT<br>".PHP_EOL;
		echo "Go back to correct your input<br>".PHP_EOL;
	}
	else {
		echo ("You have entered {$_SESSION['num_events']} event(s)<br>".PHP_EOL);
		// calculate payment
		$total_event_cost = ($_SESSION['event_cost_pence'] * $_SESSION['num_events']) / 100;
		$_SESSION['total_event_cost'] = $total_event_cost;
		printf ( "Cost of event entry: &pound;%.2f<br>".PHP_EOL, $total_event_cost );	
		echo "Press the Next button to confirm your entry and pay<br>".PHP_EOL;
	}
	$output = ob_get_contents();
	ob_end_clean();
	return $output;	
}
add_shortcode( 'gala_entry_display_summary', 'gala_entry_display_summary_func' );

// save the entry from the session to the database
function gala_entry_save_entry_func ($atts) {
	// retrieve entry from the session
	$event_numbers = $_SESSION['event_numbers'];
	$event_descriptions = $_SESSION['event_descriptions'];
	$clean_entrytimes = $_SESSION['clean_entrytimes'];
	$num_events = $_SESSION['num_events'];
	
	// Check format of entry parameters
	$_SESSION['submit_disabled'] = TRUE;
	if(empty($event_numbers))
	{
		return ("Didn't get any events");
	}
	if(empty($event_descriptions))
	{
		return("Didn't get any descriptions");
	}
	if(empty($clean_entrytimes))
	{
		return("Didn't get any times");
	}
	$num_event_numbers = count($event_numbers);
	$num_descriptions = count($event_descriptions);
	$num_times = count($clean_entrytimes);
	
	if($num_events != $num_event_numbers)
	{
		return("Error with returned data");
	}
	if($num_events != $num_times)
	{
		return("Error with returned data");
	}
	if($num_events != $num_descriptions)
	{
		return("Error with returned data");
	}
	
	// connect to database
	$mysqli = openNDSC();

	// add the entry
	$values = "'". $_SESSION['swimmer_ID']. "', '". $_SESSION['swimmer_name']. "', '". $_SESSION['gala_index']. "', '". $_SESSION['gala_live']. "', Now()";
	$sql = "INSERT INTO `entry` (`ID`, `Name`, `gala_index`, `live`, `whenadded`) VALUES ($values);";
	setResults ($mysqli, $sql);
	
	// add the events
	$entry_index = $mysqli->insert_id;
	$_SESSION['entry_index'] = $entry_index;
	for ($i=0; $i<$num_events; $i++) {
		$values = "'{$entry_index}', '{$event_numbers[$i]}', '{$event_descriptions[$i]}', '{$clean_entrytimes[$i]}'";
		$sql = "INSERT INTO `entry_events` (`entry_index`, `event_number`, `event_description`, `entry_time`) VALUES ($values);";
		setResults ($mysqli, $sql);
	}
	$_SESSION['submit_disabled'] = FALSE;
	return ("<p>You have entered $num_events event(s)<br>");
}
add_shortcode( 'gala_entry_save_entry', 'gala_entry_save_entry_func' );

function gala_entry_display_payment_func ($atts) {
	// calculate payment
	ob_start();
	printf ( "Cost of event entry: &pound;%.2f</p>", $_SESSION['total_event_cost'] );	
	
	printf ("<h3>Pay by PayPal</h3><p>No account needed, there is a %d pence transaction charge</br>".PHP_EOL, $_SESSION['entry_surcharge_pence']);

	$total_paypal_charge = $_SESSION['total_event_cost'] + ($_SESSION['entry_surcharge_pence'] / 100);
	$_SESSION['paypal_total_charge'] = sprintf ("%.2f", $total_paypal_charge);
	$_SESSION['paypal_item_number'] = sprintf ("%s - %03d", $_SESSION['swimmer_ID'], $_SESSION['entry_index']);
	echo "PayPal total: &pound;{$_SESSION['paypal_total_charge']}</p>".PHP_EOL;

	$output = ob_get_contents();
	ob_end_clean();
	return $output;	
}
add_shortcode( 'gala_entry_display_payment', 'gala_entry_display_payment_func' );


function gala_entry_paypal_button_func ($atts) {
return <<<PAYPAL
	<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
		<input type="hidden" name="cmd" value="_xclick">
		<input type="hidden" name="business" value="TEPSUFJADTJV6">
		<input type="hidden" name="lc" value="GB">
		<input type="hidden" name="item_name" value="{$_SESSION['gala_name']} - Event Entry">
		<input type="hidden" name="item_number" value="{$_SESSION['paypal_item_number']}">
		<input type="hidden" name="amount" value="{$_SESSION['paypal_total_charge']}">
		<input type="hidden" name="currency_code" value="GBP">
		<input type="hidden" name="button_subtype" value="services">
		<input type="hidden" name="no_note" value="1">
		<input type="hidden" name="no_shipping" value="1">
		<input type="hidden" name="bn" value="PP-BuyNowBF:btn_buynowCC_LG.gif:NonHosted">
		<input type="image" src="https://www.paypalobjects.com/en_US/GB/i/btn/btn_buynowCC_LG.gif" border="0" name="submit" alt="PayPal – The safer, easier way to pay online.">
		<img alt="" border="0" src="https://www.paypalobjects.com/en_GB/i/scr/pixel.gif" width="1" height="1">
	</form>
PAYPAL;
}
add_shortcode( 'gala_entry_paypal_button', 'gala_entry_paypal_button_func' );

function gala_entry_cheque_payment_func ($atts) {
	ob_start();
	echo "<h2>Pay by cheque</h3>".PHP_EOL;
	printf ("Send a cheque for &pound;%.2f with the entry number %s on the back<br>".PHP_EOL, $_SESSION['total_event_cost'], $_SESSION['paypal_item_number']);
	echo "Please ensure this reaches the meet administrator by the closing date</p>".PHP_EOL;
	
	$output = ob_get_contents();
	ob_end_clean();
	return $output;	
}
add_shortcode( 'gala_entry_cheque_payment', 'gala_entry_cheque_payment_func' );

function gala_entry_problem_mail_func($atts) {
    extract(shortcode_atts(array("mailto" => ''), $atts));
    $mailto = antispambot($mailto);
    return 'Please report errors or omissions to <a href="mailto:' . $mailto . '">' . $mailto . '</a>';
}
add_shortcode( 'gala_entry_problem_mail', 'gala_entry_problem_mail_func' );

?>