<?php
// CONTROL LOGIN, HIDE WP PVTCONTENT USERS AND TURN THEM AS EXTERNAL VISITORS ALSO IF LOGGED
// file called only if sync is active


// login control - check pvtContent user status
function pc_wp_user_login($user_login, $user) {
	global $wpdb;
	global $pc_wp_user;
	
	// do not execute if is a pvtcontent login
	if(!isset($GLOBALS['pc_wps_standard_login'])) { 
	
		$user_data = $pc_wp_user->wp_user_is_linked($user->ID);
		if($user_data) {
			
			// PC-FILTER - custom login control for custom checks - passes false and user id - return message to abort login otherwise false
			$custom_check = apply_filters('pc_login_custom_check', false, $user_data->id);
			
			// check status
			if((int)$user_data->status !== 1 || $custom_check !== false) {
				// Clear cookies -> log user out
				wp_clear_auth_cookie();
				
				$error_param = ($custom_check !== false) ? $custom_check : $user_data->status;
				
				// redirect adding disabling parameter - for pc_wp_user_login_message() function 
				$login_url = site_url('wp-login.php', 'login');
				$login_url = add_query_arg('pc_disabled', $error_param, $login_url);
				
				wp_redirect($login_url);
				exit;
			}
			
			else {
				include_once(PC_DIR . '/functions.php');
			
				//// login in pvtContent	
				// setup user session, cookie and global
				$_SESSION['pc_user_id'] = $user_data->id;
				$GLOBALS['pc_user_id'] = $user_data->id;
				
				// set cookie
				$cookie_time = (isset($_POST['rememberme'])) ? (3600 * 24 * 30 * 6) : (3600 * 6); // 6 month or 6 hours
				setcookie('pc_user', $user_data->id.'|||'.$user_data->psw, time() + $cookie_time, '/');
				
				// update last login date
				$wpdb->update(PC_USERS_TABLE, array('last_access' => current_time('mysql')), array('id' => $user_data->id)); 
				
				
				//// redirect after login
				// check for custom categories redirects
				$custom_cat_redirect = pc_user_cats_login_redirect($user_data->categories);
				if($custom_cat_redirect) {
					$redirect_url = $custom_cat_redirect;	
				}
				else {
					if(get_option('pg_logged_user_redirect')) {
						$redirect_url = pc_man_redirects('pg_logged_user_redirect');
					}
					else {$redirect_url = site_url();}
				}
				
				wp_redirect($redirect_url);
				exit;
			}
		}
	}
}
add_action('wp_login', 'pc_wp_user_login', 10, 2);


// notify that related pvtContent user is not active
function pc_wp_user_login_message($message) {
	if (isset($_GET['pc_disabled'])) {
		
		if((int)$_GET['pc_disabled'] === 3 || (int)$_GET['pc_disabled'] == 2) {
			// pending user message
			$message = pc_get_message('pc_default_pu_mex');
		}
		
		$message =  '<div id="login_error">'. $message .'</div>';
	}
	return $message;
}
add_filter('login_message', 'pc_wp_user_login_message');


// manage WP logout - if is linked to a pvtContent user
function pc_wp_user_logout() {
    global $wpdb;
	global $pc_wp_user;
	
    $user = wp_get_current_user();
	
	if(isset($user->ID) && !empty($user->ID)) {
		$user_data = $pc_wp_user->wp_user_is_linked($user->ID);
		
		if($user_data) {
			if(!isset($GLOBALS['pc_only_wp_logout'])) {pc_logout();}
			
			// check if a redirect is needed
			if(get_option('pg_logout_user_redirect')) {
				$redirect_url = pc_man_redirects('pg_logout_user_redirect');
				wp_redirect($redirect_url);
				exit;
			}
		}
	}
}
add_action('clear_auth_cookie', 'pc_wp_user_logout', 100);



//////////////////////////////////////////////////////////////////////////



// avoid pvtcontent users to go into default WP dashboard
function pc_wp_user_no_admin() {
	if(is_admin()) {
		global $current_user;
		if(isset($current_user) && isset($current_user->caps) && isset($current_user->caps['pvtcontent']) && $current_user->caps['pvtcontent']) {
			ob_start();
			ob_clean();
			header('location: '.site_url());
		}
	}
}
add_action('admin_enqueue_scripts', 'pc_wp_user_no_admin', 1);



// disable admin bar
if (current_user_can('pvtcontent')) {	
	show_admin_bar(false); 
}



//////////////////////////////////////////////////////////////////////////


// hide privateContent from dropdown choiches in users.php
// remove ability to edit or delete user
function pc_hide_pvtcontent_role_dd() {
	global $current_screen;

	if(isset($current_screen->base) && $current_screen->base == 'users') {
		?>
    	<script type="text/javascript">
		jQuery(document).ready(function(e) {
        	jQuery('select#new_role option[value=pvtcontent]').remove();  
			
			jQuery('#the-list tr').each(function() {
                var $row = jQuery(this);
				if($row.find('.column-role').text() == 'PrivateContent') {
					$row.find('.check-column').empty();
					$row.find('.row-actions').remove();
					
					$row.find('.username a').each(function() {
                        var content = jQuery(this).contents();
						jQuery(this).replaceWith(content);
                    });	
				}
            });
        });
		</script>    
    	<?php	
	}
	elseif(isset($current_screen->base) && ($current_screen->base == 'user-edit' || $current_screen->base == 'user')) {
		?>
        <script type="text/javascript">
		jQuery(document).ready(function(e) {
        	jQuery('select#role option[value=pvtcontent]').remove();  
        });
		</script> 
    	<?php			
	}
}
add_action('admin_footer', 'pc_hide_pvtcontent_role_dd', 1);


// avoid pvtcontent role filter
function pc_avoid_pvtcontent_role_filter() {
	include_once(PC_DIR . '/functions.php');
	$curr_url = pc_curr_url();

	if(strpos($curr_url, 'users.php') !== false && strpos($curr_url, 'role=pvtcontent') !== false) {
		ob_start();
		ob_clean();
		header('location: '.admin_url('users.php'));	
	}
}
add_action('admin_init', 'pc_avoid_pvtcontent_role_filter', 1);


// avoid users to edit synced through user-edit.php interface
function pc_avoid_pvtcontent_edit() {
	include_once(PC_DIR . '/functions.php');
	$curr_url = pc_curr_url();

	if(strpos($curr_url, 'user-edit.php') !== false) {
		global $pc_wp_user;
		$user_data = get_userdata($_REQUEST['user_id']);
		
		if(isset($user_data->caps['pvtcontent'])) {
			ob_start();
			ob_clean();
			header('location: '.admin_url('users.php'));	
		}
	}
}
add_action('admin_init', 'pc_avoid_pvtcontent_edit', 1);



// avoid users to delete synced through user-edit.php interface
function pc_avoid_pvtcontent_del() {
	include_once(PC_DIR . '/functions.php');
	$curr_url = pc_curr_url();

	if(strpos($curr_url, 'users.php') !== false && strpos($curr_url, 'action=delete') !== false) {
		global $pc_wp_user;
		
		if(isset($_REQUEST['user'])) {$users = array($_REQUEST['user']);}
		elseif(isset($_REQUEST['users'])) {
			$users = $_REQUEST['users'];	
		}

		foreach($users as $user_id) {
			$user_data = get_userdata($user_id);
			
			if(isset($user_data->caps['pvtcontent'])) {
				ob_start();
				ob_clean();
				header('location: '.admin_url('users.php'));	
				break;
			}
		}
	}
}
add_action('admin_init', 'pc_avoid_pvtcontent_del', 1);

