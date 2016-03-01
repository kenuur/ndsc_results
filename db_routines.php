<?php
// db_routines.php

$db_name = "ndsccouk_swim_results";

// NDSC Results Database access
function toptimes_enabled_check()
{
	$enabled = false;
	global $db_name;
	$mysqli = openDatabase($db_name);
	$sql = "SELECT `value` FROM `settings` WHERE `name` = \"toptimes_status\"";
	$result = getResults ($mysqli, $sql);
	if ($result) {
		if($row = $result->fetch_assoc()) {
			extract($row);
			if (strcasecmp ($value, "Enabled") == 0) {
				$enabled = true;
			}
		}
		$result->close();
	}
	$mysqli->close();
	return $enabled;
}

// Return HTML to display a table of top times for the given search parameters
 function display_toptimes_func ($stroke, $age_distance, $sex)
 {

	global $db_name;
	$mysqli = openDatabase($db_name);
	$output = "";
	
	$sql = "SELECT value FROM settings WHERE name = \"toptimes_date\"";
	$result = getResults ($mysqli, $sql);
	if ($result) {
		if($row = $result->fetch_assoc()) {
			extract($row);
			$output = "Results for the year ending $value. Times are converted to short course.<p>";
		}
	}
	
	switch ($age_distance) {
		case '12U_50':
			$ageSelect = '((`Age`)<=12)';
			$distance = '50';
			break;
		case '13O_50':
			$ageSelect = '((`Age`)>=13)';
			$distance = '50';
			break;
		case '12U_100':
			$ageSelect = '((`Age`)<=12)';
			$distance = '100';
			break;
		case '13O_100':
			$ageSelect = '((`Age`)>=13)';
			$distance = '100';
			break;
		default:
			$ageSelect = '((`Age`)>0)';
			$distance = $age_distance;
			break;
	}
		
	$sql = "SELECT `PrintableTime`, `Course`, `AthleteName`, `Age`, DATE_FORMAT(MeetDate, '%d-%m-%y') AS MeetDateFormatted,`MeetName`\n"
		. "FROM toptimes\n"
		. "WHERE ($ageSelect AND ((`Sex`)=\"$sex\") AND ((`Distance`)=\"$distance\") AND ((`Stroke`)=\"$stroke\"))\n"
		. "ORDER BY `ConvertedTime`\n"
		. "LIMIT 20";

	$result = getResults ($mysqli, $sql);
	if ($result) {
		ob_start();
		//echo $sql;
			
		echo '<table class="ndsc-results-table">'; 
		echo '<tr><th/><th>Time</th><th/><th>Name</th><th>Age</th><th>Date</th><th>Meet</th></tr>';
		
		$rank = 1;
		while ($row = $result->fetch_assoc()) {
			extract($row);
			echo '<tr>';
			echo "<td> $rank </td>";
			echo '<td style="text-align:right">' . "$PrintableTime </td>";
			echo "<td> $Course </td>";
			echo "<td> $AthleteName </td>";
			echo "<td> $Age </td>";
			echo "<td> $MeetDateFormatted</td>";
			echo "<td> $MeetName </td>";
			echo '</tr>'. PHP_EOL;
			$rank++;
		}
		echo '</table>';
		/* close result set */
		$result->close();
		$output .= ob_get_contents();
		ob_end_clean();
		/* close connection */
		$mysqli->close();

		$output .= "<p/>If you attend a non-club meet and wish to have results included ";
		$output .= "please notify Sally Cowan with a <i>link to the results on the host club website</i> and copy your coach.";

		return $output;	

	}
	else {
		/* close connection */
		$mysqli->close();
		return "There was a problem fetching the results. "
		. "If this persists, please contact the website admin via the contact form.";
	}

}	

// Return HTML to display a table of matching swimmers
 function display_swimmersearch_func ($typedName)
 {
 	// don't search for very short strings
 	if (strlen($typedName) < 3) {
 		return "Search name is too short, please be more specific";
 	}
 	//search for names which match the provided name with wild cards before and after
 	$searchString = '%'.$typedName .'%';
	global $db_name;
	$mysqli = openDatabase($db_name);
	$stmt = $mysqli->prepare('SELECT DISTINCT(AthleteName) FROM personalbest WHERE AthleteName LIKE ? ORDER BY AthleteName');
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
	$stmt->bind_result($athleteName);
	
	ob_start();
	echo 'Select the swimmer you wish to see results for'. PHP_EOL;
	echo '<table>'. PHP_EOL; 
	while ($stmt->fetch()) {
		echo '<tr>';
		$argument = "'$athleteName'";
		echo '<td><a onclick="ajaxndscresults_swimmerresults('. $argument .')" >';
		echo "$athleteName</a></td>";
		echo '</tr>'. PHP_EOL;
	}
	echo '</table>';	
	
	$output .= ob_get_contents();
	ob_end_clean();
	$mysqli->close();
	return $output;		
}

// return html to display a table of personal best times for the named swimmer
// TODO: Get long course results
function display_swimmer_results ($swimmerName)
{
	global $db_name;
	$mysqli = openDatabase($db_name);
	$courses = array("S", "L");
	$retVal = "Results for $swimmerName";

	foreach ($courses as $course) {
		$sql = "SELECT `Stroke`, `Distance`, `PrintableTime`, `Course`, DATE_FORMAT(MeetDate, '%d-%m-%y') AS MeetDateFormatted,`MeetName`\n"
			. " FROM `personalbest` WHERE `AthleteName` = \"$swimmerName\" AND Course = \"$course\""
			. " ORDER BY `Course` DESC, `Stroke`, CAST(`Distance` AS UNSIGNED)";
		//$retVal .= "$sql<p>";
		$result= $mysqli->query($sql);
		if ($result) {
			if ($result->num_rows != 0) {
				$retVal .= extract_course_results ($result);
			}
		}
	}
	$mysqli->close();
	$retVal .= "<p/>Results are extracted from the club database and may be out of date. If you attend a non-club meet and wish to have results included ";
	$retVal .= "please notify Sally Cowan with a <i>link to the results on the host club website</i> and copy your coach.";

	return $retVal;
}

// Take a mysqli_result and return HTML table of results
function extract_course_results ($result)
{
		ob_start();
		echo '<table class="ndsc-results-table">'; 
		echo '<tr><th>Course</th><th>Stroke</th><th>Distance</th><th>Time</th><th>Date</th><th>Meet</th></tr>';
		
		while ($row = $result->fetch_assoc()) {
			extract($row);
			echo '<tr>';
			echo "<td> $Course </td>";
			echo "<td> $Stroke </td>";
			echo '<td style="text-align:right">' . "$Distance </td>";
			echo '<td style="text-align:right">' . "$PrintableTime </td>";
			echo "<td> $MeetDateFormatted</td>";
			echo "<td> $MeetName </td>";
			echo '</tr>'. PHP_EOL;
			}
		echo '</table>';
		// close result set 
		$result->close();
		
		$output .= ob_get_contents();
		ob_end_clean();
		return $output;	

}

// import personal best times from uploaded csv
function import_personalbest ($filePath)
{
	global $db_name;
	$mysqlix = openDatabase($db_name);
	
	if (mysqli_connect_errno()) {
	    printf("Connect failed: %s\n", mysqli_connect_error());
	    exit();
	}
	
	truncateTable ($mysqlix, "personalbest");

	$sql = 	"LOAD DATA LOCAL INFILE  '$filePath' ". 
		"REPLACE INTO TABLE `personalbest` ".
		"FIELDS TERMINATED BY ',' ENCLOSED BY '\"' LINES TERMINATED BY '\\r\\n' ".
		"(MeetName, @mdate, Distance, Course, Stroke, AthleteName, ConvertedTime, PrintableTime, Sex) ".
		"set MeetDate = STR_TO_DATE(@mdate, '%d/%m/%Y')";
	// debug echo $sql. "<br>";
	//Try to execute query (not stmt) and catch mysqli error from engine and php error
	if (!($stmt = $mysqlix->query($sql))) {
	    echo "\nQuery execute failed: ERRNO: (" . $mysqlix->errno . ") " . $mysqlix->error . "<br>";
	}
	echo "Done import PB<br>";
}

// import rankings from csv
function import_rankings ($filePath, $toptimes_date)
{
	global $db_name;
	$mysqlix = openDatabase($db_name);
	
	if (mysqli_connect_errno()) {
	    printf("Connect failed: %s\n", mysqli_connect_error());
	    exit();
	}
	
	truncateTable ($mysqlix, "toptimes");
	
	$sql = 	"LOAD DATA LOCAL INFILE  '$filePath' ". 
		"REPLACE INTO TABLE `toptimes` ".
		"FIELDS TERMINATED BY ',' ENCLOSED BY '\"' LINES TERMINATED BY '\\r\\n' ".
		"(MeetName, @mdate, Distance, Course, Stroke, AthleteName, ConvertedTime, Age, PrintableTime, Sex) ".
		"set MeetDate = STR_TO_DATE(@mdate, '%d/%m/%Y')";
	// Debug echo $sql. "<br>";
	//Try to execute query (not stmt) and catch mysqli error from engine and php error
	if (!($stmt = $mysqlix->query($sql))) {
	    echo "\nQuery execute failed: ERRNO: (" . $mysqlix->errno . ") " . $mysqlix->error . "<br>";
	}
	
	// set the top times date
	$sql = "UPDATE settings SET value = \"$toptimes_date\" WHERE name=\"toptimes_date\" ";
	if (!($stmt = $mysqlix->query($sql))) {
	    echo "\nQuery execute failed: ERRNO: (" . $mysqlix->errno . ") " . $mysqlix->error . "<br>";
	}
	
	echo "Done import rank<br>";
}

function truncateTable ($mysqlix, $tableName)
{
	$sql = 	"TRUNCATE TABLE " . $tableName;
	setResults ($mysqlix, $sql);
}


// open the ndsc database and return sqli object
function openDatabase ($dbname)
{
	$mysqli = new mysqli ("localhost", "ndsccouk_swimres", "6fac7fe3e7", $dbname);
	if (mysqli_connect_error()) {
	    echo 'Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error();
	}
	//echo 'Success... ' . $mysqli->host_info . "\n";	
	return $mysqli;
}

/* return name of current default database */
function reportDatabase ($mysqli)
{
	if ($result = $mysqli->query("SELECT DATABASE()")) {
	    $row = $result->fetch_row();
	    printf("Default database is %s.<p>\n", $row[0]);
	    $result->close();
	}
}

// perform a simple quesry and return results
function getResults ($mysqli, $sql)
{
	if (($res = $mysqli->query($sql)) == FALSE) {
		echo "Could not successfully run query ($sql) from DB: " .  $mysqli->error;
	}
	return $res;
}

// perform a simple quesry and return results
function setResults ($mysqli, $sql)
{
	if (($res = $mysqli->query($sql)) == FALSE) {
		echo "Could not successfully run query ($sql) from DB: " .  $mysqli->error;
	}
}

?>