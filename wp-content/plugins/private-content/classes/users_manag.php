<?php
// TOOLSET TO CREATE AND MANAGE USERS

class pc_users {
	// pvtContent fixed fields
	public $fixed_fields = array('id','insert_date','name','surname','username','psw','categories','email','tel','page_id','disable_pvt_page','status','wp_user_id','last_access'); 
	
	public $validation_errors = ''; // static resource for methods using validator - contains HTML code with errors
	public $wp_sync_error = ''; // static resource for WP sync errors printing - contains a string
	
	protected $user_id; // static resource storing user ID for sequential operations
	public $wp_user_sync; // flag to understand if wp-user-sync is enabled 
	
	
	// avoid to recall $wpdb var each time
	protected $db; 
	public function __construct() {
		$this->db = $GLOBALS['wpdb'];	
		$this->wp_user_sync = get_option('pg_wp_user_sync');
	}
		
	
	/* 
	 * USERS QUERY - fetches also User Data add-on
	 * @param (array) $args = query array - here's the legend
	 *
	 *  (int/array) user_id = specific user ID or IDs array to fetch (by default queries every user)
	 *  (int) limit = query limit (related to users) - default 100 (use -1 to fetch any)
	 *  (int) offset = query offset (related to users)
	 *  (int) status = user status (1 = active / 2 = disabled / 3 = pending) - by default uses false to fetch any
	 *  (int/array) categories = pc category IDs to filter users
	 *  (array) to_get = user data to fetch - by default is everything
	 *  (string) orderby = how to sort query results - default id
	 *  (string) order = sorting method (ASC / DESC) - default ASC
	 *  (array) search = array of associative arrays('key'=>$value, 'operator'=>'=', 'val'=>$value) to search users - supported operators (=, !=, >, <, >=, <=, IN, NOT IN, LIKE)
	 *  (string) custom_search = custom WHERE parameters to customize the search. Is added to the dynamically created code
	 *  (string) search_operator = operator to use for the query (AND / OR)
	 *  (bool) count = if true returns only query rows count
	 *
	 * @return (int/array) associative array(key=>val) for each user or query row count
	 */
	public function get_users($args = array()) {
		$supported_operators = array('=', '>', '<', '>=', '<=', 'IN', 'NOT IN', 'LIKE');
		$use_meta_join = false;
		
		$def_args = array(
			'user_id'	=> false,
			'limit' 	=> 100,
			'offset' 	=> 0,
			'status'	=> false,
			'categories'=> false,
			'to_get'	=> array(), 
			'orderby'	=> 'id',
			'order'		=> 'ASC',
			'search'	=> array(),
			'custom_search'		=> '',
			'search_operator' 	=> 'AND',
			'count'		=> false
		);
		$args = array_replace($def_args, $args);
		
		/*** search parameters ***/
		$user_s_parts = array();
		$meta_s_parts = array();
		
		// dynamic search params
		$search = (array)$args['search'];
		if(!empty($args['user_id'])) 	{$search[] = array('key'=>'id', 'val'=>$args['user_id']);}
		if(!empty($args['status'])) 	{$search[] = array('key'=>'status', 'val'=>(int)$args['status']);}	
		if(!empty($args['categories'])) {$search[] = array('key'=>'categories', 'val'=>(array)$args['categories']);}
		
		// split where clauses to use join
		$user_s_parts = array();
		$meta_s_parts = array();
		$multi_meta_s_join = array();
		
		$meta_s_count = 0; 
		foreach($search as $s) {
			
			// search terms check
			if(!isset($s['key']) || !isset($s['val'])) {continue;}
			if(!isset($s['operator'])) {$s['operator'] = 'IN';}
			
			// sanitize value
			$s['val'] = (is_array($s['val'])) ? $s_parts[] = array_map("addSlashes", $s['val']) : addslashes($s['val']);
							
			// build - fixed fields
			if(in_array($s['key'], $this->fixed_fields)) {
				
				if($s['key'] == 'id') { // users search
					$user_s_parts[] = "id IN ('". implode("','", (array)$s['val']) ."')";
				}
				elseif($s['key'] == 'categories') { // categories trick
					$user_s_parts[] = $this->categories_query($s['val']);
				} 
				else {
					$user_s_parts[] = $s['key'] . $this->get_search_part($s['val'], $s['operator']);
				}
			}
			
			// build - user meta
			else {
				$multi_meta_s = ($meta_s_count) ? 'um'.$meta_s_count.'.' : PC_META_TABLE.'.';
				$meta_s_parts[] = $multi_meta_s."meta_key = '". addslashes($s['key']) ."' AND ".$multi_meta_s."meta_value". $this->get_search_part($s['val'], $s['operator']);
				
				if($meta_s_count) {
					$multi_meta_s_join[] = 'LEFT OUTER JOIN wp_pc_user_meta AS um'.$meta_s_count.' ON id = um'.$meta_s_count.'.user_id';
				}
				
				$use_meta_join = true;
				$meta_s_count++;
			}
		}	
				
		// wrap up search parts
		if($args['search_operator'] != 'AND' && $args['search_operator'] != 'OR') {$args['search_operator'] = 'AND';}

		$user_cond = (count($user_s_parts)) ? implode(' '.$args['search_operator'].' ', $user_s_parts) : '';
		$meta_cond = (count($meta_s_parts)) ? implode(' '.$args['search_operator'].' ', $meta_s_parts) : '';
		$custom_cond = (!empty($args['custom_search'])) ? (string)$args['custom_search'] : '';
		
		$unified_cond = ($user_cond && $meta_cond) ? '('.$user_cond.' '.$args['search_operator'].' '.$meta_cond.')' : '('.$user_cond.$meta_cond.')';
		if($unified_cond == '()') {$unified_cond = ($custom_cond) ? '' : 1;}
		
		
		/*** what to get ***/
		$args['to_get'] = (array)$args['to_get'];
		$meta_on_get = '';
		
		// if counting - get only IDs
		if(!empty($args['count'])) {$args['to_get'] = array('id');}
		elseif(empty($args['to_get']) || in_array('*', $args['to_get'])) { // check the asterisk
			$args['to_get'] = array('*');
			$use_meta_join = true;	
		} 
		else {
			$meta_get = array();
			$get_not_in_fixed = (empty($args['to_get']) || in_array('*', $args['to_get'])) ? array() : array_diff($args['to_get'], $this->fixed_fields);
			
			// understand if is searching a meta
			if(!$use_meta_join && count($get_not_in_fixed)) {
				$use_meta_join = true;	
			} 
			
			// if get meta - split to_get 
			if($args['to_get'][0] != '*' && count($get_not_in_fixed)) {
				$meta_get = array();
				
				foreach($args['to_get'] as $k => $val) {
					if(!in_array($val, $this->fixed_fields)) {
						$meta_get[] = $val;
						unset($args['to_get'][$k]);	
					}
				}
				
				// query part - to append to ON join
				$meta_on_get = " AND ".PC_META_TABLE.".meta_key IN ('". implode("','", $meta_get) ."')"; 
			}
			else {$meta_on_get = '';}
			
			// be sure to fetch the user ID
			if(!in_array('id', $args['to_get']) ) {
				array_unshift($args['to_get'], "id");
			}
			
			// if meta query - get meta columns
			if($use_meta_join) {
				array_push($args['to_get'], PC_META_TABLE.'.meta_id', PC_META_TABLE.'.meta_key', PC_META_TABLE.'.meta_value');
			}
		}
		
		
		/*** order-by part ***/
		if(in_array($args['orderby'], $this->fixed_fields)) {
			$orderby = ' ORDER BY '. addslashes($args['orderby']) .' '.$args['order'];
		} else {
			$orderby = ' ORDER BY FIELD('.PC_META_TABLE.'.meta_key, "'. addslashes($args['orderby']) .'") DESC, '.PC_META_TABLE.'.meta_value '.$args['order'].', id ASC';
		}
		
		
		/*** limit part ***/
		if($args['limit'] === -1) {$args['limit'] = 9999999;}
		
		$limit_part = ' LIMIT '.(int)$args['offset'].',';
		$limit_part .= (!empty($args['count'])) ? '9999999' : (int)$args['limit'];
		
		/*** SETUP QUERY ***/
		$query = 'SELECT ';
		$query .= addslashes(implode(',', array_unique($args['to_get'])));
		$query .= ' FROM ';
	
		
		/*** FROM management - to be as light as possible ***/
		// if not fetching meta and sorting by fixed field
		if(!$use_meta_join && in_array($args['orderby'], $this->fixed_fields) && !count($multi_meta_s_join) && empty($custom_cond)) {
			$query .= ' '.PC_USERS_TABLE.'
				WHERE '.$unified_cond.
				$orderby.
				$limit_part;
		}
		// not fetching meta but sorting by them or searching by them - or counting
		elseif(!$use_meta_join || !empty($args['count'])) {
			$query .= '
				'.PC_USERS_TABLE.'
				LEFT OUTER JOIN '.PC_META_TABLE.'
				ON id = '.PC_META_TABLE.'.user_id
				'.implode(' ',$multi_meta_s_join).'
				WHERE '.$unified_cond.' '.$custom_cond.'  
			   	GROUP BY id'. 
				$orderby.
				$limit_part;
		}
		// fetching meta and sorting by fixed field - without custom condition
		elseif($use_meta_join && in_array($args['orderby'], $this->fixed_fields) && !count($multi_meta_s_join) && empty($custom_cond)) {
			$query .= ' (
				SELECT '.implode(',', $this->fixed_fields).' FROM '.PC_USERS_TABLE.'
				WHERE '.$unified_cond.'
			   	GROUP BY id'. 
				$orderby.
				$limit_part.'
			) as users_table 
			LEFT OUTER JOIN '.PC_META_TABLE.' 
			ON users_table.id = '.PC_META_TABLE.'.user_id '.$meta_on_get.'
			LIMIT 9999999'; 
		}
		// fetching meta and sorting by them
		else {
			$query .= ' (
				SELECT '.implode(',', $this->fixed_fields).' FROM '.PC_USERS_TABLE.'
				LEFT OUTER JOIN '.PC_META_TABLE.' 
				ON id = '.PC_META_TABLE.'.user_id
				'.implode(' ',$multi_meta_s_join).'
				WHERE '.$unified_cond.' '.$custom_cond.'
			   	GROUP BY id'. 
				$orderby.
				$limit_part.'
			) as users_table 
			LEFT OUTER JOIN '.PC_META_TABLE.' 
			ON users_table.id = '.PC_META_TABLE.'.user_id '.$meta_on_get.'
			LIMIT 9999999'; 
		}

		// DEBUG - print query structure
		//echo $query;

		// perform
		if(empty($args['count'])) {
			$data = $this->db->get_results($query);
			if(!is_array($data)) {return array();}
			$users = array();
			
			// create array using user ID as index and managing metas
			foreach($data as $row) {
				$uid = 'u'.$row->id; // array index
				
				// flag to fixed data once
				if(!isset($users[$uid])) {	
					$new_user = true;
					$users[$uid] = array();
				} 
				else {$new_user = false;}
				
				// fixed fields
				foreach($row as $field => $val) {
					if(in_array($field, $this->fixed_fields) && $new_user) {
						$users[$uid][$field] = ($field == 'categories') ? unserialize($val) : $val;	
					}
				}
				
				// meta
				if(isset($row->meta_key) && !empty($row->meta_key)) {
					/* PC-FILTER - add the ability to manage fetched meta value */
					$users[$uid][$row->meta_key] = apply_filters('pc_meta_val', maybe_unserialize($row->meta_value), $row->meta_key);	
				}
			}
			
			// if searching specific metas - check if someuser hasn't got it and add empty array keys
			if(isset($get_not_in_fixed)) {
				foreach($users as $key => $user) {
					foreach($get_not_in_fixed as $meta) {
						if(!isset($user[$meta])) {
							$users[$key][$meta] = false;	
						}
					}
				}
			}
			
			// recreate the array, using standard numeric indexes
			$final_arr = array();
			foreach($users as $key => $val) {
				$final_arr[] = $val;	
			}
			
			return $final_arr;
		}
		else {
			$this->db->query($query);
			return $this->db->num_rows;
		}	
	}

	/* utility - query search block setup according to value type and operator */
	private function get_search_part($val, $operator) {
		if($operator == 'IN' || $operator == 'NOT IN') {
			return 	' '. $operator . " ('". implode("','", (array)$val) ."')";
		}
		else {
			return ' '. $operator ." '". (string)$val ."'";
		}
	}
	
	/* query part for categories
	 * @param (array) $cats = categories ID array
	 */
	public function categories_query($cats) {
		$cat_s = array();
		
		foreach((array)$cats as $cat_id) {
			$cat_s[] = "categories LIKE '%\"". (int)$cat_id ."\"%'";
		}
		return '('.implode(') OR (', $cat_s) . ')';
	}
	
	
	
	/* GET SINGLE USER DATA
	 * @param (int) $user_id = the user ID to match
	 * @param (array) $args = get_users query args (except user_id index)
	 * @return (bool/array) false if user is not found otherwise associative data array for the user
	 */
	public function get_user($user_id, $args = array()) {
		$args['user_id'] = $user_id; 
		$data = $this->get_users($args);
		
		if(!is_array($data) || !count($data)) {
			return false;	
		} else {
			return $data[0];
		}
	}
	
	
	/* GET SINGLE FIELD FOR A SINGLE USER
	 * @param (int) $user_id = the user ID to match
	 * @param (string) $field = field name to retreve - could be a fixed field or a meta
	 * @return (bool/mixed) false if user is not found otherwise the field value
	 */
	public function get_user_field($user_id, $field) {
		$args = array(
			'user_id' 	=> $user_id,
			'to_get'	=> array($field)
		);
		$data = $this->get_users($args);
		
		if(!is_array($data) || !count($data)) {
			return false;	
		}
		else {
			return $data[0][$field];
		}
	}
	
	
	
	/* CONVERT FETCHED DATA TO A HUMAN READABLE FORMAT 
	 * @param (string) $index = index relative to the value stored in database (could be a fixed field or a meta key)
	 * @param (mixed) $data = fetched data related to the index
	 * @param (bool) $ignore_dates = whether to ignore insert and registration dates
	 */
	public function data_to_human($index, $data, $ignore_dates = false) {
		include_once('pc_form_framework.php');
		$form_fw = new pc_form;	
		
		// date WP options
 		if(!property_exists('pc_users', 'wp_date_format')) {$this->wp_date_format = get_option('date_format');}
		if(!property_exists('pc_users', 'wp_time_format')) {$this->wp_time_format = get_option('time_format');}
		if(!property_exists('pc_users', 'wp_timezone')) {$this->wp_timezone = get_option('timezone_string');}
		
		
		// PC-FILTER - given the index control how data are shown in human format
		$orig_data = $data;
		$data = apply_filters('pc_data_to_human', $data);
		if($data !== $orig_data) {return $data;}
		
		// standard cases
		if($index == 'categories' && !empty($data)) {
			if(!is_array($data)) {$data = unserialize($data);}
			$terms = get_terms('pg_user_categories', array('include'=>$data, 'orderby'=>'none', 'fields'=>'names', 'hide_empty'=>false));
			$data = implode(', ', (array)$terms);
		}
		elseif($index == 'insert_date' && !$ignore_dates) {
			$data = '<time title="'. date_i18n($this->wp_date_format.' - '.$this->wp_time_format ,strtotime($data)) .' '. $this->wp_timezone .' timezone">'. 
				date_i18n($this->wp_date_format ,strtotime($data)) .'</time>';
		}
		elseif($index == 'last_access' && !$ignore_dates) {
			include_once(PG_DIR . '/functions.php');
			
			if(strtotime($data) < 0) {$data = '<small>'.__('no access', 'pc_ml').'</small>';}
			else {
				$data = '<time title="'. date_i18n($this->wp_date_format.' - '.$this->wp_time_format ,strtotime($data)) .' '. $this->wp_timezone .' timezone">'. 
				pc_elapsed_time($data).' '.__('ago', 'pc_ml').'</time>';	
			}
		}
		
		// if field is single-opt checkbox - print a check
		elseif(isset($form_fw->fields[$index]) && $form_fw->fields[$index]['type'] == 'single_checkbox' && !empty($data)) {
			$data = '&radic;';	
		}
		
		return (is_array($data)) ? implode(', ', $data) : $data;	
	}
	
	
	/////////////////////////////////////////////////////////////////////////////////////////////
	
	
	/* INSERT USER - performs a basic data validation + psw strength + username and mail unicity + WP sync outcome, specific ones must be performed using the pc_insert_user_data_check filter
	 * eventually performs WP-user-sync
	 * performs meta insertion
	 *
	 * @param (array) $data = user data, associative array containing fixed ones and meta data
	 *
	 * fixed fields array indexes(
		  name, 		(string) max 150 chars
		  surname, 		(string) max 150 chars 
		  username, 	(string) max 150 chars - mandatory 
		  tel, 			(string) max length 20 chars
		  email, 		(string) valid e-mail max length 255 chars - if WP-sync enabled is mandatory 
		  psw 			(string) (mandatory), 
		  disable_pvt_page, (bool) 1 or 0 
		  categories, (array) containing categories ID
	 * )
	 * @param (int) $status = user status (1=active, 2=disabled, 3=pending)
	 * @param (bool) $allow_wp_sync_fail = whether to allow registration also if WP user sync fails
	 *
	 * @return (int/bool) the user ID if is successfully added otherwise false
	 */
	public function insert_user($data, $status = 1, $allow_wp_sync_fail = false) {
		include_once('pc_form_framework.php');
		$form_fw = new pc_form;	
		
		// put array elements in $_POST globval to use validator
		foreach((array)$data as $key => $val) {$_POST[$key] = $val;}
		
		// if password repeat field is empty - clone automatically (useful for import)
		if(!isset($_POST['check_psw'])) {
			$_POST['check_psw'] = (isset($_POST['psw'])) ? $_POST['psw'] : '';	
		}
		
		// form structure - mandatory registration fields
		$form_fields = array('username', 'psw', 'categories');
		$require = ($form_fw->mail_is_required) ? array('email') : array();
		
		// add $data fields
		$form_fields = array_merge($form_fields, array_keys($data));

		/* PC-FILTER - customize required fields for user registration */
		$require = apply_filters('pc_insert_user_required_fields', $require);
		
		$form_structure = array(
			'include' => array_unique($form_fields),
			'require' => array_unique($require)
		);	
		
		// validation structure
		$indexes = $form_fw->generate_validator($form_structure);

		// add index for disable_pvt_page
		if(in_array('disable_pvt_page', $form_fields)) { 
			$indexes[] = array('index'=>'disable_pvt_page', 'label'=>__("Disable private page", 'pc_ml'), 'type'=>'int', 'max_len'=>1);
		}
		
		/*** standard validation ***/
		$is_valid = $form_fw->validate_form($indexes);
		$fdata = $form_fw->form_data;

		/*** advanced/custom validations ***/
		if($is_valid) {
			// if allow WP-sync error - set global to disable debug (might broke ajax answers)
			if($allow_wp_sync_fail) {$GLOBALS['pc_disable_debug'] = true;}
			
			$params = array(
				'fdata' => $fdata,
				'allow_wp_sync_fail' => $allow_wp_sync_fail
			);
			$this->specific_user_check('insert', $params);
			if(!empty($this->validation_errors)) {return false;}
			
			/* PC-FILTER - custom data validation before user insertion - pass/return HTML code for error message */
			$this->validation_errors = apply_filters('pc_insert_user_data_check', $this->validation_errors, $fdata);
			if(!empty($this->validation_errors)) {return false;}
		}
		

		// abort or create
		if(!$is_valid) {
			$this->validation_errors = $form_fw->errors;
			return false;
		}
		else {
			$this->validation_errors = '';
			
			// create user page
			global $current_user;
			$fdata = $form_fw->form_data;

			$new_entry = array();
			$new_entry['post_author'] = $current_user->ID;
			$new_entry['post_content'] = get_option('pg_pvtpage_default_content', '');
			$new_entry['post_status'] = 'publish';
			$new_entry['post_title'] = $fdata['username'];
			$new_entry['post_type'] = 'pg_user_page';
			$pvt_pag_id = wp_insert_post($new_entry, true);
			
			if(!$pvt_pag_id) {
				$this->debug_note(__('Error during user page creation', 'pc_ml'));
				return false;
			}
			else {
				/*** add user ***/
				// prepare query array with fixed fields
				$query_arr = array();
				
				foreach($this->fixed_fields as $ff) {
					switch($ff) {
						case 'categories' 	: $val = serialize((array)$fdata[$ff]); break;
						case 'psw' 			: $val = $this->encrypt_psw($fdata[$ff]); break;
						default				: $val = isset($fdata[$ff]) ? $fdata[$ff] : false; break;	
					}
					if($val !== false) {$query_arr[$ff] = $val;}	
				}
				$query_arr['insert_date'] = current_time('mysql');
				$query_arr['page_id'] = $pvt_pag_id;
				$query_arr['status'] = ((int)$status >= 1 && (int)$status <= 3) ? (int)$status : 1;

				// wp-user-sync
				if($this->wp_user_sync && isset($fdata['email']) && !empty($fdata['email'])) {
					include_once('wp_user_sync.php');
					global $pc_wp_user;	
					
					$wp_user_id = $pc_wp_user->sync_wp_user($fdata);
					if($wp_user_id) {
						$query_arr['wp_user_id'] = $wp_user_id;
						$this->wp_sync_error = $pc_wp_user->pwu_sync_error;
					}
					else {$this->wp_sync_error = '';}
				}
				
				// insert
				$result = $this->db->insert(PC_USERS_TABLE, $query_arr);	
				
				if(!$result) {
					$this->debug_note(__('Error inserting user data into database', 'pc_ml'));
					$this->validation_errors = __('Error updating user data into database', 'pc_ml');
					return false;	 
				} 
				else {
					$user_id = $this->db->insert_id;

					// insert metas
					$this->save_meta_fields($user_id, $form_structure['include'], $fdata);
					
					/* PC-ACTION - triggered when user is added */
					do_action('pc_user_added', $user_id);
					
					return $user_id;
				}
			}
		}
	}


	
	/* UPDATE USER - performs data validation following what declared in registered pvtContent fields 
	 * and eventually psw strength + username and mail unicity + WP sync outcome, specific ones must be performed using pc_update_user_data_check filter
	 * performs meta update
	 * 
	 * @param (int) $user_id = user id to update
	 * @param (array) $data = user data, associative array containing fixed ones and meta data. Check insert_user for fields legend + you can use status key
	 *
	 * @return (bool) true is successfully updated otherwise false
	 */
	public function update_user($user_id, $data) {
		include_once('pc_form_framework.php');
		$form_fw = new pc_form;	
		
		// wp-sync init
		if($this->wp_user_sync) {
			include_once('wp_user_sync.php');
			global $pc_wp_user;
			$is_wp_synced = $pc_wp_user->pvtc_is_synced($user_id);		
		}
		else {$is_wp_synced = false;}
		
		// put array elements in $_POST globval to use validator
		foreach((array)$data as $key => $val) {$_POST[$key] = $val;}
		
		/*** form structure ***/
		$form_fields = array();
		$require = (isset($data['email']) && $form_fw->mail_is_required) ? array('email') : array();
		
		// add $data fields
		foreach((array)$data as $key => $val) {$form_fields[] = $key;}
		
		/* PC-FILTER - customize required fields for user update */
		$require = apply_filters('pc_update_user_required_fields', $require);
		
		$form_structure = array(
			'include' => array_unique($form_fields),
			'require' => array_unique($require)
		);

		// if WP synced - ignore username
		if($this->wp_user_sync && $is_wp_synced) {
			if(($key = array_search('username', $form_structure['include'])) !== false) {
				unset($form_structure['include'][$key]);
			}	
		}
		
		// if password is empty - ignore
		if(in_array('psw', $form_structure['include']) && (!isset($data['psw']) || empty($data['psw']))) {
			if(($key = array_search('psw', $form_structure['include'])) !== false) {
				unset($form_structure['include'][$key]);
			}		
		}
		
		// if password is ok but repeat password doesn't exist - set it
		if(in_array('psw', $form_structure['include']) && !isset($data['check_psw'])) {
			$_POST['check_psw'] = $data['psw']; 	
			$data['check_psw'] = $_POST['check_psw'];		
		}


		// validation structure
		$indexes = $form_fw->generate_validator($form_structure);

		// add index for disable_pvt_page
		if(in_array('disable_pvt_page', $form_fields)) { 
			$indexes[] = array('index'=>'disable_pvt_page', 'label'=>__("Disable private page", 'pc_ml'), 'type'=>'int', 'max_len'=>1);
		}

		/*** standard validation ***/
		$is_valid = $form_fw->validate_form($indexes, array(), $user_id);
		$fdata = $form_fw->form_data;
		
		/*** advanced/custom validations ***/
		if($is_valid) {
			$params = array(
				'fdata'		=> $fdata,
				'user_id' 	=> $user_id,
				'wp_synced' => $is_wp_synced
			);
			$this->specific_user_check('update', $params);
			if(!empty($this->validation_errors)){return false;}
			
			/* PC-FILTER - custom data validation before user insertion - pass/return HTML code for error message */
			$this->validation_errors = apply_filters('pc_update_user_data_check', $this->validation_errors, $fdata);
			if(!empty($this->validation_errors)){return false;}
		}
		

		// abort or update
		if(!$is_valid) {
			$this->validation_errors = $form_fw->errors;
			return false;
		}
		else {
			$this->validation_errors = '';
			
			/*** update user ***/
			// prepare query array with fixed fields
			$query_arr = array();
			foreach($this->fixed_fields as $ff) {
				if(isset($fdata[$ff])) {
					switch($ff) {
						case 'categories' 	: $val = serialize((array)$fdata[$ff]); break;
						case 'psw' 			: $val = $this->encrypt_psw($fdata[$ff]); break;
						default				: $val = isset($fdata[$ff]) ? $fdata[$ff] : false; break;	
					}	
					if($val !== false) {$query_arr[$ff] = $val;}	
					
					// sanitize known data for saving
					if(isset($query_arr['disable_pvt_page'])) {$query_arr['disable_pvt_page'] = (int)$query_arr['disable_pvt_page'];}
				}
			}

			// only if there are fixed fields to save
			if(!empty($query_arr)) {
				$result = $this->db->update(PC_USERS_TABLE, $query_arr,  array('id' => (int)$user_id));
			} else {
				$result = 0; // simulate "no fields updated" response
			}

			if($result === false) { // if data is same, returns 0. Check for false
				$this->debug_note(__('Error updating user data into database', 'pc_ml'));
				$this->validation_errors = __('Error updating user data into database', 'pc_ml');
				return false;	
			} 
			else {
				// if is wp-synced
				if($this->wp_user_sync && $is_wp_synced) {
					$wp_user_id = $pc_wp_user->sync_wp_user($fdata, $is_wp_synced->ID);
				}
				
				// update metas
				$this->save_meta_fields($user_id, $form_structure['include'], $fdata);
				
				/* PC-ACTION - triggered when user is updated - passes user id */
				do_action('pc_user_updated', $user_id);
				
				return true;
			}
		}
	}
	
	
	/* SAVE/UPDATE METAS DURING USER SAVE/UPDATE 
	 * @param (array) $user_id - id of the updated/inserted user
	 * @param (array) $fields - fields passed in the insert/update function
	 * @param (array) $fdata - fields data, fetched by simple_form_validator engine
	 */
	private function save_meta_fields($user_id, $fields, $fdata) {
		$remaining = array_diff((array)$fields, $this->fixed_fields);
		if(count($remaining)) {
			include_once('meta_manag.php');	
			$meta = new pc_meta;
			
			foreach($fields as $f) {
				if(in_array($f, $this->fixed_fields)) {continue;}
				
				// TODO - check fields with non-latin names
				$meta->add_meta($user_id, $f, $fdata[$f]);
			}
		}
	}
	
	
	/* CHECK MAIL UNICITY, STATUS VALUE AND WP-SYNC OUTCOME (if required)
	 * @param (string) $action = when function is called? (insert/update)
	 * @param (array) $params = associative array containing parameters used in the function
	 * @return (string) error message to stop user insertion/update or empty string
	 */
	private function specific_user_check($action, $params) {
		$fdata = $params['fdata'];
		
		// WP user sync - includes and declarations
		if($this->wp_user_sync) {
			include_once('wp_user_sync.php');
			global $pc_wp_user;
		}
		else {$is_wp_synced = false;}
		
		
		// status
		if($action == 'update' && isset($fdata['status'])) {
			if(!in_array((int)$fdata['status'], array(1,2,3))) {
				$this->validation_errors .= __('Wrong status value', 'pc_ml').'<br/>';
			}
		}
		
		// mail unicity 
		if(isset($fdata['email']) && !empty($fdata['email']) && !get_option('pg_allow_duplicated_mails')) {
			$user_id = ($action == 'update') ? $params['user_id'] : false;
			
			if($this->user_mail_exists($fdata['email'], $user_id)) {
				$this->validation_errors .= __('Another user has the same e-mail', 'pc_ml').'<br/>';
				return false;
			}
		}

		// check possible WP sync
		if($this->wp_user_sync) {
			if($action == 'insert') {
				if(!$params['allow_wp_sync_fail']) {
					if($pc_wp_user->wp_user_exists($fdata['username'], $fdata['email'])) {
						$this->validation_errors .= __('Another user has the same username or e-mail', 'pc_ml');	
						return false;
					}	
				}
			}
			else {
				if(isset($fdata['email'])) {
					$wp_user_id = ($params['wp_synced']) ? $params['wp_synced']->ID : 0; 
					if($params['wp_synced']) {
						if(!$pc_wp_user->new_mail_is_ok($wp_user_id, $fdata['email'])) {
							$this->validation_errors .= __('WP sync - another user has the same e-mail', 'pc_ml');	
							return false;	
						}
					}
				}
			}
		}
			
		return false;	
	}
	
	
	/* CHANGE USERS STATUS  (enable/activate or disable)
	 * @param (int/array) $users_id - one target user ID or ID's array
	 * @param (int) $new_status - new status to apply (1=active, 2=disabled)
	 * @return (int) number of users with changed status (zero could mean user already had that status)
	 */
	public function change_status($users_id, $new_status) {
		if(!in_array((int)$new_status, array(1,2))) {
			$this->debug_note('wrong status value');
			return 0;
		}
		
		// get current user without new status to avoid useless changes
		$args = array(
			'user_id' 	=> (array)$users_id,
			'to_get'	=> array('id', 'status'),
			'search'	=> array('key'=>'status', 'val'=>(int)$new_status, 'operator'=>'!=')
		);
		$result = $this->get_users($args);
		
		$affected = array();
		foreach($result as $user) {
			if($user['status'] != (int)$new_status) {
				if($user['status'] == 3) {
					// PC-ACTION - pending user is activated (thrown just BEFORE database change) - passes user ID
					do_action('pc_user_activated', $user['id']);
				}
				
				/* PC-ACTION - triggered when user is updated - passes user id */
				do_action('pc_user_updated', $user['id']);
				
				$affected[] = $user['id'];
			}
		}
		
		// update affected ones
		if(count($affected)) {
			$result = $this->db->query( 
				$this->db->prepare( 
					"UPDATE ".PC_USERS_TABLE." SET status = %d WHERE ID IN (". implode(',', $affected) .")",
					(int)$new_status
				) 
			);
		}
		
		return count($affected);
	}
	
	
	/* DELETE USER (and private page and metas and WP unsync) 
	 * @param (int) $user_id - the user ID to remove 
	 * @return (bool)
	 */
	public function delete_user($user_id) {
		if((int)$user_id == 0) {
			$this->debug_note('invalid user ID');	
			return false;
		}
		
		// get private page IDs
		$pvt_pag_id = (int)$this->get_user_field($user_id, 'page_id');
		if(!$pvt_pag_id) {
			$this->debug_note('invalid user ID');	
			return false;
		}
		
		// PC-ACTION - triggered before deleting an user
		do_action('pc_pre_user_delete', $user_id);
		
		$result = $this->db->delete(PC_USERS_TABLE, array('id' => $user_id));
		if(!$result) {
			$this->debug_note('error deleting user');	
			return false;
		}
		
		else {
			// PC-ACTION - triggered after user (and meta) deletion
			do_action('pc_deleted_user', $user_id);	
			
			// delete private page
			wp_delete_post($pvt_pag_id, $force_delete = true);
			
			// delete metas
			$result = $this->db->delete(PC_META_TABLE, array('user_id' => $user_id));
			
			// unsync with WP
			include_once('wp_user_sync.php');
			global $pc_wp_user;	
			$pc_wp_user->detach_wp_user($user_id, $save_in_db = false);	
			
			return true;
		}
	}
	 

	/////////////////////////////////////////////////////////////////////////////////////////////

	
	/* CHECK WHETHER E-MAIL IS ALREADY USED
	 * @param (string) $email = the user e-mail
	 * @param (int) $user_id = user_id to exclude
	 * @return (int/bool) the user ID with same e-mail or false if is unique
	 */
	public function user_mail_exists($email, $user_id = false) {
		$exclde_user = ($user_id) ? 'AND id != '.(int)$user_id : '';
		$val = $this->db->get_results(
			$this->db->prepare("SELECT id FROM ".PC_USERS_TABLE." WHERE email = %s ".$exclde_user." LIMIT 1", 
				$email
			)
		);
		
		return(!empty($val)) ? $val[0]->id : false;	
	}

	
	/* password hashing system */
	public function encrypt_psw($psw) {
		return base64_encode( serialize( array(base64_encode($psw), md5(sha1(10091988*strlen($psw))) )));
	}
	public function decrypt_psw($psw) {
		$clean = (array)unserialize(base64_decode($psw));
		return base64_decode($clean[0]);
	}
	

	/* username to ID */
	public function username_to_id($username) {
		// reduce DB load using globals
		if(!isset($GLOBALS['pc_un_to_id'])) {$GLOBALS['pc_un_to_id'] = array();}
		if(isset($GLOBALS['pc_un_to_id'][$username])) {return $GLOBALS['pc_un_to_id'][$username];}
		
		$val = $this->db->get_results(
			$this->db->prepare( "SELECT id FROM ".PC_USERS_TABLE." WHERE username = %s AND status != 0 LIMIT 1", $username)
		);

		if(!count($val)) {
			$this->debug_note('no user found with this username');
			$this->user_id = false;
			return false;
		}
		else {
			$this->user_id = (int)$val[0]->id;
			$GLOBALS['pc_un_to_id'][$username] = (int)$val[0]->id;
			return (int)$val[0]->id;
		}
	}
	
	
	/* ID to username */
	public function id_to_username($user_id) {
		// reduce DB load using globals
		if(!isset($GLOBALS['pc_id_to_un'])) {$GLOBALS['pc_id_to_un'] = array();}
		if(isset($GLOBALS['pc_id_to_un'][$user_id])) {return $GLOBALS['pc_id_to_un'][$user_id];}
		
		$val = $this->db->get_results(
			$this->db->prepare( "SELECT username FROM ".PC_USERS_TABLE." WHERE id = %d AND status != 0 LIMIT 1", $user_id)
		);

		if(!count($val)) {
			$this->debug_note('no user found with this id');
			return false;
		}
		else {
			$GLOBALS['pc_id_to_un'][$user_id] = $val[0]->username;
			return $val[0]->username;
		}
	}	
	
	
	/* BE SURE that variable contains an user id (int value)
	 *
	 * @param (int/string) $subj = variable used to target anuser via id or username
	 * @return (int) the user id or zero
	 */
	protected function check_user_id($subj) {
		if(!filter_var($subj, FILTER_VALIDATE_INT)) {
			return (int)$this->username_to_id($subj);
		} else {
			$this->user_id = $subj;
			return $subj;
		}
	}
	
	
	/* UTILITY - use trigger_error function to track debug notes */
	protected function debug_note($message) {
		if(!isset($GLOBALS['pc_disable_debug']) && defined('WP_DEBUG') && WP_DEBUG) {
			trigger_error('PrivateContent - '.$message);	
		}
		return true;
	}
}

$GLOBALS['pc_users'] = new pc_users();
