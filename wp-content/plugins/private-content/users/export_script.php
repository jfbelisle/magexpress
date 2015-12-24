<?php 
////////////////////////////////////////////////////
/////////// IF EXPORTING USERS DATA ////////////////
////////////////////////////////////////////////////

// security check
if(!isset($_POST['pc_export_user_data'])) {die('<p>Nice try!</p>');}
if (!isset($_POST['pc_nonce']) || !wp_verify_nonce($_POST['pc_nonce'], 'lcwp_nonce')) {die('<p>Cheating?</p>');};

global $pc_users;
include_once(PC_DIR . '/classes/simple_form_validator.php');		
		
$validator = new simple_fv;
$indexes = array();

$indexes[] = array('index'=>'users_type', 'label'=>__( 'Users type', 'pc_ml' ), 'required'=>true);
$indexes[] = array('index'=>'export_type', 'label'=>__( 'Export type', 'pc_ml' ), 'required'=>true);
$indexes[] = array('index'=>'pc_all_cats', 'label'=>'export all');
$indexes[] = array('index'=>'pc_categories', 'label'=>'Categories');


$validator->formHandle($indexes);
$fdata = $validator->form_val;

if(empty($fdata['pc_all_cats']) && empty($fdata['pc_categories'])) {
	$validator->custom_error[ __('Exported categories', 'pc_ml') ] = __('Choose at least one option', 'pc_ml');
}
$error = $validator->getErrors();

if($error) {$error = '<div class="error"><p>'.$error.'</p></div>';}
else {
	require_once(PC_DIR . '/classes/pc_form_framework.php');
	require_once(PC_DIR . '/functions.php');
	
	$f_fw = new pc_form;
	
	// clean buffer to avoid php warnings and start again to catch data 
	ob_end_clean();
	ob_start();
	
	// status to export
	switch($fdata['users_type']) {
		case 'disabled' : $status = array(2); break;
		case 'actives'	: $status = array(1); break;	
		default 		: $status = array(1,2); break;
	}
	
	// what to export - associative array index => label
	$to_get = array('id' => 'ID');
	
	foreach($f_fw->fields as $key => $data) {
		if(!in_array($key, array('psw', 'pc_disclaimer'))) {
			$to_get[$key] = $data['label'];	
		}
	}
	
	$to_get = array_merge(
		$to_get,
		array(
			'insert_date' 	=> __('Registered on', 'pc_ml'),
			'last_access' 	=> __('Last access', 'pc_ml'),		
		)
	);
	
	if($fdata['users_type'] == 'all') {
		$to_get['status'] = __('Status', 'pc_ml');	
	}
	
	// PC-FILTER - add export fields - associative array(db_key => name)
	$to_get = apply_filters('pc_export_fields', $to_get);
	
	// users query
	$args = array(
		'limit' 	=> 9999999,
		'to_get'	=> array_keys($to_get),
		'search'	=> array(
			array('key'=>'status', 'val'=>$status, 'operator'=>'IN')
		) 
	);
	
	// in case of specific cats fetching
	if(empty($fdata['pc_all_cats'])) {
		$args['categories'] = (array)$fdata['pc_categories'];
	}
	
	$exp_query = $pc_users->get_users($args); 
	if(!is_array($exp_query) || !count($exp_query)) {die('no users found');}

	
	///////////////////////////////////////
	// CSV ////////////////////////////////
	if($fdata['export_type'] == 'csv') {		
		$fh = @fopen('php://output', 'w');
		
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header('Content-Description: File Transfer');
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=pc_users_data_".date('Y_m_d').".csv");
		header("Pragma: public");
		header("Expires: 0");
		
		// headings
		fputcsv($fh, $to_get);	  
		
		//data
		foreach($exp_query as $user) {
			$sanitized = array();
			
			foreach($to_get as $key => $label) {
			  	$val = $user[$key];
				$sanitized[] = $pc_users->data_to_human($key, $val, true);	
			}

			fputcsv($fh, $sanitized);
		}
		
		fclose($fh);
		die();
	}
	
	
	
	///////////////////////////////////////
	// EXCEL //////////////////////////////
	elseif($fdata['export_type'] == 'excel') {

		echo '
		<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
		<html>
		<head></head>
		<body>
		';

		// headings
		print '
		<table border="1" cellspacing="0" cellpadding="3">
		  <tr>
			';
			
			foreach($to_get as $key => $colname) {
				print '<th scope="col">'.mb_convert_encoding($colname, 'HTML-ENTITIES', 'utf-8').'</th>';	
			}
		  
		  print '</tr>';
		 
		 
		  // body
		  foreach($exp_query as $user) {			  
			  print '<tr>';
			  
			  foreach($to_get as $key => $label) {
			  	$val = $user[$key];
				print '<td>'. mb_convert_encoding( $pc_users->data_to_human($key, $val, true) ,'HTML-ENTITIES','utf-8') .'</td>';	
			  }
			  
			  print '</tr>'; 
		 }
		 
		  print '
		  </table>
		  </body>
		  </html>
		  ';
		  
		  $contents = ob_get_contents();
		  ob_end_clean();
		  
		  header ("Content-Type: application/vnd.ms-excel; charset=UTF-8");
		  header ("Content-Disposition: inline; filename=pc_users_data_".date('Y_m_d').".xls");
		  
		  print $contents;
		die();		
	}
	
}	
