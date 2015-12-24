<?php
// setup session
// handles frontend AJAX requests to login/logout users
// finally - reads user cookie to re-log user



// setting up session
function pc_init_session() {
	if (!session_id()) {
		ob_start();
		ob_clean();
		@session_start();
	}
	
	// if isset session - move into globals
	if(isset($_SESSION['pc_user_id'])) {$GLOBALS['pc_user_id'] = $_SESSION['pc_user_id'];}
}
add_action('init', 'pc_init_session', 1);


////////////////////////////////////////////////////////////////


// load login form form
add_action('init', 'pc_load_auth_form', 1);
function pc_load_auth_form() {
	if(isset($_POST['type']) && $_POST['type'] == 'pc_get_auth_form') {
		echo pc_login_form();
		die();
	}
}


// handle the ajax form submit
add_action('init', 'pc_user_auth', 2);
function pc_user_auth() {
	global $wpdb, $pc_users;
	
	if(isset($_POST['type']) && $_POST['type'] == 'js_ajax_auth') {
		include_once(PC_DIR.'/classes/pc_form_framework.php');
		include_once(PC_DIR . '/classes/simple_form_validator.php');
		include_once(PC_DIR . '/functions.php');	
		
		$f_fw = new pc_form();
		$validator = new simple_fv;
		$indexes = array();
		
		$indexes[] = array('index'=>'pc_auth_username', 'label'=>'username', 'required'=>true);
		$indexes[] = array('index'=>'pc_auth_psw', 'label'=>'psw', 'required'=>true);
		$indexes[] = array('index'=>'pc_remember_me', 'label'=>'remember me');

		$validator->formHandle($indexes);
		$error = $validator->getErrors();
		$fdata = $validator->form_val;
		
		// honeypot check
		if(!$f_fw->honeypot_validaton()) {
			echo json_encode(array( 
				'resp' => 'error',
				'mess' => "Antispam - we've got a bot here!"
			));
			die();
		}
		
		// error message
		if($error) {
			die( json_encode(array( 
				'resp' => 'error',
				'mess' => __('Incorrect username or password', 'pc_ml')
			)));
		}
		else {
			//// try to login
			$response = pc_login($fdata['pc_auth_username'], $fdata['pc_auth_psw'], $fdata['pc_remember_me']);
			
			// user not found
			if(!$response) {
				echo json_encode(array( 
					'resp' => 'error',
					'mess' => __('Username or password incorrect', 'pc_ml')
				));
				die();
			}
			
			// disabled/pending user
			elseif($response === 2 || $response === 3) {
				echo json_encode(array(
					'resp' => 'error',
					'mess' => pc_get_message('pc_default_pu_mex')
				));	
				die();
			}
			
			// custom error
			if($response !== true) {
				echo json_encode(array(
					'resp' => 'error',
					'mess' => $response
				));		
				die();
			}
			
			// successfully logged
			else {
				// redirect logged user to pvt page
				if(get_option('pg_redirect_back_after_login') && isset($_SESSION['pc_last_restricted']) && filter_var($_SESSION['pc_last_restricted'], FILTER_VALIDATE_URL)) {
					$redirect_url = $_SESSION['pc_last_restricted'];
				}
				else {
					// check for custom categories redirects
					$custom_cat_redirect = pc_user_cats_login_redirect( pc_user_logged('categories'));
					
					$redirect_url = ($custom_cat_redirect) ? $custom_cat_redirect : pc_man_redirects('pg_logged_user_redirect');	
				}
				
				echo json_encode(array(
					'resp' => 'success',
					'mess' => pc_get_message('pc_login_ok_mex'),
					'redirect' => $redirect_url
				));	
				die();
			}
		}
		die(); // security block
	}
}


////////////////////////////////////////////////////////////////


// execute logout
function pc_logout_user() {
	if((isset($_REQUEST['type']) && $_REQUEST['type'] == 'pc_logout') || isset($_REQUEST['pc_logout']) || isset($_REQUEST['pg_logout'])) {
		include_once(PC_DIR . '/functions.php');
		
		$GLOBALS['pc_is_logging_out'] = true;
		pc_logout();	
		
		// if logging out through URL parameter - stop here
		if(!isset($_REQUEST['type'])) {return true;}
		
		// check if a redirect is needed
		echo pc_man_redirects('pg_logout_user_redirect');
		die();
	}
}
add_action('init', 'pc_logout_user', 3); // IMPORTANT - execute as third to avoid interferences but let user data to be setup


////////////////////////////////////////////////////////////////


// setup logged user id - check for login cookie if session doesn't exists
function pc_cookie_check() {
	if(!isset($GLOBALS['pc_user_id']) && isset($_COOKIE['pc_user']) && !isset($GLOBALS['pc_is_logging_out'])) {
		global $wpdb, $pc_users;
		
		// get user ID and password
		$c_data = explode('|||', $_COOKIE['pc_user']);
		if(count($c_data) < 2) {return false;}
		
		$user_data = $wpdb->get_row(
			$wpdb->prepare( 
				"SELECT username, psw FROM ".PC_USERS_TABLE." WHERE status = 1 AND id = %d AND psw = %s",
				$c_data[0],
				$c_data[1]
			)
		);
		
		if($wpdb->num_rows) {
			$decrypted_psw = $pc_users->decrypt_psw($user_data->psw);
			pc_login($user_data->username, $decrypted_psw);
		}
	}
}
add_action('init', 'pc_cookie_check', 4); // IMPORTANT - execute as fourth to let logout execute first
