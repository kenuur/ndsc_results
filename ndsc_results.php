<?php
/**
 * Plugin Name: NDSC Results
 * Plugin URI: http://ndsc.co.uk
 * Description: Top times for Newbury Swimming Club.
 * Version: 1.0
 * Author: Ian Crane
 * License: 
 */
 
 require_once 'db_routines.php';
 require_once 'admin_menu.php';
 require_once 'gala_entry.php';
 require_once 'db_gala_routines.php';


// Form to select the desired top times results and
// Ajax link in the page to fetch results
function ajaxndscresults_showtoptimes(){
	
	if (toptimes_enabled_check()) {
		ob_start();
	
		// form to select the event options
		echo '<form id="toptimes" onSubmit="return ajaxndscresults_toptimes()">'. PHP_EOL;
		// stroke
		echo 'Stroke:<select name="Stroke" id="stroke" onChange="topTimes_strokeChange()">'. PHP_EOL;
		echo 	'<option value="Free">Free</option>'. PHP_EOL;
		echo 	'<option value="Breast">Breast</option>'. PHP_EOL;
		echo 	'<option value="Fly">Fly</option>'. PHP_EOL;
		echo 	'<option value="Back">Back</option>'. PHP_EOL;
		echo 	'<option value="IM">IM</option>'. PHP_EOL;
		echo 	'</select>'. PHP_EOL;
		echo 	'Distance:<select name="Distance" id="distance">'. PHP_EOL;
		echo	'<option value="12U_50">12 and Under 50m</option>'. PHP_EOL;
		echo	'<option value="13O_50">13 and Over 50m</option>'. PHP_EOL;
		echo	'<option value="12U_100">12 and Under 100m</option>'. PHP_EOL;
		echo	'<option value="13O_100">13 and Over 100m</option>'. PHP_EOL;
		echo	'<option value="200">Open 200m</option>'. PHP_EOL;
		echo	'<option value="400">Open 400m</option>'. PHP_EOL;
		echo	'<option value="800">Open 800m</option>'. PHP_EOL;
		echo	'<option value="1500">Open 1500m</option>'. PHP_EOL;
		echo 	'</select>'. PHP_EOL;
		echo 	'Sex:<select name="Sex" id="sex">'. PHP_EOL;
		echo 	'<option value="M">Male</option>'. PHP_EOL;
		echo 	'<option value="F">Female</option>'. PHP_EOL;
		echo    '</select>'. PHP_EOL;
		echo '<input type="submit" value="Go">';
		echo '</form>'. PHP_EOL;
		 
		// result area
		echo '<p><div id="showtoptimesresult">Select the combination of Stroke, Age, Distance and Gender you wish to see results for then click Go</div>'. PHP_EOL;
		
		$output = ob_get_contents();
		ob_end_clean();
		return $output;	
	}
 	else {
 		return "The top times database is down for maintenance, it will retun shortly. Press F5 to refresh.";
 	}
}
 
function ajaxloadpost_shortcode_function( $atts ){
    return ajaxndscresults_showtoptimes();
}
add_shortcode( 'AJAXTOPTIMES', 'ajaxloadpost_shortcode_function' );

// function to display search dialog for swimer PBs
// Includes div for Ajax to place matching swimmers
function ajaxndsc_showpbsearch (){
	ob_start();
	echo '<form id="personalbest" onSubmit="return ajaxndscresults_swimmersearch()">'. PHP_EOL;
	echo 'Swimmer Name:<input type="text" name="NameSearch"/>'. PHP_EOL;
	echo '<input type="submit" value="Search">'. PHP_EOL;
	echo '</form>'. PHP_EOL;
	// result area
	echo '<p><div id="showswimmersearchresult">Type part of the swimmer name you are searching for then click Go</div>'. PHP_EOL;
	echo '<p><div id="showswimmerresults"></div>'. PHP_EOL;
	
	
	$output = ob_get_contents();
	ob_end_clean();
	return $output;	
}
function ajaxshowpb_shortcode_function( $atts ){
    return ajaxndsc_showpbsearch();
}
add_shortcode( 'AJAXPERSONALBEST', 'ajaxshowpb_shortcode_function' );
 

 //Ajax functionality
 //Define the url for ajax calls back into this plugin
define('AJAXNDSCRESULTSURL', WP_PLUGIN_URL."/".dirname( plugin_basename( __FILE__ ) ) );

// queue the js scripts so they are included on the page
function ajaxndscresults_enqueuescripts() {
	wp_enqueue_script('ajaxndscresults', AJAXNDSCRESULTSURL.'/js/ajaxndscresults.js', array('jquery'));
	wp_localize_script( 'ajaxndscresults', 'ajaxndscresultsajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
}
add_action('wp_enqueue_scripts', ajaxndscresults_enqueuescripts);

// ajax handler to display the top times
function ajaxndscresults_ajaxhandler() {
	$form_data = $_POST['form_data'];
	parse_str ($form_data);
	//$results = "Stroke:$Stroke<p>Distance:$Distance<p>". PHP_EOL;
	$results = display_toptimes_func($Stroke, $Distance, $Sex);
	die($results);
}

// ajax handler to display the swimers search result
function ajaxndscswimmersearch_ajaxhandler() {
	$form_data = $_POST['form_data'];
	parse_str ($form_data);
	//$results = "Stroke:$Stroke<p>Distance:$Distance<p>". PHP_EOL;
	$results = display_swimmersearch_func($NameSearch);
	die($results);
}
// ajax handler to display the swimmers results
function ajaxndscswimmerresults_ajaxhandler() {
	$swimmerName =  $_POST['swimmerName'];
	$results = display_swimmer_results ($swimmerName );
	die($results );
}


// register top times handler with Wordpress
add_action( 'wp_ajax_nopriv_ajaxndscresults_ajaxhandler', 'ajaxndscresults_ajaxhandler' );
add_action( 'wp_ajax_ajaxndscresults_ajaxhandler', 'ajaxndscresults_ajaxhandler' ); 
 // register swimmer search handler with Wordpress
add_action( 'wp_ajax_nopriv_ajaxndscswimmersearch_ajaxhandler', 'ajaxndscswimmersearch_ajaxhandler' );
add_action( 'wp_ajax_ajaxndscswimmersearch_ajaxhandler', 'ajaxndscswimmersearch_ajaxhandler' ); 
 // register personal best handler with Wordpress
add_action( 'wp_ajax_nopriv_ajaxndscswimmerresults_ajaxhandler', 'ajaxndscswimmerresults_ajaxhandler' );
add_action( 'wp_ajax_ajaxndscswimmerresults_ajaxhandler', 'ajaxndscswimmerresults_ajaxhandler' ); 