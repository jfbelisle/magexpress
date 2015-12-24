<?php
// Handle AJAX call for custom forms

add_action('init', 'pcud_handle_custom_form', 5); // use 5 to be sure taxonomies have been registered and auths performed

function pcud_handle_custom_form() {
	if(isset($_POST['type']) && $_POST['type'] == 'pcud_cf_submit') {
		require_once(PC_DIR .'/classes/pc_form_framework.php');
		require_once(PCUD_DIR .'/functions.php');
		global $wpdb, $pc_users;
		
		$f_fw = new pc_form;
		$form_id = (int)$_POST['pcud_fid']; 
		
		// check for logged users
		$pc_logged = pc_user_logged(false);
		if(!$pc_logged && !current_user_can(get_option('pg_min_role', 'upload_files'))) {
			die( json_encode(array( 
				'resp' => 'error',
				'mess' => __('You must be logged to use this form', 'pcud_ml') 
			)));
		}


		////////// VALIDATION ////////////////////////////////////
		
		// get form structure	
		$term = get_term_by('id', $form_id, 'pcud_forms');
		if(empty($term)) {
			die( json_encode(array( 
				'resp' => 'error',
				'mess' => __('Form not found', 'pcud_ml') 
			)));
		}
		
		if(empty($term->description)) {
			// retrocompatibility
			$form_fields = (array)get_option('pcud_form_'.$form_id, array());	
		} else {
			$form_fields = unserialize(base64_decode($term->description));	
		}

	
		$indexes = $f_fw->generate_validator( pcud_v2_field_names_sanitize($form_fields) );
		
		$is_valid = $f_fw->validate_form($indexes, $cust_errors = array(), false, false);	
		$fdata = $f_fw->form_data;
		
		if(!$is_valid) {
			$error = $f_fw->errors;
		}
		else {
			// check for redirects
			if(isset($form_fields['redirect']) && !empty($form_fields['redirect'])) {
				$redirect = ($form_fields['redirect'] == 'custom') ? $form_fields['cust_redir'] : get_permalink($form_fields['redirect']);
			}
			else {$redirect = '';}
			
			// if not PC user - stop here
			if(!$pc_logged) {
				die( json_encode(array( 
					'resp' 		=> 'success',
					'mess' 		=> __('Form submitted successfully.<br/> Not logged as PrivateContent user, nothing has been saved', 'pcud_ml'),
					'redirect'	=> $redirect
				)));	
			}

			// update user
			$result = $pc_users->update_user($GLOBALS['pc_user_id'], $fdata);
			if(!$result) {
				$error = $pc_users->validation_errors;	
			}
		}

		// results
		if(isset($error) && !empty($error)) {
			die( json_encode(array( 
				'resp' => 'error',
				'mess' => $error
			)));
		}
		else {
			// if is updating password - sync also cookie
			if(isset($fdata['psw'])) {
				$encrypted = $pc_users->get_user_field($user_id, $field);
				
				setcookie('pc_user', $GLOBALS['pc_user_id'].'|||'. $encrypted, time() + (3600 * 6), '/');
			}
			
			// PCUD-ACTION - user updated its data - passes form data
			do_action('pcud_user_updated_data', $fdata);	
			
			// success message
			$mess = json_encode(array( 
				'resp' 		=> 'success',
				'mess' 		=> __('Data saved succesfully', 'pc_ml'),
				'redirect'	=> $redirect
			));
			die($mess);
		}
		
		die(); // security block
	}
}
