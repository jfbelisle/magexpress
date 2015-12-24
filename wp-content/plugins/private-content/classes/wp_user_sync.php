<?php
// TOOLSET TO SYNC PVTCONTENT UESRS WITH WP ONES
include_once('users_manag.php');

class pc_wp_user extends pc_users {
	
	public $forbidden_roles = array('pvtcontent', 'administrator', 'editor', 'author'); // forbidden custom roles to be applied to synced users
	public $wps_roles = false; // custom roles to apply to synced WP users

	public $pwu_sync_error = false; // sync wordpress error

	
	/* Global sync - for every user */
	public function global_sync() {
		global $wpdb, $pc_users;
		
		// disable debug
		$GLOBALS['pc_disable_debug'] = true;
		
		$user_query = $wpdb->get_results("SELECT username, psw, email, name, surname FROM ".PC_USERS_TABLE." WHERE status != 0 AND wp_user_id = 0", ARRAY_A);
		if(!is_array($user_query) || count($user_query) == 0) {return __('All users already synced', 'pc_ml');}
		
		$not_synced = 0;
		$synced = 0;
		foreach($user_query as $ud) {
			if(empty($ud['email'])) {$not_synced++;}
			else {
				$ud['psw'] = $pc_users->decrypt_psw($ud['psw']);
				$result	= $this->sync_wp_user($ud, 0, true); 
				
				if(!filter_var($result, FILTER_VALIDATE_INT)) {$not_synced++;}
				else {$synced++;}
			}
		}	
		
		$ns_mess = ($not_synced > 0) ? ' <em>('.$not_synced.' '.__("can't be synced because of their username or e-mail", 'pc_ml').')</em>' : '';
		return $synced.' '. __('Users synced successfully!', 'pc_ml') . $ns_mess;
	}
	
	
	/* 
	 * Sync a pvtContent user with a WP one (add or update)
	 * @param (array) $user_data - associative array containing data to use for new WP user. Indexes: username (required if not updating), email (required), psw, name, surname
	 * @param (int) $existing_id = WP user id to be updated
	 * @param (bool) $save_in_db = whether to save the created WP user id in pvtContent database 
	 *
	 * @return (bool/int) the created/updated user ID or false
	 */
	public function sync_wp_user($user_data = array(), $existing_id = 0, $save_in_db = false) {
		if(empty($existing_id)) {
			if(!isset($user_data['username'])) {
				$this->debug_note('WP-sync - username is mandatory to sync with WP user');
				return false;
			}
			if(!isset($user_data['email'])) {
				$this->debug_note('WP-sync - e-mail is mandatory to sync with WP user');
				return false;
			}
			if(!isset($user_data['psw'])) {
				$this->debug_note('WP-sync - password is mandatory to sync with WP user');
				return false;
			}
		}
		
		/* args composition */
		$args = array('role' => 'pvtcontent');
		if(empty($existing_id)) {
			$args['user_login'] = $user_data['username'];
			$args['user_email'] = $user_data['email'];
		}
		else {
			if(isset($user_data['email'])) {$args['user_email'] = $user_data['email'];}
		}
		if(isset($user_data['psw'])) {$args['user_pass'] = $user_data['psw'];}
		if(isset($user_data['name'])) {$args['first_name'] = $user_data['name'];}
		if(isset($user_data['surname'])) {$args['last_name'] = $user_data['surname'];}
		
		// update user
		if(!empty($existing_id)) {
			add_filter('send_password_change_email', '__return_false', 999);
			add_filter('send_email_change_email', '__return_false', 999);
			
			$args['ID'] = $existing_id;
			
			// nicename 
			if(isset($args['first_name']) && isset($args['last_name'])) {
				$nicename = $args['first_name'] .' '.$args['last_name'];
				if(trim($nicename) != '') {
					$args['user_nicename'] = $nicename;
					$args['display_name'] = $nicename;	
				}
			}
	
			$wp_user_id = wp_update_user($args);
		}
		else {
			$wp_user_id = wp_insert_user($args) ;
		}

		
		if(is_wp_error($wp_user_id) ) {
			$this->pwu_sync_error = $wp_user_id->get_error_message();
			$this->debug_note('WP-sync - '. $this->pwu_sync_error );
			return false;
		}
		else {
			$this->wp_sync_error = false;
			
			if(!$existing_id) {
				$this->set_wps_custom_roles($wp_user_id, true);
				
				// PC-ACTION - pvtcontent user has been synced with WP user
				do_action('pc_user_synced_with_wp', $user_data['username'], $wp_user_id);
			}
			
			// if not updating - add record in pvtcontent DB
			if(!$existing_id && $save_in_db) {
				global $wpdb;
				$wpdb->query( 
					$wpdb->prepare( 
						"UPDATE ".PC_USERS_TABLE." SET wp_user_id = %d WHERE username = %s AND status != 0",
						$wp_user_id,
						$user_data['username']
					) 
				);	
			}
					
			return $wp_user_id;
		}	
	}
	
	
	
	/* Search existing pvtContent -> WP matches and sync */
	public function search_and_sync_matches() {
		global $wpdb;
		
		$user_query = $wpdb->get_results("SELECT username, psw, email, name, surname FROM ".PC_USERS_TABLE." WHERE status != 0 AND wp_user_id = 0 AND email != ''");
		if(!is_array($user_query) || count($user_query) == 0) {return __('All users already synced', 'pc_ml');}
		
		$synced = 0;
		foreach($user_query as $ud) {
			$existing_username = username_exists($ud->username);
			$existing_mail = email_exists($ud->email);
				
			if($existing_username && $existing_username == $existing_mail) {
				add_filter('send_password_change_email', '__return_false', 999);
				add_filter('send_email_change_email', '__return_false', 999);
				
				$userdata = array(
					'ID' 			=> $existing_username,
					'user_pass'		=> $this->decrypt_psw($ud->psw),
					'first_name'	=> $ud->name,
					'last_name'		=> $ud->surname,
					'role'			=> 'pvtcontent'
				);	
				$wp_user_id = wp_update_user($userdata);
				
				if(filter_var($wp_user_id, FILTER_VALIDATE_INT)) {
					$synced++;
					
					global $wpdb;
					$wpdb->query( 
						$wpdb->prepare( 
							"UPDATE ".PC_USERS_TABLE." SET wp_user_id = %d WHERE username = %s AND status != 0",
							$wp_user_id,
							$ud->username
						) 
					);	
					
					$this->set_wps_custom_roles($wp_user_id, true);
				}
			}
		}	
		
		return $synced .' '. __('matches found and syncs performed', 'pc_ml');
	}
	
	
	
	/* Global detach */
	public function global_detach() {
		global $wpdb;
		
		$user_query = $wpdb->get_results("SELECT id FROM ".PC_USERS_TABLE." WHERE wp_user_id != 0 AND status != 0");
		if(!is_array($user_query) || count($user_query) == 0) {return __('All users already detached', 'pc_ml');}
		
		foreach($user_query as $ud) {
			$result	= $this->detach_wp_user($ud->id);
		}	
		
		return __('Users detached successfully!', 'pc_ml');
	}
	
	
	/* 
	 * Detach a pvtContent user with related WP one and delete it
	 * (int) $user_id = privatecontent user id
	 * (bool) $save_in_db = whether update sync record in pvtContent database 
	 */
	public function detach_wp_user($user_id, $save_in_db = true) {
		$wp_user_id = $this->get_user_field($user_id, 'wp_user_id');
		if(empty($wp_user_id)) {return true;}
		
		// PC-ACTION - pvtcontent user is being detached from WP user. Used right before WP user deletion
		do_action('pc_user_detached_from_wp', $user_id, $wp_user_id);
		
		wp_delete_user($wp_user_id);
		
		if($save_in_db) {
			global $wpdb;
			$wpdb->query( 
				$wpdb->prepare( 
					"UPDATE ".PC_USERS_TABLE." SET wp_user_id = 0 WHERE id = %d AND status != 0",
					$user_id
				) 
			);			
		}
		return true;
	}
	
	
	
	/* 
	 * Check if a wp user is linked to a pvtcontent user
	 * @param (int) $user_id = wordpress user id
	 * @return (bool/obj) false if user is not synced otherwise the query object
	 */
	public function wp_user_is_linked($user_id) {
		global $wpdb;
		if(empty($user_id)) {return false;}
		
		$user_data = $wpdb->get_row( 
			$wpdb->prepare(
				"SELECT id, categories, status FROM ".PC_USERS_TABLE." WHERE wp_user_id = %d LIMIT 1",
				$user_id
			) 
		);
		return $user_data;
	}
	
	
	
	/* 
	 * Check whether a pvtcontent user is synced 
	 * @param (int) $user_id = privatecontent user id
	 * @params (bool) $return_id = whether to return found WP user id directly
	 * @return (bool/obj) false if not synced, otherwise the synced user data
	 */
	public function pvtc_is_synced($user_id, $return_id = false) {
		global $wpdb;
		if(empty($user_id)) {return false;}
		
		$user = $wpdb->get_row( 
			$wpdb->prepare(
				"SELECT wp_user_id FROM ".PC_USERS_TABLE." WHERE id = %d AND status != 0 LIMIT 1",
				$user_id
			) 
		);

		$exists = get_userdata($user->wp_user_id);
		if($return_id) {
			return (!$exists) ? false : $exists->ID;
		} else {
			return (!$exists) ? false : $exists;
		}
	}
	
	
	/* Update WP user nicename */
	public function update_nicename($wp_user_id) {
		$ud = get_userdata($wp_user_id);
		
		$nicename = $ud->user_firstname .' '. $ud->user_lastname;
		if(empty($nicename)) {$nicename = $ud->user_login;}
		
		wp_update_user(array(
			'ID'=>$wp_user_id, 
			'user_nicename' => $nicename, 
			'display_name' => $nicename
		));
	}
	
	
	/* Check if new e-mail is ok for an existing WP user */
	public function new_mail_is_ok($wp_user_id, $email) {
		$exists = email_exists($email);
		return (!$exists || $exists == $wp_user_id) ? true : false;
	}
	
	
	/* Check whether username or password already exists for a WP user 
	 * @return (bool) true if exists, otherwise false
	 */
	public function wp_user_exists($username, $email) {
		return (!username_exists($username) && !email_exists($email)) ? false : true; 	
	}
	
	
	
	/* Get custom roles assigned to synced WP users - set it also in $this->wps_roles
	 * @return (array) array of WP roles to assign
	 */
	public function get_wps_custom_roles() {
		if($this->wps_roles) {return $this->wps_roles;}
		
		$this->wps_roles = array_unique( array_merge(array('pvtcontent'), (array)get_option('pc_custom_wps_roles', array()) ));
		return $this->wps_roles;
	}
	
	
	/* Apply custom roles - performs a DB query replacing original values as serialized array
	 * @param (int/array) $user_id = single WP user id to update or multiple IDs - by default updates any synced user
	 * @param (bool) $is_new_user = if true, avoid update if role is only pvtcontent
	 *
	 * @return (bool) true if successful otherwise false 
	 */
	public function set_wps_custom_roles($user_id = false, $is_new_user = false) {
		$this->get_wps_custom_roles();	
		
		// be sure pvtContent role is in
		if(!in_array('pvtcontent', $this->wps_roles)) {
			$this->wps_roles = array_merge(array('pvtcontent'), $this->wps_roles);	
		}
		
		// if new user and only pvtcontent - do nothing
		if($is_new_user && $this->wps_roles === array('pvtcontent')) {
			return false;	
		}
		
		if(!$user_id) {
			// get all synced users
			$users = $this->get_users(array(
				'limit'		=> -1,
				'search' 	=> array(array('key'=>'wp_user_id', 'operator'=>'!=', 'val'=>0)),
				'to_get' 	=> array('wp_user_id')
			));
			
			// build WP users ID array
			if(!count($users)) {return true;}
			$wp_users_id = array();
			
			foreach($users as $u) {
				$wp_users_id[] = $u['wp_user_id'];	
			}
		}
		else{
			$wp_users_id = (array)$user_id;	
		}
		
		// setup roles array
		$roles_array = array('pvtcontent' => true);
		foreach($this->wps_roles as $role) {
			if($role && !isset($roles_array[$role]) && !in_array($role, $this->forbidden_roles)) {
				$roles_array[$role] = true;
			}
		}
		
		// perform
		$result = $this->db->query( 
			$this->db->prepare( 
				"UPDATE ".$this->db->prefix."usermeta SET meta_value = %s WHERE meta_key = 'wp_capabilities' AND user_id IN (". implode(',', $wp_users_id) .")",
				serialize($roles_array)
			) 
		);
		
		if($result === false) {
			$this->debug_note('Error updating WP user capabilities');	
		}
		return ($result === false) ? false : true;
	}
}


$GLOBALS['pc_wp_user'] = new pc_wp_user;
