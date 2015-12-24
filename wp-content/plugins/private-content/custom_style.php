<?php
////////////////////////////////////
// DYNAMICALLY CREATE THE CSS //////
////////////////////////////////////
include_once(PC_DIR . '/functions.php');

// remove the HTTP/HTTPS for SSL compatibility
$safe_baseurl = str_replace(array('http:', 'https:', 'HTTP:', 'HTTPS:'), '', PC_URL);
?>
/*BASIC STYLES */
@import url("<?php echo $safe_baseurl; ?>/css/frontend.css");


/***********************************
    GLOBAL ELEMENTS
 ***********************************/
  
/* containers style */
.pc_registration_form,
.pc_login_form,
.pc_custom_form {
	background-color: <?php echo get_option('pg_forms_bg_col', '#fefefe') ?>;
    border: 1px solid <?php echo get_option('pg_forms_border_col', '#ebebeb') ?>;
    border-radius: <?php echo get_option('pg_form_border_radius', 3) ?>px;
	color: <?php echo get_option('pg_label_col', '#333') ?>;	
}


/* fields style */
.pc_rf_field input, .pc_rf_field select, .pc_rf_field textarea,
.pc_multiselect .ms-choice, .pc_multiselect .ms-drop,
.pc_login_row input, .pcma_psw_username,
.pc_rf_recaptcha #recaptcha_response_field {
	background: <?php echo get_option('pg_fields_bg_col', '#fefefe') ?>;
    border: <?php echo get_option('pg_field_border_w', 1) ?>px solid <?php echo get_option('pg_fields_border_col', '#ccc') ?>;
    color: <?php echo get_option('pg_fields_txt_col', '#808080') ?>;	
	padding: <?php echo get_option('pg_field_padding', 3) ?>px !important;
	border-radius: <?php echo get_option('pg_field_border_radius', 1) ?>px !important;
}
.pc_rf_field input:hover, .pc_rf_field select:hover, .pc_rf_field textarea:hover,
.pc_rf_field input:active, .pc_rf_field select:active, .pc_rf_field textarea:active,
.pc_rf_field input:focus, .pc_rf_field select:focus, .pc_rf_field textarea:focus,
.pc_multiselect .ms-choice:hover, .pc_multiselect .ms-choice:active, .pc_multiselect .ms-choice:focus,
.pc_login_row input:hover, .pcma_psw_username:hover,
.pc_login_row input:active, .pcma_psw_username:active,
.pc_login_row input:focus, .pcma_psw_username:focus,
.pc_rf_recaptcha #recaptcha_response_field:focus {
	background: <?php echo get_option('pg_fields_bg_col_h', '#fff') ?>;
    border: <?php echo get_option('pg_field_border_w', 1) ?>px solid <?php echo get_option('pg_fields_border_col_h', '#aaa') ?>;
    color: <?php echo get_option('pg_fields_txt_col_h', '#444') ?>;	
	box-shadow: none;	
}
.pc_login_form label, .pc_form_flist, .pc_form_flist label {
	color: <?php echo get_option('pg_label_col', '#333') ?>;
}

/* multiselect fix */
.pc_multiselect .ms-drop {
	padding: 0 !important;	
}


/* typography */
.pc_login_row label {
	font-size: <?php echo get_option('pg_lf_font_size', 15); ?>px;
}
.pc_form_flist > li > label,
.pc_disclaimer_txt {
	font-size: <?php echo get_option('pg_rf_font_size', 15); ?>px;
}
<?php if(get_option('pg_forms_font_family')) : ?>
.pc_login_row label, .pc_rf_field > label,
.pc_form_txt_block, .pc_disclaimer_txt,
.pc_auth_btn, .pc_reg_btn, .pc_logout_btn {
	font-family: "<?php get_option('pg_forms_font_family') ?>";
}
<?php endif; ?>


/* submit buttons */
.pc_login_form input[type="button"], 
.pc_registration_form input[type="button"],
.pc_custom_form input[type="button"],
.pc_logout_btn {
	background: <?php echo get_option('pg_btn_bg_col', '#f4f4f4') ?> !important;
	border: 1px solid <?php echo get_option('pg_btn_border_col', '#cccccc') ?> !important;
	border-radius: <?php echo get_option('pg_btn_border_radius', 1) ?>px !important;
	box-shadow: none;
	color: <?php echo get_option('pg_btn_txt_col', '#444444') ?> !important;	
}
.pc_login_form input[type="button"]:hover, .pc_login_form input[type="button"]:active, .pc_login_form input[type="button"]:focus,  
.pc_registration_form input[type="button"]:hover, .pc_registration_form input[type="button"]:active, .pc_registration_form input[type="button"]:focus,
.pc_custom_form input[type="button"]:hover, .pc_custom_form input[type="button"]:active, .pc_custom_form input[type="button"]:focus,
.pc_logout_btn:hover, .pc_logout_btn:active, .pc_logout_btn:focus,
.pc_loading_btn:hover, .pc_loading_btn:active, .pc_loading_btn:focus {
	background: <?php echo get_option('pg_btn_bg_col_h', '#efefef') ?> !important;
	border-color: <?php echo get_option('pg_btn_border_col_h', '#cacaca') ?> !important;
	color: <?php echo get_option('pg_btn_txt_col_h', '#222222') ?> !important;
}

.pc_rf_disclaimer_sep {
	border-bottom: 1px solid <?php echo get_option('pg_forms_border_col', '#ebebeb') ?>;	
}


/* recaptcha */
.pc_rf_recaptcha #recaptcha_table {
	border-color: <?php echo get_option('pg_fields_border_col', '#ccc') ?> !important;
}	
.pc_rf_recaptcha #recaptcha_response_field {
	background: <?php echo get_option('pg_fields_bg_col', '#fefefe') ?> !important;
	border-color: <?php echo get_option('pg_fields_border_col', '#ccc') ?> !important;	
	color: <?php echo get_option('pg_fields_txt_col', '#ccc') ?> !important;
}
.pc_rf_recaptcha #recaptcha_response_field:focus {
	border-color: <?php echo get_option('pg_fields_border_col_h', '#aaa') ?> !important;	
	color: <?php echo get_option('pg_fields_txt_col_h', '#fff') ?> !important;
    background: <?php echo get_option('pg_fields_bg_col_h', '#fff') ?> !important;
}
.pc_rf_recaptcha #recaptcha_reload_btn {
	background: url('<?php echo $safe_baseurl; ?>/img/recaptcha_icons/refresh_<?php echo get_option('pg_recaptcha_col', 'l') ?>.png') no-repeat center center transparent !important;
}
.pc_rf_recaptcha #recaptcha_switch_audio_btn {
	background: url('<?php echo $safe_baseurl; ?>/img/recaptcha_icons/sound_<?php echo get_option('pg_recaptcha_col', 'l') ?>.png') no-repeat center center transparent !important;
}
.pc_rf_recaptcha #recaptcha_switch_img_btn {
	background: url('<?php echo $safe_baseurl; ?>/img/recaptcha_icons/text_<?php echo get_option('pg_recaptcha_col', 'l') ?>.png') no-repeat center center transparent !important;
}
.pc_rf_recaptcha #recaptcha_whatsthis_btn {
	background: url('<?php echo $safe_baseurl; ?>/img/recaptcha_icons/question_<?php echo get_option('pg_recaptcha_col', 'l') ?>.png') no-repeat center center transparent !important;
}


  
/*********************************
   STANDARD LOGIN FORM ELEMENTS
 ********************************/
  
/* container message */
.pc_login_block p {
    border-radius: <?php echo get_option('pg_field_border_radius', 1) ?>px;
}
 

/*****************************
   SUCCESS AND ERROR MESSAGES
 *****************************/
 
/* standard form messages / widget form messages  */
.pc_error_mess,
.pc_success_mess,
.widget .pc_error_mess,
.widget .pc_success_mess {
    border-radius: <?php echo get_option('pg_field_border_radius', 1) ?>px;
}


/* login form smalls */
.pc_login_smalls small {
	color: <?php echo get_option('pg_label_col', '#333') ?>;	
    opacity: 0.8;
    filter: alpha(opacity=70);
}

/* show and hide recovery form trigger */
.pc_rm_login .pcma_psw_recovery_trigger {
	border-left-color: <?php echo get_option('pg_forms_border_col', '#ebebeb') ?>;	
}


<?php 
// PC-ACTION - print code into custom style CSS
do_action('pc_custom_style_css'); 
?>