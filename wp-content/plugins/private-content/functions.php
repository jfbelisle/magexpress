<?php 

// get the current URL
function pc_curr_url() {
	$pageURL = 'http';
	
	if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
	$pageURL .= "://" . $_SERVER['HTTP_HOST'].$_SERVER["REQUEST_URI"];

	return $pageURL;
}


// get file extension from a filename
function pc_stringToExt($string) {
	$pos = strrpos($string, '.');
	$ext = strtolower(substr($string,$pos));
	return $ext;	
}


// get filename without extension
function pc_stringToFilename($string, $raw_name = false) {
	$pos = strrpos($string, '.');
	$name = substr($string,0 ,$pos);
	if(!$raw_name) {$name = ucwords(str_replace('_', ' ', $name));}
	return $name;	
}


// string to url format
function pc_stringToUrl($string){
	$trans = array("à" => "a", "è" => "e", "é" => "e", "ò" => "o", "ì" => "i", "ù" => "u");
	$string = trim(strtr($string, $trans));
	$string = preg_replace('/[^a-zA-Z0-9-.]/', '_', $string);
	$string = preg_replace('/-+/', "_", $string);
	return $string;
}


// normalize a url string
function pc_urlToName($string) {
	$string = ucwords(str_replace('_', ' ', $string));
	return $string;	
}


// sanitize input field values
function pc_sanitize_input($val) {
	global $wp_version;
	
	// not sanitize quotes  in WP 4.3 and newer
	if ($wp_version >= 4.3) {
		return trim(
			str_replace(array('"'), array('&quot;'), (string)$val)
		);	
	}
	else {
		return trim(
			str_replace(array('\'', '"', '<', '>', '&'), array('&apos;', '&quot;', '&lt;', '&gt;', '&amp;'), (string)$val)
		);	
	}
}
function pg_sanitize_input($val) {return pc_sanitize_input($val);} // retrocompatibility


// serialize and sanitize values for DB usage
function pc_serialize_sanitize($data) {
	$data = (!is_serialized($data)) ? maybe_serialize($data) : $data;
	return (!is_serialized($data)) ? addslashes($data) : $data;	
}


// calculate elapsed time
function pc_elapsed_time($date) {
    // PHP <5.3 fix
	if(!method_exists('DateTime','getTimestamp')) {
		include_once(PC_DIR . '/classes/datetime_getimestamp_fix.php');
		
		$dt = new pc_DateTime($date);
		$timestamp = $dt->getTimestamp();	
	}
	else {	
		$dt = new DateTime($date);
		$timestamp = $dt->getTimestamp();
	}
	
	// calculate difference between server time and given timestamp
    $timestamp = current_time('timestamp') - $timestamp;

    //if no time was passed return 0 seconds
    if ($timestamp < 1){
        return '1 '. __('second', 'pc_ml');
    }

    //create multi-array with seconds and define values
    $values = array(
		12*30*24*60*60  =>  'year',
		30*24*60*60     =>  'month',
		24*60*60        =>  'day',
		60*60           =>  'hour',
		60              =>  'minute',
		1               =>  'second'
	);

    //loop over the array
    foreach ($values as $secs => $point){
        
		//check if timestamp is equal or bigger the array value
        $divRes = $timestamp / $secs;
        if ($divRes >= 1){
            
			//if timestamp is bigger, round the divided value and return it
            $res = round($divRes);
			
			// translatable strings
			switch($point) {
				case 'year' : $txt = ($res > 1) ? __('years', 'pc_ml') : __('year', 'pc_ml'); break; 
				case 'month': $txt = ($res > 1) ? __('months', 'pc_ml') : __('month', 'pc_ml'); break;
				case 'day'  : $txt = ($res > 1) ? __('days', 'pc_ml') : __('day', 'pc_ml'); break;	
				case 'hour' : $txt = ($res > 1) ? __('hours', 'pc_ml') : __('hour', 'pc_ml'); break;	
				case'minute': $txt = ($res > 1) ? __('minutes', 'pc_ml') : __('minute', 'pc_ml'); break;	
				case'second': $txt = ($res > 1) ? __('seconds', 'pc_ml') : __('second', 'pc_ml'); break;	
			}
            return $res. ' ' .$txt;
        }
    }
}


// get all the custom post types
function pc_get_cpt() {
	$args = array(
		'public'   => true,
		'publicly_queryable' => true,
		'_builtin' => false
	);
	$cpt_obj = get_post_types($args, 'objects');
	
	if(count($cpt_obj) == 0) { return false;}
	else {
		$cpt = array();
		foreach($cpt_obj as $id => $obj) {
			$cpt[$id] = $obj->labels->name;	
		}
		
		return $cpt;
	}	
}


// get all the custom taxonomies
function pc_get_ct() {
	$args = array(
		'public' => true,
		'_builtin' => false
	);
	$ct_obj = get_taxonomies($args, 'objects');
	
	if(count($ct_obj) == 0) { return false;}
	else {
		$ct = array();
		foreach($ct_obj as $id => $obj) {
			$ct[$id] = $obj->labels->name;	
		}
		
		return $ct;
	}	
}


// get affected post types
function pc_affected_pt() {
	$basic = array('post','page');	
	$cpt = get_option('pg_extend_cpt'); 

	if(is_array($cpt)) {
		$pt = array_merge((array)$basic, (array)$cpt);	
	}
	else {$pt = $basic;}

	return $pt;
}
function pg_affected_pt() {return pc_affected_pt();} // retrocompatibility


// get affected  taxonomies
function pc_affected_tax() {
	$basic = array('category');	
	$ct = get_option('pg_extend_ct'); 
	
	if(is_array($ct)) {
		$tax = array_merge((array)$basic, (array)$ct);	
	}
	else {$tax = $basic;}
	
	return $tax;
}


// WP capabilities
function pc_wp_roles($role = false) {
	$roles = array(
		'read' 				=> __('Subscriber', 'pc_ml'),
		'edit_posts'		=> __('Contributor', 'pc_ml'),
		'upload_files'		=> __('Author', 'pc_ml'),
		'edit_pages'		=> __('Editor', 'pc_ml'),
		'install_plugins' 	=> __('Administrator', 'pc_ml')
	);
	
	if($role) {return $roles[$role];}
	else {return $roles;}
}


// stripslashes for options inserted
function pc_strip_opts($fdata) {
	if(!is_array($fdata)) {return false;}
	
	foreach($fdata as $key=>$val) {
		if(!is_array($val)) {
			$fdata[$key] = stripslashes($val);
		}
		else {
			$fdata[$key] = array();
			foreach($val as $arr_val) {$fdata[$key][] = stripslashes($arr_val);}
		}
	}
	
	return $fdata;
}


// manage redirects URL (for custom redirects)
function pc_man_redirects($key) {
	$baseval = get_option($key);
	if($baseval == '') {return '';}
	
	if($baseval == 'custom') {return get_option($key.'_custom');}
	else {
		// WPML integration
		$baseval = pc_wpml_translated_pag_id($baseval); 
		return get_permalink($baseval);
	}
}


// WPML integration - given a page ID, searches a translation. If not found, return original value
function pc_wpml_translated_pag_id($obj_id){
	if(function_exists('icl_object_id')) {
		$trans_val = icl_object_id($obj_id, 'page', true);
		if($trans_val && get_post_status($trans_val) == 'publish') {
			return $trans_val;
		}
	} 	
	
	return $obj_id;
}


// given user categories - return first category custom login redirect
function pc_user_cats_login_redirect($cats) {
	if(!is_array($cats)) {return '';}
	
	foreach($cats as $term_id) {
		$redirect = get_option("pg_ucat_".$term_id."_login_redirect");
		if($redirect) {
			return $redirect;
			break;	
		}
	}
}


// associative array of user categories (id => name) 
// $escape_no_reg = escape ones prevented from registration
function pc_user_cats($escape_no_reg = false) {
	$user_categories = get_terms('pg_user_categories', 'orderby=name&hide_empty=0');	
	$cats = array();

	if (!is_wp_error($user_categories)) {
		foreach ($user_categories as $ucat) {
			if($escape_no_reg && get_option("pg_ucat_".$ucat->term_id."_no_registration")) {continue;}
				
			// WPML compatibility
			if(function_exists('icl_t')){
				$cat_name = icl_t('PrivateContent Categories', $ucat->term_taxonomy_id, $ucat->name);
			} else {
				$cat_name = $ucat->name;
			}
			
			$cats[$ucat->term_id] = $cat_name;	
		}
	}
	
	return $cats;
}


// create the frontend css and js
function pc_create_custom_style() {	
	ob_start();
	require(PC_DIR.'/custom_style.php');
	
	$css = ob_get_clean();
	if(trim($css) != '') {
		if(!@file_put_contents(PC_DIR.'/css/custom.css', $css, LOCK_EX)) {$error = true;}
	} else {
		if(file_exists(PC_DIR.'/css/custom.css'))	{ unlink(PC_DIR.'/css/custom.css'); }
	}
	
	if(isset($error)) {return false;}
	else {return true;}
}


// check inherited page parents /post categories restrictions
//// if category $param == term ID
//// if page $param == $post object
function pc_restrictions_helper($subj, $param, $tax = false) {
	$restr = array();
	
	// post types
	if($subj == 'post') {
		
		// search in the taxonomy term
		$term = get_term_by('id', $param, $tax);
		$allowed = trim( (string)get_option('taxonomy_'. $term->term_id .'_pg_redirect')); 
					
		if($allowed != '') {
			$restr[$term->name] = array();
			
			if($allowed ==  'all') {$restr[$term->name] = __('any logged user', 'pc_ml');}
			else {
				$allowed = explode(',', $allowed);
				$allowed_names = array(); 
				
				if(count($allowed) > 0) {
					foreach($allowed as $user_cat) {
						$uc_data = get_term_by('id', $user_cat, 'pg_user_categories');
						$allowed_names[] = $uc_data->name;
					}
					
					$restr[$term->name] = implode(', ', $allowed_names);
				}
			}
		}
		
		// check parent categories
		if(isset($term->category_parent) && $term->category_parent != 0) {
			$parent = get_term_by('id', $term->category_parent,  $tax);
			
			// recursive
			$rec_restr = pc_restrictions_helper('post', $parent->term_id, $tax);
			if($rec_restr) {
				$restr = array_merge($restr, $rec_restr);	
			}	
		}	
		
	}
	
	// page types
	else {
		
		// check parents page
		if($param->post_parent != 0) {
			$parent = get_post($param->post_parent);
		
			$allowed = get_post_meta($parent->ID, 'pg_redirect', true);
			
			if($allowed && is_array($allowed) && count($allowed) > 0) {
				if($allowed[0] ==  'all') 			{$restr[$parent->post_title] = __('any logged user', 'pc_ml');}
				elseif($allowed[0] == 'unlogged')	{$restr[$parent->post_title] = __('unlogged users', 'pc_ml');}
				else {
					$allowed_names = array(); 

					foreach($allowed as $user_cat) {
						$uc_data = get_term_by('id', $user_cat, 'pg_user_categories');
						$allowed_names[] = $uc_data->name;
					}
					
					$restr[$parent->post_title] = implode(', ', $allowed_names);
				}
			}
			
			// check deeper in parents
			if($param->post_parent != 0) {
				$post_obj = get_post($param->post_parent);
				
				// recursive
				$rec_restr = pc_restrictions_helper('page', $post_obj);
				if($rec_restr) {
					$restr = array_merge($restr, $rec_restr);	
				}	
			}
		}
		
	}
	
	return (empty($restr)) ? false : $restr;
}


/////////////////////////////////////////////////////

// email is mandatory?
function pc_mail_is_required() {
	include_once(PC_DIR . '/classes/pc_form_framework.php');
	$form_fw = new pc_form;
	return $form_fw->mail_is_required;	
}

