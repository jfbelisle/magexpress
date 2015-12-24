<?php
////////// SHORCODES LIST

// [pcud-form] 
//// print custom form
function pcud_form_shortcode($atts, $content = null) {
	require_once(PC_DIR .'/classes/pc_form_framework.php');
	$f_fw = new pc_form;
	
	include_once(PCUD_DIR . '/functions.php');
	
	extract( shortcode_atts( array(
		'form' => '',
		'layout' => ''
	), $atts ) );
	
	if(!filter_var($form, FILTER_VALIDATE_INT)) {return false;}
	
	// execute only if pvtContent or WP user is logged 
	$pc_logged = pc_user_logged(false);
	if(!$pc_logged && !current_user_can(get_option('pg_min_role', 'upload_files'))) {return false;} // ignore testing mode
	
	$user_id = ($pc_logged) ? $GLOBALS['pc_user_id'] : 0;	

	// form structure
	$term = get_term_by('id', $form, 'pcud_forms');
	if(empty($term)) {return false;}
	
	if(empty($term->description)) {
		// retrocompatibility
		$form_fields = (array)get_option('pcud_form_'.$form, array());	
	} else {
		$form_fields = unserialize(base64_decode($term->description));	
	}
	
	// layout
	if(empty($layout) || !in_array($layout, array('one_col', 'fluid'))) {
		$layout_class = 'pc_'. get_option('pg_reg_layout', 'one_col') .'_form';
	} else {
		$layout_class = 'pc_'. $layout .'_form';	
	}
		
	$form = '
	<form class="pc_custom_form pc_custom_form_'.$form.' '.$layout_class.'">
		<input type="hidden" name="pcud_fid" value="'.$form.'" />';

		$form .= $f_fw->form_code( pcud_v2_field_names_sanitize($form_fields), false, $user_id);
	
		$form .= '
		<div class="pc_custom_form_message"></div>
	
		<input type="button" class="pc_custom_form_btn" value="'. __('Submit', 'pcud_ml') .'" />
	</form>';
	
	return str_replace(array("\r", "\n", "\t", "\v"), '', $form);
}
add_shortcode('pcud-form', 'pcud_form_shortcode');



// [pcud-user-data] 
//// print a specific user data
function pcud_data_shortcode( $atts, $content = null ) {
	extract( shortcode_atts( array(
		'f' => ''
	), $atts ) );
	
	
	$data = pc_user_logged( sanitize_title($f) );
	if(empty($data)) {return false;}
	
	return (is_array($data)) ? implode(', ', $data) : $data;
}
add_shortcode('pcud-user-data', 'pcud_data_shortcode');



// [pcud-cond-block] 
// hide shortcode content if user data satisfy the condition
function pcud_cond_block_sc( $atts, $content = null ) {
	extract( shortcode_atts( array(
		'f' 	=> '',
		'cond'	=> '=',
		'val'	=> ''
	), $atts ) );
	
	// logged user data
	$ud = pc_user_logged( sanitize_title($f) );
	
	if(!$ud) {return '';}
	else {		
	
		// turn field to array to use a cycle
		$arr = (is_array($ud)) ? $ud : array($ud);
		
		foreach($arr as $ud_val) {
			switch($cond) {
				case '=' :
					$to_return = ($ud_val == $val) ? do_shortcode($content) : false;
					break;
						
				case '!=' :
					$to_return = ($ud_val != $val) ? do_shortcode($content) : false;
					break;
					
				case 'big' :
					$to_return = ((float)$ud_val > (float)$val) ? do_shortcode($content) : false;
					break;
					
				case 'small' :
					$to_return = ((float)$ud_val < (float)$val) ? do_shortcode($content) : false;
					break;
					
				case 'like' : // value contains string
					$to_return = (strpos((string)$ud_val, (string)$val) !== false) ? do_shortcode($content) : false;
					break;
				
					
				default : return ''; break; // if wrong condition - return nothing					
			}
			
			if($to_return !== false) {
				return $to_return;
				break;
			}
		}
	}
}
add_shortcode('pcud-cond-block', 'pcud_cond_block_sc');

