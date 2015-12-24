<?php
// PRIVATECONTENT INTEGRATIONS


///////////////////////////////////////////////
// add custom fields into framework ////////////
///////////////////////////////////////////////

function pcud_add_fields_into_pc($fields) {
	include_once(PCUD_DIR . '/functions.php');
	
	$pcud_fields = get_terms('pcud_fields', 'hide_empty=0');

	// if is WP error (called before taxonomies setup) - return fields
	if(is_wp_error($pcud_fields)) {return $fields;} 
	
	foreach($pcud_fields as $term) {
		$data = unserialize(base64_decode($term->description));
		$field_args = array('label' => pcud_wpml_translated_string($term->slug, $term->name));
		
		foreach($data as $index => $val) {
			if($index == 'opt') {
				$field_args[$index] = pcud_wpml_translated_string($term->slug, $val, 'options');	
			} 
			elseif($index == 'multi_select') {
				$field_args['multiple'] = (empty($val)) ? false : true;
			} 
			elseif($index == 'placeh') {
				$field_args[$index] = pcud_wpml_translated_string($term->slug, $val, 'placeh');	
			} 
			elseif($index == 'check_txt') {
				$field_args[$index] = pcud_wpml_translated_string($term->slug, $val, 'check_txt');	
			} 
			else {
				$field_args[$index] = $val;	
			}
		}
		
		$fields[$term->slug] = $field_args;
	}
	
	return $fields;
}
add_filter('pc_form_fields_filter', 'pcud_add_fields_into_pc');



///////////////////////////////////////////////
// USERS LIST - show custom fields ////////////
///////////////////////////////////////////////

function pcud_user_list_custom_fields($table_cols) {
	$to_show = get_option('pcud_fields_in_users_list', array());

	if(is_array($to_show)) {
		// avoid useless queries - use stored fields
		include_once(PC_DIR .'/classes/pc_form_framework.php');
		$f_fw = new pc_form;
		$fields = $f_fw->fields;
		
		foreach($to_show as $index) {
			if(isset($fields[$index])) {
				$table_cols[$index] = array(
					'name' 		=> $fields[$index]['label'],
					'sortable' 	=> (in_array($fields[$index]['type'], array('multi_select', 'checkbox'))) ? false : true,
				);
			}
		}
	}	
	  
	return $table_cols;	
}
add_filter('pc_users_list_table_fiels', 'pcud_user_list_custom_fields');



//////////////////////////////////////////////////////////
// IMPORT PROCESS - add custom fields and try to import //
//////////////////////////////////////////////////////////

// form - wizard to add fields
function pcud_import_form($fields) {
	include_once(PCUD_DIR .'/functions.php');
	return array_merge($fields, pcud_sorted_fields_indexes()); 
}
add_filter('pc_import_custom_fields', 'pcud_import_form');




////////////////////////////////////////////////
// ADD USER PAGE - ADD AND VALIDATE FIELDS /////
////////////////////////////////////////////////

// validation
function pcud_add_user_validation($form_structure) {
	include_once(PCUD_DIR .'/functions.php');
	
	$form_structure['include'] = array_merge($form_structure['include'], pcud_sorted_fields_indexes());  
	return $form_structure;
}
add_filter('pc_add_user_form_validation', 'pcud_add_user_validation');


// code
function pcud_add_user_fields($fdata, $user_id) {
	include_once(PCUD_DIR .'/functions.php');
	include_once(PC_DIR .'/classes/pc_form_framework.php');
	$form_fw = new pc_form;	
	
	$custom_f_indexes = pcud_sorted_fields_indexes();
	if(empty($custom_f_indexes)) {return false;}
	
	$code = '
	<h3 style="border: none !important;">User Data add-on - '. __('custom fields', 'pcud_ml') .'</h3>
	<table class="widefat pc_table pc_add_user" style="margin-bottom: 25px;">
      <tbody>';
	  
	  $a = 0;
	  foreach($custom_f_indexes as $f_index) {
		  $f = $form_fw->fields[$f_index];
		  
		  // user data exists?
		  $val = (!empty($fdata) && isset($fdata[$f_index])) ? $fdata[$f_index] : false;
		
		  // specific cases
		  $placeh = isset($f['placeh']) ? 'placeholder="'.$f['placeh'].'"' : '';
					
		  // start code
		  if(!$a) {$code .= '<tr>';}
		  $left_border = (!$a) ? '' : 'style="border-left: 1px solid #DFDFDF;"';
		  $code .= '<td class="pc_label_td" '.$left_border.'>'. $f['label'] .'</td>';
		  
		  // field type switch
		  if($f['type'] == 'text') {
			  $dp_class = ($f['subtype'] == 'eu_date' || $f['subtype'] == 'us_date') ? 'class="pcud_datepicker pcud_dp_'.$f['subtype'].'"': '';
			 
			  $code .= '
			  <td class="pc_field_td">
			  	<input type="'.$f['type'].'" name="'.$f_index.'" value="'.pc_sanitize_input($val).'" maxlength="'.$f['maxlen'].'" '.$placeh.' '. $dp_class.' autocomplete="off" />
			  </td>';
		  }
		  
		  // textarea
		  elseif($f['type'] == 'textarea') {
			  $code .= '
			  <td class="pc_field_td">
			  	<textarea name="'.$f_index.'" autocomplete="off" '.$placeh.' style="width: 90%; height: 45px;">'.$val.'</textarea>
			  </td>';
		  }
		  
		  // select
		  elseif($f['type'] == 'select' || $f['type'] == 'checkbox') {	
			  $opts = $form_fw->get_field_options($f['opt']);
			  $multiple = ($f['type'] == 'checkbox' || (isset($f['multiple']) && $f['multiple'])) ? 'multiple="multiple"' : '';
			  $multi_name = ($multiple) ? '[]' : '';
			  
			  $code .= '
			  <td class="pc_field_td">
			  	<select name="'.$f_index.$multi_name.'"  class="lcweb-chosen" '.$multiple.' data-placeholder="'. __('Select values', 'pcud_ml') .' .." autocomplete="off" style="width: 90%;">';
			  
				  foreach($opts as $opt) { 
					  $sel = (in_array($opt, (array)$val)) ? 'selected="selected"' : false;
					  $code .= '<option value="'.$opt.'" '.$sel.'>'.$opt.'</option>'; 
				  }
				  
				  $code.= '
				  </select>
			  </td>';			
		  }

		  // single-option checkbox
		  elseif($f['type'] == 'single_checkbox') {	
			  $checked = (empty($val)) ? '' : 'checked="checked"';
			  $code .= '
			  <td class="pc_field_td">
			  	<input type="checkbox" name="'.$f_index.'" value="1" '.$checked.' class="ip_checks" autocomplete="off" />
			  </td>';
		  }
		  
		if($a == 1) {
			$code .= '</tr>';
			$a = 0;
		} else { 
			$a++;
		}
	}
	
	// if missing a TD - add it
	if($a !== 0) {
		$code .= '<td style="border-left: 1px solid #DFDFDF;" colspan="2"></td></tr>';	
	}
	
	// add-user button utility
	$btn_val = (empty($fdata)) ? __('Add User', 'pc_ml') : __('Update User', 'pc_ml');
	$code .= '
	<tr>
		<td colspan="2" style="width: 50%;">
			<input type="submit" name="pc_man_user_submit" value="'.$btn_val.'" class="button-primary" />
		</td>
		<td colspan="2" style="width: 50%;"></td>
	</tr>
	';
	
	$code .= "
	<!-- datepicker init -->
	<script type='text/javascript'>
	jQuery(document).ready(function() {
		if(jQuery('.pcud_datepicker').size() > 0) {
			// dynamically add datepicker style
			jQuery('head').append(\"<link rel='stylesheet' href='".PCUD_URL."/css/datepicker/light/pcud_light.theme.min.css' type='text/css' media='all' />\");
			
			var pcud_datepicker_init = function(type) {
				return {
					dateFormat : (type == 'eu') ? 'dd/mm/yy' : 'mm/dd/yy',
					beforeShow: function(input, inst) {
						jQuery('#ui-datepicker-div').wrap('<div class=\"pcud_dp\"></div>');
					},
					monthNames: 		pcud_datepick_str.monthNames,
					monthNamesShort: 	pcud_datepick_str.monthNamesShort,
					dayNames: 			pcud_datepick_str.dayNames,
					dayNamesShort: 		pcud_datepick_str.dayNamesShort,
					dayNamesMin:		pcud_datepick_str.dayNamesMin,
					isRTL:				pcud_datepick_str.isRTL
				};	
			}
			
			jQuery('.pcud_dp_eu_date').datepicker( pcud_datepicker_init('eu') );
			jQuery('.pcud_dp_us_date').datepicker( pcud_datepicker_init('us') );
		}
	});
	</script>
	";
	  
	echo $code;
}
add_action('pc_add_user_body', 'pcud_add_user_fields', 10, 2);



///////////////////////////////////////////////////////////////////////////////////

// MAIL ACTIONS INTEGRATIONS 
if(defined('PCMA_DIR')) :

// pcma settings - add tab	
function pcud_pcma_tabs() {
	echo '<li><a href="#pcud_data_update">User Data add-on</a></li>';	
}
add_action('pcma_settings_tabs', 'pcud_pcma_tabs');	
	
	
// pcma settings - add tab contents	
function pcud_pcma_tab_content() {
	$enable = (isset($_POST['pcud_sf_admin_notif'])) ? $_POST['pcud_sf_admin_notif'] : get_option('pcud_sf_admin_notif');
	$receivers = (isset($_POST['pcud_sfan_receivers'])) ? $_POST['pcud_sfan_receivers'] : get_option('pcud_sfan_receivers');
	$title = (isset($_POST['pcud_sfan_title'])) ? stripslashes($_POST['pcud_sfan_title']) : get_option('pcud_sfan_title', __("User's data updated", 'pcud_ml'));
	$txt = (isset($_POST['pcud_sfan_txt'])) ? stripslashes($_POST['pcud_sfan_txt']) : get_option('pcud_sfan_txt', __('Hello,
%USERNAME% has just updated its data through custom form.', 'pcma_ml'));
	?>
	<div id="pcud_data_update">   
        <h3>User Data add-on</h3>
        <table class="widefat pc_table">
          <tr>
            <td class="pc_label_td"><?php _e("E-mail admins on custom form submission?", 'pcud_ml'); ?></td>
            <td class="pc_field_td">
                <?php $checked = ($enable) ?  'checked="checked"' : ''; ?>
                <input type="checkbox" name="pcud_sf_admin_notif" value="1" <?php echo $checked; ?> class="ip_checks" />
            </td>
            <td><span class="info"><?php _e('If checked, notifies admins if a custom form is submitted by an user', 'pcud_ml'); ?></span></td>
          </tr>
          <tr>
            <td class="pc_label_td"><?php _e('Receiver e-mail address', 'pcma_ml'); ?></td>
            <td class="pc_field_td" colspan="2">
                <?php $receivers = (is_array($receivers)) ? implode(',', $receivers) : $receivers; ?>
                <input type="text" name="pcud_sfan_receivers" value="<?php echo pc_sanitize_input(stripslashes($receivers)); ?>" style="width: 90%; min-width: 200px;" autocomplete="off" />
                <p><span class="info"><?php _e('Notification receivers - multiple addresses supported, <strong>comma split</strong>', 'pcud_ml'); ?></span></p>
            </td>
          </tr>
        </table>
        
        <h3><?php _e("E-mail builder", 'pcma_ml'); ?></h3>
        <table class="widefat pc_table">
          <tr>
            <td class="pc_label_td"><?php _e("Allowed Variables for title and text", 'pcma_ml'); ?></td>
            <td>
               <table class="pcma_legend_table"> 
                  <tr>
                    <td style="width: 180px;">%SITE-URL%</td>
                    <td><span class="info"><?php _e("Website url (link)", 'pcma_ml'); ?></span></td>
                  </tr> 
                  <tr>
                    <td style="width: 180px;">%SITE-TITLE%</td>
                    <td><span class="info"><?php _e("Website title specified in the WP settings", 'pcma_ml'); ?></span></td>
                  </tr>
                  <tr>
                    <td style="width: 180px;">%NAME%</td>
                    <td><span class="info"><?php _e("User's Name", 'pcma_ml'); ?></span></td>
                  </tr>
                  <tr>
                    <td style="width: 180px;">%SURNAME%</td>
                    <td><span class="info"><?php _e("User's Surname", 'pcma_ml'); ?></span></td>
                  </tr>
                  <tr>
                    <td style="width: 180px;">%USERNAME%</td>
                    <td><span class="info"><?php _e("User's Username", 'pcma_ml'); ?></span></td>
                  </tr>
                  <tr>
                    <td style="width: 180px;">%CAT%</td>
                    <td><span class="info"><?php _e("User Categories", 'pcma_ml'); ?></span></td>
                  </tr>
                  <tr>
                    <td colspan="2"><?php _e('Remember you can user User Data add-on shortcodes to print custom data', 'pcud_ml') ?></td>
                  </tr>
                </table>  
            </td>
          </tr>
          <tr>
             <td class="pc_label_td"><?php _e("E-mail title", 'pcma_ml'); ?></td>
             <td>
               <input type="text" name="pcud_sfan_title" value="<?php echo pc_sanitize_input($title); ?>" maxlength="255" style="width: 75%; min-width: 200px;" autocomplete="off" />
             </td>
           </tr>
          <tr>
             <td class="pc_label_td"><?php _e("E-mail text", 'pcma_ml'); ?></td>
             <td>
               <?php 
               $args = array('textarea_rows' => 9);
               echo wp_editor($txt, 'pcud_sfan_txt', $args);
               ?>
             </td>
           </tr>
        </table>  
    </div>
    
    <?php
}
add_action('pcma_settings_tab_contents', 'pcud_pcma_tab_content');		
 
  	
// pcma settings - validate data
function pcud_pcma_validate_data($indexes) {
	
	// set as requred data if module is enabled
	$required = (isset($_POST['pcud_sf_admin_notif']) && $_POST['pcud_sf_admin_notif']) ? true : false;
	
	// turn mails into array to validate
	if($required) {
		$_POST['pcud_sfan_receivers'] = explode(',', $_POST['pcud_sfan_receivers']);
	}
	
	$indexes[] = array('index'=>'pcud_sf_admin_notif', 'label'=>'Enable custom form submission notification');
	$indexes[] = array('index'=>'pcud_sfan_receivers', 'label'=>'User Data add-on '. __('admin notification - receivers', 'pcud_ml'), 'required'=>$required, 'type'=>'email');
	$indexes[] = array('index'=>'pcud_sfan_title', 'label'=>'User Data add-on - '. __('admin notification - title', 'pcud_ml'), 'required'=>$required, 'max_len'=>255);
	$indexes[] = array('index'=>'pcud_sfan_txt', 'label'=>'User Data add-on - '. __('admin notification - text', 'pcud_ml'), 'required'=>$required);
	
	return $indexes;	
}
add_filter('pcma_settings_validation', 'pcud_pcma_validate_data');	


// pcma settings - send e-mail
function pcud_pcma_send_email($indexes) {
	if(function_exists('pcma_is_active') && pcma_is_active() && get_option('pcud_sf_admin_notif')) {
		include_once(PCMA_DIR . '/functions.php');
		
		$txt = pcma_replace_placeholders($GLOBALS['pc_user_id'], nl2br(get_option('pcud_sfan_txt')));
		$title = pcma_replace_placeholders($GLOBALS['pc_user_id'], get_option('pcud_sfan_title'));
		$mail_sent = pcma_send_mail('', get_option('pcud_sfan_receivers'), $title, $txt);
	}	
}
add_action('pcud_user_updated_data', 'pcud_pcma_send_email');


// mail actions integration's end	
endif;

