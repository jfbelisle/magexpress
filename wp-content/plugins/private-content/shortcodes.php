<?php
////////// LIST OF SHORCODES

// [pc-login-form] 
// get the login form
function pc_login_form_shortcode( $atts, $content = null ) {
	extract( shortcode_atts( array(
		'redirect' 	=> ''
	), $atts ));
	
	return str_replace(array("\r", "\n", "\t", "\v"), '', pc_login_form($redirect));
}
add_shortcode('pc-login-form', 'pc_login_form_shortcode');
add_shortcode('pg-login-form', 'pc_login_form_shortcode');



// [pc-logout-box] 
// get the logout box
function pc_logout_box_shortcode( $atts, $content = null ) {	
	extract( shortcode_atts( array(
		'redirect' 	=> ''
	), $atts ));
	
	return str_replace(array("\r", "\n", "\t", "\v"), '', pc_logout_btn($redirect));
}
add_shortcode('pc-logout-box', 'pc_logout_box_shortcode');
add_shortcode('pg-logout-box', 'pc_logout_box_shortcode');



// [pc-registration-form] 
// get the registration form
function pc_registration_form_shortcode( $atts, $content = null ) {
	extract( shortcode_atts( array(
		'id' => '',
		'layout' => '',
		'custom_categories' => '',
		'redirect' => ''
	), $atts ));

	return str_replace(array("\r", "\n", "\t", "\v"), '', pc_registration_form($id, $layout, $custom_categories, $redirect));	
}
add_shortcode('pc-registration-form', 'pc_registration_form_shortcode');
add_shortcode('pg-registration-form', 'pc_registration_form_shortcode');



// [pc-pvt-content] 
// hide shortcode content if user is not logged and is not of the specified category or also if is logged
function pc_pvt_content_shortcode( $atts, $content = null ) {
	extract( shortcode_atts( array(
		'allow' 	=> 'all',
		'block'		=> '',
		'warning'	=> '1',
		'message'	=> ''
	), $atts ) );
	
	$custom_message = $message;
	
	// if nothing is specified, return the content
	if(trim($allow) == '') {return do_shortcode($content);}
	include_once(PC_DIR.'/functions.php');	
	
	// MESSAGES
	// print something only if warning is active
	if($warning == '1') {
		
		// switch for js login system
		$js_login = (!get_option('pg_js_inline_login')) ? '' : ' - <span class="pc_login_trig pc_trigger">'. __('login', 'pc_ml') .'</span>';

		// prepare the message if user is not logged
		$message = '<div class="pc_login_block"><p>'. pc_get_message('pc_default_nl_mex', $message) .$js_login.'</p></div>';
		
		// prepare message if user has not the right category
		$not_has_level_err = '<div class="pc_login_block"><p>'. pc_get_message('pc_default_uca_mex', $message)  .'</p></div>';
	} 
	else {
		$message = '';	
		$not_has_level_err = '';
	}
	
	$response = pc_user_check($allow, $block, $wp_user_pass = true); 
	

	if($response === 1) {
		return do_shortcode($content);
	}
	elseif($response === 2) {
		return $not_has_level_err;
	}
	else {
		// if has to be show to unlogged users return only custom message 
		if($allow == 'unlogged') {
			return (!empty($custom_message)) ? '<div class="pc_login_block"><p>'. $custom_message .'</p></div>' : '';
		}

		$login_form = (isset($js_login) && $js_login) ? '<div class="pc_inl_login_wrap" style="display: none;">'. pc_login_form() .'</div>' : ''; 
		return $message . $login_form;
	}
}
add_shortcode('pc-pvt-content', 'pc_pvt_content_shortcode');
add_shortcode('pg-pvt-content', 'pc_pvt_content_shortcode');