<?php

$db_gala_name = "ndsccouk_swim_gala";

// swimming gala entry pages
// gala details are loaded from the db and the user is allowed to search for the
// swimmer to enter. 
// Clicking on the user displays a list of events to enter

// get the gala info from the database
// and save it in the session
function gala_entry_start_func ($atts) {
	$mysqli = openDatabase($db_gala_name);

	extract( shortcode_atts( array('gala_index' => '1'), $atts ) );

	$sql = "SELECT `name`, `index`, `active`, `event_cost_pence` FROM `gala_list` WHERE `index` = {$gala_index}";
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


// Find the swimmer to enter
// function to display search dialog for swimer PBs
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

 // register swimmer search handler with Wordpress
add_action( 'wp_ajax_nopriv_ajaxndscgala_swimmersearch_ajaxhandler', 'ajaxndscgala_swimmersearch_ajaxhandler' );
add_action( 'wp_ajax_ajaxndscgala_swimmersearch_ajaxhandler', 'ajaxndscgala_swimmersearch_ajaxhandler' ); 

// queue the js scripts so they are included on the page
function ajaxndscgala_enqueuescripts() {
	wp_enqueue_script('ajaxndscgala', AJAXNDSCRESULTSURL.'/js/ajaxndscgala.js', array('jquery'));
	wp_localize_script( 'ajaxndscgala', 'ajaxndscgalaajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
}
add_action('wp_enqueue_scripts', ajaxndscgala_enqueuescripts);