<?php
// TOOLSET TO MANAGE USERS META
include_once('users_manag.php');

class pc_meta extends pc_users {
	
	/* FIND OUT IF USER HAS GOT META 
	 *
	 * @param (int/string) subj - user id or username
	 * @param (string) meta_name = meta option to find
	 * @param (array) get = fields to return - by default only meta_id 
	 *
	 * @return meta_id or false
	 */
	public function has_meta($subj, $meta_name, $get = array('meta_id')) {
		$val = $this->db->get_results(
			$this->db->prepare("SELECT ". addslashes(implode(',', (array)$get)) ." FROM ".PC_META_TABLE." WHERE user_id = %d AND meta_key = %s LIMIT 1", 
				$this->check_user_id($subj),
				$meta_name
			)
		);
		
		return(count($val)) ? $val[0] : false;
	}
	
	
	/* GET USER META 
	 *
	 * @param (int/string) subj - user id or username
	 * @param (string) meta_name = meta option to find
	 * @return meta value ready to be used or false
	 */
	public function get_meta($subj, $meta_name) {
		$val = $this->has_meta($subj, $meta_name, 'meta_value');		
		return($val !== false) ? maybe_unserialize($val->meta_value) : false;
	}
	
	
	/* ADD USER META - if meta already exists -> update it
	 *
	 * @param (int/string) subj - user id or username
	 * @param (string) meta_name = meta name - max 255 chars long
	 * @param (mixed) value = meta valuw - can by anything
	 * @return (mixed) meta id if successfull, otherwise false  
	 */
	public function add_meta($subj, $meta_name, $value) {
		if(!$this->check_meta_name($meta_name)) {return false;}

		// check if exists
		if($this->has_meta($subj, $meta_name) !== false) {
			return $this->update_meta($subj, $meta_name, $value);	
		}
		else {
			if(!$this->user_id) {return false;}	
			include_once(PC_DIR . '/functions.php');
		
			// add
			$result = $this->db->insert( 
				PC_META_TABLE, 
				array(
					'user_id' 	=> $this->user_id,
					'meta_key' 	=> $meta_name,
					'meta_value'=> pc_serialize_sanitize($value)
				)
			);
			
			return ($result) ? $this->db->insert_id : false;	
		}
	}
	
	
	/* UPDATE USER META - if meta doesn't exist -> create it
	 *
	 * @param (int/string) subj - user id or username
	 * @param (string) meta_name = meta name - max 255 chars long
	 * @param (mixed) value = meta value - can by anything
	 * @return (bool) true if successfull, otherwise false  
	 */
	
	public function update_meta($subj, $meta_name, $value) {
		if(!$this->check_meta_name($meta_name)) {return false;}
		
		// check if exists
		if($this->has_meta($subj, $meta_name) === false) {
			return $this->add_meta($subj, $meta_name, $value);	
		}
		else {
			if(!$this->user_id) {return false;}	
			include_once(PC_DIR . '/functions.php');
			
			// update
			return $this->db->update( 
				PC_META_TABLE, 
				array( 
					'meta_value' => pc_serialize_sanitize($value),
				), 
				array(
					'user_id' 	=> $this->user_id,
					'meta_key' 	=> $meta_name
				) 
			);
		}
	}	 
	
	
	/* DELETE USER META
	 *
	 * @param (int/string) subj - user id or username
	 * @param (string) meta_name = meta option to find
	 */
	public function delete_meta($subj, $meta_name) {
		return $this->db->delete(PC_META_TABLE, array(
				'user_id' 	=> $this->check_user_id($subj),
				'meta_key' 	=> $meta_name,
			)
		);
	}
	
	
	/* BULK META ACTION - performs a bulk meta action forone or multiple users 
	 *
	 * @param (int/string/array) $users = single user ID/username or array containing usernames or IDs but must be uniform
	 * @param (string) $action = bulk action to perform (add/update/delete)
	 * @param (string/array) meta_name = single meta name or meta names array - max 255 chars long
	 * @param (mixed/array) value = meta value or array of meta values (in this case, must copy meta_name array length) - can by anything
	 * @param (string) data_type = declares the data type passed in the first array (id/username)
	 * @return (bool) true if operation is complete, otherwise false
	 */
	public function bulk_meta_action($users, $action, $meta_name, $value = '', $data_type = 'id') {
		$users = (array) $users;
		if(!count($users)) {
			$this->debug_note('No users specified');
			return false;
		}
		
		if(is_array($meta_name) && !isset($value[0])) {
			$this->debug_note('no multi value found for multi meta names');
			return false;	
		}
		
		// perform
		$a = 0;
		if($action == 'add') {
			foreach($users as $user) {
				if(is_array($meta_name)) {
					foreach($meta_name as $name) {
						$this->add_meta($user, $name, $value[$a]);
						$a++;
					}
				}
				else {$this->add_meta($user, $meta_name, $value);}
			}	
		}
		elseif($action == 'update') {
			foreach($users as $user) {
				if(is_array($meta_name)) {
					foreach($meta_name as $name) {
						$this->update_meta($user, $name, $value[$a]);
						$a++;
					}
				}
				else {$this->update_meta($user, $meta_name, $value);}
			}	
		}
		elseif($action == 'delete') {
			foreach($users as $user) {
				if(is_array($meta_name)) {
					foreach($meta_name as $name) {
						$this->delete_meta($user, $name);
						$a++;
					}
				}
				else {$this->delete_meta($user, $meta_name);}
			}		
		}
		return true;
	}
	
	
	/* UTILITY - validate meta name */
	private function check_meta_name($meta_name) {
		if(empty($meta_name) || (!is_string($meta_name) && !is_numeric($meta_name))) {
			$this->debug_note('meta name must be a valid string');
			return false;
		}
		
		if(strlen($meta_name) > 255) {
			$this->debug_note('meta name too long. Limit is 255 chars');
			return false;
		}
		
		return true;
	}
}

$GLOBALS['pc_meta'] = new pc_meta;
