<?php
/* 
Plugin Name: PrivateContent - User Data Add-on 
Plugin URI: http://codecanyon.net/item/privatecontent-user-data-addon/2399731?ref=LCweb
Description: Manage PrivateContent user data by creating new fields to add to the registration form or just create new forms and use them in your website. Finally, export completely the user data. 
Author: Luca Montanari
Version: 2.05
Author URI: http://codecanyon.net/user/LCweb?ref=LCweb
*/  


/////////////////////////////////////////////
/////// MAIN DEFINES ////////////////////////
/////////////////////////////////////////////

// plugin path
$wp_plugin_dir = substr(plugin_dir_path(__FILE__), 0, -1);
define( 'PCUD_DIR', $wp_plugin_dir );

// plugin url
$wp_plugin_url = substr(plugin_dir_url(__FILE__), 0, -1);
define( 'PCUD_URL', $wp_plugin_url );



///////////////////////////////////////////////
/////// CHECK IF PRIVATECONTENT IS ACTIVE /////
///////////////////////////////////////////////

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if(!is_plugin_active('private-content/private_content.php') ) {
	function pcud_no_plugin_warning() {
		echo '
		<div class="error">
		   <p>'. __('Please activate PrivateContent plugin to use the "User Data" add-on', 'pcud_ml') .'</p>
		</div>';
	}
	add_action('admin_notices', 'pcud_no_plugin_warning');
}


else {
	/////////////////////////////////////////////
	/////// MULTILANGUAGE SUPPORT ///////////////
	/////////////////////////////////////////////
	
	function pcud_multilanguage() {
	  $param_array = explode('/', PCUD_DIR);
	  $folder_name = end($param_array);
	  
	  if(is_admin()) {
		 load_plugin_textdomain( 'pcud_ml', false, $folder_name . '/languages');  
	  }
	}
	add_action('init', 'pcud_multilanguage', 1);
	
	
	////////////////////////////////////////////////
	// ADD THE ADD-ON ITEMS TO THE PVT CONTENT MENU
	function pcud_add_submenus() { 
		add_submenu_page('pc_user_manage', __('Custom Fields Builder', 'pcud_ml'), __('Custom Fields Builder', 'pcud_ml'), 'install_plugins', 'pcud_fields_builder', 'pcud_cust_fields_builder');	
		add_submenu_page('pc_user_manage', __('Custom Forms Builder', 'pcud_ml'), __('Custom Forms Builder', 'pcud_ml'), 'install_plugins', 'pcud_forms_builder', 'pcud_cust_forms_builder');
	}
	add_action('admin_menu', 'pcud_add_submenus', 999); 
	
	
	function pcud_cust_fields_builder() { include(PCUD_DIR . '/cust_fields_builder.php'); }
	function pcud_cust_forms_builder() { include(PCUD_DIR . '/cust_forms_builder.php');	}
	
	
	//////////////////////
	// CUSTOM FORMS TAXONOMY
	function register_taxonomy_pcud_forms() {
		$labels = array( 
			'name' => _x('Forms', 'pcud_forms' ),
			'singular_name' => _x( 'Form', 'pcud_forms' ),
			'search_items' => _x( 'Search Forms', 'pcud_forms' ),
			'popular_items' => _x( 'Popular Forms', 'pcud_forms' ),
			'all_items' => _x( 'All Forms', 'pcud_forms' ),
			'parent_item' => _x( 'Parent Form', 'pcud_forms' ),
			'parent_item_colon' => _x( 'Parent Form:', 'pcud_forms' ),
			'edit_item' => _x( 'Edit Form', 'pcud_forms' ),
			'update_item' => _x( 'Update Form', 'pcud_forms' ),
			'add_new_item' => _x( 'Add New Form', 'pcud_forms' ),
			'new_item_name' => _x( 'New Form', 'pcud_forms' ),
			'separate_items_with_commas' => _x( 'Separate forms with commas', 'pcud_forms' ),
			'add_or_remove_items' => _x( 'Add or remove Forms', 'pcud_forms' ),
			'choose_from_most_used' => _x( 'Choose from most used Forms', 'pcud_forms' ),
			'menu_name' => _x( 'Forms', 'pcud_forms' ),
		);
	
		$args = array( 
			'labels' => $labels,
			'public' => false,
			'show_in_nav_menus' => false,
			'show_ui' => false,
			'show_tagcloud' => false,
			'hierarchical' => false,
			'rewrite' => false,
			'query_var' => true
		);
		register_taxonomy('pcud_forms', null, $args);
	}
	add_action('init', 'register_taxonomy_pcud_forms', 1);
	
	
	//////////////////////
	// CUSTOM FIELDS TAXONOMY
	function register_taxonomy_pcud_fields() {
		$labels = array( 
			'name' => _x('Fields', 'pcud_fields' ),
			'singular_name' => _x( 'Field', 'pcud_fields' ),
			'search_items' => _x( 'Search Fields', 'pcud_fields' ),
			'popular_items' => _x( 'Popular Fields', 'pcud_fields' ),
			'all_items' => _x( 'All Fields', 'pcud_fields' ),
			'parent_item' => _x( 'Parent Field', 'pcud_fields' ),
			'parent_item_colon' => _x( 'Parent Field:', 'pcud_fields' ),
			'edit_item' => _x( 'Edit Field', 'pcud_fields' ),
			'update_item' => _x( 'Update Field', 'pcud_fields' ),
			'add_new_item' => _x( 'Add New Field', 'pcud_fields' ),
			'new_item_name' => _x( 'New Field', 'pcud_fields' ),
			'separate_items_with_commas' => _x( 'Separate fields with commas', 'pcud_fields' ),
			'add_or_remove_items' => _x( 'Add or remove Fields', 'pcud_fields' ),
			'choose_from_most_used' => _x( 'Choose from most used Fields', 'pcud_fields' ),
			'menu_name' => _x( 'Fields', 'pcud_fields' ),
		);
	
		$args = array( 
			'labels' => $labels,
			'public' => false,
			'show_in_nav_menus' => false,
			'show_ui' => false,
			'show_tagcloud' => false,
			'hierarchical' => false,
			'rewrite' => false,
			'query_var' => true
		);
		register_taxonomy('pcud_fields', null, $args);
	}
	add_action('init', 'register_taxonomy_pcud_fields', 1);
	

	////////////////////////////////////////////////////////
	
	
	// SCRIPTS AND CSS ENQUEUING
	function pcud_global_scripts() { 
		include_once(PCUD_DIR . '/functions.php');
		$cust_fields = pcud_unserialize( get_option('pcud_custom_fields'));
		
		// datepicker if a date field exists
		if(is_array($cust_fields)) {
			foreach($cust_fields as $key => $val) {
				if($val['type'] == 'text' && ($val['subtype'] == 'eu_date' || $val['subtype'] == 'us_date')) {
					wp_enqueue_script("jquery-ui-datepicker"); 
					$pc_form_style = get_option('pc_style', 'minimal');
					
					if($pc_form_style == 'custom') {
						$datepicker_style = get_option('pc_datepicker_col', 'light');
					} else {
						$datepicker_style = ($pc_form_style == 'dark') ? 'dark' : 'light';	
					}
					break;
				}
			}
		}
			
		if (!is_admin()) {
			wp_enqueue_script('pcud_fontend_js', PCUD_URL . '/js/private-content-ud.js', 99, '2.04');	
			
			if(isset($datepicker_style)) {
				wp_enqueue_style( 'pcud_datepicker', PCUD_URL.'/css/datepicker/'.$datepicker_style.'/pcud_'.$datepicker_style.'.theme.min.css', 999);
			}
		}
		
		// datepicker localization
		if(isset($datepicker_style)) {
			global $wp_locale;	

			$str_array = array(
				'monthNames'        => pcud_strip_array_indices( $wp_locale->month ),
				'monthNamesShort'   => pcud_strip_array_indices( $wp_locale->month_abbrev ),
				'dayNames'          => pcud_strip_array_indices( $wp_locale->weekday ),
				'dayNamesShort'     => pcud_strip_array_indices( $wp_locale->weekday_abbrev ),
				'dayNamesMin'       => pcud_strip_array_indices( $wp_locale->weekday_initial),
				'firstDay'          => get_option('start_of_week'),
				'isRTL'             => (isset($wp_locale->text_direction) && $wp_locale->text_direction == 'rtl') ? true : false,
			);
			if (!is_admin()) {
				wp_localize_script('pcud_fontend_js', 'pcud_datepick_str', $str_array);
			} else {
				wp_localize_script('jquery-ui-datepicker', 'pcud_datepick_str', $str_array);	
			}
		}
	}
	add_action('wp_enqueue_scripts', 'pcud_global_scripts');
	add_action('admin_enqueue_scripts', 'pcud_global_scripts');
	
	////////////////////////////////////////////////////////
	
	
	// INTEGRATIONS
	include_once(PCUD_DIR . '/integrations.php');
	
	// TINYMCE - PVTCONTENT SHORCODE WIZARD INTEGRATION
	include_once(PCUD_DIR . '/tinymce_integration.php');
	
	// PUBLIC API (retrocompatibily)
	include_once(PCUD_DIR . '/public_api.php');
	
	// AJAX
	include_once(PCUD_DIR . '/ajax.php');
	
	// SHORTCODE
	include_once(PCUD_DIR . '/shortcodes.php');
	
	// CUSTOM FORMS HANDLE
	include_once(PCUD_DIR . '/cust_forms_handle.php');
	
	
	////////////
	// UPDATE NOTIFIER
	if(!class_exists('lc_update_notifier')) {
		include_once(PCUD_DIR . '/lc_update_notifier.php');
	}
	$lcun = new lc_update_notifier(__FILE__, 'http://www.lcweb.it/envato_update/pcud.php');
	////////////
	

	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////


	//////////////////////////////////////////////////
	// ACTIONS ON ADD-ON ACTIVATION //////////////////
	//////////////////////////////////////////////////
	
	function pcud_on_activation() {
		$db_version = (float)get_option('pcud_db_version', 0);
		
		if($db_version < 2.01 || isset($_GET['pcud_db_update'])){
			global $pc_users, $pc_meta;
			if(empty($pc_meta)) {
				deactivate_plugins( plugin_basename( __FILE__ ) );
				wp_die('User Data add-on '. __('requires at least', 'pcud_ml') .' PrivateContent v5 installed');	
			}
			
			
			// if previous to v1.4 - serialize saved fields
			$custom_fields = get_option('pcud_custom_fields');
			if(!empty($custom_fields) && is_array($custom_fields)) { 
				update_option('pcud_custom_fields', serialize($custom_fields));
			}
			
			// pass user custom data into new meta management
			if($db_version < 2.01) {
				$users = $pc_users->get_users(array(
					'limit'		=> -1,
					'to_get' 	=> 'id'
				));
				
				foreach($users as $ud) {
					$old_stored = maybe_unserialize(get_option('pcud_user_'. $ud['id'] .'_custom_data', ''));
					if(empty($old_stored)) {continue;}
					
					foreach( (array)$old_stored as $field => $val) {
						if(!empty($field)) {
							$pc_meta->add_meta($ud['id'], sanitize_title($field), $val);
						}
					}	
				}
			}

			// move custom fields into a custom taxonomy
			if(!empty($custom_fields) && $db_version < 2.0) {
				register_taxonomy_pcud_fields();
				if(!is_array($custom_fields)) {$custom_fields = unserialize($custom_fields);}
				
				foreach($custom_fields as $field_id => $data) {
					$label = $data['label'];
					unset($data['label']);
					
					$result = wp_insert_term($label, 'pcud_fields', array(
						'slug' 			=> $field_id,
						'description' 	=> base64_encode(serialize($data))
					));		
				}
			}
			
			update_option('pcud_db_version', 2.01);
		}
	}
	register_activation_hook(__FILE__, 'pcud_on_activation');

	if(isset($_GET['pcud_db_update'])) {
		add_action('admin_init', 'pcud_on_activation');
    }

}


