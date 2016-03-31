<?php

$db_gala_name = "ndsccouk_swim_gala";

// Load the requested gala basic values and return in an array to display
// the Gala name
function load_gala ($gala_index)
{
    global $db_gala_name;
    $mysqli = openDatabase($db_gala_name);

    $stmt = $mysqli->prepare('SELECT `name`, `active`, `event_cost_pence`, UNIX_TIMESTAMP(`closing_date`) FROM `gala_list` WHERE `gala_index` = ?');
    $stmt->bind_param('i', $gala_index);
    $stmt->execute();
    $stmt->store_result();

    // Check to see only one event matches
    if ($stmt->num_rows != 1) {
        return NULL;
    }

    // save it in the session
    $stmt->bind_result($name, $active, $event_cost_pence, $closing_date);

    $result = array();

    //$row = $res->fetch_assoc();
    //extract($row);
    $stmt->fetch();

    $result['gala_name'] = $name;
    $result['gala_live'] = $active;
    $result['event_cost_pence'] = $event_cost_pence;
    $result['gala_index'] = $gala_index;
    $result['closing_date'] = $closing_date;
    return $result;
}


// return an array of swimmers that match a partial name search
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

// fetch the specified swimmers details from the ASA number
function fetchSwimmer($swimmerID)
{
    global $db_gala_name;
    $mysqli = openDatabase($db_gala_name);
    $sql = "SELECT AthleteName, Sex, BirthDate, Squad FROM `athletes` WHERE (AsaNo='$swimmerID')";
    $res = getResults ($mysqli, $sql);
    
    $results = array();
    // check the number of matches, should only be one
    if ($res->num_rows == 0) {
        $results['error'] = "Somthing went wrong, couldn't find swimmer: ". $swimmerID;
        return $results;
    }
    if ($res->num_rows > 1) {
        $results['error'] = "Somthing went wrong, more than one swimmer with that ID: ". $swimmerID;
        return $results;
    }
    
    // load the one record into the result array to return
    $row = $res->fetch_assoc();
    extract($row);
    // setup the session variables
    $results['swimmer_name']= $AthleteName;
    $results['athlete_squad']=$Squad;
    $results['gender']=$Sex;
    $results['BirthDate']=$BirthDate;
    return $results;
}

// return an array of events available to enter for a given meet and gender
// TODO refine to limit by squad
// TODO give warning message where permission is needed
function fetchEvents($gala, $sex) {
    $events = array();
    global $db_gala_name;
    $mysqli = openDatabase($db_gala_name);
    if ($stmt = $mysqli->prepare('SELECT sex, event_name, event_type, event_number FROM gala_events WHERE gala = ? AND sex = ?')) {
        $stmt->bind_param('ss', $gala, $sex);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($sex, $eventName, $eventType, $eventNumber);
        
        while ($stmt->fetch()) {
            $event = array();
            $event['sex']=$sex;
            $event['name']=$eventName;
            $event['type']=$eventType;
            $event['number']=$eventNumber;
            $events[]=$event;
        }
    }
    
    return $events;
}