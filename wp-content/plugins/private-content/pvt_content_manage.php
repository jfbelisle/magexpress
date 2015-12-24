<?php
// MANAGE PRIVATE CONTENTS AND PRIVATE PAGE


//// private page
// if isset a specific page as user global login manage the page to display a plugin page
function pc_pvt_page_management($content) {
	include_once(PC_DIR . '/functions.php');
	global $wpdb, $post, $pc_users;
	
	$orig_content = $content;
	$target_page = (int)get_option('pg_target_page');
	$curr_page_id = (int)get_the_ID();
	
	// must be the chosen container page
	if(pc_wpml_translated_pag_id($target_page) != pc_wpml_translated_pag_id(get_the_ID())) {
		return $content;
	}
		
		
	// preview check
	if(is_user_logged_in() && isset($_REQUEST['pc_pvtpag']) && isset($_REQUEST['pc_utok'])) {
		if(!wp_verify_nonce($_REQUEST['pc_utok'], 'lcwp_nonce')) {return 'Cheating?';}
		$GLOBALS['pc_user_id'] = (int)$_REQUEST['pc_pvtpag'];
	}

	// check logged user
	$user_data = pc_user_logged(array('page_id', 'disable_pvt_page', 'wp_user_id'));
	if(!$user_data) {
		
		// return page content and eventually attach form
		$login_form = pc_login_form();
		$pvt_nl_content = get_option('pg_target_page_content');
		
		//only original contents
		if($pvt_nl_content == 'original_content') {
			$content = $content;   
		}	
		// contents + form
		elseif($pvt_nl_content == 'original_plus_form') {
			$content = $content . $login_form;   
		}
		// form + contents
		elseif($pvt_nl_content == 'form_plus_original') {
			$content = $login_form . $content;   
		}
		// only form
		else {$content = $login_form;}
		
		return $content;
	}	
		
	// if not have a reserved area
	if(!empty($user_data['disable_pvt_page'])) {
		return '<p>'. pc_get_message('pc_default_nhpa_mex') .'</p>';	
	}	
	
	// flag for pvt page usage
	$GLOBALS['pc_pvt_page_is_displaying'] = true;
	
	// private page contents
	$page_data = get_post( $user_data['page_id']);
	$content = $page_data->post_content;
						
	// if there's WP [embed] shortcode, execute it
	if(strpos($content, '[/embed]') !== -1) {
		global $wp_embed;
		$content = $wp_embed->run_shortcode($content);
	}
		
	// PC-FILTER - private page contents - useful to customize what is returned
	$content = apply_filters('pc_pvt_page_contents', $content);
	$content = do_shortcode(wpautop($content));
		
	// PC-ACTION - private page is being displayed - triggered in the_content hook
	do_action('pc_pvt_page_display');
	
	
	//// COMMENTS
	// disable comments if not synced
	if(!$pc_users->wp_user_sync || !get_option('pg_pvtpage_wps_comments') || !$user_data['wp_user_id'] || $page_data->comment_status != 'open') {
		add_filter('comments_template', 'pc_comments_template', 500);
	}
	else {
		// override query
		$GLOBALS['pc_custom_comments_template'] = 'original';
		$GLOBALS['pc_pvt_page_id'] = $user_data['page_id'];
		$GLOBALS['pc_pvt_page_obj'] = $page_data;
		$GLOBALS['pc_pvt_page_container_id'] = $curr_page_id;
		
		// override $post
		global $post;
		$post = get_post($user_data['page_id']);
		
		// PC-ACTION - give the opportunity to override comments template	
		$custom_template = do_action('pc_pvt_page_comments_template');  
		if(!empty($custom_template)) {
			$GLOBALS['pc_custom_comments_template'] = $custom_template;	
		}
		
		add_filter('comments_template', 'pc_comments_template',500);
	}
			
	return $content;
}
add_filter('the_content', 'pc_pvt_page_management', 500); // use 500 - before comments restriction and PC hide


// preset contents - used through hooks
function pc_pvt_page_preset_texts($content) {
	if(get_option('pg_pvtpage_enable_preset')) {$preset = do_shortcode( wpautop(get_option('pg_pvtpage_preset_txt')));}
	else {return $content;}
	
	if(get_option('pg_pvtpage_preset_pos') == 'before') {$content = $preset . $content;}
	else {$content = $content . $preset;}	
	
	return $content;
}
add_filter('pc_pvt_page_contents', 'pc_pvt_page_preset_texts', 50);


// override default comment template - by default returns an empty template
function pc_comments_template($template){
	if (!isset($GLOBALS['pc_custom_comments_template']) || empty($GLOBALS['pc_custom_comments_template'])) {
		$url = PC_DIR . "/comment_hack.php";	
	} 
	else {		
		// override current WP_query parameters to show pvt page contents
		global $post;
		$post = get_post($GLOBALS['pc_pvt_page_id']);
		
		global $wp_query;
		
		$wp_query->queried_object->ID	= $GLOBALS['pc_pvt_page_id'];
		$wp_query->posts[0]->ID 		= $GLOBALS['pc_pvt_page_id'];
		$wp_query->post->ID				= $GLOBALS['pc_pvt_page_id'];
		
		$wp_query->queried_object->comment_status 	= 'open';
		$wp_query->posts[0]->comment_status 		= 'open';
		$wp_query->post->comment_status 			= 'open';
		
		$wp_query->queried_object->comment_count	= $GLOBALS['pc_pvt_page_obj']->comment_count;
		$wp_query->posts[0]->comment_count 			= $GLOBALS['pc_pvt_page_obj']->comment_count;
		$wp_query->post->comment_count 				= $GLOBALS['pc_pvt_page_obj']->comment_count;
		$wp_query->comment_count 					= $GLOBALS['pc_pvt_page_obj']->comment_count;
		
		$wp_query->comments = get_comments( array('post_id' => $GLOBALS['pc_pvt_page_id']) );

		$url = ($GLOBALS['pc_custom_comments_template'] == 'original') ? $template : $GLOBALS['pc_custom_comments_template'];
	}

	return $url;
}


//if private page and override comments - reset post
function pc_restore_after_comments_override() {
	if(isset($GLOBALS['pc_pvt_page_container_id'])) {
		global $post;
		$post = get_post($GLOBALS['pc_pvt_page_container_id']);
	}
}
do_action( 'comment_form_after', 'pc_restore_after_comments_override', 1);


//////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////


// comments restriction
function pc_perform_comments_restriction($content) {
	global $post, $pc_users;
	
	if($pc_users->wp_user_sync && !isset($GLOBALS['pc_pvt_page_is_displaying']) && comments_open($post->ID)) {
		$allowed = get_post_meta($post->ID, 'pg_hide_comments', true);
		
		// check global restriction
		if(!$allowed) {
			if(get_option('pg_lock_comments')) {$allowed = 'all';}	
		}
		
		// if restrict - use global to comunicate with fake template
		if($allowed) {
			
			// allow any WP logged user
			if(!is_user_logged_in() || current_user_can('pvtcontent')) {
				$result = pc_user_check( implode(',', $allowed));
				if($result !== 1) {
					$post_warning = get_post_meta($post->ID, 'pg_hc_use_warning', true);
					$show_warning = ($post_warning == 'yes' || ($post_warning != 'no' && get_option('pg_hc_warning'))) ? true : false;

					$GLOBALS['pc_comment_restriction_warning'] = ($show_warning) ? array('check_result'=>$result) : false;
					add_filter('comments_template', 'pc_comments_restriction_template', 750); 			
				}
				else {
					// PC-ACTION - restricted comments block is shown to user
					do_action('pc_restricted_comment_is_show');	
				}
			}
			else {
				do_action('pc_restricted_comment_is_show');	
			}
		}
	}
	
	return $content;	
}
add_filter('the_content', 'pc_perform_comments_restriction', 750); // use 750 - before PC hide


// override comments template
function pc_comments_restriction_template($template) {
	return PC_DIR . "/comment_hack.php";
}


//////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////


// if post category has got "PC HIDE", hide the content
function pc_manage_cat_limit_post($the_content) {
	global $post;
	
	if(isset($post->ID)) {	
		include_once(PC_DIR . '/functions.php');
	
		// check if term has PC limitations
		$terms = array();
		foreach(pc_affected_tax() as $tax) {
			$terms = array_merge((array)$terms, (array)wp_get_post_terms($post->ID, $tax));
		}
		
		$pc_limit = '';
		if(is_array($terms)) {
			foreach($terms as $post_term) {
				if(!is_object($post_term)) {continue;}
				
				$limit_this = get_option('taxonomy_'.$post_term->term_id.'_pg_cats');
				if($limit_this) {
					$pc_limit = $limit_this;
					break;
				}
			}
		}

		// use shortcode for contents and eventually hide comments
		if(!empty($pc_limit)) {
			$result = do_shortcode('[pc-pvt-content allow="'.$pc_limit.'"]'. $the_content .'[/pc-pvt-content]');
			
			// user has no access - limit comments
			if(strpos($result, 'class="pc_login_block"') !== false) {
				$GLOBALS['pc_comment_restriction_warning'] = false; 
				add_filter('comments_template', 'pc_comments_template', 999); 	
			}
			
			return $the_content;
		}
		else {return $the_content;}
	}
	else {return $the_content;}
}
add_filter('the_content', 'pc_manage_cat_limit_post', 999); // use 999 - latest check


///////////////////////////////////////////////////////////////


/* CHECK IF USER CAN SEE A RESTRICTED PAGE
 *
 * @param subj = subject to analyze (category or page)
 * @subj_data = data object of the subject
 */
function pc_redirect_check($subj, $subj_data, $taxonomy = false) {
	if($subj == 'page') {
		$allowed = get_post_meta($subj_data->ID, 'pg_redirect', true);
		
		if($allowed) {
			$allowed = trim(implode(',', $allowed));
			
			if($allowed == 'unlogged') {
				include_once(PC_DIR . '/functions.php');
				
				// where to move users to a custom page
				$custom_unl_redir = get_post_meta($subj_data->ID, 'pg_unlogged_redirect', true);
				if($custom_unl_redir) {
					$GLOBALS['pc_unlogged_custom_redirect'] = get_permalink( pc_wpml_translated_pag_id($custom_unl_redir));	
				}
				
				return (pc_user_check('unlogged', '', true) === 1) ? true : false;	
			}
			
			return (!empty($allowed) && pc_user_check($allowed, '', true) !== 1) ? false : true;
		}
		
		// parent page
		else {
			if($subj_data->post_parent) {
				$parent = get_post($subj_data->post_parent);
				return pc_redirect_check('page', $parent); // recursive	
			}
			else {return true;}
		}
	}
	
	// category
	else {
		$allowed = get_option('taxonomy_'.$subj_data->term_id.'_pg_redirect');
		
		if($allowed) {
			return (pc_user_check($allowed, '', true) !== 1) ? false : true;
		}
		
		// parent category
		else {
			if(isset($subj_data->category_parent) && $subj_data->category_parent) {
				$parent = get_term_by('id', $subj_data->category_parent,  $taxonomy);
				
				// recursive
				return pc_redirect_check('category', $parent, $taxonomy);	
			}
			else {return true;}
		}
	}
}


// PERFORMS REDIRECT 
function pc_pvt_redirect() {
	include_once(PC_DIR . '/functions.php');
	
	$orig_redirect_val = get_option('pg_redirect_page');
	$redirect_url = pc_man_redirects('pg_redirect_page');
	
	// only if redirect option is setted
	if(!empty($redirect_url)) {

		// get redirect page url
		$orig_redirect_val = get_option('pg_redirect_page');
		$redirect_url = pc_man_redirects('pg_redirect_page');
		
		//////////////////////////////////////////////////////////////
		// complete website lock
		if(get_option('pg_complete_lock') && pc_user_check('all', '', true) !== 1) {
			global $post;
			
			$excluded_pages = (filter_var($orig_redirect_val, FILTER_VALIDATE_INT)) ? array($orig_redirect_val) : array();
			
			// PC-FILTER - add page IDS to exclude from complete site lock - page IDs array
			$excluded_pages = apply_filters('pc_complete_lock_exceptions', $excluded_pages);
			
			// exceptions check
			foreach((array)$excluded_pages as $pag_id) {				
				if($pag_id == $post->ID) {
					$exception_page = true;
					break;		
				}
				
				// WPML integration - if current page is translation of an exception
				elseif(pc_wpml_translated_pag_id($pag_id) == $post->ID) {
					$exception_page = true;
					break;	
				}
			}
			
			if(!isset($exception_page)) {
				// last restricted page redirect system
				if(get_option('pg_redirect_back_after_login') && pc_curr_url() != '') {
					$_SESSION['pc_last_restricted'] = pc_curr_url();
				}

				header('location: '.$redirect_url);
				die();	
			}	
		}
		
		//////////////////////////////////////////////////////////////
		// single page/post redirect
		if(is_page() || is_single()) {
			global $post;				
			$result = pc_redirect_check('page', $post);
			
			// custom unlogged redirect system
			$is_unl_custom_redir = (isset($GLOBALS['pc_unlogged_custom_redirect'])) ? true : false;
			if($is_unl_custom_redir) {
				$redirect_url = $GLOBALS['pc_unlogged_custom_redirect'];
				
				// avoid redirect loops
				if($redirect_url == pc_curr_url()) {return false;}
			}
			
			if(($post->ID != $orig_redirect_val || $is_unl_custom_redir) && !$result) {
				
				// last restricted page redirect system
				if(get_option('pg_redirect_back_after_login') && pc_curr_url() != '' && !$is_unl_custom_redir) {
					$_SESSION['pc_last_restricted'] = pc_curr_url();
				}
				
				header('location: '.$redirect_url);
				die();	
			}
		}
		
		//////////////////////////////////////////////////////////////
		// if is category or archive
		if(is_category() || is_archive()) {
			$cat_id = get_query_var('cat');

			// know which taxonomy is involved
			foreach(pc_affected_tax() as $tax) {
				$cat_data = get_term_by('id', $cat_id, $tax);
				
				if($cat_data != false) {
					if(!pc_redirect_check('category', $cat_data, $tax)) {
						if(get_option('pg_redirect_back_after_login') && pc_curr_url() != '') {
							$_SESSION['pc_last_restricted'] = pc_curr_url();	
						}
						
						header('location: '.$redirect_url);
						die();	
					}
					
					break;	
				}
			}
		}
		
		
		//////////////////////////////////////////////////////////////
		// WooCommerce category
		if(function_exists('is_product_category') && is_product_category()) {
			$cat_slug = get_query_var('product_cat');
			$cat_data = get_term_by('slug', $cat_slug, 'product_cat');
				
			if($cat_data != false) {
				if(!pc_redirect_check('category', $cat_data, 'product_cat')) {
					if(get_option('pg_redirect_back_after_login') && pc_curr_url() != '') {
						$_SESSION['pc_last_restricted'] = pc_curr_url();	
					}
					
					header('location: '.$redirect_url);
					die();	
				}
			}
		}
		
		
		//////////////////////////////////////////////////////////////
		// if is a single post (check category restriction)
		if(is_single()) {
			global $post;
			include_once(PC_DIR . '/functions.php');
			
			// search post terms in every involved taxonomy
			foreach(pc_affected_tax() as $tax) {
				$terms = wp_get_post_terms($post->ID, $tax);
				
				if(is_array($terms)) {
					foreach($terms as $term) {
						$cat_data = get_term_by('id', $term->term_id, $tax);
						
						if(!pc_redirect_check('category', $cat_data, $tax)) {
							if(get_option('pg_redirect_back_after_login') && pc_curr_url() != '') {
								$_SESSION['pc_last_restricted'] = pc_curr_url();
							}
							
							header('location: '.$redirect_url);
							die();	
						}	
					}		
				}
			}
		}
		
		
		//////////////////////////////////////////////////////////////
		// PC-FILTER custom restriction (URL based) - associative array('url' => array('allowed', 'blocked'))
		$restrictet_urls = apply_filters('pc_custom_restriction', array());
		if(is_array($restrictet_urls) && count($restrictet_urls)) {
			$curr_url = pc_curr_url();
			
			foreach((array)$restrictet_urls as $url => $val) {
				if(isset($val['allowed']) && $curr_url == $url) {
					$blocked = (isset($val['blocked'])) ? $val['blocked'] : ''; 
					
					if(pc_user_check($val['allowed'], $blocked, true) !== 1) {
						header('location: '.$redirect_url);
						die();	
					}	
				}
			}	
		}
	}	
}
add_action('template_redirect', 'pc_pvt_redirect', 1);


/////////////////////////////////////////////////////////////////////

// SINGLE MENU ITEM CHECK
function pc_single_menu_check($items, $item_id) {
	foreach($items as $item) {
		if($item->ID == $item_id) {
			
			if($item->menu_item_parent) {
				$parent_check = pc_single_menu_check($items, $item->menu_item_parent);	
				if(!$parent_check) {return false;}
			}

			// if allowed users array exist 
			if(isset($item->pc_hide_item) && is_array($item->pc_hide_item)) {
				$allowed = implode(',', $item->pc_hide_item);
				return (pc_user_check($allowed, '', true) === 1) ? true : false;
			}	
		}		
	}
	
	return true;
}


// HIDE MENU ITEMS IF USER HAS NO PERMISSIONS
function pc_menu_filter($items) {	
	$new_items = array();
	
	// full website lock 
	if(get_option('pg_complete_lock') && pc_user_check('all', '', true) !== 1) {
		return $new_items;	
	}
	
	foreach($items as $item) {
		if(isset($item->menu_item_parent) && $item->menu_item_parent) {
			$parent_check = pc_single_menu_check($items, $item->menu_item_parent);	
		}
		else {$parent_check = true;}
		
		if($parent_check) {

			// if allowed users array exist 
			if(isset($item->pc_hide_item) && is_array($item->pc_hide_item)) {
				$allowed = implode(',', $item->pc_hide_item);
				if(pc_user_check($allowed, '', true) === 1) {$new_items[] = $item;}	
			}
			else {$new_items[] = $item;}
		}
	}
	
	return $new_items;
}
add_action( 'wp_nav_menu_objects', 'pc_menu_filter' );


//////////////////////////////////////////////////////////////////


// REMOVE RESTRICTED TERMS / POSTS FROM WP_QUERY
// search filter
function pc_query_filter($query) {
	
	if(!$query->is_admin && !$query->is_single && !$query->is_page) {	
		include_once(PC_DIR . '/functions.php');
		global $pc_query_filter_post_array;
		
		// remove restricted terms
		$exclude_cats = pc_query_filter_cat_array(); 
		if(count($exclude_cats) > 0) {
			$exclude_cat_string = str_replace('-', '', implode(',', $exclude_cats));
			$query->set('category__not_in', explode(',', $exclude_cat_string)); // terms ID array
		}
		
		// remove restricted posts
		$exclude_posts = $pc_query_filter_post_array;
		if(is_array($exclude_posts) && count($exclude_posts) > 0) {
			$query->set('post__not_in', $exclude_posts ); //Post ID array
		}
	}

	return $query;
}
add_filter('pre_get_posts', 'pc_query_filter', 999);


// REMOVE TERMS FROM CATEGORIES WIDGET
function pc_widget_categories_args_filter($cat_args) {
	include_once(PC_DIR . '/functions.php');
	global $pc_query_filter_post_array;
	
	// remove restricted terms
	$exclude_cats = pc_query_filter_cat_array(); 
	if(count($exclude_cats) > 0) {
		if (isset($cat_args['exclude']) && $cat_args['exclude']) {
			$cat_args['exclude'] = $cat_args['exclude'] . ',' . implode(',', $exclude_cats);
		} else {
			$cat_args['exclude'] = implode(',', $exclude_cats);
		}
	}
	   
	return $cat_args;
}
add_filter( 'widget_categories_args', 'pc_widget_categories_args_filter', 10, 1 );


// create an array of restricted terms
function pc_query_filter_cat_array() {
	$exclude_array = array();
	
	$args = array( 'hide_empty' => 0);
	$categories = get_terms( pc_affected_tax(), $args );
	
	foreach( $categories as $category ) { 
		if(!pc_redirect_check('category', $category)) {
			$exclude_array[] = '-'.$category->term_id;
		}	
	}
	
	return $exclude_array;	
}


// create an array of restricted posts and pages 
// triggers on INIT and set GLOBALS to avoid incompatibilities with pre_get_posts
function pc_query_filter_post_array() {
	
	if(!is_admin() && !is_single() && !is_page()) {	
		$exclude_array = array();
	
		$args = array(
			'post_type' => pc_affected_pt(),
			'posts_per_page' => -1,
			'post_status' => 'publish'
		);
		$posts = get_posts( $args );
	
		foreach( $posts as $post ) { 
			if(!pc_redirect_check('page', $post)) {
				$exclude_array[] = $post->ID;
			}	
		}
		
		$GLOBALS['pc_query_filter_post_array'] = $exclude_array;
	}
}
add_action('init', 'pc_query_filter_post_array', 1);
