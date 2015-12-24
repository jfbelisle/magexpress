<?php
// empty page to override comments template - checks comments restriction parameter

if(isset($GLOBALS['pc_comment_restriction_warning']) && !empty($GLOBALS['pc_comment_restriction_warning'])) {
	$cr = $GLOBALS['pc_comment_restriction_warning'];
	
	$key = ($cr['check_result'] === 2) ? 'pc_default_hcwp_mex' : 'pc_default_hc_mex'; 

	// switch for js login system
	$js_login = ($cr['check_result'] === 2 || !get_option('pg_js_inline_login')) ? '' : ' - <span class="pc_login_trig pc_trigger">'. __('login', 'pc_ml') .'</span>';
	$login_form = ($js_login) ? '<div class="pc_inl_login_wrap" style="display: none;">'. pc_login_form() .'</div>' : ''; 

	// prepare the message if user is not logged
	echo '<div class="pc_login_block pc_comment_hide"><p>'. pc_get_message($key) .$js_login.'</p></div>' . $login_form;		
}
