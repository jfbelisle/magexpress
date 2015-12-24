<?php 
include_once(PC_DIR . '/functions.php'); 
global $pc_users, $pc_wp_user;

// custom post type and taxonomies
$cpt = pc_get_cpt();
$ct = pc_get_ct();

// pages list
$pages = get_pages(); 

// PC-FILTER - add custom validation indexes to settings - use Simple Form Validator format
$custom_validations = apply_filters('pc_settins_validations', array());
?>

<style type="text/css">
#pc_pvtpage_default_content_ifr, #pc_pvtpage_preset_txt_ifr {
	background-color: #fff;	
}
</style>

<div class="wrap pc_form lcwp_form">  
	<div class="icon32" id="icon-pc_user_manage"><br></div>
    <?php    echo '<h2 class="pc_page_title">' . __( 'PrivateContent Settings', 'pc_ml') . "</h2>"; ?>  

    <?php
	// HANDLE DATA
	if(isset($_POST['pc_admin_submit'])) { 
		if (!isset($_POST['pc_nonce']) || !wp_verify_nonce($_POST['pc_nonce'], __FILE__)) {die('<p>Cheating?</p>');};
		include_once(PC_DIR . '/classes/simple_form_validator.php');		
		
		$validator = new simple_fv;
		$indexes = array();
		
		$indexes[] = array('index'=>'pg_target_page', 'label'=>"User's Private Page");
		$indexes[] = array('index'=>'pg_target_page_content', 'label'=>'Target page content');
		$indexes[] = array('index'=>'pg_pvtpage_default_content', 'label'=>'Private page default content');
		$indexes[] = array('index'=>'pg_pvtpage_enable_preset', 'label'=>'Enable preset content');
		$indexes[] = array('index'=>'pg_pvtpage_preset_pos', 'label'=>'Preset content position');
		$indexes[] = array('index'=>'pg_pvtpage_preset_txt', 'label'=>'Preset content');
		$indexes[] = array('index'=>'pg_pvtpage_wps_comments', 'label'=>'Allow comments for WP synced users');
		
		$indexes[] = array('index'=>'pg_redirect_page', 'label'=>__( 'Restriction redirect target', 'pc_ml' ), 'required'=>true);
		$indexes[] = array('index'=>'pg_redirect_page_custom', 'label'=>__( 'Redirect Page - Custom URL', 'pc_ml' ));
		$indexes[] = array('index'=>'pg_logged_user_redirect', 'label'=>__( 'Logged Users Redirect', 'pc_ml' ));
		$indexes[] = array('index'=>'pg_logged_user_redirect_custom', 'label'=>__( 'Logged Users Redirect - Custom URL', 'pc_ml' ));
		$indexes[] = array('index'=>'pg_redirect_back_after_login', 'label'=>__( 'Move logged users to last restricted page', 'pc_ml' ));
		$indexes[] = array('index'=>'pg_logout_user_redirect', 'label'=>__( 'Users Redirect after logout', 'pc_ml' ));
		$indexes[] = array('index'=>'pg_logout_user_redirect_custom', 'label'=>__( 'Users Redirect after logout - Custom URL', 'pc_ml' ));
		
		$indexes[] = array('index'=>'pg_complete_lock', 'label'=>'Complete Lock');	
		$indexes[] = array('index'=>'pg_wp_user_sync', 'label'=>'WP user sync');	
		$indexes[] = array('index'=>'pg_require_wps_registration', 'label'=>'Require WP user sync in frontend registration?');
		$indexes[] = array('index'=>'pg_custom_wps_roles', 'label'=>'Custom roles for synced WP users');
		$indexes[] = array('index'=>'pg_lock_comments', 'label'=>'Hide comment block on pages');
		$indexes[] = array('index'=>'pg_hc_warning', 'label'=>'Comments restriction - display warning?');
		$indexes[] = array('index'=>'pg_extend_cpt', 'label'=>'Extend for CPT');
		$indexes[] = array('index'=>'pg_extend_ct', 'label'=>'Extend for CT');	
		$indexes[] = array('index'=>'pg_test_mode', 'label'=>'Testing Mode');
		$indexes[] = array('index'=>'pg_use_remember_me', 'label'=>'Use remember me cookies');
		$indexes[] = array('index'=>'pg_use_first_last_name', 'label'=>'Use first and last name');
		$indexes[] = array('index'=>'pg_js_inline_login', 'label'=>'Login in inline restrictions');
		$indexes[] = array('index'=>'pg_min_role', 'label'=>'Minimum role');
		$indexes[] = array('index'=>'pg_min_role_tmu', 'label'=>'Minimum role to manage users');
		$indexes[] = array('index'=>'pg_force_inline_css', 'label'=>'Force inline css usage');
		
		$indexes[] = array('index'=>'pg_allow_duplicated_mails', 'label'=>'Allow duplicated e-mails?');
		$indexes[] = array('index'=>'pg_registration_cat', 'label'=>__( 'Default registered user categories', 'pc_ml' ), 'required'=>true);
		$indexes[] = array('index'=>'pg_reg_cat_label', 'label'=>'Categories field - custom label');
		$indexes[] = array('index'=>'pg_reg_multiple_cats', 'label'=>'Allow multiple categories selection during registration?');
		$indexes[] = array('index'=>'pg_antispam_sys', 'label'=>'Anti spam system');
		$indexes[] = array('index'=>'pg_registered_pending', 'label'=>'Pending Status registered');
		$indexes[] = array('index'=>'pg_registered_pvtpage', 'label'=>'Private page for registered');
		$indexes[] = array('index'=>'pg_registered_user_redirect', 'label'=>__( 'Registered Users Redirect', 'pc_ml' ));	
		$indexes[] = array('index'=>'pg_use_disclaimer', 'label'=>'Use disclaimer');
		$indexes[] = array('index'=>'pg_disclaimer_txt', 'label'=>__('Disclaimer text', 'pc_ml'), 'required'=>true);
		$indexes[] = array('index'=>'pg_psw_min_length', 'label'=>__('Minimum password length', 'pc_ml'), 'type'=>'int', 'required'=>true);
		$indexes[] = array('index'=>'pg_psw_strength', 'label'=>'Password strength');
		
		$indexes[] = array('index'=>'pg_reg_layout', 'label'=>'Form layout');
		$indexes[] = array('index'=>'pg_style', 'label'=>'Plugin style');
		$indexes[] = array('index'=>'pg_disable_front_css', 'label'=>'Disable Front CSS');
		
		$indexes[] = array('index'=>'pg_field_padding', 'label'=>__('Fields padding', 'pc_ml'), 'type'=>'int');
		$indexes[] = array('index'=>'pg_field_border_w', 'label'=>__('Fields border width', 'pc_ml'), 'type'=>'int');
		$indexes[] = array('index'=>'pg_form_border_radius', 'label'=>__('Forms border radius', 'pc_ml'), 'type'=>'int');
		$indexes[] = array('index'=>'pg_field_border_radius', 'label'=>__('Fields border radius', 'pc_ml'), 'type'=>'int');
		$indexes[] = array('index'=>'pg_btn_border_radius', 'label'=>__('Buttons border radius', 'pc_ml'), 'type'=>'int');	
		$indexes[] = array('index'=>'pg_lf_font_size', 'label'=>__('Login form - labels font size', 'pc_ml'), 'type'=>'int');
		$indexes[] = array('index'=>'pg_rf_font_size', 'label'=>__('Registration form - labels font size', 'pc_ml'), 'type'=>'int');
		$indexes[] = array('index'=>'pg_forms_font_family', 'label'=>'Forms font-family');
		$indexes[] = array('index'=>'pg_forms_bg_col', 'label'=>__('Forms background color', 'pc_ml'), 'type'=>'hex');
		$indexes[] = array('index'=>'pg_forms_border_col', 'label'=>__('Forms border color', 'pc_ml'), 'type'=>'hex');
		$indexes[] = array('index'=>'pg_label_col', 'label'=>__('Labels color', 'pc_ml'), 'type'=>'hex');
		$indexes[] = array('index'=>'pg_recaptcha_col', 'label'=>'Recaptcha icons color');
		$indexes[] = array('index'=>'pg_datepicker_col', 'label'=>'Datepicker theme');
		$indexes[] = array('index'=>'pg_fields_bg_col', 'label'=>__('Fields background color', 'pc_ml'), 'type'=>'hex');
		$indexes[] = array('index'=>'pg_fields_border_col', 'label'=>__('Fields border color', 'pc_ml'), 'type'=>'hex');
		$indexes[] = array('index'=>'pg_fields_txt_col', 'label'=>__('Fields text color', 'pc_ml'), 'type'=>'hex');
		$indexes[] = array('index'=>'pg_fields_bg_col_h', 'label'=>__('Fields background color - on hover', 'pc_ml'), 'type'=>'hex');
		$indexes[] = array('index'=>'pg_fields_border_col_h', 'label'=>__('Fields border color - on hover', 'pc_ml'), 'type'=>'hex');
		$indexes[] = array('index'=>'pg_fields_txt_col_h', 'label'=>__('Fields text color - on hover', 'pc_ml'), 'type'=>'hex');
		$indexes[] = array('index'=>'pg_btn_bg_col', 'label'=>__('Buttons background color', 'pc_ml'), 'type'=>'hex');
		$indexes[] = array('index'=>'pg_btn_border_col', 'label'=>__('Buttons border color', 'pc_ml'), 'type'=>'hex');
		$indexes[] = array('index'=>'pg_btn_txt_col', 'label'=>__('Buttons text color', 'pc_ml'), 'type'=>'hex');
		$indexes[] = array('index'=>'pg_btn_bg_col_h', 'label'=>__('Buttons background color - on hover', 'pc_ml'), 'type'=>'hex');
		$indexes[] = array('index'=>'pg_btn_border_col_h', 'label'=>__('Buttons border color - on hover', 'pc_ml'), 'type'=>'hex');
		$indexes[] = array('index'=>'pg_btn_txt_col_h', 'label'=>__('Buttons text color - on hover', 'pc_ml'), 'type'=>'hex');
		
		$indexes[] = array('index'=>'pg_custom_css', 'label'=>'Custom CSS');	
		
		$indexes[] = array('index'=>'pg_default_nl_mex', 'label'=>__("Unlogged user custom message", 'pc_ml'), 'maxlen'=>255);
		$indexes[] = array('index'=>'pg_default_uca_mex', 'label'=>__("Wrong permissions custom message", 'pc_ml'), 'maxlen'=>170);
		$indexes[] = array('index'=>'pg_default_hc_mex', 'label'=>__("Hidden comments custom message", 'pc_ml'), 'maxlen'=>255);
		$indexes[] = array('index'=>'pg_default_hcwp_mex', 'label'=>__("Hidden comments - wrong permissions message", 'pc_ml'), 'maxlen'=>255);
		$indexes[] = array('index'=>'pg_default_nhpa_mex', 'label'=>__("Disabled reserved area custom message", 'pc_ml'), 'maxlen'=>255);
		$indexes[] = array('index'=>'pg_login_ok_mex', 'label'=>__("Successful login custom message", 'pc_ml'), 'maxlen'=>170);
		$indexes[] = array('index'=>'pg_default_pu_mex', 'label'=>__("Pending user custom message", 'pc_ml'), 'maxlen'=>170);
		$indexes[] = array('index'=>'pg_default_sr_mex', 'label'=>__("Successful registration custom message", 'pc_ml'), 'maxlen'=>170);
		
		if(!empty($custom_validations)) {
			$indexes = array_merge($indexes, $custom_validations);	
		}
		
		$validator->formHandle($indexes);
		$fdata = $validator->form_val;
		
		
		// custom redirects error
		if($fdata['pg_redirect_page'] == 'custom' && !filter_var($fdata['pg_redirect_page_custom'], FILTER_VALIDATE_URL)) {
			$validator->custom_error[ __('Restriction redirect target / Custom URL', 'pc_ml') ] = __('Insert a valid URL', 'pc_ml'); 	
		}
		if($fdata['pg_logged_user_redirect'] == 'custom' && !filter_var($fdata['pg_logged_user_redirect_custom'], FILTER_VALIDATE_URL)) {
			$validator->custom_error[ __('Logged Users Redirect / Custom URL', 'pc_ml') ] = __('Insert a valid URL', 'pc_ml'); 	
		}
		if($fdata['pg_logout_user_redirect'] == 'custom' && !filter_var($fdata['pg_logout_user_redirect_custom'], FILTER_VALIDATE_URL)) {
			$validator->custom_error[ __('Logged Users Redirect / Custom URL', 'pc_ml') ] = __('Insert a valid URL', 'pc_ml'); 	
		}
		
		$error = $validator->getErrors();
		
		if($error) {echo '<div class="error"><p>'.$error.'</p></div>';}
		else {
			// clean data and save options
			foreach($fdata as $key => $val) {
				if(!is_array($val)) {
					$fdata[$key] = stripslashes($val);
				} else {
					$fdata[$key] = array();
					foreach($val as $arr_val) {$fdata[$key][] = stripslashes($arr_val);}
				}
				
				// save and apply custom WPS roles
				if($fdata['pg_wp_user_sync'] && $key == 'pg_custom_wps_roles') {
					$old_roles = $pc_wp_user->get_wps_custom_roles();
					$new_roles = array_unique( array_merge(array('pvtcontent'), (array)$fdata['pg_custom_wps_roles']));
					
					sort($old_roles); sort($new_roles);
					
					if($old_roles !== $new_roles) {
						$pc_wp_user->wps_roles = $new_roles;
						$pc_wp_user->set_wps_custom_roles();
						update_option($key, $new_roles);
					}	
				}
				
				else {
					if($fdata[$key] === false) {delete_option($key);}
					else {update_option($key, $fdata[$key]);}
				}
			}

			// create custom style css file
			if(!get_option('pg_inline_css') && $fdata['pg_style'] == 'custom') {
				if(!pc_create_custom_style()) {
					update_option('pg_inline_css', 1);	
					echo '<div class="updated"><p>'. __('An error occurred during dynamic CSS creation. The code will be used inline anyway', 'pc_ml') .'</p></div>';
				}
				else {delete_option('pg_inline_css');}
			}
			
			echo '<div class="updated"><p><strong>'. __('Options saved', 'pc_ml') .'</strong></p></div>';
		}
	}
	
	else {  
		// Normal page display
		$fdata['pg_target_page'] = get_option('pg_target_page');  
		$fdata['pg_target_page_content'] = get_option('pg_target_page_content');
		$fdata['pg_pvtpage_default_content'] = get_option('pg_pvtpage_default_content');
		$fdata['pg_pvtpage_enable_preset'] = get_option('pg_pvtpage_enable_preset');
		$fdata['pg_pvtpage_preset_pos'] = get_option('pg_pvtpage_preset_pos');
		$fdata['pg_pvtpage_preset_txt'] = get_option('pg_pvtpage_preset_txt');
		$fdata['pg_pvtpage_wps_comments'] = get_option('pg_pvtpage_wps_comments');
		
		$fdata['pg_redirect_page'] = get_option('pg_redirect_page'); 
		$fdata['pg_redirect_page_custom'] = get_option('pg_redirect_page_custom'); 
		$fdata['pg_registered_user_redirect'] = get_option('pg_registered_user_redirect');
		$fdata['pg_logged_user_redirect'] = get_option('pg_logged_user_redirect');
		$fdata['pg_logged_user_redirect_custom'] = get_option('pg_logged_user_redirect_custom');
		$fdata['pg_redirect_back_after_login'] = get_option('pg_redirect_back_after_login');
		$fdata['pg_logout_user_redirect'] = get_option('pg_logout_user_redirect');
		$fdata['pg_logout_user_redirect_custom'] = get_option('pg_logout_user_redirect_custom');		
		$fdata['pg_complete_lock'] = get_option('pg_complete_lock');
		$fdata['pg_wp_user_sync'] = get_option('pg_wp_user_sync');	
		$fdata['pg_require_wps_registration'] = get_option('pg_require_wps_registration');	
		$fdata['pg_custom_wps_roles'] = get_option('pg_custom_wps_roles', array());	
		$fdata['pg_lock_comments'] = get_option('pg_lock_comments');	
		$fdata['pg_hc_warning'] = get_option('pg_hc_warning');
		$fdata['pg_extend_cpt'] = get_option('pg_extend_cpt');	
		$fdata['pg_extend_ct'] = get_option('pg_extend_ct');	
		$fdata['pg_test_mode'] = get_option('pg_test_mode'); 
		$fdata['pg_use_remember_me'] = get_option('pg_use_remember_me');
		$fdata['pg_use_first_last_name'] = get_option('pg_use_first_last_name');
		$fdata['pg_js_inline_login'] = get_option('pg_js_inline_login'); 
		$fdata['pg_min_role'] = get_option('pg_min_role', 'upload_files');
		$fdata['pg_min_role_tmu'] = get_option('pg_min_role_tmu'); 	
		$fdata['pg_force_inline_css'] = get_option('pg_force_inline_css'); 	
			
		$fdata['pg_allow_duplicated_mails'] = get_option('pg_allow_duplicated_mails');	
		$fdata['pg_registration_cat'] = get_option('pg_registration_cat', array());
		$fdata['pg_reg_cat_label'] = get_option('pg_reg_cat_label', '');
		$fdata['pg_reg_multiple_cats'] = get_option('pg_reg_multiple_cats');
		$fdata['pg_antispam_sys'] = get_option('pg_antispam_sys');
		$fdata['pg_registered_pending'] = get_option('pg_registered_pending');
		$fdata['pg_registered_pvtpage'] = get_option('pg_registered_pvtpage');
		$fdata['pg_use_disclaimer'] = get_option('pg_use_disclaimer');
		$fdata['pg_disclaimer_txt'] = get_option('pg_disclaimer_txt', 'By creating an account, you agree to the site <a href="#">Conditions of Use</a> and <a href="#">Privacy Notice</a>');
		$fdata['pg_psw_min_length'] = get_option('pg_psw_min_length', 4);
		$fdata['pg_psw_strength'] = get_option('pg_psw_strength', array());
		
		$fdata['pg_reg_layout'] = get_option('pg_reg_layout');
		$fdata['pg_style'] = get_option('pg_style', 'minimal');
		$fdata['pg_disable_front_css'] = get_option('pg_disable_front_css'); 
		
		$fdata['pg_field_padding'] = get_option('pg_field_padding', 3);
		$fdata['pg_field_border_w'] = get_option('pg_field_border_w', 1);
		$fdata['pg_form_border_radius'] = get_option('pg_form_border_radius', 3);
		$fdata['pg_field_border_radius'] = get_option('pg_field_border_radius', 1);
		$fdata['pg_btn_border_radius'] = get_option('pg_btn_border_radius', 2);
		$fdata['pg_lf_font_size'] = get_option('pg_lf_font_size', 15);
		$fdata['pg_rf_font_size'] = get_option('pg_rf_font_size', 15);
		$fdata['pg_forms_font_family'] = get_option('pg_forms_font_family');
		$fdata['pg_forms_bg_col'] = get_option('pg_forms_bg_col', '#fefefe');
		$fdata['pg_forms_border_col'] = get_option('pg_forms_border_col', '#ebebeb');
		$fdata['pg_label_col'] = get_option('pg_label_col', '#333333');
		$fdata['pg_recaptcha_col'] = get_option('pg_recaptcha_col', 'l');
		$fdata['pg_datepicker_col'] = get_option('pg_datepicker_col', 'light');
		$fdata['pg_fields_bg_col'] = get_option('pg_fields_bg_col', '#fefefe');
		$fdata['pg_fields_border_col'] = get_option('pg_fields_border_col', '#cccccc');
		$fdata['pg_fields_txt_col'] = get_option('pg_fields_txt_col', '#808080');
		$fdata['pg_fields_bg_col_h'] = get_option('pg_fields_bg_col_h', '#ffffff');
		$fdata['pg_fields_border_col_h'] = get_option('pg_fields_border_col_h', '#aaaaaa');
		$fdata['pg_fields_txt_col_h'] = get_option('pg_fields_txt_col_h', '#444444');
		$fdata['pg_btn_bg_col'] = get_option('pg_btn_bg_col', '#f4f4f4');
		$fdata['pg_btn_border_col'] = get_option('pg_btn_border_col', '#cccccc');
		$fdata['pg_btn_txt_col'] = get_option('pg_btn_txt_col', '#444444');
		$fdata['pg_btn_bg_col_h'] = get_option('pg_btn_bg_col_h', '#efefef');
		$fdata['pg_btn_border_col_h'] = get_option('pg_btn_border_col_h', '#cacaca');
		$fdata['pg_btn_txt_col_h'] = get_option('pg_btn_txt_col_h', '#222222');
		
		$fdata['pg_custom_css'] = get_option('pg_custom_css'); 
		
		$fdata['pg_default_nl_mex'] = get_option('pg_default_nl_mex');
		$fdata['pg_default_uca_mex'] = get_option('pg_default_uca_mex'); 
		$fdata['pg_default_hc_mex'] = get_option('pg_default_hc_mex'); 
		$fdata['pg_default_hcwp_mex'] = get_option('pg_default_hcwp_mex');
		$fdata['pg_default_nhpa_mex'] = get_option('pg_default_nhpa_mex');
		$fdata['pg_login_ok_mex'] = get_option('pg_login_ok_mex');
		$fdata['pg_default_pu_mex'] = get_option('pg_default_pu_mex');
		$fdata['pg_default_sr_mex'] = get_option('pg_default_sr_mex');
		
		if(!empty($custom_validations)) {
			foreach($custom_validations as $k => $data) {
				$fdata[ $data['index'] ] = get_option( $data['index'] );	
			}
		}
	}  
	
	// double check psw strength var type
	if(!is_array($fdata['pg_psw_strength'])) {$fdata['pg_psw_strength'] = array();}
	?>
    
    <br/>
    <div id="tabs">
    <form name="pc_admin" method="post" class="form-wrap" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">  
    
    <ul class="tabNavigation">
    	<li><a href="#main_opt"><?php _e('Main Options', 'pc_ml') ?></a></li>
        <li><a href="#form_opt"><?php _e('Registration', 'pc_ml') ?></a></li>
        <li><a href="#styling"><?php _e('Styling', 'pc_ml') ?></a></li>
        <li><a href="#mex_opt"><?php _e('Messages', 'pc_ml') ?></a></li>
        <?php 
		// PC-ACTION - print custom tabs list - must be ok with tabs structure
		do_action ('pc_settings_tabs_list');
		?> 
    </ul>
        
    <div id="main_opt">
    	<h3><?php _e("Users Private Page", 'pc_ml'); ?></h3>
        <table class="widefat pc_table">
          <tr>
          	<td class="pc_label_td"><?php _e("Page to use as users private page container" ); ?></td>
            <td class="pc_field_td">
            	<select name="pg_target_page" class="lcweb-chosen" data-placeholder="<?php _e('Select a page', 'pc_ml') ?> .." autocomplete="off">
                  <option value="">(<?php _e('no private page', 'pc_ml') ?>)</option>
                  <?php
                  foreach ( $pages as $pag ) {
                      ($fdata['pg_target_page'] == $pag->ID) ? $selected = 'selected="selected"' : $selected = '';
                      echo '<option value="'.$pag->ID.'" '.$selected.'>'.$pag->post_title.'</option>';
                  }
                  ?>
              </select>  
            </td>
            <td><span class="info"><?php _e("The chosen page's content will be <strong>overwritten</strong> once an user will log in", 'pc_ml') ?></span></td>
          </tr>
          <tr>
          	<td class="pc_label_td"><?php _e("Users private page content", 'pc_ml'); ?></td>
            <td class="pc_field_td">
            	<select name="pg_target_page_content" class="lcweb-chosen" data-placeholder="<?php _e('Select an option', 'pc_ml') ?> .." tabindex="2">
                  <option value="original_content"  <?php if(!$fdata['pg_target_page_content'] || $fdata['pg_target_page_content'] == 'original_content') {echo 'selected="selected"';} ?>>
                  	<?php _e("Original content", 'pc_ml') ?>
                  </option>
                  
                  <option value="original_plus_form" <?php if($fdata['pg_target_page_content'] == 'original_plus_form') {echo 'selected="selected"';} ?>>
                  	<?php _e("Original content + login form", 'pc_ml') ?>
                  </option>
                  
                  <option value="form_plus_original" <?php if($fdata['pg_target_page_content'] == 'form_plus_original') {echo 'selected="selected"';} ?>>
                  	<?php _e("Login form + original content", 'pc_ml') ?>
                  </option>
                  
                  <option value="only_form" <?php if($fdata['pg_target_page_content'] == 'only_form') {echo 'selected="selected"';} ?>>
                  	<?php _e("Only login form", 'pc_ml') ?>
                  </option>
                </select>  
            </td>
            <td><span class="info"><?php _e("Content that will see non logged users", 'pc_ml') ?></span></td>
          </tr>
          <tr>
           <td class="pc_label_td"><?php _e("Default private page content for new users", 'pc_ml'); ?></td>
           <td class="pc_field_td" colspan="2">
			  <?php 
			  $args = array('textarea_rows'=> 4);
			  echo wp_editor( $fdata['pg_pvtpage_default_content'], 'pc_pvtpage_default_content', $args); 
			  ?>
           </td>
         </tr>
         <tr>
           <td class="pc_label_td"><?php _e("Enable preset content?", 'pc_ml'); ?></td>
           <td class="pc_field_td">
            <?php ($fdata['pg_pvtpage_enable_preset']) ? $checked= 'checked="checked"' : $checked = ''; ?>
            <input type="checkbox" name="pg_pvtpage_enable_preset" value="1" <?php echo $checked; ?> class="ip_checks" />
           </td>
           <td><span class="info"><?php _e('If checked, display the preset content in pvt pages', 'pc_ml') ?></span></td>
         </tr>
         <tr>
          	<td class="pc_label_td"><?php _e("Preset content position" ); ?></td>
            <td class="pc_field_td">
            	<select name="pg_pvtpage_preset_pos" class="lcweb-chosen" data-placeholder="<?php _e('Select an option', 'pc_ml') ?> .." tabindex="2">
                  <option value="before" <?php if(!$fdata['pg_pvtpage_preset_pos'] || $fdata['pg_pvtpage_preset_pos'] == 'before') {echo 'selected="selected"';} ?>>
                  	<?php _e('Before the page content', 'pc_ml') ?>
                  </option>
                  <option value="after" <?php if($fdata['pg_pvtpage_preset_pos'] == 'after') {echo 'selected="selected"';} ?>>
                  	<?php _e('After the page content', 'pc_ml') ?>
                  </option>
                </select>  
            </td>
            <td><span class="info"><?php _e('Set the preset content position in the pvt page', 'pc_ml') ?></span></td>
          </tr>
          <tr>
           <td class="pc_label_td"><?php _e("Preset content", 'pc_ml'); ?></td>
           <td class="pc_field_td" colspan="2">
           	 <?php 
			 $args = array('textarea_rows'=> 4);
			 echo wp_editor( $fdata['pg_pvtpage_preset_txt'], 'pg_pvtpage_preset_txt', $args); 
			 ?>
           </td>
         </tr>
         <tr>
           <td class="pc_label_td"><?php _e("Allow comments for WP synced users?", 'pc_ml'); ?></td>
           <td class="pc_field_td">
            <?php $checked = ($fdata['pg_pvtpage_wps_comments']) ? 'checked="checked"' : ''; ?>
            <input type="checkbox" name="pg_pvtpage_wps_comments" value="1" <?php echo $checked; ?> class="ip_checks" />
           </td>
           <td><span class="info"><?php _e('Gives the ability to communicate with user through comments in his private page', 'pc_ml') ?><br/>
            <?php _e('<strong>Note</strong>: only users with WP sync will be able to do this', 'pc_ml') ?></span></td>
         </tr>
       </table> 
         
         
       <h3><?php _e("Redirects", 'pc_ml'); ?></h3>
       <table class="widefat pc_table">
        <tr>
          <td class="pc_label_td" rowspan="2"><?php _e("Restriction redirect target", 'pc_ml'); ?></td>
          <td class="pc_field_td">
              <select name="pg_redirect_page" id="pc_redirect_page" class="lcweb-chosen" data-placeholder="<?php _e('Select a page', 'pc_ml') ?> .." tabindex="2">
                  <option value="custom"><?php _e('Custom redirect', 'pc_ml') ?></option>
                  <?php
                  $a = 0;
				  foreach ( $pages as $pag ) {
                      ($fdata['pg_redirect_page'] == $pag->ID) ? $selected = 'selected="selected"' : $selected = '';
					 
					  if($a == 0 && !$fdata['pg_redirect_page']) {$selected = 'selected="selected"';}
					  $a++;
					  
					  echo '<option value="'.$pag->ID.'" '.$selected.'>'.$pag->post_title.'</option>';
                  }
                  ?>
              </select>   
          </td>
          <td><span class="info"><?php _e('Choose the page where users without permissions will be redirected', 'pc_ml') ?></span></td>
        </tr>
        <tr id="pc_redirect_page_cst_wrap">
        	<td colspan="2" <?php if($fdata['pg_redirect_page'] != 'custom') {echo 'style="display: none;"';} ?>>
            	<input type="text" name="pg_redirect_page_custom" value="<?php echo pc_sanitize_input($fdata['pg_redirect_page_custom']); ?>" style="width: 70%;" />
            </td>
        </tr>       
        <tr>
          <td class="pc_label_td"><?php _e("Redirect page after registration", 'pc_ml'); ?></td>
          <td class="pc_field_td">
            <select name="pg_registered_user_redirect" class="lcweb-chosen" data-placeholder="<?php _e("Select a page", 'pc_ml'); ?> .." tabindex="2">
              <option value=""><?php _e("Do not redirect users", 'pc_ml'); ?></option>
              <?php
              foreach ( $pages as $pag ) {
                  ($fdata['pg_registered_user_redirect'] == $pag->ID) ? $selected = 'selected="selected"' : $selected = '';
                  echo '<option value="'.$pag->ID.'" '.$selected.'>'.$pag->post_title.'</option>';
              }
              ?>
            </select>   
          </td>
          <td><span class="info"><?php _e("Select the page where registered users will be redirected after registration", 'pc_ml'); ?></span></td>
        </tr>
        <tr>
          	<td class="pc_label_td" rowspan="2"><?php _e("Redirect page after user login", 'pc_ml'); ?></td>
            <td class="pc_field_td">
              <select name="pg_logged_user_redirect" id="pc_logged_user_redirect" class="lcweb-chosen" data-placeholder="Select a page .." tabindex="2">
                <option value=""><?php _e('Do not redirect users', 'pc_ml') ?></option>
                <option value="custom" <?php if($fdata['pg_logged_user_redirect'] == 'custom') echo 'selected="selected"'; ?>><?php _e('Custom redirect', 'pc_ml') ?></option>
                <?php
                foreach ( $pages as $pag ) {
                    ($fdata['pg_logged_user_redirect'] == $pag->ID) ? $selected = 'selected="selected"' : $selected = '';
                    echo '<option value="'.$pag->ID.'" '.$selected.'>'.$pag->post_title.'</option>';
                }
                ?>
              </select>   
            </td>
            <td><span class="info"><?php _e('Select page where users will be redirected after login', 'pc_ml') ?></span></td>
          </tr>
          <tr id="pc_logged_user_redirect_cst_wrap">
        	<td colspan="2" <?php if($fdata['pg_logged_user_redirect'] != 'custom') {echo 'style="display: none;"';} ?>>
            	<input type="text" name="pg_logged_user_redirect_custom" value="<?php echo pc_sanitize_input($fdata['pg_logged_user_redirect_custom']); ?>" style="width: 70%;" />
            </td>
          </tr>
          <tr>
             <td class="pc_label_td"><?php _e("Redirect users to the last restricted page?", 'pc_ml'); ?></td>
             <td class="pc_field_td">
              <?php ($fdata['pg_redirect_back_after_login']) ? $checked= 'checked="checked"' : $checked = ''; ?>
              <input type="checkbox" name="pg_redirect_back_after_login" value="1" <?php echo $checked; ?> class="ip_checks" />
             </td>
             <td><span class="info"><?php _e('If checked, move logged users back to the last restricted page they tried to see (if available)', 'pc_ml') ?></span></td>
          </tr>
          
          <tr>
          	<td class="pc_label_td" rowspan="2"><?php _e("Redirect page after user logout", 'pc_ml'); ?></td>
            <td class="pc_field_td">
              <select name="pg_logout_user_redirect" id="pc_logout_user_redirect" class="lcweb-chosen" data-placeholder="<?php _e('Select a page', 'pc_ml') ?> .." tabindex="2">
                <option value=""><?php _e('Do not redirect users', 'pc_ml') ?></option>
                <option value="custom" <?php if($fdata['pg_logout_user_redirect'] == 'custom') echo 'selected="selected"'; ?>><?php _e('Custom redirect', 'pc_ml') ?></option>
                <?php
                foreach ( $pages as $pag ) {
                    ($fdata['pg_logout_user_redirect'] == $pag->ID) ? $selected = 'selected="selected"' : $selected = '';
                    echo '<option value="'.$pag->ID.'" '.$selected.'>'.$pag->post_title.'</option>';
                }
                ?>
              </select>   
            </td>
            <td><span class="info"><?php _e('Select the page where users will be redirected after logout', 'pc_ml') ?></span></td>
          </tr>
          <tr id="pc_logout_user_redirect_cst_wrap">
        	<td colspan="2" <?php if($fdata['pg_logout_user_redirect'] != 'custom') {echo 'style="display: none;"';} ?>>
            	<input type="text" name="pg_logout_user_redirect_custom" value="<?php echo pc_sanitize_input($fdata['pg_logout_user_redirect_custom']); ?>" style="width: 70%;" />
            </td>
          </tr>
       </table>   
       
       
       <h3><?php _e("Complete Site Lock", 'pc_ml') ?></h3>
       <table class="widefat pc_table">
         <tr>
           <td class="pc_label_td"><?php _e("Enable lock?", 'pc_ml'); ?></td>
           <td class="pc_field_td">
            <?php ($fdata['pg_complete_lock']) ? $checked= 'checked="checked"' : $checked = ''; ?>
            <input type="checkbox" name="pg_complete_lock" value="1" <?php echo $checked; ?> class="ip_checks" />
           </td>
           <td>
           	<span class="info" style="line-height: 23px;"><?php _e('If checked, website will be completely hidden for non logged users', 'pc_ml') ?> <br/>
            <?php _e('<strong>Note</strong>: "Restriction redirect target" will be visible to allow the users login. Be sure you are using a Wordpress page', 'pc_ml') ?></span>
           </td>
         </tr>
       </table>
       
       <div <?php if(!$cpt && !$ct) {echo 'style="display: none;"';} ?>>
         <h3><?php _e("Custom Post types and Taxonomies"); ?></h3>
         <table class="widefat pc_table">
           <?php if($cpt) : ?>
           <tr>
             <td class="pc_label_td"><?php _e("Enable restriction on these post types", 'pc_ml') ?></td>
             <td>
             <select name="pg_extend_cpt[]" multiple="multiple" class="lcweb-chosen" data-placeholder="<?php _e('Select the custom post types', 'pc_ml') ?> .." style="width: 50%;">
                <?php
                foreach($cpt as $id => $name) {
                    (is_array($fdata['pg_extend_cpt']) && in_array($id, $fdata['pg_extend_cpt'])) ? $selected = 'selected="selected"' : $selected = '';
                    echo '<option value="'.$id.'" '.$selected.'>'.$name.'</option>';
                }
                ?>
              </select> 
             </td>
           </tr>
           <?php 
		   endif;
		   
		   if($ct) : 
		   ?>
           <tr>
             <td class="pc_label_td"><?php _e("Enable restriction on these taxonomies", 'pc_ml'); ?></td>
             <td>
               <select name="pg_extend_ct[]" multiple="multiple" class="lcweb-chosen" data-placeholder="<?php _e("Select the custom taxonomies", 'pc_ml'); ?> .." tabindex="2" style="width: 50%;">
				<?php
                foreach($ct as $id => $name) {
                    (is_array($fdata['pg_extend_ct']) && in_array($id, $fdata['pg_extend_ct'])) ? $selected = 'selected="selected"' : $selected = '';
                    echo '<option value="'.$id.'" '.$selected.'>'.$name.'</option>';
                }
                ?>
              </select> 
             </td>
           </tr>
           <?php endif; ?>
         </table>
       </div>
       
       <h3><?php _e("Wordpress user system integration", 'pc_ml') ?></h3>
       <table class="widefat pc_table">
         <tr>
           <td class="pc_label_td"><?php _e("Enable integration?", 'pc_ml'); ?></td>
           <td class="pc_field_td">
            <?php $checked = ($fdata['pg_wp_user_sync']) ? 'checked="checked"' : ''; ?>
            <input type="checkbox" name="pg_wp_user_sync" value="1" <?php echo $checked; ?> class="ip_checks" />
           </td>
           <td>
           	<span class="info" style="line-height: 23px;"><?php _e('If checked, privateContent users will be logged also with basic WP account', 'pc_ml') ?> <br/>
            <?php _e("<strong>What does this implies?</strong> For more details, please check the related documentation chapter", 'pc_ml') ?></span>
           </td>
         </tr>
         
         <?php if($fdata['pg_wp_user_sync']): ?>
         <tr>
           <td class="pc_label_td"><?php _e("Require sync during frontend registration?", 'pc_ml'); ?></td>
           <td class="pc_field_td">
            <?php $checked = ($fdata['pg_require_wps_registration']) ? 'checked="checked"' : ''; ?>
            <input type="checkbox" name="pg_require_wps_registration" value="1" <?php echo $checked; ?> class="ip_checks" />
           </td>
           <td><span class="info"><?php _e('Allow new users only if WP user sync is successful (automatically adds e-mail field into registration form)', 'pc_ml') ?></span></td>
         </tr>
         <tr>
           <td class="pc_label_td"><?php _e("Additional user roles", 'pc_ml'); ?></td>
           <td class="pc_field_td">
              <select name="pg_custom_wps_roles[]" class="lcweb-chosen" data-placeholder="<?php _e("Select a role", 'pc_ml'); ?> .." multiple="multiple" autocomplete="off">
				  <?php
                  foreach (get_editable_roles() as $role_id => $data) { 
                    if(!in_array($role_id, $pc_wp_user->forbidden_roles)) {
						$selected = (in_array($role_id, (array)$fdata['pg_custom_wps_roles'])) ? 'selected="selected"' : '';
						echo '<option value="'. $role_id .'" '.$selected.'>'. $data['name'] .'</option>';
					}
                  }
                  ?>
              </select> 
           </td>
           <td><span class="info"><?php _e('Set which roles will be applied to synced users', 'pc_ml') ?></span></td>
         </tr>
         <tr><td colspan="3"></td></tr>
         <tr>
           <td class="pc_label_td"><?php _e("Hide comments block in every page?", 'pc_ml'); ?></td>
           <td class="pc_field_td">
            <?php $checked = ($fdata['pg_lock_comments']) ? 'checked="checked"' : ''; ?>
            <input type="checkbox" name="pg_lock_comments" value="1" <?php echo $checked; ?> class="ip_checks" />
           </td>
           <td><span class="info"><?php _e('If checked, totally hides comment block on site for unlogged users', 'pc_ml') ?></span></td>
         </tr>
         <tr>
           <td class="pc_label_td"><?php _e("Display warning for hidden comment blocks?", 'pc_ml'); ?></td>
           <td class="pc_field_td">
            <?php $checked = ($fdata['pg_hc_warning']) ? 'checked="checked"' : ''; ?>
            <input type="checkbox" name="pg_hc_warning" value="1" <?php echo $checked; ?> class="ip_checks" />
           </td>
           <td><span class="info"><?php _e('If checked, shows a warning box replacing comment form (can be overrided for single posts)', 'pc_ml') ?></span></td>
         </tr>
         <tr>
           <td colspan="2">
           	<input type="button" id="pc_do_wp_sync" class="button-secondary" value="<?php _e('Sync users', 'pc_ml') ?>" />
           	<span class="pc_gwps_result"></span>
           </td>
           <td><span class="info"><strong><?php _e('Only users with unique username and e-mail will be synced', 'pc_ml') ?></strong></span></td>
         </tr>
         
		 <?php // search existing pvtContent -> WP matches and sync 
		 if(isset($_GET['wps_existing_sync'])) : ?>
         <tr>
           <td colspan="2">
            <input type="button" id="pc_wps_matches_sync" class="button-secondary" value="<?php _e('Search existing matches and sync', 'pc_ml') ?>" />
            <span class="pc_gwps_result"></span>
           </td>
           <td><span class="info"><strong><?php _e('Search matches between existing PrivateContent and WP users, and sync them', 'pc_ml') ?></strong></span></td>
         </tr>
         <?php endif; ?>
           
         <tr>
           <td colspan="2">
           	<input type="button" id="pc_clean_wp_sync" class="button-secondary" value="<?php _e('Clear sync', 'pc_ml') ?>" />
           	<span class="pc_gwps_result"></span>
           </td>
           <td><span class="info"><?php _e('Detach previous sync and delete related WP users', 'pc_ml') ?></span></td>
         </tr>
         <?php endif; ?>
       </table>
       
       <h3><?php _e("Advanced", 'pc_ml'); ?></h3>
       <table class="widefat pc_table">
       	 <tr>
           <td class="pc_label_td"><?php _e('Enable "testing" mode?', 'pc_ml'); ?></td>
           <td class="pc_field_td">
            <?php ($fdata['pg_test_mode']) ? $checked= 'checked="checked"' : $checked = ''; ?>
            <input type="checkbox" name="pg_test_mode" value="1" <?php echo $checked; ?> class="ip_checks" />
           </td>
           <td><span class="info"><?php _e("If checked, WP users won't be able to see private contents", 'pc_ml'); ?></span></td>
         </tr>
         <tr>
           <td class="pc_label_td"><?php _e('Use "remember me" check in login form?', 'pc_ml'); ?></td>
           <td class="pc_field_td">
            <?php ($fdata['pg_use_remember_me']) ? $checked= 'checked="checked"' : $checked = ''; ?>
            <input type="checkbox" name="pg_use_remember_me" value="1" <?php echo $checked; ?> class="ip_checks" />
           </td>
           <td><span class="info"><?php _e('If checked, allow users to keep logged into the website', 'pc_ml'); ?></span></td>
         </tr>
         <tr>
           <td class="pc_label_td"><?php _e('Use first/last name in forms?', 'pc_ml'); ?></td>
           <td class="pc_field_td">
            <?php $checked = ($fdata['pg_use_first_last_name']) ? 'checked="checked"' : ''; ?>
            <input type="checkbox" name="pg_use_first_last_name" value="1" <?php echo $checked; ?> class="ip_checks" />
           </td>
           <td><span class="info"><?php _e('If checked, replaces name/surname with first/last name', 'pc_ml'); ?></span></td>
         </tr>
         <tr>
           <td class="pc_label_td"><?php _e("Allow inline login within PrivateContent warings?", 'pc_ml'); ?></td>
           <td class="pc_field_td">
            <?php ($fdata['pg_js_inline_login']) ? $checked= 'checked="checked"' : $checked = ''; ?>
            <input type="checkbox" name="pg_js_inline_login" value="1" <?php echo $checked; ?> class="ip_checks" />
           </td>
           <td><span class="info"><?php _e('If checked, allow users to login from yellow warning boxes', 'pc_ml'); ?></span></td>
         </tr>
         <tr>
           <td class="pc_label_td"><?php _e("Minimum role to use the plugin", 'pc_ml'); ?></td>
           <td class="pc_field_td">
           	  <select name="pg_min_role" class="lcweb-chosen" data-placeholder="<?php _e('Select a role', 'pc_ml'); ?>" tabindex="2">
				<?php
                foreach(pc_wp_roles() as $capab => $name) {
                    ($fdata['pg_min_role'] == $capab) ? $selected = 'selected="selected"' : $selected = '';
                    echo '<option value="'.$capab.'" '.$selected.'>'.$name.'</option>';
                }
                ?>
              </select> 
           </td>
           <td><span class="info"><?php _e('Minimum WP role to use the plugin and see private contents', 'pc_ml'); ?></span></td>
         </tr>
         <tr>
           <td class="pc_label_td"><?php _e("Minimum role to manage users", 'pc_ml'); ?></td>
           <td class="pc_field_td">
           	  <select name="pg_min_role_tmu" class="lcweb-chosen" data-placeholder="<?php _e('Select a role', 'pc_ml'); ?>" tabindex="2">
				<?php
				if(!$fdata['pg_min_role_tmu']) {$fdata['pg_min_role_tmu'] = 'upload_files';}
                foreach(pc_wp_roles() as $capab => $name) {
                    ($fdata['pg_min_role_tmu'] == $capab) ? $selected = 'selected="selected"' : $selected = '';
                    echo '<option value="'.$capab.'" '.$selected.'>'.$name.'</option>';
                }
                ?>
              </select> 
           </td>
           <td><span class="info"><?php _e('Minimum WP role to edit and manage users', 'pc_ml'); ?></span></td>
         </tr>
         <tr>
            <td class="pc_label_td"><?php _e("Use custom CSS inline?", 'pc_ml'); ?></td>
            <td class="pc_field_td">
                <?php ($fdata['pg_force_inline_css'] == 1) ? $sel = 'checked="checked"' : $sel = ''; ?>
                <input type="checkbox" value="1" name="pg_force_inline_css" class="ip_checks" <?php echo $sel; ?> />
            </td>
            <td>
            	<span class="info"><?php _e('If checked, uses custom CSS inline (useful for multisite installations)', 'pc_ml') ?></span>
            </td>
          </tr>
       </table>
    </div>
    

    <div id="form_opt">
    	<h3><?php _e("General registration settings", 'pc_ml'); ?></h3>
    	<table class="widefat pc_table">
         <tr>
            <td class="pc_label_td"><?php _e("Allow duplicated e-mails?", 'pc_ml'); ?></td>
            <td class="pc_field_td">
            	<?php $checked = ($fdata['pg_allow_duplicated_mails']) ? 'checked="checked"' : ''; ?>
            	<input type="checkbox" name="pg_allow_duplicated_mails" value="1" <?php echo $checked; ?> class="ip_checks" />
            </td>
            <td><span class="info"><?php _e("Check if want to allow users with same e-mail into the database", 'pc_ml'); ?></span></td>
         </tr>
         <tr>
           <td class="pc_label_td"><?php _e("Default category for registered users", 'pc_ml'); ?></td>
           <td class="pc_field_td">
           	  <select name="pg_registration_cat[]" class="lcweb-chosen" data-placeholder="<?php _e("Select a category", 'pc_ml'); ?> .." multiple="multiple" autocomplete="off">
                <option value=""></option>
				  <?php
				  foreach (pc_user_cats() as $cat_id => $cat_name) { 
					$selected = (in_array($cat_id, (array)$fdata['pg_registration_cat'])) ? 'selected="selected"' : '';
					echo '<option value="'. $cat_id .'" '.$selected.'>'. $cat_name .'</option>';
				  }
                  ?>
              </select> 
           </td>
           <td><span class="info"><?php _e("Default user registration categories (ingored if you use category field in forms)", 'pc_ml'); ?></span></td>
         </tr>
         <tr>
            <td class="pc_label_td"><?php _e('"Category" field - custom label', 'gg_ml'); ?></td>
            <td class="pc_field_td">
                <input type="text" value="<?php echo pc_sanitize_input($fdata['pg_reg_cat_label']); ?>" name="pg_reg_cat_label" autocomplete="off" />
            </td>
            <td><span class="info"><?php _e('Set a custom label for category field in registration forms', 'gg_ml'); ?></span></td>
          </tr>
          <tr>
            <td class="pc_label_td"><?php _e("Allow multiple user categories selection during registration?", 'pc_ml'); ?></td>
            <td class="pc_field_td">
            	<?php $checked = ($fdata['pg_reg_multiple_cats']) ? 'checked="checked"' : ''; ?>
            	<input type="checkbox" name="pg_reg_multiple_cats" value="1" <?php echo $checked; ?> class="ip_checks" />
            </td>
            <td><span class="info"><?php _e('Check to allow users choose multiple categories in registration forms', 'gg_ml'); ?></span></td>
         </tr>
         <tr>
           <td class="pc_label_td"><?php _e("Anti-spam system", 'pc_ml'); ?></td>
           <td class="pc_field_td">
           	  <select name="pg_antispam_sys" class="lcweb-chosen" data-placeholder="<?php _e("Select an option", 'pc_ml'); ?> .." tabindex="2">
                <option value="honeypot"><?php _e('Honey pot hidden system', 'pc_ml') ?></option>
				<option value="recaptcha" <?php if($fdata['pg_antispam_sys'] == 'recaptcha') echo'selected="selected"' ?>><?php _e('reCAPTCHA validation', 'pc_ml') ?></option>
              </select> 
           </td>
           <td><span class="info"><?php _e("Choose the anti-spam solution you prefer", 'pc_ml'); ?></span></td>
         </tr>
         <tr>
            <td class="pc_label_td"><?php _e("Set users status as pending after registration?", 'pc_ml'); ?></td>
            <td class="pc_field_td">
            	<?php ($fdata['pg_registered_pending']) ? $checked= 'checked="checked"' : $checked = ''; ?>
            	<input type="checkbox" name="pg_registered_pending" value="1" <?php echo $checked; ?> class="ip_checks" />
            </td>
            <td></td>
         </tr>
         <tr>
            <td class="pc_label_td"><?php _e("Enable private page for new registered users?", 'pc_ml'); ?></td>
            <td class="pc_field_td">
            	<?php ($fdata['pg_registered_pvtpage']) ? $checked= 'checked="checked"' : $checked = ''; ?>
            	<input type="checkbox" name="pg_registered_pvtpage" value="1" <?php echo $checked; ?> class="ip_checks" />
            </td>
            <td></td>
         </tr>
      </table>
        
      <h3><?php _e("Disclaimer", 'pc_ml'); ?></h3>
      <table class="widefat pc_table">
        <tr>
           <td class="pc_label_td"><?php _e('Enable disclaimer?', 'pc_ml'); ?></td>
           <td class="pc_field_td">
            <?php ($fdata['pg_use_disclaimer']) ? $checked= 'checked="checked"' : $checked = ''; ?>
            <input type="checkbox" name="pg_use_disclaimer" value="1" <?php echo $checked; ?> class="ip_checks" />
           </td>
           <td><span class="info"><?php _e('If checked, append the disclaimer to the registration form', 'pc_ml'); ?></span></td>
        </tr>
        <tr>
          <td class="pc_label_td"><?php _e("Disclaimer text", 'pc_ml'); ?></td>
          <td class="pc_field_td" colspan="2">
			  <?php 
			  $args = array('textarea_rows'=> 2);
			  echo wp_editor( $fdata['pg_disclaimer_txt'], 'pg_disclaimer_txt', $args); 
			  ?>
          </td>
		</tr>
      </table>
       
      <h3><?php _e("Password security settings", 'pc_ml'); ?></h3>
      <table class="widefat pc_table">
        <tr>
           <td class="pc_label_td"><?php _e('Minimum password length', 'pc_ml'); ?></td>
           <td class="pc_field_td">
              <div class="lcwp_slider" step="1" max="10" min="4"></div>
              <input type="text" value="<?php echo (int)$fdata['pg_psw_min_length']; ?>" name="pg_psw_min_length" class="lcwp_slider_input" />
          </td>
          <td><span class="info"><?php _e('Set a minimum characters number for user passwords', 'pc_ml'); ?></span></td>
		</tr>
        <tr>
           <td class="pc_label_td"><?php _e("Password strength options", 'pc_ml') ?></td>
           <td>
           <select name="pg_psw_strength[]" multiple="multiple" class="lcweb-chosen" data-placeholder="<?php _e('select an option', 'pc_ml') ?> .."  style="width: 100%;">
              <option value="chars_digits" <?php if(in_array('chars_digits', $fdata['pg_psw_strength'])) echo'selected="selected"' ?>><?php _e('use characters and digits', 'pc_ml') ?></option>
              <option value="use_uppercase" <?php if(in_array('use_uppercase', $fdata['pg_psw_strength'])) echo'selected="selected"' ?>><?php _e('use uppercase characters', 'pc_ml')?></option>
              <option value="use_symbols" <?php if(in_array('use_symbols', $fdata['pg_psw_strength'])) echo'selected="selected"' ?>><?php _e('use symbols', 'pc_ml') ?></option>
            </select> 
           </td>
           <td><span class="info"><?php _e('Improve passwords strength with these options', 'pc_ml'); ?></span></td>
         </tr>
       </table> 
        
    	<h3 style="border-bottom: none;"><?php _e("Registration forms builder", 'pc_ml'); ?></h3>
        <table id="pc_reg_form_builder_cmd_wrap"class="widefat">
         <tr>
          	<td style="padding-right: 0;">
            	<input type="text" name="pg_new_reg_form_name" id="pc_new_reg_form_name" placeholder="<?php _e("New form's name", 'pc_ml') ?>" maxlength="150" autocomplete="off" />
            </td>
            <td style="width: 55px; text-align: center; padding-right: 25px; border-right: 1px solid #e1e1e1;">
            	<input type="button" value="<?php _e('Add', 'pc_ml') ?>" id="pc_reg_form_add" class="button-secondary" />
            </td>
            <td style="padding-left: 25px; padding-right: 0;">
            	<select name="pg_form_builder_dd" class="lcweb-chosen pc_form_builder_dd" data-placeholder="<?php _e('Select a form to edit', 'pc_ml') ?> .." autocomplete="off">
					<?php 
					$a = 0;
					$reg_forms = get_terms('pc_reg_form', 'hide_empty=0&order=DESC');
					foreach($reg_forms as $rf) {
						$sel = (!$a) ? 'selected="selected"' : '';
						echo '<option value="'.$rf->term_id.'" '.$sel.'>'.$rf->term_id.' - '.$rf->name.'</option>';
						$a++;
					}
					?>
                </select>
            </td>
            <td id="pc_reg_form_cmd" style="width: 130px; text-align: center; visibility: hidden;">
				<input type="button" value="<?php _e('Save', 'pc_ml') ?>" id="pc_reg_form_save" class="button-primary" />
                <input type="button" value="<?php _e('Delete', 'pc_ml') ?>" id="pc_reg_form_del" class="button-secondary" style="margin-left: 10px;" />
            </td>
         </tr> 	
        </table>
        <i id="pc_reg_form_loader"></i>
        <br style="clear: both;" /> 
        
        <div id="pc_reg_form_builder"></div>
    </div>
    
    
    <div id="styling">
		<h3><?php _e("General settings", 'pc_ml') ?></h3>
		<table class="widefat pc_table">
          <tr>
           <td class="pc_label_td"><?php _e("Default forms layout", 'pc_ml'); ?></td>
           <td class="pc_field_td">
              <select name="pg_reg_layout" class="lcweb-chosen" data-placeholder="<?php _e('Select an option', 'pc_ml') ?> ..">
                <option value="one_col"><?php _e('Single column', 'pc_ml') ?></option>
                <option value="fluid" <?php if($fdata['pg_reg_layout'] == 'fluid') {echo 'selected="selected"';} ?>><?php _e('Fluid (multi column)', 'pc_ml') ?></option>
              </select>             
           </td>
           <td><span class="info"><?php _e('Select default layout for registration and User Data add-on forms', 'pc_ml') ?></span></td>
         </tr>
         <tr>
           <td class="pc_label_td"><?php _e("Frontend style", 'pc_ml'); ?></td>
           <td class="pc_field_td">
              <select name="pg_style" class="lcweb-chosen" data-placeholder="<?php _e('Select a style', 'pc_ml') ?> .." >
                <option value="minimal"><?php _e('Minimal', 'pc_ml') ?></option>
                <option value="light" <?php if($fdata['pg_style'] == 'light') {echo 'selected="selected"';} ?>><?php _e('Light', 'pc_ml') ?></option>
                <option value="dark" <?php if($fdata['pg_style'] == 'dark') {echo 'selected="selected"';} ?>><?php _e('Dark', 'pc_ml') ?></option>
                <option value="custom" <?php if($fdata['pg_style'] == 'custom') {echo 'selected="selected"';} ?>><?php _e('Custom', 'pc_ml') ?></option>
              </select>             
           </td>
           <td><span class="info"><?php _e('Select the style that will be used for the frontend forms and boxes', 'pc_ml') ?></span></td>
         </tr>
         <tr>
           <td class="pc_label_td"><?php _e("Disable default frontend CSS?", 'pc_ml'); ?></td>
           <td class="pc_field_td">
           	 <?php ($fdata['pg_disable_front_css']) ? $checked= 'checked="checked"' : $checked = ''; ?>
             <input type="checkbox" name="pg_disable_front_css" value="1" <?php echo $checked; ?> class="ip_checks" />
           </td>
           <td><span class="info"><?php _e("If checked, prevents plugin CSS to be used", 'pc_ml'); ?></span></td>
         </tr>
		</table>
    	
		<h3><?php _e("Elements layout", 'pc_ml') ?></h3>
		<table class="widefat pc_table">
          <tr>
            <td class="pc_label_td"><?php _e('Fields padding', 'pc_ml'); ?></td>
            <td class="pc_field_td">
            	<div class="lcwp_slider" step="1" max="15" min="0"></div>
            	<input type="text" value="<?php echo (int)$fdata['pg_field_padding']; ?>" name="pg_field_padding" class="lcwp_slider_input" autocomplete="off" />
                <span>px</span>
            </td>
            <td><span class="info"></span></td>
          </tr>
          <tr>
            <td class="pc_label_td"><?php _e('Fields border width', 'pc_ml'); ?></td>
            <td class="pc_field_td">
            	<div class="lcwp_slider" step="1" max="5" min="0"></div>
            	<input type="text" value="<?php echo (int)$fdata['pg_field_border_w']; ?>" name="pg_field_border_w" class="lcwp_slider_input" />
                <span>px</span>
            </td>
            <td><span class="info"></span></td>
          </tr>
          <tr>
            <td class="pc_label_td"><?php _e('Forms border radius', 'pc_ml'); ?></td>
            <td class="pc_field_td">
            	<div class="lcwp_slider" step="1" max="40" min="0"></div>
            	<input type="text" value="<?php echo (int)$fdata['pg_form_border_radius']; ?>" name="pg_form_border_radius" class="lcwp_slider_input" />
                <span>px</span>
            </td>
            <td><span class="info"></span></td>
          </tr>
          <tr>
            <td class="pc_label_td"><?php _e('Fields border radius', 'pc_ml'); ?></td>
            <td class="pc_field_td">
            	<div class="lcwp_slider" step="1" max="20" min="0"></div>
            	<input type="text" value="<?php echo (int)$fdata['pg_field_border_radius']; ?>" name="pg_field_border_radius" class="lcwp_slider_input" />
                <span>px</span>
            </td>
            <td><span class="info"></span></td>
          </tr>
          <tr>
            <td class="pc_label_td"><?php _e('Buttons border radius', 'pc_ml'); ?></td>
            <td class="pc_field_td">
            	<div class="lcwp_slider" step="1" max="20" min="0"></div>
            	<input type="text" value="<?php echo (int)$fdata['pg_btn_border_radius']; ?>" name="pg_btn_border_radius" class="lcwp_slider_input" />
                <span>px</span>
            </td>
            <td><span class="info"></span></td>
          </tr>
        </table>
        
        <h3><?php _e("Typography", 'pc_ml') ?></h3>
		<table class="widefat pc_table">
          <tr>
            <td class="pc_label_td"><?php _e('Login form labels - font size', 'pc_ml'); ?></td>
            <td class="pc_field_td">
            	<div class="lcwp_slider" step="1" max="18" min="12"></div>
            	<input type="text" value="<?php echo (int)$fdata['pg_lf_font_size']; ?>" name="pg_lf_font_size" class="lcwp_slider_input" autocomplete="off" />
                <span>px</span>
            </td>
            <td><span class="info"><?php _e('Set login form labels size (default: 15px)', 'pc_ml') ?></span></td>
          </tr>
          <tr>
            <td class="pc_label_td"><?php _e('Registration form labels - font size', 'pc_ml'); ?></td>
            <td class="pc_field_td">
            	<div class="lcwp_slider" step="1" max="18" min="12"></div>
            	<input type="text" value="<?php echo (int)$fdata['pg_rf_font_size']; ?>" name="pg_rf_font_size" class="lcwp_slider_input" autocomplete="off" />
                <span>px</span>
            </td>
            <td><span class="info"><?php _e('Set registration form labels size (default: 15px)', 'pc_ml') ?></span></td>
          </tr>
          <tr>
            <td class="pc_label_td"><?php _e('Registration form labels - font size', 'pc_ml'); ?></td>
            <td class="pc_field_td">
            	<input type="text" value="<?php echo $fdata['pg_forms_font_family']; ?>" name="pg_forms_font_family"  autocomplete="off" />
            </td>
            <td><span class="info"><?php _e("Set forms font family (leave empty to use theme's one)", 'pc_ml') ?></span></td>
          </tr>
        </table>
        
        <h3><?php _e("Colors", 'pc_ml') ?></h3>
		<table class="widefat pc_table">
          <tr>
            <td class="pc_label_td"><?php _e("Forms background color", 'pc_ml'); ?></td>
            <td class="pc_field_td">
                <div class="lcwp_colpick">
                	<span class="lcwp_colblock" style="background-color: <?php echo $fdata['pg_forms_bg_col']; ?>;"></span>
                	<input type="text" name="pg_forms_bg_col" value="<?php echo $fdata['pg_forms_bg_col']; ?>" maxlength="7" autocomplete="off" />
                </div>
            </td>
            <td><span class="info"></span></td>
          </tr>
          <tr>
            <td class="pc_label_td"><?php _e("Forms border color", 'pc_ml'); ?></td>
            <td class="pc_field_td">
                <div class="lcwp_colpick">
                	<span class="lcwp_colblock" style="background-color: <?php echo $fdata['pg_forms_border_col']; ?>;"></span>
                	<input type="text" name="pg_forms_border_col" value="<?php echo $fdata['pg_forms_border_col']; ?>" maxlength="7" autocomplete="off" />
                </div>
            </td>
            <td><span class="info"></span></td>
          </tr>
          <tr>
            <td class="pc_label_td"><?php _e("Labels color", 'pc_ml'); ?></td>
            <td class="pc_field_td">
                <div class="lcwp_colpick">
                	<span class="lcwp_colblock" style="background-color: <?php echo $fdata['pg_label_col']; ?>;"></span>
                	<input type="text" name="pg_label_col" value="<?php echo $fdata['pg_label_col']; ?>" maxlength="7" autocomplete="off" />
                </div>
            </td>
            <td><span class="info"></span></td>
          </tr>
          <tr>
            <td class="pc_label_td"><?php _e("reCAPTCHA icons color", 'pc_ml'); ?></td>
            <td class="pc_field_td">
                <select name="pg_recaptcha_col" class="lcweb-chosen" data-placeholder="<?php _e('Select an option', 'pc_ml') ?> ..">
                  <option value="l"><?php _e('Dark', 'pc_ml') ?></option>
                  <option value="d" <?php if($fdata['pg_recaptcha_col'] == 'd') {echo 'selected="selected"';} ?>><?php _e('Light', 'pc_ml') ?></option>
                </select>  
            </td>
            <td><span class="info"></span></td>
          </tr>
          
          <?php if(defined('PCUD_URL')): ?>
          <tr>
            <td class="pc_label_td"><?php _e("Datepicker theme", 'pc_ml'); ?></td>
            <td class="pc_field_td">
                <select name="pg_datepicker_col" class="lcweb-chosen" data-placeholder="<?php _e('Select an option', 'pc_ml') ?> ..">
                  <option value="light"><?php _e('Light', 'pc_ml') ?></option>
                  <option value="dark" <?php if($fdata['pg_datepicker_col'] == 'dark') {echo 'selected="selected"';} ?>><?php _e('Dark', 'pc_ml') ?></option>
                </select>  
            </td>
            <td><span class="info"></span></td>
          </tr>
          <?php endif; ?>
          
          <tr><td colspan="3"></td></tr>
          <tr>
            <td class="pc_label_td"><?php _e("Fields background color", 'pc_ml'); ?></td>
            <td class="pc_field_td">
                <div class="lcwp_colpick">
                	<span class="lcwp_colblock" style="background-color: <?php echo $fdata['pg_fields_bg_col']; ?>;"></span>
                	<input type="text" name="pg_fields_bg_col" value="<?php echo $fdata['pg_fields_bg_col']; ?>" maxlength="7" autocomplete="off" />
                </div>
            </td>
            <td><span class="info"><?php _e('Fields background color - default status', 'pc_ml') ?></span></td>
          </tr>
          <tr>
            <td class="pc_label_td"><?php _e("Fields border color", 'pc_ml'); ?></td>
            <td class="pc_field_td">
                <div class="lcwp_colpick">
                	<span class="lcwp_colblock" style="background-color: <?php echo $fdata['pg_fields_border_col']; ?>;"></span>
                	<input type="text" name="pg_fields_border_col" value="<?php echo $fdata['pg_fields_border_col']; ?>" maxlength="7" autocomplete="off" />
                </div>
            </td>
            <td><span class="info"><?php _e('Fields border color - default status', 'pc_ml') ?></span></td>
          </tr>
          <tr>
            <td class="pc_label_td"><?php _e("Fields text color", 'pc_ml'); ?></td>
            <td class="pc_field_td">
                <div class="lcwp_colpick">
                	<span class="lcwp_colblock" style="background-color: <?php echo $fdata['pg_fields_txt_col']; ?>;"></span>
                	<input type="text" name="pg_fields_txt_col" value="<?php echo $fdata['pg_fields_txt_col']; ?>" maxlength="7" autocomplete="off" />
                </div>
            </td>
            <td><span class="info"><?php _e('Fields text color - default status', 'pc_ml') ?></span></td>
          </tr>
          <tr>
            <td class="pc_label_td"><?php _e("Fields background color - on hover", 'pc_ml'); ?></td>
            <td class="pc_field_td">
                <div class="lcwp_colpick">
                	<span class="lcwp_colblock" style="background-color: <?php echo $fdata['pg_fields_bg_col_h']; ?>;"></span>
                	<input type="text" name="pg_fields_bg_col_h" value="<?php echo $fdata['pg_fields_bg_col_h']; ?>" maxlength="7" autocomplete="off" />
                </div>
            </td>
            <td><span class="info"><?php _e('Fields background color - hover status', 'pc_ml') ?></span></td>
          </tr>
          <tr>
            <td class="pc_label_td"><?php _e("Fields border color - on hover", 'pc_ml'); ?></td>
            <td class="pc_field_td">
                <div class="lcwp_colpick">
                	<span class="lcwp_colblock" style="background-color: <?php echo $fdata['pg_fields_border_col_h']; ?>;"></span>
                	<input type="text" name="pg_fields_border_col_h" value="<?php echo $fdata['pg_fields_border_col_h']; ?>" maxlength="7" autocomplete="off" />
                </div>
            </td>
            <td><span class="info"><?php _e('Fields border color - hover status', 'pc_ml') ?></span></td>
          </tr>
          <tr>
            <td class="pc_label_td"><?php _e("Fields text color - on hover", 'pc_ml'); ?></td>
            <td class="pc_field_td">
                <div class="lcwp_colpick">
                	<span class="lcwp_colblock" style="background-color: <?php echo $fdata['pg_fields_txt_col_h']; ?>;"></span>
                	<input type="text" name="pg_fields_txt_col_h" value="<?php echo $fdata['pg_fields_txt_col_h']; ?>" maxlength="7" autocomplete="off" />
                </div>
            </td>
            <td><span class="info"><?php _e('Fields text color - hover status', 'pc_ml') ?></span></td>
          </tr>
          <tr><td colspan="3"></td></tr>
          <tr>
            <td class="pc_label_td"><?php _e("Buttons background color", 'pc_ml'); ?></td>
            <td class="pc_field_td">
                <div class="lcwp_colpick">
                	<span class="lcwp_colblock" style="background-color: <?php echo $fdata['pg_btn_bg_col']; ?>;"></span>
                	<input type="text" name="pg_btn_bg_col" value="<?php echo $fdata['pg_btn_bg_col']; ?>" maxlength="7" autocomplete="off" />
                </div>
            </td>
            <td><span class="info"><?php _e('Buttons background color - default status', 'pc_ml') ?></span></td>
          </tr>
          <tr>
            <td class="pc_label_td"><?php _e("Buttons border color", 'pc_ml'); ?></td>
            <td class="pc_field_td">
                <div class="lcwp_colpick">
                	<span class="lcwp_colblock" style="background-color: <?php echo $fdata['pg_btn_border_col']; ?>;"></span>
                	<input type="text" name="pg_btn_border_col" value="<?php echo $fdata['pg_btn_border_col']; ?>" maxlength="7" autocomplete="off" />
                </div>
            </td>
            <td><span class="info"><?php _e('Buttons border color - default status', 'pc_ml') ?></span></td>
          </tr>
          <tr>
            <td class="pc_label_td"><?php _e("Buttons text color", 'pc_ml'); ?></td>
            <td class="pc_field_td">
                <div class="lcwp_colpick">
                	<span class="lcwp_colblock" style="background-color: <?php echo $fdata['pg_btn_txt_col']; ?>;"></span>
                	<input type="text" name="pg_btn_txt_col" value="<?php echo $fdata['pg_btn_txt_col']; ?>" maxlength="7" autocomplete="off" />
                </div>
            </td>
            <td><span class="info"><?php _e('Buttons text color - default status', 'pc_ml') ?></span></td>
          </tr>
          <tr>
            <td class="pc_label_td"><?php _e("Buttons background color - on hover", 'pc_ml'); ?></td>
            <td class="pc_field_td">
                <div class="lcwp_colpick">
                	<span class="lcwp_colblock" style="background-color: <?php echo $fdata['pg_btn_bg_col_h']; ?>;"></span>
                	<input type="text" name="pg_btn_bg_col_h" value="<?php echo $fdata['pg_btn_bg_col_h']; ?>" maxlength="7" autocomplete="off" />
                </div>
            </td>
            <td><span class="info"><?php _e('Buttons background color - hover status', 'pc_ml') ?></span></td>
          </tr>
          <tr>
            <td class="pc_label_td"><?php _e("Buttons border color - on hover", 'pc_ml'); ?></td>
            <td class="pc_field_td">
                <div class="lcwp_colpick">
                	<span class="lcwp_colblock" style="background-color: <?php echo $fdata['pg_btn_border_col_h']; ?>;"></span>
                	<input type="text" name="pg_btn_border_col_h" value="<?php echo $fdata['pg_btn_border_col_h']; ?>" maxlength="7" autocomplete="off" />
                </div>
            </td>
            <td><span class="info"><?php _e('Buttons border color - hover status', 'pc_ml') ?></span></td>
          </tr>
          <tr>
            <td class="pc_label_td"><?php _e("Buttons text color - on hover", 'pc_ml'); ?></td>
            <td class="pc_field_td">
                <div class="lcwp_colpick">
                	<span class="lcwp_colblock" style="background-color: <?php echo $fdata['pg_btn_txt_col_h']; ?>;"></span>
                	<input type="text" name="pg_btn_txt_col_h" value="<?php echo $fdata['pg_btn_txt_col_h']; ?>" maxlength="7" autocomplete="off" />
                </div>
            </td>
            <td><span class="info"><?php _e('Buttons text color - hover status', 'pc_ml') ?></span></td>
          </tr>
        </table>
        
        <h3><?php _e("Custom CSS", 'pc_ml'); ?></h3>
        <table class="widefat lcwp_table">
          <tr>
            <td class="pc_field_td">
            	<textarea name="pg_custom_css" style="width: 100%" rows="6"><?php echo $fdata['pg_custom_css']; ?></textarea>
            </td>
          </tr>
        </table>
    </div>
    
    
    <div id="mex_opt">
    	<h3><?php _e("Restricted Content Message", 'pc_ml'); ?></h3>
        <table class="widefat pc_table">
          <tr>
            <td class="pc_label_td"><?php _e("Default message for unlogged users", 'pc_ml'); ?></td>
            <td class="pc_field_td">
               <input type="text" name="pg_default_nl_mex" value="<?php echo pc_sanitize_input($fdata['pg_default_nl_mex']); ?>" maxlength="255" autocomplete="off" /> 
               <p class="info"><?php _e('By default is "You must be logged in to view this content"', 'pc_ml'); ?></p>
            </td>
         </tr>
         <tr>
            <td class="pc_label_td"><?php _e("Custom message for wrong permission users", 'pc_ml'); ?></td>
            <td class="pc_field_td">
            	<input type="text" name="pg_default_uca_mex" value="<?php echo pc_sanitize_input($fdata['pg_default_uca_mex']); ?>" maxlength="170" autocomplete="off" />
              	<p class="info"><?php _e('By default is "Sorry, you don\'t have the right permissions to view this content"', 'pc_ml'); ?></p>
            </td>
         </tr>
        </table> 
        
        <h3><?php _e("Restricted comments message", 'pc_ml'); ?></h3>
        <table class="widefat pc_table">
         <tr>
            <td class="pc_label_td"><?php _e("Custom message for unlogged users", 'pc_ml'); ?></td>
            <td class="pc_field_td">
               <input type="text" name="pg_default_hc_mex" value="<?php echo pc_sanitize_input($fdata['pg_default_hc_mex']); ?>" maxlength="255" autocomplete="off" /> 
               <p class="info"><?php _e('By default is "You must be logged in to post comments"', 'pc_ml'); ?></p>
            </td>
         </tr>
         <tr>
            <td class="pc_label_td"><?php _e("Custom message for wrong permission users", 'pc_ml'); ?></td>
            <td class="pc_field_td">
            	<input type="text" name="pg_default_hcwp_mex" value="<?php echo pc_sanitize_input($fdata['pg_default_hcwp_mex']); ?>" maxlength="170" autocomplete="off" />
              	<p class="info"><?php _e('By default is "Sorry, you don\'t have the right permissions to post comments"', 'pc_ml'); ?></p>
            </td>
         </tr>
        </table> 
        
        <h3><?php _e("Private Page Messages", 'pc_ml'); ?></h3>
        <table class="widefat pc_table">
         <tr>
            <td class="pc_label_td"><?php _e("Default message if a user not have the reserved area", 'pc_ml'); ?></td>
            <td class="pc_field_td">
            	<input type="text" name="pg_default_nhpa_mex" value="<?php echo pc_sanitize_input($fdata['pg_default_nhpa_mex']); ?>" maxlength="255" autocomplete="off" />
              	<p class="info"><?php _e('By default is "You don\'t have a reserved area"', 'pc_ml'); ?></p>
            </td>
         </tr>
        </table> 
    
		<h3><?php _e("Login Form Messages", 'pc_ml'); ?></h3>
        <table class="widefat pc_table">
         <tr>
            <td class="pc_label_td"><?php _e("Default message for successful login", 'pc_ml'); ?></td>
            <td class="pc_field_td">
            	<input type="text" name="pg_login_ok_mex" value="<?php echo pc_sanitize_input($fdata['pg_login_ok_mex']); ?>" maxlength="170" autocomplete="off" />
              	<p class="info"><?php _e('By default is "Logged successfully, welcome!"', 'pc_ml'); ?></p>
            </td>
         </tr>
         <tr>
            <td class="pc_label_td"><?php _e("Default message for pending users", 'pc_ml'); ?></td>
            <td class="pc_field_td">
            	<input type="text" name="pg_default_pu_mex" value="<?php echo pc_sanitize_input($fdata['pg_default_pu_mex']); ?>" maxlength="170" autocomplete="off" />
              	<p class="info"><?php _e('By default is "Sorry, your account has not been activated yet"', 'pc_ml'); ?></p>
            </td>
         </tr>
        </table>  
         
        <h3><?php _e("Registration Form Message" ); ?></h3>
        <table class="widefat pc_table">
          <tr>
            <td class="pc_label_td"><?php _e("Default message for succesfully registered users", 'pc_ml'); ?></td>
            <td class="pc_field_td">
               <input type="text" name="pg_default_sr_mex" value="<?php echo pc_sanitize_input($fdata['pg_default_sr_mex']); ?>" maxlength="170" autocomplete="off" /> 
               <p class="info"><?php _e('By default is "Registration was successful. Welcome!"', 'pc_ml'); ?></p>
            </td>
         </tr>
       </table> 
       
       <?php 
	   // PC-ACTION - insert custom messages customizer - has $fdata param - must print code
	   do_action('pc_settings_messages', $fdata); 
	   ?> 
    </div>
     
    <?php 
	// PC-ACTION - print custom tabs code - must be ok with tabs structure - has $fdata param
	do_action ('pc_settings_tabs_body', $fdata);
	?> 
     
    <input type="hidden" name="pc_nonce" value="<?php echo wp_create_nonce(__FILE__) ?>" /> 
    <input type="submit" name="pc_admin_submit" value="<?php _e('Update Options', 'pc_ml') ?>" class="button-primary" />  
    
   </form>
</div>  

<?php // SCRIPTS ?>
<script src="<?php echo PC_URL; ?>/js/lc-switch/lc_switch.min.js" type="text/javascript"></script>
<script src="<?php echo PC_URL; ?>/js/chosen/chosen.jquery.min.js" type="text/javascript"></script>
<script src="<?php echo PC_URL; ?>/js/colpick/js/colpick.min.js" type="text/javascript"></script>


<script type="text/javascript" charset="utf8">
jQuery(document).ready(function($) {
	var rf_is_acting = false; // registration form builder flag  
	var wps_is_acting = false; // WP user sync flag 
	var pc_nonce = '<?php echo wp_create_nonce('lcwp_ajax') ?>';
	
	// registration form builder - add form
	jQuery('body').delegate('#pc_reg_form_add', 'click', function() {
		var name = jQuery.trim( jQuery('#pc_new_reg_form_name').val());
		if(!name || rf_is_acting) {return false;}
		
		rf_is_acting = true;
		jQuery('#pc_reg_form_loader').html('<span class="pc_loading"></span>');
		
		var data = {
			action: 'pc_add_reg_form',
			form_name: name,
			pc_nonce: pc_nonce
		};
		jQuery.post(ajaxurl, data, function(response) {
			jQuery('#pc_reg_form_loader').empty();
			rf_is_acting = false;
			
			if(jQuery.isNumeric(response)) {
				jQuery('.pc_form_builder_dd option').removeAttr('selected');
				jQuery('.pc_form_builder_dd').append('<option value="'+ response +'" selected="selected">'+ response +' - '+ name +'</option>');
				
				jQuery('#pc_new_reg_form_name').val('');
				jQuery('.pc_form_builder_dd').trigger("chosen:updated").trigger('change');
			}
			else {
				alert(response);	
			}
		});
	});
	
	
	// registration form builder - load builder
	jQuery('body').delegate('.pc_form_builder_dd', 'change', function() {
		var val = jQuery(this).val();
		if(!val) {
			jQuery('#pc_reg_form_cmd').css('visibility', 'hidden');
			jQuery('#pc_reg_form_builder').empty();
			return false;
		}
		
		if(rf_is_acting) {return false;}
		rf_is_acting = true;
		jQuery('#pc_reg_form_loader').html('<span class="pc_loading"></span>');
		
		var data = {
			action: 'pc_reg_form_builder',
			form_id: val,
			pc_nonce: pc_nonce
		};
		jQuery.post(ajaxurl, data, function(response) {
			jQuery('#pc_reg_form_cmd').css('visibility', 'visible');
			jQuery('#pc_reg_form_builder').html(response);
			
			pc_live_checks();
			pc_live_chosen();
			
			/*** sort formbuilder rows ***/
			jQuery( "#pc_reg_form_builder tbody" ).sortable({ handle: '.pc_move_field' });
			jQuery( "#pc_reg_form_builder tbody td .pc_move_field" ).disableSelection();
			
			jQuery('#pc_reg_form_loader').empty();
			rf_is_acting = false;
		});
	});
	// on start - load first form
	if(jQuery('.pc_form_builder_dd option').size()) {
		jQuery('.pc_form_builder_dd').trigger('change');	
	}
	

	// add field to builder
	jQuery('body').delegate('#pc_rf_add_field', 'click', function() { 
		var f_val = jQuery('.pc_rf_fields_dd').val();
		var f_name = jQuery('.pc_rf_fields_dd option[value="'+ f_val +'"]').text();
		
		if(f_val != 'custom|||text' && jQuery('#pc_rf_builder_table tr[rel="'+ f_val +'"]').size()) {
			alert("<?php _e('Field already in the form', 'pc_ml') ?>");
			return false;	
		}
		
		var required = (f_val == 'categories') ? 'checked="checked"' : '';
		var disabled = (f_val == 'categories') ? 'disabled="disabled"' : ''; 
		
		if(f_val == 'custom|||text') {
			var code = 
			'<td colspan="2">'+
				'<input type="hidden" name="pc_reg_form_field[]" value="'+ f_val +'" class="pc_reg_form_builder_included" />'+
				'<textarea name="pc_reg_form_texts[]" placeholder="<?php _e('Supports HTML and shortcodes', 'pc_ml') ?>"></textarea>'+
			'</td>';
		}
		else {
			var code = 
			'<td>'+
				'<input type="hidden" name="pc_reg_form_field[]" value="'+ f_val +'" class="pc_reg_form_builder_included" />'+
				f_name +
			'</td>'+
			'<td>'+
				'<input type="checkbox" name="pc_reg_form_req[]" value="'+ f_val +'" '+required+' '+disabled+' class="ip_checks pc_reg_form_builder_required" autocomplete="off" />'+
			'</td>';	
		}
		
		jQuery('#pc_rf_builder_table tbody').append(
		'<tr rel="'+ f_val +'">'+
			'<td><span class="pc_del_field" title="<?php _e('remove field', 'pc_ml') ?>"></span></td>'+
			'<td><span class="pc_move_field" title="<?php _e('sort field', 'pc_ml') ?>"></span></td>'+
			code +
		'</tr>');
		
		pc_live_checks();
	});
	
	
	// delete form field
	jQuery('body').delegate('#pc_rf_builder_table .pc_del_field', 'click', function() { 
		if(!rf_is_acting) {
			jQuery(this).parents('tr').fadeOut(400 ,function() {
				jQuery(this).remove();
			});
		}
	});
	
	
	// update form structure 
	jQuery('body').delegate('#pc_reg_form_save', 'click', function() {
		if(rf_is_acting) {return false;}
		
		rf_is_acting = true;
		jQuery('#pc_reg_form_loader').html('<span class="pc_loading"></span>');
		
		var form_id = jQuery('.pc_form_builder_dd').val();
		var form_name = jQuery('#pc_rf_name').val();

		// create fields + required array
		var included = jQuery.makeArray();
		var required = jQuery.makeArray();
		var texts 	= jQuery.makeArray();
		
		jQuery('#pc_rf_builder_table tbody tr').each(function(i,v) {
        	var f = jQuery(this).find('.pc_reg_form_builder_included').val();
		    included.push(f);
			
			if(f == 'custom|||text') {
				texts.push( jQuery(this).find('textarea').val() );	
			}
			else {
				if( jQuery(this).find('.pc_reg_form_builder_required').is(':checked') ) {
					required.push(f);	
				}
			}
        });
		
		var data = {
			action: 'pc_update_reg_form',
			form_id: form_id,
			form_name: form_name, 
			fields_included: included,
			fields_required: required,
			texts: texts,
			pc_nonce: pc_nonce
		};
		jQuery.post(ajaxurl, data, function(response) {
			jQuery('#pc_reg_form_loader').empty();
			rf_is_acting = false;
			
			if(jQuery.trim(response) == 'success') {
				jQuery('.pc_form_builder_dd option[value='+ form_id +']').html( form_id+' - '+form_name );
				jQuery('.pc_form_builder_dd').trigger("chosen:updated");	
				
				jQuery('#pc_reg_form_save').css('background-color', '#3C7336').css('color', '#fff');
				setTimeout(function(){
					jQuery('#pc_reg_form_save').css('background-color', '').css('color', '');
				}, 500);
			}
			else {alert(response);}
		});	
	});
	
	
	// delete form - leaving one
	jQuery('body').delegate('#pc_reg_form_del', 'click', function() {
		if(jQuery('.pc_form_builder_dd option').size() == 1) {
			alert("<?php _e('At least one form is required', 'pc_ml') ?>");
			return false;	
		}
		
		var form_id = jQuery('.pc_form_builder_dd').val();
		if(!form_id) {return false;}
		
		if(confirm("<?php _e('Delete this form? Related shortcodes will show the first one', 'pc_ml') ?>")) {
			rf_is_acting = true;
			jQuery('#pc_reg_form_loader').html('<span class="pc_loading"></span>');

			var data = {
				action: 'pc_del_reg_form',
				form_id: form_id,
				pc_nonce: pc_nonce
			};
			jQuery.post(ajaxurl, data, function(response) {
				jQuery('#pc_reg_form_loader').empty();
				rf_is_acting = false;
				
				if(jQuery.trim(response) == 'success') {
					jQuery('.pc_form_builder_dd option[value='+ form_id +']').remove();
					jQuery('.pc_form_builder_dd option').first().attr('selected', 'selected');
					jQuery('.pc_form_builder_dd').trigger("chosen:updated").trigger('change');	
					
					jQuery('#pc_reg_form_del').css('background-color', '#BB7071').css('color', '#fff');
					setTimeout(function(){
						jQuery('#pc_reg_form_del').css('background-color', '').css('color', '');
					}, 500);
				}
				else {alert(response);}
			});	
		}
	});
	
	
	///////////////////////////////////////////////////
	
	// sync WP users sync
	jQuery('body').delegate('#pc_do_wp_sync', 'click', function() {
		if(!wps_is_acting && confirm("<?php _e('Mirror wordpress users will be created. Continue?', 'pc_ml') ?>")) {
			
			wps_is_acting = true;
			var $result_wrap = jQuery(this).next('span');
			$result_wrap.html('<div class="pc_loading" style="margin-bottom: -7px;"></div>');
			
			var data = {
				action: 'pc_wp_global_sync',
				pc_nonce: pc_nonce
			};
			jQuery.post(ajaxurl, data, function(response) {
				$result_wrap.html(response);
				wps_is_acting = false;
			});
		}
	});
	
	// clean WP users sync
	jQuery('body').delegate('#pc_clean_wp_sync', 'click', function() {
		if(!wps_is_acting && confirm("<?php _e('WARNING: this will delete connected wordpress users and any related content will be lost. Continue?', 'pc_ml') ?>")) {
			
			wps_is_acting = true;
			var $result_wrap = jQuery(this).next('span');
			$result_wrap.html('<div class="pc_loading" style="margin-bottom: -7px;"></div>');
			
			var data = {
				action: 'pc_wp_global_detach',
				pc_nonce: pc_nonce
			};
			jQuery.post(ajaxurl, data, function(response) {
				$result_wrap.html(response);
				wps_is_acting = false;
			});
		}
	});
	
	// search existing matches and sync
	jQuery('body').delegate('#pc_wps_matches_sync', 'click', function() {
		if(!wps_is_acting && confirm("<?php _e('WARNING: this will turn matched WP userse into PrivateContent mirrors. Continue?', 'pc_ml') ?>")) {
			
			wps_is_acting = true;
			var $result_wrap = jQuery(this).next('span');
			$result_wrap.html('<div class="pc_loading" style="margin-bottom: -7px;"></div>');
			
			var data = {
				action: 'pc_wps_search_and_sync_matches',
				pc_nonce: pc_nonce
			};
			jQuery.post(ajaxurl, data, function(response) {
				$result_wrap.html(response);
				wps_is_acting = false;
			});
		}
	});
	
	//////////////////////////////////////
	
	
	//// redirects toggle
	// redirect target
	jQuery('body').delegate('#pc_redirect_page', 'change', function(){
		var red_val = jQuery(this).val();
		
		if(red_val == 'custom') {jQuery('#pc_redirect_page_cst_wrap td').fadeIn();}
		else {jQuery('#pc_redirect_page_cst_wrap td').fadeOut();}
	});
	
	// login redirect 
	jQuery('body').delegate('#pc_logged_user_redirect', 'change', function(){
		var red_val = jQuery(this).val();
		
		if(red_val == 'custom') {jQuery('#pc_logged_user_redirect_cst_wrap td').fadeIn();}
		else {jQuery('#pc_logged_user_redirect_cst_wrap td').fadeOut();}
	});
	
	// logout redirect 
	jQuery('body').delegate('#pc_logout_user_redirect', 'change', function(){
		var red_val = jQuery(this).val();
		
		if(red_val == 'custom') {jQuery('#pc_logout_user_redirect_cst_wrap td').fadeIn();}
		else {jQuery('#pc_logout_user_redirect_cst_wrap td').fadeOut();}
	});
	///////////////////////////////
	
	// sliders
	pc_slider_opt = function() {
		var a = 0; 
		$('.lcwp_slider').each(function(idx, elm) {
			var sid = 'slider'+a;
			jQuery(this).attr('id', sid);	
		
			svalue = parseInt(jQuery("#"+sid).next('input').val());
			minv = parseInt(jQuery("#"+sid).attr('min'));
			maxv = parseInt(jQuery("#"+sid).attr('max'));
			stepv = parseInt(jQuery("#"+sid).attr('step'));
			
			jQuery('#' + sid).slider({
				range: "min",
				value: svalue,
				min: minv,
				max: maxv,
				step: stepv,
				slide: function(event, ui) {
					jQuery('#' + sid).next().val(ui.value);
				}
			});
			jQuery('#'+sid).next('input').change(function() {
				var val = parseInt(jQuery(this).val());
				var minv = parseInt(jQuery("#"+sid).attr('min'));
				var maxv = parseInt(jQuery("#"+sid).attr('max'));
				
				if(val <= maxv && val >= minv) {
					jQuery('#'+sid).slider('option', 'value', val);
				}
				else {
					if(val <= maxv) {jQuery('#'+sid).next('input').val(minv);}
					else {jQuery('#'+sid).next('input').val(maxv);}
				}
			});
			
			a = a + 1;
		});
	}
	pc_slider_opt();
	
	
	// colorpicker
	pc_colpick = function () {
		jQuery('.lcwp_colpick input').each(function() {
			var curr_col = jQuery(this).val().replace('#', '');
			jQuery(this).colpick({
				layout:'rgbhex',
				submit:0,
				color: curr_col,
				onChange:function(hsb,hex,rgb, el, fromSetColor) {
					if(!fromSetColor){ 
						jQuery(el).val('#' + hex);
						jQuery(el).parents('.lcwp_colpick').find('.lcwp_colblock').css('background-color','#'+hex);
					}
				}
			}).keyup(function(){
				jQuery(this).colpickSetColor(this.value);
				jQuery(this).parents('.lcwp_colpick').find('.lcwp_colblock').css('background-color', this.value);
			});  
		});
	}
	pc_colpick();
	
	
	// lc switch
	var pc_live_checks = function() { 
		jQuery('.ip_checks').lc_switch('YES', 'NO');
	}
	pc_live_checks();
	
	// chosen
	var pc_live_chosen = function() { 
		jQuery('.lcweb-chosen').each(function() {
			var w = jQuery(this).css('width');
			jQuery(this).chosen({width: w}); 
		});
		jQuery(".lcweb-chosen-deselect").chosen({allow_single_deselect:true});
	}
	pc_live_chosen();
	
	// tabs
	jQuery("#tabs").tabs();
});
</script>
