<?php
// add custom post type to add user pages

add_action( 'init', 'register_pg_user_page', 1);
function register_pg_user_page() {

	////////////////////////////////////////////////
	// WP roles control if level under "editor"
	$cap = get_option('pg_min_role', 'upload_files');
	$cpt = 'pg_user_page';
	
	switch($cap) {
		case 'read' 		: 
			$add = array('subscriber', 'contributor', 'author', 'editor', 'administrator');
			$remove = array(); 
			break;
			
		case 'edit_posts' 	: 
			$add = array('contributor', 'author', 'editor', 'administrator');
			$remove = array('subscriber');  
			break;
			
		case 'upload_files' : 
			$add = array('author', 'editor', 'administrator');
			$remove = array('subscriber', 'contributor'); 
			break;	
			
		case 'edit_pages' :
			$add = array('editor', 'administrator');
			$remove = array('subscriber', 'contributor', 'author'); 
			break;
			
		case 'install_plugins' :
			$add = array('administrator');
			$remove = array('subscriber', 'contributor', 'author', 'editor'); 
			break;	
	}
	
	foreach($add as $subj) {
		$role = get_role($subj);

		if(is_object($role)) {
			$role->add_cap( "edit_".$cpt );
			$role->add_cap( "read_".$cpt );
			$role->add_cap( "delete_".$cpt );
			$role->add_cap( "edit_".$cpt."s" );
			$role->add_cap( "edit_others_".$cpt."s" );
			$role->add_cap( "publish_".$cpt."s" );
			$role->add_cap( "read_private_".$cpt."s" );
			$role->add_cap( "delete_".$cpt."s" );
			$role->add_cap( "delete_private_".$cpt."s" );
			$role->add_cap( "delete_published_".$cpt."s" );
			$role->add_cap( "delete_others_".$cpt."s" );
			$role->add_cap( "edit_private_".$cpt."s" );
			$role->add_cap( "edit_published_".$cpt."s" );
		}
	}
	foreach($remove as $subj) {
		$role = get_role($subj);
		
		if(is_object($role)) {
			$role->remove_cap( "edit_".$cpt );
			$role->remove_cap( "read_".$cpt );
			$role->remove_cap( "delete_".$cpt );
			$role->remove_cap( "edit_".$cpt."s" );
			$role->remove_cap( "edit_others_".$cpt."s" );
			$role->remove_cap( "publish_".$cpt."s" );
			$role->remove_cap( "read_private_".$cpt."s" );
			$role->remove_cap( "delete_".$cpt."s" );
			$role->remove_cap( "delete_private_".$cpt."s" );
			$role->remove_cap( "delete_published_".$cpt."s" );
			$role->remove_cap( "delete_others_".$cpt."s" );
			$role->remove_cap( "edit_private_".$cpt."s" );
			$role->remove_cap( "edit_published_".$cpt."s" );
		}
	}

	///////////////////////////////////////////
	// add
    $labels = array( 
        'name' => __('User Pages', 'pc_ml'),
        'singular_name' => __('User Page', 'pc_ml'),
        'add_new' => __('Add New', 'pc_ml'),
        'add_new_item' => __('Add New User Page', 'pc_ml'),
        'edit_item' => __('Edit User Page', 'pc_ml'),
        'new_item' => __('New User Page', 'pc_ml'),
        'view_item' => __('View User Page', 'pc_ml'),
        'search_items' => __('Search User Pages', 'pc_ml'),
        'not_found' => __('No user pages found', 'pc_ml'),
        'not_found_in_trash' => __('No user pages found in Trash', 'pc_ml'),
        'parent_item_colon' => __('Parent User Page:', 'pc_ml'),
        'menu_name' => __('User Pages', 'pc_ml'),
    );

    $args = array( 
        'labels' => $labels,
        'hierarchical' => false,
        'description' => 'Private pages for privateContent users',
        'supports' => array( 'editor', 'thumbnail', 'revisions', 'comments'),
        
        'public' => false,
        'show_ui' => ((float)substr(get_bloginfo('version'), 0, 3) >= 4.4) ? true : false,
		'show_in_menu' => false,
        'show_in_nav_menus' => false,
        'publicly_queryable' => false,
        'exclude_from_search' => true,
        'has_archive' => false,
        'query_var' => true,
        'can_export' => true,
        'rewrite' => false,
        'capability_type' => $cpt,
		'map_meta_cap' => true
    );
    register_post_type($cpt, $args);
}


////////////////////////////////////////
// Avoid direct page creation //////////
////////////////////////////////////////

add_action('admin_head-post-new.php', 'pc_avoid_manual_pvt_page_creation', 1);

function pc_avoid_manual_pvt_page_creation() {
	global $post_type;

    if('pg_user_page' == $post_type) {
		wp_die("Direct creation forbidden!");
	}
}



////////////////////////////////////////
// Edit custom post type edit page /////
////////////////////////////////////////

// FIX FOR QTRANSLATE - to avoid qtranslate JS error i have to add title support to post type
// but I've hidden them with the CSS

// edit submitbox - hide minor submit minor-publishing and delete page

add_action('admin_head-post.php', 'user_page_admin_script', 15 );

function user_page_admin_script() {
    global $post_type;
	global $wpdb;

    if('pg_user_page' == $post_type) {
		
		// hide ADD PAGE
		?>
		<style type="text/css">
		.page-title-action,
		.add-new-h2,
		#titlediv,
		#slugdiv.postbox,
		.qtrans_title_wrap,
		.qtrans_title {
			display: none;	
		}
		
		#submitpost .misc-pub-post-status,
		#submitpost #visibility,
		#submitpost .misc-pub-curtime,
		#minor-publishing-actions,
		#delete-action {
			display: none;	
		}
		
		.updated.notice.notice-success a {
			display: none !important;
		}
		</style>
		<?php
		
		
		// append username to the edit-page title 
		$user_data = $wpdb->get_row( $wpdb->prepare( 
			"SELECT id, username FROM  ".PC_USERS_TABLE." WHERE page_id = %d",
			$_REQUEST['post']
		) );
		$username = $user_data->username;
		
		?>
		<script type="text/javascript">
        jQuery(document).ready(function(){
            jQuery(".wrap > h1, .wrap > h2").append(" - <?php echo addslashes($username) ?>");
        });
        </script>
		<?php
		
		
		// add preview link
		$container_id = get_option('pg_target_page');
		if(!empty($container_id)) {
			$link = get_permalink($container_id);
			$conj = (strpos($link, '?') === false) ? '?' : '&'; 
			
			$preview_link = $link.$conj. 'pc_pvtpag='.$user_data->id. '&pc_utok='.wp_create_nonce('lcwp_nonce');
			
			?>
			<script type="text/javascript">
            jQuery(document).ready(function(){
                var pc_live_preview = 
				'<a href="<?php echo $preview_link ?>" target="_blank" id="pc_pp_preview_link"><?php echo pc_sanitize_input( __("Live preview", 'pc_ml')) ?> &raquo;</a>';
			
				jQuery('#major-publishing-actions').prepend(pc_live_preview);
            });
            </script>
            <?php
		} // if pvt pag container exists - end
	}
}


/////////////////////////////////////////////////////////////////////////

// comments reply fix on pvt pages - always redirect to container
function pc_pvtpag_comment_redirect_fix() {
	$pvt_pag_id = get_option('pg_target_page');
	
	if(isset($_REQUEST['pg_user_page']) && !empty($pvt_pag_id)) {
		header('Location: '. get_permalink($pvt_pag_id));	
	}
}
add_action('template_redirect', 'pc_pvtpag_comment_redirect_fix', 1);
