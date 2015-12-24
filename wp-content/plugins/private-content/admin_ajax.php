<?php
////////////////////////////////////////////////
////// USERS LIST - BULK REMOVE USERS //////////
////////////////////////////////////////////////

function delete_pc_user_php() {
	if (!isset($_POST['pc_nonce']) || !wp_verify_nonce($_POST['pc_nonce'], 'lcwp_ajax')) {die('Cheating?');};
	
	$user_id = trim(addslashes($_POST['pc_user_id'])); 
	if (!filter_var($user_id, FILTER_VALIDATE_INT)) {die( __('Error processing the action', 'pc_ml') );}
	
	global $pc_users;
	$pc_users->delete_user($user_id);
	
	echo 'success';
	die();	
}
add_action('wp_ajax_delete_pc_user', 'delete_pc_user_php');



////////////////////////////////////////////////
////// USERS LIST - BULK ASSIGN CATEGORIES /////
////////////////////////////////////////////////

function pc_bulk_cat_change() {
	if (!isset($_POST['pc_nonce']) || !wp_verify_nonce($_POST['pc_nonce'], 'lcwp_ajax')) {die('Cheating?');};
	
	$users = (array)$_POST['users'];
	if(!count($users)) {die('select at least one user');}
	
	$cats = (array)$_POST['cats'];
	if(!count($cats)) {die('select at least one category');}
	
	global $wpdb;
	$rows_affected = $wpdb->query(
		$wpdb->prepare(
			"UPDATE ".PC_USERS_TABLE." SET categories = %s WHERE id IN (". addslashes(implode(',', $users)) .")",
			serialize($cats)
		)
	);
	
	if((int)$rows_affected != count($users)) {
		die('Error updating one or more users');	
	}

	die('success');	
}
add_action('wp_ajax_pc_bulk_cat_change', 'pc_bulk_cat_change');



/*******************************************************************************************************************/


////////////////////////////////////////////////
/// WP USER SYNC - MANUALLY SYNC SINGLE USER ///
////////////////////////////////////////////////

function pc_wp_sync_single_user() {
	if (!isset($_POST['pc_nonce']) || !wp_verify_nonce($_POST['pc_nonce'], 'lcwp_ajax')) {die('Cheating?');};
	include_once(PC_DIR . '/functions.php');
	
	global $pc_users, $pc_wp_user;
	$user_id = (int)$_POST['pc_user_id']; 
	
	$args = array('to_get' => array('username', 'psw', 'email', 'name', 'surname'));
	$ud = $pc_users->get_user($user_id, $args);
	if(empty($ud)) {die('user does not exist');}	
	
	$ud['psw'] = $pc_users->decrypt_psw($ud['psw']);
	$result = $pc_wp_user->sync_wp_user($ud, 0, true);	
	
	echo (!$result) ? $pc_wp_user->pwu_sync_error : 'success';
	die();	
}
add_action('wp_ajax_pc_wp_sync_single_user', 'pc_wp_sync_single_user');



//////////////////////////////////////////////////
/// WP USER SYNC - MANUALLY DETACH SINGLE USER ///
//////////////////////////////////////////////////

function pc_wp_detach_single_user() {
	if (!isset($_POST['pc_nonce']) || !wp_verify_nonce($_POST['pc_nonce'], 'lcwp_ajax')) {die('Cheating?');};
	include_once(PC_DIR . '/functions.php');
	
	global $pc_wp_user;
	$user_id = (int)$_POST['pc_user_id']; 
	
	$result = $pc_wp_user->detach_wp_user($user_id);
	
	echo ($result === true) ? 'success' : $result;
	die();	
}
add_action('wp_ajax_pc_wp_detach_single_user', 'pc_wp_detach_single_user');



////////////////////////////////////////////////
/// WP USER SYNC - GLOBAL SYNC /////////////////
////////////////////////////////////////////////

function pc_wp_global_sync() {
	if (!isset($_POST['pc_nonce']) || !wp_verify_nonce($_POST['pc_nonce'], 'lcwp_ajax')) {die('Cheating?');};
	global $pc_wp_user;
	
	echo $pc_wp_user->global_sync();
	die();	
}
add_action('wp_ajax_pc_wp_global_sync', 'pc_wp_global_sync');



////////////////////////////////////////////////
/// WP USER SYNC - GLOBAL DETACH ///////////////
////////////////////////////////////////////////

function pc_wp_global_detach() {
	if (!isset($_POST['pc_nonce']) || !wp_verify_nonce($_POST['pc_nonce'], 'lcwp_ajax')) {die('Cheating?');};
	global $pc_wp_user;
	
	echo $pc_wp_user->global_detach();
	die();	
}
add_action('wp_ajax_pc_wp_global_detach', 'pc_wp_global_detach');



////////////////////////////////////////////////////
/// WP USER SYNC - SERACH & SYNC EXISTING MATCHES //
////////////////////////////////////////////////////

function pc_wps_search_and_sync_matches() {
	if (!isset($_POST['pc_nonce']) || !wp_verify_nonce($_POST['pc_nonce'], 'lcwp_ajax')) {die('Cheating?');};
	global $pc_wp_user;
	
	echo $pc_wp_user->search_and_sync_matches();
	die();	
}
add_action('wp_ajax_pc_wps_search_and_sync_matches', 'pc_wps_search_and_sync_matches');



/*******************************************************************************************************************/


////////////////////////////////////////////////////
/// REGISTRATION FORMS - ADD FORM //////////////////
////////////////////////////////////////////////////

function pc_add_reg_form() {
	if (!isset($_POST['pc_nonce']) || !wp_verify_nonce($_POST['pc_nonce'], 'lcwp_ajax')) {die('Cheating?');};
	
	$form_name = trim($_POST['form_name']); 
	if (empty($form_name) || strlen($form_name) > 250) {die( __('Please insert a valid form name', 'pc_ml') );}
	
	$result = wp_insert_term($form_name, 'pc_reg_form', array(
		'description' => base64_encode(serialize( array(
			'include' => array('username', 'psw'), 'require' => array('username', 'psw')
		)))
	));	
	
	echo (is_wp_error($result)) ? $result->get_error_message() : $result['term_id'];
	die();	
}
add_action('wp_ajax_pc_add_reg_form', 'pc_add_reg_form');



////////////////////////////////////////////////////
/// REGISTRATION FORMS - SHOW BUILDER //////////////
////////////////////////////////////////////////////

function pc_reg_form_builder() {
	if (!isset($_POST['pc_nonce']) || !wp_verify_nonce($_POST['pc_nonce'], 'lcwp_ajax')) {die('Cheating?');};
	include_once(PC_DIR . '/classes/pc_form_framework.php');
	$f_fw = new pc_form;	
	
	$form_id = trim(addslashes($_POST['form_id'])); 
	if (!filter_var($form_id, FILTER_VALIDATE_INT)) {die('Invalid form ID');}

	$term = get_term($form_id, 'pc_reg_form');
	$structure = unserialize(base64_decode($term->description));

	echo '
	<table id="pc_rf_add_f_table" class="widefat pc_table">
	  <tbody>
	  	<tr>
		  <td class="pc_label_td">'. __('Form name', 'pc_ml') .'</td>
		  <td class="pc_field_td">
		  	<input type="text" name="pc_rf_name" id="pc_rf_name" value="'. $term->name .'" placeholder="'. __("New form's name", 'pc_ml').'" autocomplete="off" />
		  </td>
		</tr>
		<tr>
		  <td class="pc_label_td"><input type="button" name="pc_rf_add_field" id="pc_rf_add_field" class="button-secondary" value="'. __('Add field', 'pc_ml') .'" /></td>
		  <td class="pc_field_td">
		  	<select name="pc_rf_fields_dd" class="lcweb-chosen pc_rf_fields_dd" data-placeholder="'. __('Add fields', 'pc_ml') .' .." autocomplete="off">';
				
			foreach($f_fw->fields as $index => $data) {
				if(in_array($index, array('username', 'psw', 'pc_disclaimer'))) {continue;}
				echo '<option value="'. $index .'">'. $data['label'] .'</option>';	
			}
			
			echo '	
				<option value="custom|||text">'. __('TEXT BLOCK', 'pc_ml') .'</option>
			</select>
		  </td>
		</tr>  
	  </tbody>
	</table>
	
	<table id="pc_rf_builder_table" class="widefat pc_table">
	  <thead>
		<tr>
		  <th style="width: 15px;"></th>
		  <th style="width: 15px;"></th>
		  <th>'. __('Field', 'pc_ml') .'</th>
		  <th>'. __('Required?', 'pc_ml') .'</th>
		</tr>
	  </thead>
	  <tbody>';
	
	$txt_id = 0;	
	foreach($structure['include'] as $field) {
		$required = (in_array($field, (array)$structure['require']) || in_array($field, array('username', 'psw', 'categories'))) ? 'checked="checked"' : '';
		$disabled = (in_array($field, array('username', 'psw', 'categories'))) ? 'disabled="disabled"' : '';
		
		$del_code = (in_array($field, array('username', 'psw'))) ? '' : '<span class="pc_del_field" title="'. __('remove field', 'pc_ml') .'"></span>';
		
		// text block part
		if($field == 'custom|||text') {
			$content = (isset($structure['texts']) && is_array($structure['texts']) && isset($structure['texts'][$txt_id])) ? $structure['texts'][$txt_id] : '';
			
			$code = '
			<td colspan="2">
				<input type="hidden" name="pc_reg_form_field[]" value="'.$field.'" class="pc_reg_form_builder_included" />
				<textarea name="pc_reg_form_texts[]" placeholder="'. __('Supports HTML and shortcodes', 'pc_ml') .'">'. $content .'</textarea>
			</td>';
			
			$txt_id++;
		}
		
		// standard part
		else {
			$code = '
			<td>
				<input type="hidden" name="pc_reg_form_field[]" value="'.$field.'" class="pc_reg_form_builder_included" />
				'. $f_fw->get_field_name($field) .'
			</td>
			<td>
				<input type="checkbox" name="pc_reg_form_req[]" value="'.$field.'" '.$required.' '.$disabled.' class="ip_checks pc_reg_form_builder_required" autocomplete="off" />
			</td>';
		}
		
		echo '
		<tr rel="'.$field.'">
			<td>'. $del_code .'</td>
			<td><span class="pc_move_field" title="'. __('sort field', 'pc_ml') .'"></span></td>
			'. $code .'
		</tr>';
	}
		
	echo '</tbody>
	</table>';	
	die();	
}
add_action('wp_ajax_pc_reg_form_builder', 'pc_reg_form_builder');



////////////////////////////////////////////////////
/// REGISTRATION FORMS - UPDATE FORM ///////////////
////////////////////////////////////////////////////

function pc_update_reg_form() {
	if (!isset($_POST['pc_nonce']) || !wp_verify_nonce($_POST['pc_nonce'], 'lcwp_ajax')) {die('Cheating?');};
	
	include_once(PC_DIR . '/classes/simple_form_validator.php');
	$validator = new simple_fv;	
		
	$indexes = array();
	$indexes[] = array('index'=>'form_id', 'label'=>'form id', 'type'=>'int', 'required'=>true);
	$indexes[] = array('index'=>'form_name', 'label'=>'form name', 'required'=>true, 'max_len'=>250);
	$indexes[] = array('index'=>'fields_included', 'label'=>'fields included', 'required'=>true);
	$indexes[] = array('index'=>'fields_required', 'label'=>'fields required', 'required'=>true);
	$indexes[] = array('index'=>'texts', 'label'=>'text blocks');
	
	$validator->formHandle($indexes);
	$fdata = $validator->form_val;

	// check username and password fields existence
	if(!is_array($fdata['fields_included']) || !in_array('username', $fdata['fields_included']) || !in_array('psw', $fdata['fields_included'])) {
		$validator->custom_error[ __("Form structure", 'pc_ml') ] = __("Username and password fields are mandatory", 'pc_ml');	
	}
	
	$error = $validator->getErrors();
	if(!$error) {
		// clean texts from slashes
		if(!empty($fdata['texts'])) {
			$escaped = array();
			
			foreach((array)$fdata['texts'] as $val) {
				$escaped[] = stripslashes($val);
			}
			
			$fdata['texts'] = $escaped;
		}
		
		// setup array - user base64_encode to prevent WP tags cleaning
		$descr = base64_encode(
			serialize( 
				array(
					'include' => $fdata['fields_included'], 'require'=>$fdata['fields_required'], 'texts'=>$fdata['texts']
				)
			)
		);

		// update	
		$result = wp_update_term($fdata['form_id'], 'pc_reg_form', array(
			'name' => $fdata['form_name'],
			'description' => $descr
		));
		
		echo (is_wp_error($result)) ? $result->get_error_message() : 'success';
	}
	else {
		echo $error;	
	}
	die();
}
add_action('wp_ajax_pc_update_reg_form', 'pc_update_reg_form');



////////////////////////////////////////////////////
/// REGISTRATION FORMS - DELETE FORM ///////////////
////////////////////////////////////////////////////

function pc_del_reg_form() {
	if (!isset($_POST['pc_nonce']) || !wp_verify_nonce($_POST['pc_nonce'], 'lcwp_ajax')) {die('Cheating?');};
	
	$form_id = trim(addslashes($_POST['form_id'])); 
	if (!filter_var($form_id, FILTER_VALIDATE_INT)) {die('Invalid form ID');}

	echo (wp_delete_term($form_id, 'pc_reg_form')) ? 'success' : 'Error deleting form';	
	die();	
}
add_action('wp_ajax_pc_del_reg_form', 'pc_del_reg_form');



/*******************************************************************************************************************/


////////////////////////////////////////////////////
/// MENU MANAGEMENT - LOAD MENU ITEM RESTRICTIONS //
////////////////////////////////////////////////////

function pc_menu_item_restrict() {
	if (!isset($_POST['pc_nonce']) || !wp_verify_nonce($_POST['pc_nonce'], 'lcwp_ajax')) {die('Cheating?');};
	
	$menu_items = $_POST['menu_items']; 
	if (!is_array($menu_items)) {die('Invalid data');}

	$vals = array();
	foreach($menu_items as $item_id) {
		$val = get_post_meta($item_id, '_menu_item_pg_hide', true);
		$vals[$item_id] = (empty($val)) ? array('') : $val;
	}
	
	echo json_encode($vals);
	die();	
}
add_action('wp_ajax_pc_menu_item_restrict', 'pc_menu_item_restrict');



