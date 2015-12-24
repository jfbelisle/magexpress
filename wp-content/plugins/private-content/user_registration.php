<?php
// script to handle AJAX request and register the user


// HANDLE AJAX FORM SUBMIT
add_action('wp_loaded', 'pc_register_user', 1);
function pc_register_user() {
	global $wpdb, $pc_users;
		
	if(isset($_POST['type']) && $_POST['type'] == 'pc_registration') {		
		require_once(PC_DIR .'/classes/pc_form_framework.php');
		require_once(PC_DIR .'/classes/recaptchalib.php');
		include_once(PC_DIR .'/functions.php');

		////////// VALIDATION ////////////////////////////////////

		$term = get_term( (int)$_REQUEST['form_id'], 'pc_reg_form');
		if(!$term) {
			$mess = json_encode(array( 
				'resp' => 'error',
				'mess' => __('Form not found', 'pc_ml')
			));
			die($mess);	
		}
		
		$GLOBALS['pc_custom_cat_name'] = true;
		$f_fw = new pc_form(array(
			'use_custom_cat_name' => true,
			'strip_no_reg_cats' => true
		));
		
		$form_structure = unserialize(base64_decode($term->description));
		$antispam = get_option('pg_antispam_sys', 'honeypot');

		// custom validation indexes
		$custom_indexes = array();
		$indexes = $f_fw->generate_validator($form_structure, $custom_indexes);
		
		
		//// prior custom validation
		$cust_errors = array();
		if($antispam == 'honeypot') {
			if(!$f_fw->honeypot_validaton()) {
				$cust_errors[] = "Antispam - we've got a bot here!";	
			}
		}
		else {
			$privatekey = "6LfQas0SAAAAAIzpthJ7UC89nV9THR9DxFXg3nVL";
			$resp = pc_recaptcha_check_answer ($privatekey,
											$_SERVER["REMOTE_ADDR"],
											$_POST['recaptcha_challenge_field'],
											$_POST['recaptcha_response_field']);
											
			//var_dump($resp->is_valid);
			if (!$resp->is_valid) {
				$cust_errors[] = "reCAPTCHA - ". __("wasn't entered correctly", 'pc_ml');
			} 
		}

		// check disclaimer
		if(get_option('pg_use_disclaimer') && !isset($_POST['pc_disclaimer'])) {
			$cust_errors[] = __("Disclaimer", 'pc_ml') ." - ". __("must be accepted to proceed with registration", 'pc_ml');
		}
		
		// validation wrap-up
		$is_valid = $f_fw->validate_form($indexes, $cust_errors, false, false);	
		$fdata = $f_fw->form_data;
		
		if(!$is_valid) {
			$error = $f_fw->errors;
		}
		else {
			$status = (get_option('pg_registered_pending')) ? 3 : 1;
			$allow_wp_sync_fail = (!get_option('pg_require_wps_registration')) ? true : false;
			
			// if no categories field - use forced or default ones
			if(!isset($fdata['categories'])) {
				$fdata['categories'] = (isset($_POST['pc_cc']) && !empty($_POST['pc_cc'])) ? explode(',', $_POST['pc_cc']) : get_option('pg_registration_cat');
				if(isset($_POST['pc_cc']) && !empty($_POST['pc_cc'])) {$GLOBALS['pc_escape_no_reg_cats'] = true;} // flag to bypass reg cats restrictions
			}
			
			// private page switch - put in form data
			$fdata['disable_pvt_page'] = (get_option('pg_registered_pvtpage')) ? 0 : 1;
			
			// insert user
			$result = $pc_users->insert_user($fdata, $status, $allow_wp_sync_fail);
			if(!$result) {
				$error = $pc_users->validation_errors;	
			}
		}
		
		
		// results
		if(isset($error) && !empty($error)) {
			$mess = json_encode(array( 
				'resp' => 'error',
				'mess' => $error
			));
			die($mess);
		}
		else {
			// PC-ACTION - registered user - passes new user ID and status
			do_action('pc_registered_user', $result, $status);	
			
			// success message
			$mess = json_encode(array( 
				'resp' 		=> 'success',
				'mess' 		=> pc_get_message('pc_default_sr_mex'),
				'redirect'	=> pc_man_redirects('pg_registered_user_redirect')
			));
			die($mess);
		}
		
		die(); // security block
	}
}
