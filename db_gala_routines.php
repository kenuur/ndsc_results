<?php

$db_gala_name = "ndsccouk_swim_gala";

// Load the requested gala basic values and return html to display
// the Gala name
function load_gala ($gala_index)
{
        global $db_gala_name;
    	$mysqli = openDatabase($db_gala_name);

	$stmt = $mysqli->prepare('SELECT `name`, `event_index`, `active`, `event_cost_pence`, UNIX_TIMESTAMP(`closing_date`) FROM `gala_list` WHERE `event_index` = ?');
	$stmt->bind_param('i', $gala_index);
	$stmt->execute();
	$stmt->store_result();
        
        // Check to see only one event matches
	if ($stmt->num_rows != 1) {
		return "Unable to find a gala for you to enter";
	}
        
	// save it in the session
       	$stmt->bind_result($name, $event_index, $active, $event_cost_pence, $closing_date);

	//$row = $res->fetch_assoc();
	//extract($row);
        $stmt->fetch();
	$_SESSION['gala_name'] = $name;
	$_SESSION['gala_live'] = $active;
	$_SESSION['event_cost_pence'] = $event_cost_pence;
	$_SESSION['gala_index'] = $event_index;
	
	ob_start();
	echo "<h2>{$_SESSION['gala_name']}</h2>" ;
	printf ("<p>Events cost &pound;%.2f each to enter</p>", $event_cost_pence / 100);
        printf ("<p>Closing date is midnight on %s</p>", date('l jS \of F',$closing_date));
	$output = ob_get_contents();
	ob_end_clean();
	return $output;
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
	global $db_gala_name;
	$mysqli = openDatabase($db_gala_name);
	if ($stmt = $mysqli->prepare('SELECT AthleteName, AsaNo FROM athletes WHERE AthleteName LIKE ? ORDER BY AthleteName')) {
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
            echo 'Select the swimmer you wish enter<br>'. PHP_EOL;
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
        else {
            return 'Error searching for swimmer';
        }
}

