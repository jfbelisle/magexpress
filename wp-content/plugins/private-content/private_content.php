<?php
/* 
Plugin Name: PrivateContent
Plugin URI: http://codecanyon.net/item/privatecontent-multilevel-content-plugin/1467885?ref=LCweb
Description: Create unlimited lists of users and chose which of them can view page or post contents or entire areas of your website. Plus, each user have a private page.
Author: Luca Montanari
Version: 5.04
Author URI: http://codecanyon.net/user/LCweb?ref=LCweb
*/  


/////////////////////////////////////////////
/////// MAIN DEFINES ////////////////////////
/////////////////////////////////////////////

// plugin path
$wp_plugin_dir = substr(plugin_dir_path(__FILE__), 0, -1);
define('PC_DIR', $wp_plugin_dir);

// plugin url
$wp_plugin_url = substr(plugin_dir_url(__FILE__), 0, -1);
define('PC_URL', $wp_plugin_url);


/////////////////////////////////////////////
/////// MULTILANGUAGE SUPPORT ///////////////
/////////////////////////////////////////////

function pc_multilanguage() {
  $param_array = explode(DIRECTORY_SEPARATOR, PC_DIR);
  $folder_name = end($param_array);
  
  if(is_admin()) {
	 load_plugin_textdomain('pc_ml', false, $folder_name . '/lang_admin');  
  }
  load_plugin_textdomain('pc_ml', false, $folder_name . '/languages');  
}
add_action('init', 'pc_multilanguage', 1);



/////////////////////////////////////////////
/////// DATABASE MANAGEMENT /////////////////
/////////////////////////////////////////////

// database table constants
function pc_db_constants() {
	global $wpdb;
	define('PC_USERS_TABLE', $wpdb->prefix . "pc_users");
	define('PC_META_TABLE', $wpdb->prefix . "pc_user_meta");
}
add_action('init', 'pc_db_constants', 1);

function pc_db_manag() {
	include_once(PC_DIR . '/db_manag.php');
}
register_activation_hook(__FILE__, 'pc_db_manag');

// do also on specific recall
if(isset($_GET['pc_update_db_v5'])) {pc_db_manag();	}



/////////////////////////////////////////////
/////// WP USERS SYNC INITIALIZATION ////////
/////////////////////////////////////////////

function pc_wp_user_sync_init() {
	global $pc_users;
	
	if($pc_users->wp_user_sync) {
		include_once(PC_DIR . '/wp_user_tricks.php');
		
		add_role('pvtcontent', 'PrivateContent',
			array(
				'read'         => false,
				'edit_posts'   => false,
				'delete_posts' => false
			)
		);
	} else {
		remove_role('pvtcontent');
	}
}
add_action('init', 'pc_wp_user_sync_init', 1);



/////////////////////////////////////////////
// REGISTER TAXONOMY FOR REGISTATION FORMS //
/////////////////////////////////////////////

function pc_reg_form_ct() {
    $labels = array( 
        'name' => 'PrivateContent registration forms',
        'singular_name' => 'PrivateContent registration form',
        'search_items' => 'Search PrivateContent registration forms',
        'popular_items' => 'Popular PrivateContent registration forms',
        'all_items' => 'All PrivateContent registration forms',
        'parent_item' => 'Parent PrivateContent registration form',
        'parent_item_colon' => 'Parent PrivateContent registration form:',
        'edit_item' => 'Edit PrivateContent registration form',
        'update_item' => 'Update PrivateContent registration form',
        'add_new_item' => 'Add New PrivateContent registration form',
        'new_item_name' => 'New PrivateContent registration form',
        'separate_items_with_commas' => 'Separate privatecontent registration forms with commas',
        'add_or_remove_items' => 'Add or remove PrivateContent registration forms',
        'choose_from_most_used' => 'Choose from most used PrivateContent registration forms',
        'menu_name' => 'PrivateContent registration forms',
    );

    $args = array( 
        'labels' => $labels,
        'public' => false,
        'show_in_nav_menus' => false,
        'show_ui' => false,
        'show_tagcloud' => false,
        'show_admin_column' => false,
        'hierarchical' => false,
        'rewrite' => false,
        'query_var' => false
    );
    register_taxonomy('pc_reg_form', null, $args);
}
add_action('init', 'pc_reg_form_ct', 1);



///////////////////////////////////////////////////////////////
/////// FLAG FOR LOGGED WP USER - IF CAN BYPASS RESTRICTIONS //
///////////////////////////////////////////////////////////////

function pc_testing_mode_flag() {
	if(!is_admin() && !defined('PC_WP_USER_PASS')) {
		$pc_min_role = get_option('pg_min_role', 'upload_files');	
		$testing_mode = get_option('pg_test_mode');
		$GLOBALS['pc_testing_mode'] = $testing_mode;
		
		if($testing_mode) {
			$val = (!is_user_logged_in() || !current_user_can($pc_min_role)) ? true : false;
		} else {
			$val = (!is_user_logged_in() || !current_user_can($pc_min_role)) ? false : true;	
		}

		define('PC_WP_USER_PASS', $val);
	}
}
add_action('init', 'pc_testing_mode_flag', 1);



/////////////////////////////////////////////
/////// MAIN SCRIPT & CSS INCLUDES //////////
/////////////////////////////////////////////

// global script enqueuing
function pc_global_scripts() { 
	wp_enqueue_script("jquery"); 
	
	// admin css
	if(is_admin()) {  
		wp_enqueue_style('pc_admin', PC_URL . '/css/admin.css', 999, '5.04');	
		
		// add tabs scripts
		wp_enqueue_style( 'pg-ui-theme', PC_URL.'/css/ui-wp-theme/jquery-ui-1.8.17.custom.css', 999);
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-tabs');
		wp_enqueue_script('jquery-ui-sortable');
		wp_enqueue_script('jquery-ui-slider');
		
		// lc switch
		wp_enqueue_style( 'lcweb-switch', PC_URL.'/js/lc-switch/lc_switch.css', 999);
		
		// iphone checks
		wp_enqueue_style( 'lcwp-ip-checks', PC_URL.'/js/iphone_checkbox/style.css', 999);  // TO REMOVE IN NEXT RELEASE

		// colorpicker
		wp_enqueue_style( 'lcwp-colpick', PC_URL.'/js/colpick/css/colpick.css', 999);

		// chosen
		wp_enqueue_style( 'lcwp-chosen-style', PC_URL.'/js/chosen/chosen.css', 999);
	}
	
	// login, registering and logout JS file
	if(!is_admin()) {
		wp_enqueue_script('pc_frontend', PC_URL . '/js/frontend.js', 999, '5.04');	
	}
	
	// if allow multiple select during registration
	if(!is_admin()) {
		wp_enqueue_style( 'pc_multiselect', PC_URL.'/js/multiple-select/multiple-select.css', 1);
		wp_enqueue_script('pc_multiselect', PC_URL . '/js/multiple-select/multiple.select.min.js', 999, '', true);	
		add_action('wp_head', 'pc_multiselct_localize_var');
	}
	
	// custom frontend style - only if is not disabled by settings
	if(!is_admin() && !get_option('pc_disable_front_css')) {  
		$style = get_option('pg_style', 'minimal');
		
		if((!get_option('pg_inline_css') && !get_option('pg_force_inline_css')) || $style != 'custom') {
			wp_enqueue_style('pc_frontend', PC_URL . '/css/'.$style.'.css', 999, '5.04');		
		}
		else {add_action('wp_head', 'pc_inline_css', 989);}
	}
}
add_action( 'init', 'pc_global_scripts');

// multi-select variable to translate
function pc_multiselct_localize_var() {
	echo '<script type="text/javascript">pc_ms_countSelected = "'. __('# of % selected', 'pc_ml') .'"; pc_ms_allSelected = "'. __('All selected', 'pc_ml') .'";</script>';	
}

// use custom style inline
function pc_inline_css(){
	echo '<style type="text/css">';
	include_once(PC_DIR.'/custom_style.php');
	echo '</style>';
}

// custom css
function pc_custom_css(){
	$code = trim(get_option('pg_custom_css', ''));
	
	if($code) {
		echo '
<!-- privateContent custom CSS -->
<style type="text/css">'. $code .'</style>
';
	}
}
add_action('wp_head', 'pc_custom_css', 999);




//////////////////////////////////////////////////
/////////// ADMIN AREA ///////////////////////////
//////////////////////////////////////////////////

// USERS MANAGEMENT CLASS
include_once(PC_DIR . '/classes/users_manag.php');

// WP-SYNC MANAGEMENT CLASS
include_once(PC_DIR . '/classes/wp_user_sync.php');

// META MANAGEMENT CLASS
include_once(PC_DIR . '/classes/meta_manag.php');

// MENU AND TOPBAR PENDING USERS 
include_once(PC_DIR . '/admin_menu.php');

// USER CAT - CUSTOM FIELDS
include_once(PC_DIR . '/user_categories.php');

// PUBLIC API
include_once(PC_DIR . '/public_api.php');

// GLOBAL AJAX
include_once(PC_DIR . '/admin_ajax.php');

// USER POST TYPE - PVT PAGE
include_once(PC_DIR . '/users_page_post_type.php');

// METABOX 
include_once(PC_DIR . '/metaboxes.php');

// TAXONOMIES OPTION
include_once(PC_DIR . '/pc_taxonomies_option.php');

// NAV MENU OPTION
include_once(PC_DIR . '/nav_menu_option.php');

// TINYMCE BUTTON
include_once(PC_DIR . '/tinymce_implementation.php');

// SHORTCODES
include_once(PC_DIR . '/shortcodes.php');

// USER AUTH SYSTEM - FRONT AJAX
include_once(PC_DIR . '/user_auth.php');

// USER REGISTRATION SYSTEM
include_once(PC_DIR . '/user_registration.php');

// MANAGE PRIVATE CONTENT
include_once(PC_DIR . '/pvt_content_manage.php');

// LOGIN WIDGET
include_once(PC_DIR . '/login_widget.php');

// WIDGET RESTRICTION
include_once(PC_DIR . '/widgets_restriction.php');


////////////
// UPDATE NOTIFIER
if(!class_exists('lc_update_notifier')) {
	include_once(PC_DIR . '/lc_update_notifier.php');
}
$lcun = new lc_update_notifier(__FILE__, 'http://www.lcweb.it/envato_update/pg.php');
////////////


//////////////////////////////////////////////////
// ACTIONS ON PLUGIN ACTIVATION //////////////////
//////////////////////////////////////////////////

function pc_on_activation() {
	include_once(PC_DIR . '/functions.php');
	
	// check mcrypt_encrypt functions existence
	if(!function_exists('mcrypt_encrypt')) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die("PrivateContent cannot be enabled because of your PHP version doesn't support Mcrypt functions.<br/>
		Please enable it or ask to your hoster. Thanks");	
	}
	
	// create custom form style
	if(get_option('pg_style') == 'custom') {
		if(!pc_create_custom_style()) {update_option('pg_inline_css', 1);}
		else {delete_option('pg_inline_css');}
	}

	// minimum role to use plugin
	if(!get_option('pg_min_role')) { update_option('pg_min_role', 'upload_files');}
}
register_activation_hook(__FILE__, 'pc_on_activation');



//////////////////////////////////////////////////
// export users security trick - avoid issues related to php warnings
function pc_export_buffer() {
	ob_start();
}
add_action('admin_init', 'pc_export_buffer', 2);



//////////////////////////////////////////////////
// REMOVE WP HELPER FROM PLUGIN PAGES

function pc_remove_wp_helper() {
	$cs = get_current_screen();
	$hooked = array('toplevel_page_pc_user_manage', 'privatecontent_page_pc_add_user', 'privatecontent_page_pc_import_export', 'privatecontent_page_pc_settings');
	
	if(in_array($cs->base, $hooked)) {
		echo '
		<style type="text/css">
		#screen-meta-links {display: none;}
		</style>';	
	}
	
	//var_dump(get_current_screen()); // debug
}
add_action('admin_head', 'pc_remove_wp_helper', 999);



/////////////////////////////////////////////
// RETROCOMPATIBILITY FOR ADD-ONS BEFORE v5 /
/////////////////////////////////////////////

$retro_pc_dir = PC_DIR; define('PG_DIR', $retro_pc_dir);
$retro_pc_url = PC_URL; define('PG_URL', $retro_pc_url);

//////////////////////////////////////////////////////
