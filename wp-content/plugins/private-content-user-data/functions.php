<?php
// THIS FILE USES ALSO PRIVATECONTENT FUNCTIONS

// array of index to ignore - original PC fields
function pcud_index_ignore() {
	return array(
		'id', 
		'name', 
		'surname', 
		'username', 
		'psw', 
		'pc_cat', 
		'categories',
		'email', 
		'tel',
		'check_psw', 
		'insert_date', 
		'categories', 
		'page_id', 
		'disable_pvt_page', 
		'status', 	
		'wp_user_id', 
		'last_access',
		'recaptcha_challenge_field', 
		'recaptcha_response_field', 
		'pc_disclaimer', 
		'pc_hnpt_1',
		'pc_hnpt_2',
		'pc_hnpt_3'
	);	
}


// fields to ignore in wizards
function pcud_wizards_ignore_fields($use_cat = false) {
	$fields = array('psw', 'pc_disclaimer');
	if(!$use_cat) {$fields[] = 'categories';}
	
	// PCUD-FILTER - add fields to ignore in wizards
	return apply_filters('pcud_ignore_fields', $fields); 	
}



// strip indexes from an associative array
function pcud_strip_array_indices($ArrayToStrip) {
    foreach( $ArrayToStrip as $objArrayItem) {
        $NewArray[] =  $objArrayItem;
    }
    return($NewArray);
}


// array of field types
function pcud_field_types() {
	$types = array(
		'text' 				=> __('Text', 'pcud_ml'),
		'select' 			=> __('Dropdown', 'pcud_ml'),
		'textarea' 			=> __('Textarea', 'pcud_ml'),
		'checkbox' 			=> __('Checkbox', 'pcud_ml'),
		'single_checkbox' 	=> __('Single-option checkbox', 'pcud_ml'),
	);
	return $types;	
}


// array of field subtypes
function pcud_field_subtypes() {
	$subtypes = array(
		'' 			=> __('Anything', 'pcud_ml'),
		'email' 	=> __('E-mail', 'pcud_ml'),
		'int' 		=> __('Integer Number', 'pcud_ml'),
		'float' 	=> __('Floating Number', 'pcud_ml'),
		'eu_date' 	=> __('European Date', 'pcud_ml'),
		'us_date' 	=> __('US Date', 'pcud_ml'),
		'us_tel' 	=> __('US Telephone', 'pcud_ml'),
		'zipcode' 	=> __('US Zipcode', 'pcud_ml'),
		'url' 		=> __('Standard URL', 'pcud_ml')
	);
	return $subtypes;	
}


// get custom fields - already sorted - (returns array of term ojects)
function pcud_get_sorted_fields() {
	if(isset($GLOBALS['pcud_sorted_fields'])) {return $GLOBALS['pcud_sorted_fields'];} // use cache
	
	// sort fields
	$pcud_fields = get_terms('pcud_fields', 'hide_empty=0&orderby=id&order=ASC');
	$fields_order = (array)get_option('pcud_custom_fields_order', array());
	
	if(!empty($fields_order)) {
		$sorted_fields = array();
		
		foreach($fields_order as $term_id) {
			
			foreach($pcud_fields as $key => $field) {
				if($field->slug == $term_id) {
					$sorted_fields[] = $field;
					unset($pcud_fields[$key]);
				}
			}
		}	
		
		if(!empty($pcud_fields)) {
			$sorted_fields = array_merge($sorted_fields, $pcud_fields);	
		}
	}
	else {$sorted_fields = $pcud_fields;}	
	
	$GLOBALS['pcud_sorted_fields'] = $sorted_fields;
	return $sorted_fields;
}


// get sorted field indexes
function pcud_sorted_fields_indexes() {
	$data = pcud_get_sorted_fields();	
	$indexes = array();
	
	foreach($data as $term) {
		$indexes[] = $term->slug;
	}
	
	return $indexes;
}



// stored array management - compatibility tool for PCUD > 1.3
function pcud_unserialize($data) {
	return (!empty($data) && !is_array($data)) ? unserialize($data) : $data;	
}


// turn forms fields name into v2 compatible
function pcud_v2_field_names_sanitize($data) {
	if(!is_array($data) || !isset($data['include'])) {return $data;}
		
	$incl = array();
	$req = array();
	
	foreach($data['include'] as $fname) {
		$incl[] = sanitize_title($fname);	
	}
	
	if(is_array($data['require'])) {
		foreach($data['require'] as $fname) {
			$req[] = sanitize_title($fname);	
		}	
	}
	
	$data['include'] = $incl;
	$data['require'] = $req;
	
	return $data;
}


//////////////////////////////////////////////////////////////////////////////////////////////


// sync custom fields name with WPML - get data validated from fields builder
function pcud_fields_wpml_sync($fdata) {
	if(!function_exists('icl_register_string')) {return false;}
	
	$saved = (array)get_option('pcud_wpml_synced_fields', array());
	$to_save = array();

	foreach($fdata['pcud_f_index'] as $key => $val) {
		$index = $val;
		$to_save[$index] = array('label');
		
		//// label 
		// check if unregister before
		if(isset($saved[$index]) && isset($saved[$index]['label']) && $saved[$index]['label'] != $fdata['pcud_f_label'][$key]) {
			icl_unregister_string('PrivateContent User Data', 'Custom fields - '.$index); // retrocompatibility
			icl_unregister_string('PrivateContent User Data', $index.' - label');
		}
		$to_save[$index]['label'] = $fdata['pcud_f_label'][$key];
		icl_register_string('PrivateContent User Data', $index.' - label', $fdata['pcud_f_label'][$key]);	
		
		
		//// options 
		// check if unregister before
		if(isset($saved[$index]) && isset($saved[$index]['opt']) && $saved[$index]['label'] != $fdata['pcud_f_options'][$key]) {
			icl_unregister_string('PrivateContent User Data', 'Custom fields opt - '.$index); // retrocompatibility
			icl_unregister_string('PrivateContent User Data', $index.' - options');
		}
		if(!empty($fdata['pcud_f_options'][$key])) {
			$to_save[$index]['opt'] = $fdata['pcud_f_options'][$key];
			icl_register_string('PrivateContent User Data', $index.' - options', $fdata['pcud_f_options'][$key]);	
		}
		
		
		//// placeholder
		// check if unregister before
		if(isset($saved[$index]) && isset($saved[$index]['placeh']) && $saved[$index]['placeh'] != $fdata['pcud_f_placeh'][$key]) {
			icl_unregister_string('PrivateContent User Data', 'Custom fields placeholder - '.$index); // retrocompatibility
			icl_unregister_string('PrivateContent User Data', $index.' - placeholder');
		}
		if(!empty($fdata[$key]['pcud_f_placeh'])) {
			$to_save[$index]['placeh'] = $fdata['pcud_f_placeh'][$key];
			icl_register_string('PrivateContent User Data', $index.' - placeholder', $fdata['pcud_f_placeh'][$key]);	
		}
		
		
		//// single checkbox text
		// check if unregister before
		if(isset($saved[$index]) && isset($saved[$index]['check_txt']) && $saved[$index]['check_txt'] != $fdata['pcud_f_check_txt'][$key]) {
			icl_unregister_string('PrivateContent User Data', $index.' - checkbox text');
		}
		if(!empty($fdata[$key]['pcud_f_check_txt'])) {
			$to_save[$index]['check_txt'] = $fdata['pcud_f_check_txt'][$key];
			icl_register_string('PrivateContent User Data', $index.' - checkbox text', $fdata['pcud_f_check_txt'][$key]);	
		}
	}
		
	// save registered fields
	update_option('pcud_wpml_synced_fields', $to_save);	
}


// get translated field name - WPML integration
function pcud_wpml_translated_string($index, $original_val, $context = 'label') {
	if(function_exists('icl_t')){
		switch($context) {
			case 'options' 	: $suffix = ' - options'; break;
			case 'placeh' 	: $suffix = ' - placeholder'; break;
			case 'check_txt': $suffix = ' - checkbox text'; break;
			default 		: $suffix = ' - label'; break;		
		}
		
		return icl_t('PrivateContent User Data',  $index.$suffix, $original_val);
	} 
	
	return $original_val;
}

