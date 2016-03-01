<?php
// NDSC Results Admin menus
// admin_menu.php

// action to add the options for upload
add_action( 'admin_menu', 'my_plugin_menu' );

// function to add the menu option
function my_plugin_menu() {
	add_options_page( 'NDSC Results Options', 'NDSC Results', 'manage_options', 'ndsc_options', 'ndsc_plugin_options' );
}

// displays the options page
function ndsc_plugin_options() {
	$hidden_field_name = 'submit_hidden';
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	if( isset($_POST[ $hidden_field_name  ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
		check_for_uploaded_files  ();
	}
	// The Form
?>
	<div class="wrap">
	<h2>NDSC Results Setup</h2>
	<form action="" method="post" enctype="multipart/form-data">
		<h3>Personal Best Times</h3>
		<input type="hidden" name="<?php echo $hidden_field_name ; ?>" value="Y">
	
		<table class="form-table">
			<tr>
			<th scope="row"><label for="file">Upload PB File</label></th>
			<td><input type="file" name="filepb" id="filepb">
			<p class="description">.csv file of personal best results</p></td>
			</tr>
		</table>
		<h3>Rankings</h3>
	
		<table class="form-table">
			<tr>
			<th scope="row"><label for="filerank">Upload Ranking File</label></th>
			<td><input type="file" name="filerank" id="filerank">
			<p class="description">.csv file of ranking results</p></td>
			</tr>
			<tr>
			<th scope="row"><label for="toptimes_date">Toptimes Date</label></th>
			<td><input type="text" name="toptimes_date" id="toptimes_date">
			<p class="description">Date of year end for rankings</p></td>
			</tr>
		</table>
		<input type="submit" name="submit" value="Upload" class="button button-primary"></p>
	</form>
	</div>
	
	
<?php
}

// looks for files uploaded for PB and rankings and imports them if provided
function check_for_uploaded_files () {
	// Check for personal best file
	if (is_file_uploaded("filepb")) {
		import_personalbest (temp_upload_name ("filepb"));
	}
	
	// check for ranking file
	if ( is_file_uploaded("filerank") ) {
		import_rankings (temp_upload_name ("filerank"), toptimes_date ());
	}
	
}

// returns true if file has been uploaded
function is_file_uploaded ($filename) {
	$file_uploaded = false;
	if ( isset($_FILES[$filename]["name"]) and ($_FILES[$filename]["error"] != UPLOAD_ERR_NO_FILE)) { 
		if ($_FILES[$filename]["error"] > 0) { 
			echo "Error uploading file ". $filename . " - ". $_FILES[$filename]["error"];
		}
		else if(!file_exists($_FILES[$filename]['tmp_name']) || !is_uploaded_file($_FILES[$filename]['tmp_name'])) {
			echo 'Error storing file';
		}
		else {
			echo "File uploaded: ". $filename . " - " .$_FILES[$filename]["tmp_name"];
			$file_uploaded = true;
		}
	}
	return $file_uploaded;
}

// returns the temp name for an uploaded file
function temp_upload_name ($filename) {
	return ($_FILES[$filename]["tmp_name"]);
}

// gets the year end date for rankings
function toptimes_date () {
	return $_POST[ "toptimes_date" ];
}


?>