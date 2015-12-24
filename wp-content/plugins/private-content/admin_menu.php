<?php
// setting up USER MANAGEMENT admin menu
function pc_users_admin_menu() {	
	$menu_img = PC_URL.'/img/users_icon.png'; 
	
	$pc_min_role = get_option('pg_min_role', 'upload_files'); // minimum role to use plugin
	$min_role_tmu = get_option('pg_min_role_tmu', $pc_min_role); // minimum role to manage users
	
	add_menu_page('PrivateContent', 'PrivateContent', $pc_min_role, 'pc_user_manage', 'pc_users_overview', $menu_img, 46);
	
	// submenus
	add_submenu_page('pc_user_manage', __('Users List', 'pc_ml'), __('Users List', 'pc_ml'), $pc_min_role, 'pc_user_manage', 'pc_users_overview');
	add_submenu_page('pc_user_manage', __('Add User', 'pc_ml'), __('Add User', 'pc_ml'), $pc_min_role, 'pc_add_user', 'pc_add_user');	
	add_submenu_page('pc_user_manage', __('User Categories', 'pc_ml'), __('User Categories', 'pc_ml'), $min_role_tmu, 'edit-tags.php?taxonomy=pg_user_categories');
	add_submenu_page('pc_user_manage', __('Import & Export Users', 'pc_ml'), __('Import & Export Users', 'pc_ml'), $pc_min_role, 'pc_import_export', 'pc_import_export');
}
add_action('admin_menu', 'pc_users_admin_menu');

// settings item placed at the end
function pc_settings_menu_item() {	
	add_submenu_page('pc_user_manage', __('Settings', 'pc_ml'), __('Settings', 'pc_ml'), 'install_plugins', 'pc_settings', 'pc_settings');
}
add_action('admin_menu', 'pc_settings_menu_item', 999);


// fix to set the taxonomy and user pages as menu page sublevel
function user_cat_tax_menu_correction($parent_file) {
	global $current_screen;

	// hack for taxonomy
	if(isset($current_screen->taxonomy)) {
		$taxonomy = 'pg_user_categories';
		if($taxonomy == $current_screen->taxonomy) {
			$parent_file = 'pc_user_manage';
		}	
	}
	
	// hack for user pages
	if(isset($current_screen->base)) {
		$page_type = 'pg_user_page';
		if($current_screen->base == 'post' && $current_screen->id == $page_type) {
			$parent_file = 'pc_user_manage';
		}
	}
	
	return $parent_file;
}
add_action('parent_file', 'user_cat_tax_menu_correction');



////////////////////////////////////////////
// USER MANAGEMENT PAGES ///////////////////
////////////////////////////////////////////

// users list
function pc_users_overview() { include_once(PC_DIR . '/users/users_list.php'); }

// add user
function pc_add_user() {include_once(PC_DIR . '/users/add_user.php'); }

// import and export users
function pc_import_export() { include_once(PC_DIR . '/users/import_export.php'); }

// settings
function pc_settings() {  include_once(PC_DIR.'/settings.php'); }  



////////////////////////////////////////////////////////////////


//if there are pending users, show them on the WP dashboard
function pc_pending_users_warning() {	
	global $total_pen_rows, $wpdb;

	// pending users only if they exists
	$wpdb->query("SELECT ID FROM ".PC_USERS_TABLE." WHERE status = 3");
	$total_pen_rows = $wpdb->num_rows;
	
	if($total_pen_rows > 0) {
		// add submenu
		add_action('admin_menu', 'pc_pending_menu_warn', 1000);
	
		// add wp admin bar alert
		add_action('admin_bar_menu', 'pc_pending_bar_warn', 500);  
	}	
}
add_action('init', 'pc_pending_users_warning', 800);


// PC menu item
function pc_pending_menu_warn() {
	global $total_pen_rows;
	$au_cap = get_option('pg_min_role', 'upload_files'); // restrict to users allowed to manage customers
	
	add_submenu_page('pc_user_manage', __('Pending Users', 'pc_ml') .' ('.$total_pen_rows.')', __('Pending Users', 'pc_ml') .' ('.$total_pen_rows.')', $au_cap, 'admin.php?page=pc_user_manage&status=pending');	
}

// admin bar notice
function pc_pending_bar_warn() {
	global $wp_admin_bar, $total_pen_rows;
	
	// restrict to users allowed to manage customers
	$au_cap = get_option('pg_min_role', 'upload_files');
	if(current_user_can($au_cap)) {
	
		if(is_admin_bar_showing() && is_object($wp_admin_bar)) {
			$wp_admin_bar->add_menu( array( 
				'id' => 'pc_pending_users', 
				'title' => '<span>PrivateContent <span id="ab-updates">'.$total_pen_rows.' '. __('Pending Users', 'pc_ml') .'</span></span>', 
				'href' => get_admin_url() . 'admin.php?page=pc_user_manage&status=pending' 
			) );
		}
	}
}
