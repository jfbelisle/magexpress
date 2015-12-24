<?php
// DATABASE TABLE CREATION AND MAINTENANCE
//// ACTIONS PERFORMED ON PLUGINS ACTIVATION

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
include_once(PC_DIR . '/functions.php');
global $wpdb, $pc_users;


// main vars to perform actions
$db_version = 5.04;
$curr_vers = (float)get_option('pg_db_version', 0);

$PC_USERS_TABLE = $wpdb->prefix . "pc_users";
$PC_META_TABLE 	= $wpdb->prefix . "pc_user_meta";


/*** prior check - switch to v5 renaming pg table to pc ***/
if($curr_vers < 5 || isset($_GET['pc_update_db_v5'])) {
	$wpdb->query("SHOW TABLES LIKE '".$wpdb->prefix."pg_users'");
	if($wpdb->num_rows) {
		$wpdb->query("RENAME TABLE ".$wpdb->prefix."pg_users TO ".$PC_USERS_TABLE);	
	}
}



/*** manage main users table ***/
// check DB table existence
$wpdb->query("SHOW TABLES LIKE '".$PC_USERS_TABLE."'");

// add or update DB table
if(!$wpdb->num_rows || !$curr_vers || $curr_vers < $db_version) {
	$sql = "CREATE TABLE ".$PC_USERS_TABLE." (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		insert_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		name VARCHAR(150) DEFAULT '' NOT NULL,
		surname VARCHAR(150) DEFAULT '' NOT NULL,
		username VARCHAR(150) NOT NULL,
		psw text NOT NULL,
		categories text NOT NULL,
		email VARCHAR(255) NOT NULL,
		tel VARCHAR(20) NOT NULL,
		page_id int(11) UNSIGNED NOT NULL,
		wp_user_id mediumint(9) UNSIGNED NOT NULL,
		disable_pvt_page smallint(1) UNSIGNED NOT NULL,
		last_access datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		status smallint(1) UNSIGNED NOT NULL,
		UNIQUE KEY (id, page_id, wp_user_id)
	) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci;";
	
	dbDelta($sql);
}



/*** manage users meta table ***/
// check DB table existence
$wpdb->query("SHOW TABLES LIKE '".$PC_META_TABLE."'");

// add or update DB table
if(!$wpdb->num_rows || !$curr_vers || $curr_vers < $db_version) {
	$sql = "CREATE TABLE ".$PC_META_TABLE." (
		meta_id mediumint(9) NOT NULL AUTO_INCREMENT,
		user_id mediumint(9) UNSIGNED NOT NULL,
		meta_key VARCHAR(255) DEFAULT '' NOT NULL,
		meta_value longtext DEFAULT '' NOT NULL,
		UNIQUE KEY (meta_id)
	) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci;";

	dbDelta($sql);
}


/*** actions to perform if updating from old version ***/
// from 4.x versions
if($curr_vers < 5) {
	include_once(PC_DIR . '/classes/users_manag.php');

	/*** delete users having status == 0 - since v5.0 ***/
	$users = $wpdb->get_results("SELECT id FROM ".$PC_USERS_TABLE." WHERE status = 0"); 
	foreach($users as $user) {
		$pc_users->delete_user($user->id);	
	}
	$wpdb->delete($PC_USERS_TABLE, array('status' => 0));
	
	/*** register first registration form in new format - since v5.0 ***/
	pc_reg_form_ct(); // init taxonomy
	$default = array(
		'include'=>array('username', 'psw'), 'require'=>array('username', 'psw')
	);
	$args = array(
		'description' => base64_encode(serialize(get_option('pg_registration_form', $default)))
	);
	$result = wp_insert_term('First form', 'pc_reg_form', $args);	
}	
	
	
// before 5.04 - store passwords as non-reversible hasings but without mcrypt
if($curr_vers < 5.04 || isset($_GET['pc_update_db_v5'])) {	
	$users = $wpdb->get_results("SELECT id, username, psw FROM ".$PC_USERS_TABLE); 
	foreach($users as $user) {
		
		// decrypt basing on version
		if($curr_vers < 5.0 || !function_exists('mcrypt_decrypt')) {
			$psw = base64_decode($user->psw);	
		} else {
			$key = strtolower($user->username);
			$psw = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5(sha1($key.'lcweb')), base64_decode($user->psw), MCRYPT_MODE_CBC, md5(md5($key))), "\0");	
		}
		
		$wpdb->update($PC_USERS_TABLE, array('psw' => $pc_users->encrypt_psw($psw)), array('id' => $user->id));	
	}
}

			
update_option('pg_db_version', $db_version);