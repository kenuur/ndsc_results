<?php

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
	global $db_gala_name;
	$mysqli = openDatabase($db_gala_name);
	$stmt = $mysqli->prepare('SELECT AthleteName, AsaNo FROM athletes WHERE AthleteName LIKE ? ORDER BY AthleteName');
	$stmt->bind_param('s', $searchString);
	$stmt->execute();
	$stmt->store_result();

	// Check to see if too many names match
	if ($stmt->num_rows > 10) {
		return "Too many names match your search, please be more specific";
	}
	// Check if no names match
	if ($stmt->num_rows == 0) {
		return "No names match your search, please try again";
	}	
	
	// display the result names
	$stmt->bind_result($athleteName, $asaNo);
	
	ob_start();
	echo 'Select the swimmer you wish enter'. PHP_EOL;
	echo '<table>'. PHP_EOL; 
	while ($stmt->fetch()) {
		//echo '<tr>';
		$argument = "'$athleteName'";
		//echo '<td><a onclick="ajaxndscresults_swimmerresults('. $argument .')" >';
                echo '<input type="radio" name="swimmer" value="'. $asaNo . '" required>'.$athleteName. ' - '. $asaNo . "</input></BR>\n";
		//echo "$athleteName</a></td>";
		//echo '</tr>'. PHP_EOL;
	}
	echo '</table>';	
	
	$output .= ob_get_contents();
	ob_end_clean();
	$mysqli->close();
	return $output;		
}

