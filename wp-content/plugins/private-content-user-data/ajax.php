<?php

//////////////////////////////////////////////////////
////// GENERATE INDEX FROM LABEL /////////////////////
//////////////////////////////////////////////////////

function pcud_generate_index() {
	if(!isset($_POST['pcud_nonce']) || !wp_verify_nonce($_POST['pcud_nonce'], 'lcwp_ajax')) {die('Cheating?');};
	if(!isset($_POST['f_label']) || empty($_POST['f_label'])) {
		die('Missing field label');	
	}
	
	echo sanitize_title($_POST['f_label']);
	die();
}
add_action('wp_ajax_pcud_generate_index', 'pcud_generate_index');



//////////////////////////////////////////////////////
////// ADD CUSTOM FIELD //////////////////////////////
//////////////////////////////////////////////////////

function pcud_add_field() {
	if(!isset($_POST['pcud_nonce']) || !wp_verify_nonce($_POST['pcud_nonce'], 'lcwp_ajax')) {die('Cheating?');};
	
	if(!isset($_POST['f_label']) || empty($_POST['f_label'])) {
		die( __('Missing label', 'pcud_ml') );	
	}
	
	$label = $_POST['f_label'];
	$index = ((!isset($_POST['f_index']) || empty($_POST['f_index']))) ? '' : sanitize_title($_POST['f_index']);
	
	// if empty index - create it 
	if(empty($index)) {
		$san = sanitize_title($_POST['f_label']);
		
		if(!term_exists($san , 'pcud_fields')) {
			$index = $san;	
		} 
		else {
			for($a=1; $a < 50; $a++) {
				if(!term_exists($san.'_'.$a, 'pcud_fields')) {
					$index = $san.'_'.$a;
					break;	
				}
			}	
		}
	}
	
	// try to create it
	$args = array(
		'type' 		=> 'text',
		'subtype' 	=> '',
		'maxlen'	=> 255,
		'opt'		=> '',
		'placeh'	=> '',
		'note'		=> '',
		'regex'		=> '',
		'range_from'=> 0,
		'range_to'	=> 100
	);
	$result = wp_insert_term($label, 'pcud_fields', array('slug' => $index, 'description' => base64_encode(serialize($args))) );
	
	if(is_wp_error($result)) {
		echo $result->get_error_message();	
	} else {
		
		// add ID in fields list utility
		$fields_order = (array)get_option('pcud_custom_fields_order', array());
		update_option('pcud_custom_fields_order', array_merge($fields_order, array($result['term_id'])));
		
		echo 'success';	
	}

	die();
}
add_action('wp_ajax_pcud_add_field', 'pcud_add_field');



//////////////////////////////////////////////////////
////// DELETE CUSTOM FIELD ///////////////////////////
//////////////////////////////////////////////////////

function pcud_del_field() {
	if(!isset($_POST['pcud_nonce']) || !wp_verify_nonce($_POST['pcud_nonce'], 'lcwp_ajax')) {die('Cheating?');};
	
	if(!isset($_POST['f_slug']) || empty($_POST['f_slug'])) {
		die('Missing slug');	
	}
	$slug = $_POST['f_slug'];
	
	$fid = trim(addslashes($_POST['f_id'])); 
	if (!filter_var($fid, FILTER_VALIDATE_INT)) {die('Wrong field ID');} 
	
	// remove term
	$result = wp_delete_term($fid, 'pcud_fields');

	if($result === false) {
		die('Error deleting term');	
	}
	elseif(is_wp_error($result)) {
		echo $result->get_error_message();	
		die();
	}

	// in case, remove from users list fields
	$ulf = (array)get_option('pcud_fields_in_users_list', array());
	if(($key = array_search($slug, $ulf)) !== false) {
		unset($ulf[$slug]);
		update_option('pcud_fields_in_users_list', $ulf);
	}
	
	echo 'success';
	die();
}
add_action('wp_ajax_pcud_del_field', 'pcud_del_field');


///////////////////////////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////
////// ADD FORM TERM ///////////////////////////
////////////////////////////////////////////////

function pcud_add_form_term() {
	if(!isset($_POST['form_name'])) {die('data is missing');}
	$name = $_POST['form_name'];
	
	$resp = wp_insert_term( $name, 'pcud_forms', array( 'slug'=>sanitize_title($name)) );
	
	if(is_array($resp)) {die('success');}
	else {
		$err_mes = $resp->errors['term_exists'][0];
		die($err_mes);
	}
}
add_action('wp_ajax_pcud_add_form', 'pcud_add_form_term');


/////////////////////////////////////////////////
////// LOAD FORMS LIST //////////////////////////
/////////////////////////////////////////////////

function pcud_forms_list() {
	$forms = get_terms( 'pcud_forms', 'hide_empty=0' );

	// clean term array
	$clean_forms = array();

	foreach ( $forms as $form ) {
		$clean_forms[] = array('id' => $form->term_id, 'name' => $form->name);
	}
    
	echo json_encode($clean_forms);
	die();
}
add_action('wp_ajax_pcud_get_forms', 'pcud_forms_list');


////////////////////////////////////////////////
////// DELETE FORM TERM ////////////////////////
////////////////////////////////////////////////

function pcud_del_form_term() {
	if(!isset($_POST['form_id'])) {die('data is missing');}
	$id = addslashes($_POST['form_id']);
	
	$resp = wp_delete_term( $id, 'pcud_forms');

	if($resp == '1') {die('success');}
	else {die('error during the form deletion');}
}
add_action('wp_ajax_pcud_del_form', 'pcud_del_form_term');



////////////////////////////////////////////////
////// DISPLAY FORM BUILDER ////////////////////
////////////////////////////////////////////////

function pcud_form_builder() {
	require_once(PCUD_DIR . '/functions.php');
	require_once(PC_DIR .'/classes/pc_form_framework.php');
	
	if(!isset($_POST['form_id'])) {die('data is missing');}
	$form_id = addslashes($_POST['form_id']);

	// get all the fields
	$f_fw = new pc_form;
	
	// retrieve form term
	$term = get_term_by('id', $form_id, 'pcud_forms');
	if(!$term) {die('form not found');}
	
	// field selector
	?>
    <h3><?php echo $term->name; ?></h3>
    
    <div id="pcud_form_builder_top" class="postbox">
      <h3 class="hndle"><?php _e('Add Form Fields', 'pcud_ml') ?></h3>
      <div class="inside">
    
        <div>
          <table class="widefat pc_table"> 
            <tr>
              <td class="pc_label_td"><?php _e('Select field', 'pcud_ml'); ?></td>
              <td class="pc_field_td">
              	  <select data-placeholder="<?php _e('Select field', 'pcud_ml'); ?> .." name="pcud_fields_list" id="pcud_fields_list" class="lcweb-chosen" autocomplete="off" style="width: 400px;">
                    <?php 
                    foreach($f_fw->fields as $field => $data) {
                        if(!in_array($field, array_merge(pcud_wizards_ignore_fields(), array('username')) )) {
							echo '<option value="'.$field.'">'.$data['label'].'</option>';
						}
                    }
                    ?>
                    <option value="custom|||text"><?php _e('TEXT BLOCK', 'pc_ml') ?></option>
                  </select>
              </td>     
              <td>
                <div id="add_field_btn">
                  <input type="button" name="add_field" value="<?php _e('Add', 'pcud_ml'); ?>" class="button-secondary" />
                  <div style="width: 30px; padding-left: 7px; float: right;"></div>
                </div>
              </td>
            </tr>
          </table>  
        <div>  
      </div>
	</div>
    </div>
    </div>
    
    
	<?php 
    // get form fields
	if(empty($term->description)) {
		// retrocompatibility
		$form_fields = (array)get_option('pcud_form_'.$form_id, array());	
	} else {
		$form_fields = unserialize(base64_decode($term->description));	
	}
    ?>
  
    <h3><?php _e('Form Structure', 'pcud_ml') ?></h3>
    <table id="pcud_form_table" class="widefat pc_table">
      <thead>
      <tr>
        <th style="width: 15px;"></th>
        <th style="width: 15px;"></th>
        <th style="padding-left: 15px;"><?php _e('Field name', 'pcud_ml'); ?></th>
        <th><?php _e('Required?', 'pcud_ml'); ?></th>
      </tr>
      </thead>
      <tbody>
      	<?php 
		if(!empty($form_fields)) {
			$form_fields = pcud_v2_field_names_sanitize($form_fields);
			
			$incl 	= (array)$form_fields['include'];
			$req 	= (array)$form_fields['require'];
			$texts 	= (isset($form_fields['texts'])) ? (array)$form_fields['texts'] : array();  
			
			$txt_id = 0;
			foreach($incl as $f_name) {
				if($f_name == 'custom|||text' && isset($texts[$txt_id])) {
					echo '
					<tr rel="'.$field.'">
						<td><span class="pc_del_field"></span></td>
						<td><span class="pc_move_field"></span></td>
						<td colspan="2">
							<input type="hidden" name="pcud_include_field[]" value="'.$field.'" class="pcud_incl_f" />
							<textarea name="pcud_form_texts[]" placeholder="'. __('Supports HTML and shortcodes', 'pc_ml') .'">'. $texts[$txt_id] .'</textarea>
						</td>
					</tr>';
					
					$txt_id++;	
				}
				else {
					if(isset($f_fw->fields[$f_name])) {
						$field_data = $f_fw->fields[$f_name];
						$sel = (in_array($f_name, $req)) ? 'checked="checked"' : '';
						
						// if password or required email disable "required" switch 
						if($f_name == 'psw' || ($f_name == 'email' && $f_fw->mail_is_required)) {
							$dis_check= 'disabled="disabled"';
							$sel = 'checked="checked"';
						}
						else {$dis_check = '';}
	
						echo '
						<tr rel="'.$f_name.'">
						  <td><span class="pc_del_field"></span></td>
						  <td><span class="pc_move_field"></span></td>
						  <td style="padding-left: 15px;">
							<input type="hidden" name="pcud_include_field[]" value="'.$f_name.'" class="pcud_incl_f" />
							<span>'.$field_data['label'].'</span>
						  </td>	
						  <td><input type="checkbox" name="pcud_require_field[]" value="'.$f_name.'" '.$sel.' '.$dis_check.' class="pcud_req_f ip_checks" /></td>
						</tr>
						';		
					}
				}
			}
		}
		?>
      </tbody>
    </table>
    
    <?php 
	// form redirect
	$redirect = (is_array($form_fields) && isset($form_fields['redirect'])) ? $form_fields['redirect'] : ''; 
	
	// custom redirect 
	$custom_red = ($redirect == 'custom') ? $form_fields['cust_redir'] : ''; 
	
	// pages list
	$pages = get_pages(); 
	?>
    <h3><?php _e('Form Redirect', 'pcud_ml') ?></h3>
    <table id="pcud_form_table" class="widefat pc_table">
      </tbody>
        <tr>
          <td class="pc_label_td" rowspan="2"><?php _e("Redirect target", 'pcud_ml'); ?></td>
          <td class="pc_field_td">
              <select name="pcud_redirect" id="pcud_redirect" class="lcweb-chosen" data-placeholder="<?php _e('Select a page', 'pcud_ml') ?> .." autocomplete="off">
                  <option value=""><?php _e('No redirect', 'pcud_ml') ?></option>
                  <option value="custom" <?php if($redirect == 'custom') {echo 'selected="selected"';} ?>><?php _e('Custom redirect', 'pcud_ml') ?></option>
                  <?php
				  foreach ($pages as $pag ) {
					  $selected = ($redirect == $pag->ID) ? 'selected="selected"' : '';
					  echo '<option value="'.$pag->ID.'" '.$selected.'>'.$pag->post_title.'</option>';
                  }
                  ?>
              </select>   
          </td>
          <td><span class="info"><?php _e('Redirect target after successful form submission', 'pcud_ml') ?></span></td>
        </tr>
        <tr id="pcud_cust_redir_wrap">
        	<td colspan="2" <?php if($redirect != 'custom') {echo 'style="display: none;"';} ?>>
            	<input type="text" name="pcud_cust_redir" value="<?php echo pc_sanitize_input($custom_red); ?>" autocomplete="off" placeholder="<?php _e('insert a valid URL', 'pcud_ml') ?>" style="width: 100%;" />
            </td>
        </tr> 
      </tbody>
    </table>
	<?php
	die();
}
add_action('wp_ajax_pcud_form_builder', 'pcud_form_builder');



//////////////////////////////////////////////////
////// FORM GENERATOR - UPDATE FORM //////////////
//////////////////////////////////////////////////

function pcud_save_form() {
	if (!isset($_POST['pcud_nonce']) || !wp_verify_nonce($_POST['pcud_nonce'], 'lcwp_ajax')) {die('Cheating?');};
	
	include_once(PC_DIR . '/classes/simple_form_validator.php');
	$validator = new simple_fv;	
		
	$indexes = array();
	$indexes[] = array('index'=>'form_id', 'label'=>'form id', 'type'=>'int', 'required'=>true);
	$indexes[] = array('index'=>'fields_included', 'label'=>'fields included');
	$indexes[] = array('index'=>'fields_required', 'label'=>'fields required');
	$indexes[] = array('index'=>'texts', 'label'=>'text blocks');
	$indexes[] = array('index'=>'redirect', 'label'=>'redirect target');
	$indexes[] = array('index'=>'cust_redir', 'label'=>'custom redirect target');
	
	$validator->formHandle($indexes);
	$fdata = $validator->form_val;
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
					'include' 	=> (array)$fdata['fields_included'], 
					'require'	=>(array)$fdata['fields_required'], 
					'texts'		=>(array)$fdata['texts'],
					'redirect'	=> $fdata['redirect'],
					'cust_redir'=> $fdata['cust_redir']
				)
			)
		);

		// update	
		$result = wp_update_term($fdata['form_id'], 'pcud_forms', array(
			'description' => $descr
		));
		
		echo (is_wp_error($result)) ? $result->get_error_message() : 'success';
	}
	else {
		echo $error;	
	}
	die();
}
add_action('wp_ajax_pcud_save_form', 'pcud_save_form');
