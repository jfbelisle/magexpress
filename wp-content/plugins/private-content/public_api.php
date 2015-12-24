<?php

/* 
 * CHECK IF A USER IS LOGGED  
 * @param (mixed) $get_data = whether to return logged user data. 
 *	true (default) to return full user data + meta
 *	false to return only a boolean value
 *	fields array to return only them
 *
 * @return (mixed) 
 *	false if user is not logged, 
 *	true if user is logged and no data must be returned,
 *	associative array in case of multiple data to return (key => val)
 *	mixed if want to get only one value (returns directly the value)	
 */
function pc_user_logged($get_data = true) {
	if(!isset($_SESSION['pc_user_id']) && !isset($GLOBALS['pc_user_id'])) {
		return false;
	}
	else {
		global $pc_users;
		$user_id = (isset($_SESSION['pc_user_id'])) ? $_SESSION['pc_user_id'] : $GLOBALS['pc_user_id'];
		
		//// check if actual user is active		
		// if just check user without getting data
		if(!$get_data) {
			if(isset($GLOBALS['PC_VER_LOGGED_USER']) && $GLOBALS['PC_VER_LOGGED_USER'] == $user_id) {
				return true;
			}
			$result = $pc_users->get_users(array('user_id' => $user_id, 'count' => true));
			
			// check only once in this case
			if($result && !isset($GLOBALS['PC_VER_LOGGED_USER'])) {
				$GLOBALS['PC_VER_LOGGED_USER'] = $user_id;
				return ($result) ? true : false;	
			}
			else {
				return true; // user already verified
			} 
		}
		else {
			$args = array('status' => 1);
			if($get_data !== true) {$get_data = (array)$get_data;}
			$args['to_get'] = $get_data;
			
			$result = $pc_users->get_user($user_id, $args);
			
			// if getting single field - return only that
			if(count($get_data) == 1) {
				$result = $result[$get_data[0]];	
			}
			
			// if result is ok - set constant to check verified user logged
			if($result && !isset($GLOBALS['PC_VER_LOGGED_USER'])) {$GLOBALS['PC_VER_LOGGED_USER'] = $user_id;}
			return $result;	
		}
	}
}
function pg_user_logged($get_data = true) {return pc_user_logged($get_data);} // retrocompatibility



/* CHECK IF CURRENT USER CAN ACCESS TO AN AREA
 * given the allowed param, check if user has right permissions - eventually checks WP users pass
 *
 * @param (string) allowed = allowed user categories	
 *		all 	= all categories
 *		unlogged = only non logged users
 *		user categories id string (comma spilt): NUM,NUM,NUM
 *
 * @param (string) blocked = user categories blocked in this search
 *		user categories id string (comma spilt): NUM,NUM,NUM
 *
 * @param (bool) wp_user_pass - whether to count logged WP user to check permission
 * @return (mixed)
 *	false = not logged
 *	2 = user doesnt' have right permissions
 *	1 = has got permissions
 */
function pc_user_check($allowed = 'all', $blocked = '', $wp_user_pass = false) {
	global $pc_users;
	
	// if WP user can pass
	if($wp_user_pass && is_user_logged_in() && !isset($GLOBALS['pc_user_id'])) {	
		// be sure constant is initiated
		pc_testing_mode_flag();
		
		if($allowed == 'unlogged') {
			return (PC_WP_USER_PASS) ? false : 1;
		} else {
			return (PC_WP_USER_PASS) ? 1 : false;	
		}
	}
		
	///////////////////////////////////	
	
	// if any logged is allowed
	if($allowed == 'all') {
		return (pc_user_logged(false) !== false) ? 1 : false;	
	}
	
	// if allowed only unlogged
	else if($allowed == 'unlogged') {
		return (pc_user_logged(false) === false) ? 1 : false;	
	}
	
	else {
		$allowed = explode(',', $allowed);	
		$blocked = (array)array_diff(explode(',', $blocked), $allowed); // strip allowed from blocked
		
		$logged_user = pc_user_logged('categories');
		if(!$logged_user) {return false;}
		
		$user_cats = (array)$logged_user;
		
		// check blocked
		if(count($blocked) && count(array_diff($user_cats, $blocked)) != count($user_cats)) {
			return 2;	
		}
		
		if(count($allowed) && count(array_diff($user_cats, $allowed)) != count($user_cats)) {
			return 1;
		}
		
		return false;
	}
}
function pg_user_check($allowed = 'all', $blocked = '') {return pc_user_check($allowed, $blocked);} // retrocompatibility



/* GET LOGIN FORM
 * @param (string) redirect = forces a specific redirect after login - must be a valid URL
 * @return (string) the login form code or empty if a logged user is found
 */
function pc_login_form($redirect = '') {
	include_once(PC_DIR.'/classes/pc_form_framework.php');
	include_once(PC_DIR.'/functions.php');

	$f_fw = new pc_form();
	
	$custom_redirect = (!empty($redirect)) ?  'pc_redirect="'.$redirect.'"' : '';
	$remember_me = get_option('pg_use_remember_me');
	$rm_class = ($remember_me) ? 'pc_rm_login' : '';
	
	$form = '
	<form class="pc_login_form '.$rm_class.'" '.$custom_redirect.'>
		<div class="pc_login_row">
			<label for="pc_auth_username">'. __('Username', 'pc_ml') .'</label>
			<input type="text" name="pc_auth_username" value="" autocapitalize="off" autocomplete="off" autocorrect="off" maxlength="150" />
			<hr class="pc_clear" />
		</div>
		<div class="pc_login_row">
			<label for="pc_auth_psw">'. __('Password', 'pc_ml') .'</label>
			<input type="password" name="pc_auth_psw" value="" autocapitalize="off" autocomplete="off" autocorrect="off" />
			<hr class="pc_clear" />
		</div>
		'.$f_fw->honeypot_generator().'
		
		<div id="pc_auth_message"></div>
		
		<div class="pc_login_smalls">';
		
		  if($remember_me) {
			$form .= '
			<div class="pc_login_remember_me">
				<input type="checkbox" name="pc_remember_me" value="1" autocomplete="off" />
				<small>'. __('remember me', 'pc_ml') .'</small>
			</div>';
		  }
			
			//////////////////////////////////////////////////////////////
			// PSW RECOVERY TRIGGER - MAIL ACTIONS ADD-ON
			$form = apply_filters('pcma_psw_recovery_trigger', $form);	
			//////////////////////////////////////////////////////////////
		
		$form .= '
		</div>
		<input type="button" class="pc_auth_btn" value="'. __('Login', 'pc_ml') .'" />';
		
		//////////////////////////////////////////////////////////////
		// PSW RECOVERY CODE - MAIL ACTIONS ADD-ON
		$form = apply_filters('pcma_psw_recovery_code', $form);	
		//////////////////////////////////////////////////////////////
	
	$form .= '
		<hr class="pc_clear" />
	</form>';
	
	return (pc_user_logged(false)) ? '' : $form;
}
function pg_login_form($redirect = '') {return pc_login_form($redirect);} // retrocompatibility



/* GET LOGOUT BUTTON
 * @param (string) redirect = forces a specific redirect after login - must be a valid URL
 * @return (string) the logout button code or empty if a logged user is found
 */
function pc_logout_btn($redirect = '') {
	$custom_redirect = (!empty($redirect)) ?  'pc_redirect="'.$redirect.'"' : '';
	
	$logout = '
	<form class="pc_logout_box">
		<input type="button" value="'. __('Logout', 'pc_ml') .'" class="pc_logout_btn" '.$custom_redirect.' />
	</form>';
	
	return (!pc_user_logged(false)) ? '' : $logout;
}
function pg_logout_btn($redirect = '') {return pc_logout_btn($redirect);} // retrocompatibility



/* LOGGING IN USER - passing username and password, check and setup session/cookie and WP login 
 * @param (string) username
 * @param (string) password
 * @param (bool) remember_me - whether to use extended cookies (6 months)
 * @return (mixed) false if not found - true if logged sucessfully - otherwise user status (2 or 3) - or custom message for custom check
 */
function pc_login($username, $psw, $remember_me = false) {
	global $wpdb, $pc_users;

	$user_data = $wpdb->get_row( 
		$wpdb->prepare(
			"SELECT id, username, psw, status, wp_user_id FROM  ".PC_USERS_TABLE." WHERE username = %s AND psw = %s",
			trim($username),
			$pc_users->encrypt_psw($psw)
		) 
	);
	if(empty($user_data)) {return false;}
	
	// PC-FILTER - custom login control for custom checks - passes false and user id - return message to abort login otherwise false
	$custom_check = apply_filters('pc_login_custom_check', false, $user_data->id);
	
	if($custom_check !== false) {
		return $custom_check;		
	}
	elseif($user_data->status == 1) {
		// setup user session, cookie and global
		if(session_id()) {
			$_SESSION['pc_user_id'] = $user_data->id;
		}
		$GLOBALS['pc_user_id'] = $user_data->id;
		
		// set cookie
		$cookie_time = ($remember_me) ? (3600 * 24 * 30 * 6) : (3600 * 6); // 6 month or 6 hours
		setcookie('pc_user', $user_data->id.'|||'.$user_data->psw, time() + $cookie_time, '/');
		
		// wp user sync 
		if($pc_users->wp_user_sync && $user_data->wp_user_id) {
			// if an user is already logged - unlog
			if(is_user_logged_in()) {
				$GLOBALS['pc_only_wp_logout'] = true;
				wp_destroy_current_session();
				wp_clear_auth_cookie();		
			}
			
			// wp signon
			$creds = array();
			$creds['user_login'] = $user_data->username;
			$creds['user_password'] = $psw;
			$creds['remember'] = ($remember_me) ? true : false;
			
			$GLOBALS['pc_wps_standard_login'] = 1; // flag to avoid redirect after WP login by mirror user
			$user = wp_signon($creds, false);
		}
		
		// update last login date
		$wpdb->update(PC_USERS_TABLE, array('last_access' => current_time('mysql')), array('id' => $user_data->id));
		
		// PC-ACTION - user is logged in - passes user id
		do_action('pc_user_login', $user_data->id);
		return true;
	}
	else {
		return $user_data->status;	
	}
}



/* LOGGING OUT USER - deletes logged user session/cookies */
function pc_logout() {
	global $pc_users;
	
	if(isset($_SESSION['pc_user_id'])) unset($_SESSION['pc_user_id']);
	if(isset($GLOBALS['pc_user_id'])) unset($GLOBALS['pc_user_id']);
	
	setcookie('pc_user', '', time() - (3600 * 25), '/');
	
	$wp_user_id = pc_user_logged('wp_user_id');
	if($wp_user_id !== false) {
		// wp user sync - unlog if WP logged is the one synced
		if($pc_users->wp_user_sync) {
			$current_user = wp_get_current_user();
			if($current_user && $wp_user_id == $current_user->ID) {
				wp_destroy_current_session();
	
				setcookie( AUTH_COOKIE,        ' ', time() - YEAR_IN_SECONDS, ADMIN_COOKIE_PATH,   COOKIE_DOMAIN );
				setcookie( SECURE_AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, ADMIN_COOKIE_PATH,   COOKIE_DOMAIN );
				setcookie( AUTH_COOKIE,        ' ', time() - YEAR_IN_SECONDS, PLUGINS_COOKIE_PATH, COOKIE_DOMAIN );
				setcookie( SECURE_AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, PLUGINS_COOKIE_PATH, COOKIE_DOMAIN );
				setcookie( LOGGED_IN_COOKIE,   ' ', time() - YEAR_IN_SECONDS, COOKIEPATH,          COOKIE_DOMAIN );
				setcookie( LOGGED_IN_COOKIE,   ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH,      COOKIE_DOMAIN );
			
				// Old cookies
				setcookie( AUTH_COOKIE,        ' ', time() - YEAR_IN_SECONDS, COOKIEPATH,     COOKIE_DOMAIN );
				setcookie( AUTH_COOKIE,        ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN );
				setcookie( SECURE_AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, COOKIEPATH,     COOKIE_DOMAIN );
				setcookie( SECURE_AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN );
			
				// Even older cookies
				setcookie( USER_COOKIE, ' ', time() - YEAR_IN_SECONDS, COOKIEPATH,     COOKIE_DOMAIN );
				setcookie( PASS_COOKIE, ' ', time() - YEAR_IN_SECONDS, COOKIEPATH,     COOKIE_DOMAIN );
				setcookie( USER_COOKIE, ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN );
				setcookie( PASS_COOKIE, ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN );
				
				//wp_clear_auth_cookie(); // don't use the function to avoid interferences with do_action( 'clear_auth_cookie' );	
			}
		}
		
		// PC-ACTION - user is logged out - passes user id
		do_action('pc_user_logout', $GLOBALS['PC_VER_LOGGED_USER']);
		unset($GLOBALS['PC_VER_LOGGED_USER']);
	}
	
	return true;	
}
function pg_logout() {return pc_logout();} // retrocompatibility



/* REGISTRATION FORM 
 * @param (int) form_id = registration form ID to use - if invalid or null, uses first form in DB
 * @param (string) layout = form layout to use, overrides global one (one_col or fluid) 
 * @param (string) forced_cats = user category ID or IDs list (comma split) to assign to registered users
 * @param (string) redirect = custom form redirect for registered users
 */
function pc_registration_form($form_id = '', $layout = '', $forced_cats = false, $redirect = false) {
	include_once(PC_DIR.'/classes/pc_form_framework.php');
	include_once(PC_DIR.'/classes/recaptchalib.php');
	
	// if is not set the target user category, return an error
	if(!get_option('pg_registration_cat')) {
		return __('You have to set registered users default category in settings', 'pc_ml');
	}
	else {
		$f_fw = new pc_form(array(
			'use_custom_cat_name' => true,
			'strip_no_reg_cats' => true
		));
		
		//// get form structure
		// if form not found - get first in list
		if(!(int)$form_id) {
			$rf = get_terms('pc_reg_form', 'hide_empty=0&order=DESC&number=1');
			if(empty($rf)) {return __('No registration forms found', 'pc_ml');}
			
			$rf = $rf[0];		
		}
		else {
			$rf = get_term($form_id, 'pc_reg_form');	
			
			if(empty($rf)) {
				$rf = get_terms('pc_reg_form', 'hide_empty=0&order=DESC&number=1');
				if(empty($rf)) {return __('No registration forms found', 'pc_ml');}
				
				$rf = $rf[0];		
			}
		}
			
		$form_structure = unserialize(base64_decode($rf->description));	
		if(!is_array($form_structure) || !in_array('username', $form_structure['include']) || !in_array('psw', $form_structure['include'])) {
			return  __('Username and password fields are mandatory', 'pc_ml');
		}
		
		// disclaimer inclusion
		if(get_option('pg_use_disclaimer')) {
			$form_structure['include'][] = 'pc_disclaimer';
		}

		// PC-FILTER - manage registration form structure - passes structure array and form id
		$form_structure = apply_filters('pc_registration_form', $form_structure, $rf->term_id);
		
		
		
		// layout class
		$layout = (empty($layout)) ? get_option('pg_reg_layout', 'one_col') : $layout; 
		$layout_class = 'pc_'. $layout .'_form';
		
		// custom category parameter
		if(!empty($forced_cats) && !in_array("categories", $form_structure['include'])) {
			$cat_attr = 'pc_cc="'.$forced_cats.'"'; 	
		}
		else {$cat_attr = '';}
		
		// custom redirect attribute
		if(!empty($redirect)) {
			$redir_attr = 'pc_redirect="'.$redirect.'"';		
		}
		else {$redir_attr = '';}
		
		
		//// init structure
		$form = '<form class="pc_registration_form pc_rf_'.$rf->term_id.' '.$layout_class.'" '.$cat_attr.' '.$redir_attr.' rel="'.$rf->term_id.'">';
		$custom_fields = '';
		
		//// anti-spam system
		$antispam = get_option('pg_antispam_sys', 'honeypot');
		if($antispam == 'honeypot') {
			$custom_fields .= $f_fw->honeypot_generator();
		}
		else {
			$publickey = "6LfQas0SAAAAAIdKJ6Y7MT17o37GJArsvcZv-p5K";
			$custom_fields .= '
			<script type="text/javascript">
		    var RecaptchaOptions = {theme : "clean"};
		    </script>

			<li class="pc_rf_recaptcha">' . pc_recaptcha_get_html($publickey) . '</li>';
		}
		
		$form .= $f_fw->form_code($form_structure, $custom_fields);
		$form .= '
		<div id="pc_reg_message"></div>

		<input type="button" class="pc_reg_btn" value="'. __('Submit', 'pc_ml') .'" />
		</form>';
		
		return $form;
	}
}
function pg_registration_form($forced_cats = false) {return pc_registration_form($forced_cats);} // retrocompatibility



/* RETRIEVES USER MESSAGES AND GIVES ABILITY TO SET CUSTOM ONES 
 * @param (string) subj - message index to retrieve - uses DB ones
 *	- pc_default_nl_mex		=> Message for not logged users
 *	- pc_default_uca_mex	=> Message if user doesn't have right permissions
 
 *	- pc_default_hc_mex		=> Message if user can't post comments
 *	- pc_default_hcwp_mex	=> Message if user doesn't have permissions to post comments 
 
 *	- pc_default_nhpa_mex	=> Message if user doesn't have reserved area
 *	- pc_login_ok_mex		=> Message for successful login
 *	- pc_default_pu_mex		=> Message for pending users trying to login
 *	- pc_default_sr_mex		=> Message if successfully registered
 *
 * @param (string) custom_txt - custom message overriding default and DB set ones
 * @return (string) the message
 */
function pc_get_message($subj, $custom_txt = '') {
	if(!empty($custom_txt)) {return $custom_txt;}
	
	// prefix retrocompatibility
	$subj = str_replace('pg_', 'pc_', $subj);
	
	$subjs = array(
		'pc_default_nl_mex'		=> __('You must be logged in to view this content', 'pc_ml'),
		'pc_default_uca_mex'	=> __("Sorry, you don't have the right permissions to view this content", 'pc_ml'),
		
		'pc_default_hc_mex'		=> __("You must be logged in to post comments", 'pc_ml'),
		'pc_default_hcwp_mex'	=> __("Sorry, you don't have the right permissions to post comments", 'pc_ml'),
		
		'pc_default_nhpa_mex'	=> __("You don't have a reserved area", 'pc_ml'),
		'pc_login_ok_mex'		=> __('Logged successfully, welcome!', 'pc_ml'),
		'pc_default_pu_mex'		=> __('Sorry, your account has not been activated yet', 'pc_ml'),
		'pc_default_sr_mex'		=> __('Registration was successful. Welcome!', 'pc_ml'),
	);
	
	foreach($subjs as $key => $default_mess) {
		if($subj == $key) {
			
			// options still use PG
			$key = str_replace('pc_', 'pg_', $subj);
			$db_val = trim(get_option($key, ''));

			$mess = (!empty($db_val)) ? $db_val : $default_mess;
			
			// PC-FILTER - customize messages - passes message text and key
			return apply_filters('pc_customize_message', $mess, $subj);
		}
	}
	
	return '';
}
function pg_get_nl_message($mess = '') {return pc_get_message('pc_default_nl_mex', $mess);}
function pg_get_uca_message($mess = '') {return pc_get_message('pc_default_uca_mex', $mess);}
