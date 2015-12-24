<?php
// USER CATEGORY TAXONOMY - REGISTER AND CUSTOMIZE


// REGISTER TAXONOMY 
add_action( 'init', 'pc_user_cat_taxonomy' );
function pc_user_cat_taxonomy() {
    $labels = array( 
        'name' => __( 'User Categories', 'pc_ml' ),
        'singular_name' => __( 'User Category', 'pc_ml' ),
        'search_items' => __( 'Search User Categories', 'pc_ml' ),
        'popular_items' => __( 'Popular User Categories', 'pc_ml' ),
        'all_items' => __( 'All User Categories', 'pc_ml' ),
        'parent_item' => __( 'Parent User Category', 'pc_ml' ),
        'parent_item_colon' => __( 'Parent User Category:', 'pc_ml' ),
        'edit_item' => __( 'Edit User Category', 'pc_ml' ),
        'update_item' => __( 'Update User Category', 'pc_ml' ),
        'add_new_item' => __( 'Add New User Category', 'pc_ml' ),
        'new_item_name' => __( 'New User Category Name', 'pc_ml' ),
        'separate_items_with_commas' => __( 'Separate user categories with commas', 'pc_ml' ),
        'add_or_remove_items' => __( 'Add or remove user categories', 'pc_ml' ),
        'choose_from_most_used' => __( 'Choose from the most used user categories', 'pc_ml' ),
        'menu_name' => __( 'User Categories', 'pc_ml' ),
    );

    $args = array( 
        'labels' => $labels,
        'public' => false,
        'show_in_nav_menus' => false,
        'show_ui' => true,
        'show_tagcloud' => false,
        'hierarchical' => false,
        'rewrite' => false,
		'capabilities' => array( get_option('pg_min_role', get_option('pg_min_role', 'upload_files')) ),
        'query_var' => true
    );

    register_taxonomy( 'pg_user_categories', '', $args );	
}


// remove the "articles" column from the taxonomy table
add_filter( 'manage_edit-pg_user_categories_columns', 'pc_user_cat_colums', 10, 1);
function pc_user_cat_colums($columns) {
   if(isset($columns['posts'])) {
		unset($columns['posts']); 
   }

    return $columns;
}



// add order field
add_action('pg_user_categories_add_form_fields','pc_ucat_fields', 10, 2 );
add_action('pg_user_categories_edit_form_fields' , "pc_ucat_fields", 10, 2);

function pc_ucat_fields($tax_data) {
   //check for existing taxonomy meta for term ID
   if(is_object($tax_data)) {
	  $term_id = $tax_data->term_id;
	  $redirect = (string)get_option("pg_ucat_".$term_id."_login_redirect");
	  $no_registration = get_option("pg_ucat_".$term_id."_no_registration");
	}
	else {
		$redirect = '';
		$no_registration = 0;
	}
	
	// creator layout
	if(!is_object($tax_data)) :
?>
		<div class="form-field">
            <label><?php _e('Custom redirect after login', 'pc_ml') ?></label>
           	<input type="text" name="pg_ucat_login_redirect" value="<?php echo trim($redirect) ?>" autocomplete="off" placeholder="<?php _e("Use a valid URL", 'pc_ml') ?>" /> 
            <p><?php _e('Set a custom login redirect for users belonging to this category', 'pc_ml') ?></p>
        </div>
        <div class="form-field">
            <label><?php _e('Prevent this category to be used in registration form?', 'pc_ml') ?></label>
           	<input type="checkbox" name="pg_ucat_no_registration" value="1" <?php if($no_registration) echo 'checked="checked"' ?> autocomplete="off" /> 
            <p style="display: inline-block; padding-left: 5px;"><?php _e('If checked, hide the category from the registration form auto-selection dropdown', 'pc_ml') ?></p>
        </div>
	<?php
	else:
	?>
	 <tr class="form-field">
      <th scope="row" valign="top"><label><?php _e('Custom redirect after login', 'pc_ml') ?></label></th>
      <td>
        <input type="text" name="pg_ucat_login_redirect" value="<?php echo trim($redirect) ?>" autocomplete="off" placeholder="<?php _e("Use a valid URL", 'pc_ml') ?>" /> 
        <p class="description"><?php _e('Set a custom login redirect for users belonging to this category', 'pc_ml') ?></p>
      </td>
    </tr>
    <tr class="form-field">
      <th scope="row" valign="top"><label><?php _e('Prevent this category to be used in registration form?', 'pc_ml') ?></label></th>
      <td>
        <input type="checkbox" name="pg_ucat_no_registration" value="1" <?php if($no_registration) echo 'checked="checked"' ?> autocomplete="off" /> 
        <p class="description" style="display: inline-block; padding-left: 5px;"><?php _e('If checked, hide the category from the registration form auto-selection dropdown', 'pc_ml') ?></p>
      </td>
    </tr>
<?php
	endif;
}


// save the fields
add_action('created_pg_user_categories', 'save_pc_ucat_fields', 10, 2);
add_action('edited_pg_user_categories', 'save_pc_ucat_fields', 10, 2);

function save_pc_ucat_fields( $term_id ) {
    if (isset($_POST['pg_ucat_login_redirect']) ) {
        update_option("pg_ucat_".$term_id."_login_redirect", $_POST['pg_ucat_login_redirect']); 
    }
	else {delete_option("pg_ucat_".$term_id."_login_redirect");}
	
	
	if (isset($_POST['pg_ucat_no_registration']) ) {
        update_option("pg_ucat_".$term_id."_no_registration", 1); 
    }
	else {delete_option("pg_ucat_".$term_id."_no_registration");}
	
	pc_cat_wpml_sync_names();
}



/////////////////////////////
// manage taxonomy table
add_filter( 'manage_edit-pg_user_categories_columns', 'pc_cat_order_column_headers', 10, 1);
add_filter( 'manage_pg_user_categories_custom_column', 'pc_cat_order_column_row', 10, 3);


// add the table column
function pc_cat_order_column_headers($columns) {
	if(isset($columns['slug'])) {unset($columns['slug']);}
	
	$columns_local = array();
    $columns_local['login_redirect'] = __("Login Redirect", 'pc_ml');
	$columns_local['no_registration'] = __("No Registration", 'pc_ml');
	
    return array_merge($columns, $columns_local);
}


// fill the custom column row
function pc_cat_order_column_row( $row_content, $column_name, $term_id){
	
	if($column_name == 'login_redirect') {
		return get_option("pg_ucat_".$term_id."_login_redirect");
	}
	else if($column_name == 'no_registration') {
		return (get_option("pg_ucat_".$term_id."_no_registration")) ? '&radic;' : '';
	}
	else {return '&nbsp;';}
}



/////////////////////////////////////////////////


//// WPML compatibility - save/update categories name as single strings
function pc_cat_wpml_sync_names() {
	if(function_exists('icl_register_string')) {
	
		$user_categories = get_terms('pg_user_categories', 'orderby=name&hide_empty=0');
		
		if (!is_wp_error($user_categories)) {
			foreach ($user_categories as $ucat) {
				icl_register_string('PrivateContent Categories', $ucat->term_taxonomy_id, $ucat->name);	
			}
		}
	}
}


//// WPML compatibility - delete cat name string during deletion
function pc_cat_wpml_del_name($cat_id) {
	if(function_exists('icl_unregister_string')) {
		icl_unregister_string('PrivateContent Categories', $cat_id);	
	}
}
add_action('delete_term_taxonomy', 'pc_cat_wpml_del_name');
