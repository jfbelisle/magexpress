<?php
// TOOLSET TO CREATE AND MANAGE USERS

class pc_form {
	public $fields = array(); // registered fields array 
	public $mail_is_required = false; // flag for required mail 
	
	public $errors = ''; // form validation errors (HTML code)
	public $form_data = array(); // array containing form's data (associative array(field_name => value))
	
	
	/* INIT - setup plugin fields and whether mail is required 
	 * @param (array) $args = utility array, used to setup differently fields 
	 *	- use_custom_cat_name = whether to use custom category name
	 *	- strip_no_reg_cats = remove categories not allowed on registration
	 *
	 */
	public function __construct($args = array()) {
		include_once(PC_DIR .'/functions.php');
		
		// check if WP user sync is required - otherwise use PCMA mail verifier filter
		$this->mail_is_required = (get_option('pg_wp_user_sync') && get_option('pg_require_wps_registration')) ? true : apply_filters('pcma_set_mail_required', false);
		
		///////////////////////
		$fist_last_name = get_option('pg_use_first_last_name');
		$custom_cat_name = (isset($args['use_custom_cat_name']) || isset($GLOBALS['pc_custom_cat_name'])) ? trim(get_option('pg_reg_cat_label', '')) : '';
		
		$fields = array(
			'name' => array(
				'label' 	=> ($fist_last_name) ? __('First name', 'pc_ml') : __('Name', 'pc_ml'),
				'type' 		=> 'text',
				'subtype' 	=> '',
				'maxlen' 	=> 150,
				'opt'		=> '',
				'placeh'	=> '',
				'note' 		=> ($fist_last_name) ? __('User first name', 'pc_ml') : __('User name', 'pc_ml')
			),
			'surname' => array(
				'label' 	=> ($fist_last_name) ? __('Last name', 'pc_ml') : __('Surname', 'pc_ml'),
				'type' 		=> 'text',
				'subtype' 	=> '',
				'maxlen' 	=> 150,
				'opt'		=> '',
				'placeh'	=> '',
				'note' 		=> ($fist_last_name) ? __('User last name', 'pc_ml') : __('User name', 'pc_ml')
			),
			'username' => array(
				'label' 	=> __('Username', 'pc_ml'),
				'type' 		=> 'text',
				'subtype' 	=> '',
				'maxlen' 	=> 150,
				'opt'		=> '',
				'placeh'	=> '',
				'note' 		=> __('Username used for the login', 'pc_ml'),
				'sys_req' 	=> true,
			),
			'psw' => array(
				'label' 	=> __('Password', 'pc_ml'),
				'type' 		=> 'password',
				'subtype' 	=> '',
				'minlen' 	=> get_option('pg_psw_min_length', 4),
				'maxlen' 	=> 50,
				'opt'		=> '',
				'note' 		=> __('Password used for the login', 'pc_ml'),
				'sys_req' 	=> true
			),
			'categories' => array(
				'label' 	=> (empty($custom_cat_name)) ? __('Category', 'pc_ml') : $custom_cat_name,
				'type' 		=> 'assoc_select',
				'subtype' 	=> '',
				'maxlen' 	=> 20,
				'opt'		=> (isset($args['strip_no_reg_cats'])) ? pc_user_cats(true) : pc_user_cats(),
				'note' 		=> 'PrivateContent '. __('Categories', 'pc_ml'),
				'multiple'	=> (get_option('pg_reg_multiple_cats')) ? true : false,		
				'sys_req' 	=> true
			),
			'email' => array(
				'label' 	=> __('E-Mail', 'pc_ml'),
				'type' 		=> 'text',
				'subtype' 	=> 'email',
				'maxlen' 	=> 255,
				'opt'		=> '',
				'placeh'	=> '',
				'note' 		=> __('User E-mail', 'pc_ml'),
				'sys_req' 	=> $this->mail_is_required 
			),  
			'tel' => array(
				'label' 	=> __('Telephone', 'pc_ml'),
				'type' 		=> 'text',
				'subtype' 	=> '',
				'maxlen' 	=> 20,
				'opt'		=> '',
				'placeh'	=> '',
				'note' 		=> __('User Telephone', 'pc_ml')
			),
			'pc_disclaimer' => array(
				'label' 	=> __("Disclaimer", 'pc_ml'),
				'type' 		=> 'single_checkbox',
				'subtype' 	=> '',
				'maxlen' 	=> 1,
				'opt'		=> '1',
				'check_txt'	=> strip_tags((string)get_option('pg_disclaimer_txt'), '<br><a><strong><em>'),
				'disclaimer'=> true,
				'note' 		=> __('Registration disclaimer', 'pc_ml'),
				'sys_req' 	=> true
			)
		);	
	
		# PC-FILTER - add fields to the usable ones
		$this->fields = apply_filters('pc_form_fields_filter', $fields);
	}
	

	/* Retrieves a field from plugin ones */
	public function get_field($field_name) {
		return (isset($this->fields[$field_name])) ? $this->fields[$field_name] : false;
	}
	
	/* Retrieves field name from plugin ones */
	public function get_field_name($field) {
		return (isset($this->fields[$field])) ? $this->fields[$field]['label'] : false;
	}
	
	
	/* FORM CODE GENERATOR 
	 * @param (array) $fields = multidimensional array containing included and required fields array('include'=>array, 'require'=>array)
	 * @param (string) $custom_fields = custom HTML code to add custom fields to the form
	 * @param (int) $user_id = pvtContent user ID, to populate fields with its data 
	 */
	public function form_code($fields, $custom_fields = false, $user_id = false) {
		$included = $fields['include'];
		$required = $fields['require'];
		
		$disclaimers = '';
		
		$txt_count = 0;
		$texts = (isset($fields['texts']) && is_array($fields['texts'])) ? $fields['texts'] : array(); 
		
		if(!is_array($included)) {return false;}
		
		// if is specified the user id get data to fill the field
		if($user_id) {
			include_once('users_manag.php');
			$user = new pc_users;
			$query = $user->get_users(array(
				'user_id' => $user_id,
				'to_get' => $included
			)); 
			$ud = $query[0];
		}
		else {$ud = false;}

		$form = '<ul class="pc_form_flist">';
		foreach($included as $field) {
			
			// if is a text block
			if($field == 'custom|||text') {
				if(isset($texts[$txt_count])) {
					$form .= '
					<li class="pc_form_txt_block pc_ftb_'.$txt_count.'">
						'. do_shortcode($texts[$txt_count]) .'
						<hr class="pc_clear" />
					</li>';
					
					$txt_count++;
				}
			}
			
			// normal field
			else {
				$fdata = $this->get_field($field);		
				if($fdata) {
					// required message
					$req = (in_array($field, $required) || (isset($fdata['sys_req']) && $fdata['sys_req'])) ? '<span class="pc_req_field">*</span>' : '';
					
					// field classes
					$field_class = sanitize_title(urldecode($field));
					if($fdata['type'] == 'text' && ($fdata['subtype'] == 'eu_date' || $fdata['subtype'] == 'us_date')) {$field_class .= ' pcud_datepicker pcud_dp_'.$fdata['subtype'];}
					$type_class = 'class="'. $field_class .'"';
					
					// options for specific types
					if($fdata['type'] != 'assoc_select') {$opts = $this->get_field_options($fdata['opt']);}
					
					// field class - for field wrapper
					$multiselect_class = (($fdata['type'] == 'select' || $fdata['type'] == 'assoc_select') && isset($fdata['multiple']) && $fdata['multiple']) ? 'pc_multiselect' : '';
					$singlecheck_class = ($fdata['type'] == 'single_checkbox' && (!isset($fdata['disclaimer']) || empty($fdata['disclaimer']))) ? 'pc_single_check' : '';
					$f_class = 'class="pc_rf_field pc_rf_'. sanitize_title(urldecode($field)) .' '.$multiselect_class.' '.$singlecheck_class.'"';
					
					// placeholder
					$placeh = (isset($fdata['placeh']) && !empty($fdata['placeh'])) ? 'placeholder="'.$fdata['placeh'].'"' : '';
					
					
					// text types
					if($fdata['type'] == 'text') {
						$val = ($ud) ? $ud[$field] : false;
						$form .= '
						<li '.$f_class.'>
							<label>'. __($fdata['label'], 'pc_ml') .' '.$req.'</label>
							<input type="'.$fdata['type'].'" name="'.$field.'" value="'.pc_sanitize_input($val).'" maxlength="'.$fdata['maxlen'].'" '.$placeh.' autocomplete="off" '.$type_class.'  />
							<hr class="pc_clear" />
						</li>';		
					}
					
					// password type
					elseif($fdata['type'] == 'password') {					
						$form .= '
						<li '.$f_class.'>
							<label>'. __($fdata['label'], 'pc_ml') .' '.$req.'</label>
							<input type="'.$fdata['type'].'" name="'.$field.'" value="" maxlength="' . $fdata['maxlen'] . '" '.$type_class.' autocomplete="off" />
							<hr class="pc_clear" />
						</li>
						<li class="pc_rf_field pc_rf_psw_confirm">	
							<label>'. __('Repeat password', 'pc_ml').' '.$req.'</label>
							<input type="'.$fdata['type'].'" name="check_'.$field.'" value="" maxlength="' . $fdata['maxlen'] . '" autocomplete="off" '.$type_class.' />
							<hr class="pc_clear" />
						</li>';			
					}
					
					// textarea
					elseif($fdata['type'] == 'textarea') {
						$val = ($ud) ? $ud[$field] : false;
						$form .= '
						<li '.$f_class.'>
							<label class="pc_textarea_label">'. __($fdata['label'], 'pc_ml') .' '.$req.'</label>
							<textarea name="'.$field.'" class="pc_textarea '.$field_class.'" '.$placeh.' autocomplete="off">'.$val.'</textarea>
							<hr class="pc_clear" />
						</li>';		
					}
					
					// select
					elseif($fdata['type'] == 'select') {	
						$multiple = (isset($fdata['multiple']) && $fdata['multiple']) ? 'multiple="multiple"' : '';
						$multi_name = ($multiple) ? '[]' : '';
						
						$form .= '
						<li '.$f_class.'>
							<label>'. __($fdata['label'], 'pc_ml') .' '.$req.'</label>
							<select name="'.$field.$multi_name.'" '.$type_class.' '.$multiple.' autocomplete="off">';
						
						foreach($opts as $opt) { 
							$sel = ($ud && in_array($opt, (array)$ud[$field])) ? 'selected="selected"' : false;
							$form .= '<option value="'.$opt.'" '.$sel.'>'.$opt.'</option>'; 
						}
						
						$form .= '
							</select>
							<hr class="pc_clear" />
						</li>';			
					}
					
					// associative select (for pg categories)
					elseif($fdata['type'] == 'assoc_select') {	
						$multiple = (isset($fdata['multiple']) && $fdata['multiple']) ? 'multiple="multiple"' : '';
						$multi_name = ($multiple) ? '[]' : '';
						
						$form .= '
						<li '.$f_class.'>
							<label>'. __($fdata['label'], 'pc_ml') .' '.$req.'</label>
							<select name="'.$field.$multi_name.'" '.$multiple.' autocomplete="off">';
						
						foreach($fdata['opt'] as $key => $val) { 
							$sel = ($ud && $ud[$field] == $key) ? 'selected="selected"' : false;
							$form .= '<option value="'.$key.'" '.$sel.'>'.$val.'</option>'; 
						}
						
						$form .= '
							</select>
							<hr class="pc_clear" />
						</li>';			
					}
					
					// checkbox
					elseif($fdata['type'] == 'checkbox') {	
						$form .= '
						<li '.$f_class.'>
							<label class="pc_cb_block_label">'. __($fdata['label'], 'pc_ml') .' '.$req.'</label>
							<div class="pc_check_wrap">';
							
							foreach($opts as $opt) { 
								$sel = ($ud && in_array($opt, (array)$ud[$field])) ? 'checked="checked"' : false;
								$form .= '<input type="checkbox" name="'.$field.'[]" value="'.$opt.'" '.$sel.' autocomplete="off" /> <label class="pc_check_label">'.$opt.'</label>'; 
							}
						$form .= '
							</div>
							<hr class="pc_clear" />
						</li>';
					}
					
					// single-option checkbox
					elseif($fdata['type'] == 'single_checkbox') {	
						$sel = ($ud && !empty($ud[$field])) ? 'checked="checked"' : '';
						
						if(!isset($fdata['disclaimer']) || empty($fdata['disclaimer'])) {
							$form .= '
							<li '.$f_class.'>
								<input type="checkbox" name="'.$field.'" value="1" '.$sel.' autocomplete="off" />
								<label>'. $fdata['check_txt'] .' '.$req.'</label>
								<hr class="pc_clear" />
							</li>';
						} 
						else {
							$disclaimers .= '
							<li class="pc_rf_disclaimer">
								<div class="pc_disclaimer_check"><input type="checkbox" name="'.$field.'" value="1" '.$sel.' autocomplete="off" /></div>
								<div class="pc_disclaimer_txt">'. $fdata['check_txt'] .'</div>
							</li>';
						}
					}
				}
			}
		}
		
		if($custom_fields) {$form .= $custom_fields;}
		
		if(!empty($disclaimers)) {
			$form .= '<li class="pc_rf_disclaimer_sep"></li>' . $disclaimers;	
		}
		
		return $form . '</ul>';
	}
	
	
	/* GET OPTIONS ARRAY for select, checkbox and radio fields 
	 * @param (string) $opts = string of options, comma spilt (if receives an array - returns it)
	 * @return (array) array of options
	 */
	public function get_field_options($opts) {
		if(is_array($opts)) {return $opts;}
		if(trim($opts) == '') {return false;}
		
		$opts_arr = explode(',', $opts);
		foreach($opts_arr as $opt) {
			$right_opts[] = trim($opt);	
		}
		return $right_opts;
	}

	
	/* FIELDS DATA AGGREGATOR - given an indexes array, scan $_GET and $_POST to store form data - if not found use false 
	 * @param (bool) $stripslashes = whether to use stripslashes to get true values after WP filters
	 * @return (array) associative array (index => val)
	 */
	public function get_fields_data($fields, $stripslashes = true) {
		if(!is_array($fields)) {return false;}	
		
		$return = array();
		foreach($fields as $f) {
			if(isset($_POST[$f])) {$return[$f] = $_POST[$f];}
			elseif(isset($_GET[$f])) {$return[$f] = $_GET[$f];}
			else {$return[$f] = false;}
			
			$return[$f] = (is_string($return[$f]) && $stripslashes) ? stripslashes($return[$f]) : $return[$f];
		}
		
		return $return;
	}


	
	/* SIMPLE-FORM-VALIDATOR - create array indexes
	 * @param (array) $form_structure = multidimensional array containing included and required fields array('include'=>array, 'require'=>array)
	 * @param (array) $custom_valid = additional validation indexes in case of extra fields
	 * @return (array) validator indexes
	 */
	public function generate_validator($form_structure, $custom_valid = array()) {
		$included = (array)$form_structure['include'];
		$required = (array)$form_structure['require'];
		
		// merge the two arrays to not have missing elements in included
		$included = array_merge($included, $required);
		if(empty($included)) {return array();}
		
		$indexes = array();
		$a = 0;
		foreach($included as $index) {
			$fval = $this->get_field($index);
			
			$indexes[$a]['index'] = str_replace('.', '_', $index); // fix for dots in indexes
			$indexes[$a]['label'] = urldecode($fval['label']);
			
			// required
			if(in_array($index, $required) || (isset($fval['sys_req']) && $fval['sys_req'])) {
				$indexes[$a]['required'] = true;
			}
			
			// min-length
			if($fval['type'] == 'password' || ($fval['type'] == 'text' && empty($fval['subtype']))) {
				if(isset($fval['minlen'])) {$indexes[$a]['min_len'] = $fval['minlen'];}
			}
			
			// maxlenght
			if($fval['type'] == 'text' && (empty($fval['subtype']) || $fval['subtype'] == 'int')) {
				$indexes[$a]['max_len'] = $fval['maxlen'];
			}
			
			// specific types
			if($fval['type'] == 'text' && !empty($fval['subtype'])) {
				$indexes[$a]['type'] = $fval['subtype'];
			}
	
			// allowed values
			if(($fval['type'] == 'select' || $fval['type'] == 'checkbox') && !empty($fval['opt'])) {
				// remove spaces between elements
				$indexes[$a]['allowed'] = explode(',', str_replace(array(' ,', ', '), ',', $fval['opt']));
			}
			
			// numeric value range
			if($fval['type'] == 'text' && in_array($fval['subtype'], array('int', 'float')) && isset($fval['range_from']) && $fval['range_from'] !== '') {
				$indexes[$a]['min_val'] = (float)$fval['range_from'];
				$indexes[$a]['max_val'] = (float)$fval['range_to'];
			}
			
			// regex validation
			if(in_array($fval['type'], array('text', 'textarea')) && isset($fval['regex']) && !empty($fval['regex'])) {
				$indexes[$a]['preg_match'] = $fval['regex'];			
			}
	
			////////////////////////////
			// password check validation
			if($index == 'psw') {
				// add fields check
				$indexes[$a]['equal'] = 'check_psw';
				
				// check psw validation
				$a++;
				$indexes[$a]['index'] = 'check_psw';
				$indexes[$a]['label'] = __('Repeat password', 'pc_ml');
				$indexes[$a]['maxlen'] = $fval['maxlen'];
			}
	
			$a++;	
		}
		
		if(is_array($custom_valid)) {
			$indexes = array_merge($indexes, $custom_valid);	
		}
		return $indexes;
	}
	
	
	
	/* VALIDATE FORM DATA - using simple_form_validator
	 * @param (array) $indexes = validation structure built previously
	 * @param (array) $custom_errors = array containing html strings with custom errors
	 * @param (int) $user_id = utility value to perform database checks - contains a PC user ID
	 * @param (bool) $specific_checks = whether to perform categories and username unicity checks. Useful to avoid double checks on frontend insert/update
	 * @return (bool) true if form is valid, false otherwise (errors and data can be retrieved in related obj properties)
	 */
	public function validate_form($indexes, $custom_errors = array(), $user_id = false, $specific_checks = true) {
		include_once('simple_form_validator.php');
		global $wpdb;
		
		$validator = new simple_fv;	
		$validator->formHandle((array)$indexes);
		$fdata = $validator->form_val;
		
		// clean data and save options
		foreach($fdata as $key=>$val) {
			if(is_string($val)) {
				$fdata[$key] = stripslashes($val);
			} 
			elseif(is_array($val)) {
				$fdata[$key] = array();
				foreach($val as $arr_val) {$fdata[$key][] = stripslashes($arr_val);}
			}
		}
		
		/*** special validation cases ***/
		foreach($indexes as $field) {
		
			// password strength
			if($field['index'] == 'psw') {
				$psw_strength = $this->check_psw_strength($fdata['psw']);
				if($psw_strength !== true) {
					$validator->custom_error[__("Password strength", 'pc_ml')] = $psw_strength;
				}
			}
			
			// username unicity 
			if($specific_checks && $field['index'] == 'username') {
				$already_exists = ($user_id) ? ' AND id != '. (int)$user_id : '';
				$wpdb->query(
					$wpdb->prepare("SELECT id FROM ".PC_USERS_TABLE." WHERE username = %s AND status != 0 ".$already_exists." LIMIT 1", trim((string)$fdata['username']))
				);
				if($wpdb->num_rows) {
					$validator->custom_error[__("Username", 'pc_ml')] =  __("Another user already has this username", 'pc_ml');	
				}
			}
			
			// categories
			if($specific_checks && $field['index'] == 'categories' && !empty($fdata['categories'])) {
				$cats = (!isset($GLOBALS['pc_escape_no_reg_cats'])) ? pc_user_cats(false) : pc_user_cats(true);	
				
				foreach((array)$fdata['categories'] as $f_cat) {
					if(!isset($cats[$f_cat])) {
						$name = $this->fields['categories']['label']; 
						$validator->custom_error[$name] =  __("One or more chosen categories are wrong", 'pc_ml');	
						break;	
					}
				}
			}
		}
		
		// wrap up
		$this->form_data = $fdata;
		$errors = $validator->getErrors();
		
		if(!empty($custom_errors)) {
			if(!empty($errors)) {$errors .= '<br/>';}
			$errors .= implode('<br/>', $custom_errors);	
		}
		
		// PC-FILTER - add custom errors on form validation - passes errors string and form data
		$this->errors = apply_filters('pc_form_valid_errors', $errors, $fdata);
		
		return (empty($this->errors)) ? true : false;		
	}


	
	/* PASSWORD STRENGTH VALIDATOR - made to work with simple-form-validator errors 
	 * @return (bool/string) true if password is ok - otherwise string containing errors
	 */
	public function check_psw_strength($psw) {
		$options = get_option('pg_psw_strength', array());
		if(!is_array($options) || count($options) == 0) {return true;}
		
		// regex validation
		$new_error = array();
		foreach($options as $opt) {
			if($opt == 'chars_digits') {
				if(!preg_match("((?=.*\d)(?=.*[a-zA-Z]))", $psw)) {$new_error[] = __('characters and digits', 'pc_ml');}	
			}
			elseif($opt == 'use_uppercase') {
				if(!preg_match("(.*[A-Z])", $psw)) {$new_error[] = __('an uppercase character', 'pc_ml');}	
			}
			elseif($opt == 'use_symbols') {
				if(!preg_match("(.*[^A-Za-z0-9])", $psw)) {$new_error[] = __('a symbol', 'pc_ml');}	
			}
		}
		if(count($new_error) > 0) {
			$regex_err = __('must contain at least ', 'pc_ml') .' '. implode(', ', $new_error);	
		}
		
		return (!isset($regex_err)) ? true : $regex_err;
	}	
	
	
	
	// honeypot antispam code generator
	public function honeypot_generator() {
		$calculation = mt_rand(0, 100) + mt_rand(0, 100);
		$hash = md5(sha1($calculation));
		
		return '
		<div class="pc_hnpt_code" style="display: none; visibility: hidden; position: fixed; left: -9999px;">
			<label for="pc_hnpt_1">Antispam 1</label>
			<input type="text" name="pc_hnpt_1" value="" autocomplete="off" />
			
			<label for="pc_hnpt_2">Antispam 2</label>
			<input type="text" name="pc_hnpt_2" value="'.$calculation.'" autocomplete="off" />
			
			<label for="pc_hnpt_3">Antispam 3</label>
			<input type="text" name="pc_hnpt_3" value="'.$hash.'" autocomplete="off" />
		</div>'; 
	}
	
	
	// honeypot antispam validator
	public function honeypot_validaton() {
		// three fields must be valid
		if(!isset($_POST['pc_hnpt_1']) || !isset($_POST['pc_hnpt_2']) || !isset($_POST['pc_hnpt_3'])) {return false;}
		
		// first field must be empty
		if(!empty($_POST['pc_hnpt_1'])) {return false;}
		
		// hash of second must be equal to third
		if(md5(sha1($_POST['pc_hnpt_2'])) != $_POST['pc_hnpt_3']) {return false;}
		
		return true;
	}
}

