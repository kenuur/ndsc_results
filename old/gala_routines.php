<?php

// open the ndsc database and return sqli object
function openNDSC ()
{
	$mysqli = new mysqli ("localhost", "root", "root", "ndsc");
	if ($mysqli->connect_errno) {
		echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
		exit;
	}
	return $mysqli;
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

// Handle times entered by the user
// Convert to the format we want to store in the DB
// input can be blank (current PB), NT (No time) or a correctly formatted time
function handleEntryTime ($user_input)
{
	$user_input = trim ($user_input);
	if (strlen ($user_input) == 0) {
		return "Current PB";
	}
	if (strcasecmp ($user_input, "NT") == 0) {
		return "No Time";
	}
	// if we get this far we must have a time, check it matches a valid format
	// ss.ss, m:ss.ss, mm:ss.ss
	$pattern = '/^([1-2]?[0-9]:)?[0-5][0-9]\.[0-9]{2}$/';
	if (preg_match ($pattern, $user_input)) {
		return $user_input;
	}
	return null;
}
?>