<?php 
////////////////////////////////////////////////////
/////////// IF IMPORING USERS //////////////////////
////////////////////////////////////////////////////

// security check
if(!isset($_POST['pc_import_users'])) {die('Nice try!');}
if (!isset($_POST['pc_nonce']) || !wp_verify_nonce($_POST['pc_nonce'], 'lcwp_nonce')) {die('<p>Cheating?</p>');};

include_once(PC_DIR . '/functions.php');	
include_once(PC_DIR . '/classes/simple_form_validator.php');
include_once(PC_DIR . '/classes/pc_form_framework.php');
global $wpdb, $pc_users;
	
$f_fw = new pc_form;
$validator = new simple_fv;
$indexes = array();

//$indexes[] = array('index'=>'pc_imp_file', 'label'=>__('CSV file', 'pc_ml'), 'mime_type'=>array('application/vnd.ms-excel', 'application/octet-stream', 'application/csv', 'text/csv'), 'required'=>true);
$indexes[] = array('index'=>'pc_imp_separator', 'label'=>__("Field Delimiter", 'pc_ml'), 'required'=>true, 'max_len'=>1);
$indexes[] = array('index'=>'pc_imp_pvt_page', 'label'=>"Enable Pvt Page");
$indexes[] = array('index'=>'pc_imp_cat', 'label'=>__('Category', 'pc_ml'), 'required'=>true);
$indexes[] = array('index'=>'pc_imp_ignore_first', 'label'=>"Ignore first row");
$indexes[] = array('index'=>'pc_imp_error_stop', 'label'=>"Stop if errors found");
$indexes[] = array('index'=>'pc_imp_existing_stop', 'label'=>"Stop if duplicated found");
$indexes[] = array('index'=>'pc_wps_error_stop', 'label'=>"Stop if wp sync fails");
$indexes[] = array('index'=>'pc_cfi_import', 'label'=>"Custom fields");

$validator->formHandle($indexes);
$fdata = $validator->form_val;

// more compatible upload validation
if(!isset($_FILES["pc_imp_file"]) || !isset($_FILES["pc_imp_file"]["tmp_name"]) || trim($_FILES["pc_imp_file"]["tmp_name"]) == '') {
	$validator->custom_error[__("CSV file", 'pc_ml')] =  __("is missing", 'pc_ml');
}
if( pc_stringToExt(strtolower($_FILES["pc_imp_file"]["name"])) != '.csv'){
	$validator->custom_error[__("CSV file", 'pc_ml')] =  __("invalid file uploaded", 'pc_ml');
}

$error = $validator->getErrors();
if($error) {$error = '<div class="error"><p>'.$error.'</p></div>';}
else {
	mb_internal_encoding('utf-8');
	$tmp_file = $_FILES["pc_imp_file"]["tmp_name"];

	// manage CSV and save data
	$imp_err = array();
	$imp_username_exist = array();
	$imp_mail_exist = array();
	$img_wps_exists = array();
	$imp_data = array();
	
	if (($handle = fopen($tmp_file, "r")) !== false) {
		
		$row = 1;
		$fields = 6; // mandatory number of fields (name, surname, username, psw, mail, tel)
		
		while (($data = fgetcsv($handle, 0, $fdata['pc_imp_separator'])) !== FALSE) {
			if(!$fdata['pc_imp_ignore_first'] || ($fdata['pc_imp_ignore_first'] && $row > 1)) {
			
				$pcud_additional_f = (isset($_POST['pc_cfi_import'])) ? count($_POST['pc_cfi_import']) : 0;
				if(count($data) != ($fields + $pcud_additional_f)) {  
					$error = __('Row '.$row.' has a wrong number of values', 'pc_ml');
					break;
				}

				// validate data
				if(trim($data[2]) == '' || trim($data[3]) == '' || (trim($data[4]) != '' && !filter_var(trim($data[4]), FILTER_VALIDATE_EMAIL))) {
					$imp_err[] = $row;
				}
				else {
					
					//// check username and eventually mail unicity
					// mail check
					$mail_ck_query = ($f_fw->mail_is_required && !empty($data[4]) && $fdata['pc_imp_existing_stop']) ? "OR email = '".addslashes(trim($data[4]))."'" : '';  
					$existing_user = $wpdb->get_row( 
						"SELECT username, email FROM ".PC_USERS_TABLE." WHERE (username = '".addslashes(trim($data[2]))."' ".$mail_ck_query.") AND status != 0 LIMIT 1" 
					);
					if($existing_user) {
						if(trim($data[2]) == $existing_user->username) {$imp_username_exist[] = $row;}
						if(trim($data[4]) == $existing_user->email && $f_fw->mail_is_required) {$imp_mail_exist[] = $row;}
					}

					// add user to list
					else {
						$wp_sync = $pc_users->wp_user_sync;
						
						// WP user sync check
						if($fdata['pc_wps_error_stop'] && trim($data[4]) && wp_user_exists(trim($data[2]), trim($data[4])) ) {
							$img_wps_exists[] = $row;
						}
						
						// clean data
						$data = pc_strip_opts($data);
			
						$imp_data[$row]['name'] = trim($data[0]);
						$imp_data[$row]['surname'] = trim($data[1]);
						$imp_data[$row]['username'] = trim($data[2]);
						$imp_data[$row]['psw'] = $data[3];
						$imp_data[$row]['email'] = trim($data[4]);
						$imp_data[$row]['tel'] = trim($data[5]);
						$imp_data[$row]['disable_pvt_page'] = (!$fdata['pc_imp_pvt_page']) ? 1 : 0;
						$imp_data[$row]['categories'] = (array)$fdata['pc_imp_cat'];
						
						// custom fields import
						if(is_array($fdata['pc_cfi_import']) && count($fdata['pc_cfi_import'])) {
							$index = 6;
							
							foreach($fdata['pc_cfi_import'] as $field) {
								$imp_data[$row][$field] = $data[$index];
								$index++;	
							}
						}	
					}
				}
			}
			$row++;
		}
		fclose($handle);
		
		// if CSV file management is ok
		if(!$error) {
			
			// if there are errors and abort import 
			if($fdata['pc_imp_error_stop'] && count($imp_err) > 0) {
				$error = __('Missing values have been found in rows','pc_ml').': ' . implode(', ', $imp_err);	
			}
			elseif($fdata['pc_imp_existing_stop'] && (count($imp_username_exist) > 0)) {
				$error = __('Users with existing username have been found at rows','pc_ml').': ' . implode(', ', $imp_username_exist);	
			}
			elseif($fdata['pc_imp_existing_stop'] && (count($imp_mail_exist) > 0)) {
				$error = __('Users with existing e-mail have been found at rows','pc_ml').': ' . implode(', ', $imp_mail_exist);	
			}
			elseif($fdata['pc_wps_error_stop'] && (count($img_wps_exists) > 0)) {
				$error = __('Wordpress mirror users already existat rows','pc_ml').': ' . implode(', ', $img_wps_exists);	
			}

			// import
			else {
				$imported_list = array(); // users ID array for action
				
				foreach($imp_data as $user_row => $u_data) {
					$user_id = $pc_users->insert_user($u_data, $status = 1, $allow_wp_sync_fail = true);
					
					if(empty($user_id)) {
						$error = __('Error importing user "'. $u_data['username'] .'"', 'pc_ml') .' - '. $pc_users->validation_errors;
						break;
					}
					else {
						$imported_list[$user_id] = $u_data;
					}	
				}
			}
			
			////////////////////////////////////////
			// success message
			if(!$error) {
				$success = '
				<div class="updated"><p><strong>'. __('Import completed succesfully', 'pc_ml') .'</strong><br/><br/>
					Users added: '.count($imp_data);

					if(count($imp_err) > 0)	{ 
						$success .= '<br/>'. __('Missing values', 'pc_ml') .': '.count($imp_err).' ('. __('at rows', 'pc_ml') .': '.implode(',', $imp_err).')';
					}
					
					if(count($imp_username_exist) > 0)	{ 
						$success .= '<br/>'.count($imp_username_exist).' '. __('existing users', 'pc_ml').' ('. __('at rows', 'pc_ml') .': '.implode(',', $imp_username_exist).')';
					}
					
					if(count($imp_mail_exist) > 0)	{ 
						$success .= '<br/>'.count($imp_mail_exist).' '. __('duplicated e-mails', 'pc_ml').' ('. __('at rows', 'pc_ml') .': '.implode(',', $imp_mail_exist).')';
					}
					
					if(count($img_wps_exists) > 0)	{ 
						$success .= '<br/>'.count($img_wps_exists).' '. __('existing WP mirror users', 'pc_ml').' ('. __('at rows', 'pc_ml') .': '.implode(',', $img_wps_exists).')';
					}

				$success .= '</p></div>';	
				
				
				// PC-ACTION - users have been imported - passes an array containing user ids and related import data
				do_action('pc_imported_users', $imported_list);
			}
		}
		
	} 
	else {$error = __('Temporary file cannot be read', 'pc_ml');}
	
	
	if($error) {$error = '<div class="error"><p>'.$error.'</p></div>';}
}
