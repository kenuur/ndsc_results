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
        return NULL;
    }

    // save it in the session
    $stmt->bind_result($name, $event_index, $active, $event_cost_pence, $closing_date);

    $result = array();

    //$row = $res->fetch_assoc();
    //extract($row);
    $stmt->fetch();

    $result['gala_name'] = $name;
    $result['gala_live'] = $active;
    $result['event_cost_pence'] = $event_cost_pence;
    $result['gala_index'] = $event_index;
    $result['closing_date'] = $closing_date;
    return $result;
}

function searchForSwimmer ($searchString) 
{
    global $db_gala_name;
    $mysqli = openDatabase($db_gala_name);
    if ($stmt = $mysqli->prepare('SELECT AthleteName, AsaNo FROM athletes WHERE AthleteName LIKE ? ORDER BY AthleteName')) {
        $stmt->bind_param('s', $searchString);
        $stmt->execute();
        $stmt->store_result();
    }
    $results = array();

    // Load the results into an array
    $stmt->bind_result($athleteName, $asaNo);

    while ($stmt->fetch()) {
        $results[$asaNo] = $athleteName;
    }
    $mysqli->close();
    
    return $results;
}
