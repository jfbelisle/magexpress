<?php
// post and page metabox  + custom column

add_action('admin_init','pc_redirect_meta_init'); 
function pc_redirect_meta_init() {
   
    // add a meta box for affected post types
    foreach(pc_affected_pt() as $type){
        add_meta_box('pc_redirect_meta', __('PrivateContent Redirect', 'pc_ml'), 'pc_redirect_meta_setup', $type, 'side', 'default');
    }  
}

function pc_redirect_meta_setup() {
    include_once(PC_DIR . '/functions.php');
	global $post, $pc_users;
 	$user_categories = get_terms('pg_user_categories', 'orderby=name&hide_empty=0');
	
    $pc_redirect = (array)get_post_meta($post->ID, 'pg_redirect', true);
	$pc_unlogged_redirect = get_post_meta($post->ID, 'pg_unlogged_redirect', true);
	?>
    
    <p style="margin-bottom: 7px;"><?php _e('Which user categories can see the page?', 'pc_ml') ?></p> 
    <div class="pc_tax_cat_list">
        <select name="pg_redirect[]" multiple="multiple" class="lcweb-chosen pc_pag_restr" data-placeholder="<?php _e('Select categories', 'pc_ml') ?> .." autocomplete="off">
          <option value="all" class="pc_all_field" <?php if(isset($pc_redirect[0]) && $pc_redirect[0]=='all') echo 'selected="selected"'; ?>><?php _e('All', 'pc_ml') ?></option>
          <option value="unlogged" class="pc_unl_field" <?php if(isset($pc_redirect[0]) && $pc_redirect[0]=='unlogged') echo 'selected="selected"'; ?>><?php _e('Unlogged Users', 'pc_ml') ?></option>
          <?php
          foreach ($user_categories as $ucat) {
              $selected = (isset($pc_redirect[0]) && in_array($ucat->term_id, $pc_redirect)) ? 'selected="selected"' : '';
              
              echo '<option value="'.$ucat->term_id.'" '.$selected.'>'.$ucat->name.'</option>';  
          }
          ?>
        </select>   

		<div id="pc_unl_redir_wrap" <?php if(!in_array('unlogged', $pc_redirect)) {echo 'style="display: none;"';} ?>>
            <p style="margin-bottom: 7px;"><?php _e('Where to move logged users?', 'pc_ml') ?></p>
        
            <select name="pg_unlogged_redirect" class="lcweb-chosen" data-placeholder="<?php _e('Select an option', 'pc_ml') ?> .." style="width: 254px;" autocomplete="off">
              <option value=""><?php _e('Use main redirect target', 'pc_ml') ?></option>
              <?php
              foreach (get_pages() as $pag) {
				  if($pag->ID == $post->ID) {continue;} // avoid loops
				  
				  $selected = ($pc_unlogged_redirect == $pag->ID) ? 'selected="selected"' : '';
				  echo '<option value="'.$pag->ID.'" '.$selected.'>'.$pag->post_title.'</option>';
			  }
              ?>
            </select>
        </div>   
    </div>    
	
    <?php
	//// check parent restrictions and print an helper
	global $current_screen;
	$restr = array(); 

	// post types
	if($current_screen->id == 'post') {

		// search in every involved taxonomy
		foreach(pc_affected_tax() as $tax) {
			$terms = wp_get_post_terms($post->ID, $tax);
			
			if(is_array($terms)) {
				foreach($terms as $term) {
					$response = pc_restrictions_helper('post', $term->term_id, $tax);
					if($response) {$restr = array_merge($restr, $response);}
				}
			}
		}	
		
		$sing_plur = (count($restr) == 1) ? __('this category', 'pc_ml') : __('these categories', 'pc_ml');
	}
	
	// page types
	else {
		$response = pc_restrictions_helper('page', $post);
		if($response) {$restr = array_merge($restr, $response);}
		
		$sing_plur = (count($restr) == 1) ? __('this parent', 'pc_ml') : __('these parents', 'pc_ml');
	}
	
	
	// print helper
	if(is_array($restr) && count($restr) > 0) {
		echo '<div id="pc_page_rest_helper">
			<strong>'. __('Page already restricted by', 'pc_ml') .' '.$sing_plur.':</strong>
			<dl>';
		
		foreach ($restr as $index => $rs) {
			echo '<dt>'.$index.'</dt>
				<dd><em>'. __('visible by', 'pc_ml') .' '.$rs.'</em></dd>';	
		}
		
		echo '</dl></div>';	
	}
	?>
    
    
    <?php 
	// comments block restriction
	if($pc_users->wp_user_sync) :
	
		$pc_hide_comments = (array)get_post_meta($post->ID, 'pg_hide_comments', true);
		$pc_hc_warn = get_post_meta($post->ID, 'pg_hc_use_warning', true);
		?>
		<hr style="margin-top: 18px;"/>
		
		<p style="margin-bottom: 7px; font-size: 99.2%;"><?php _e('Which user categories can see comments?', 'pc_ml') ?></p>
		<div class="pc_tax_cat_list">
			<select name="pg_hide_comments[]" multiple="multiple" class="lcweb-chosen pc_pag_restr" data-placeholder="<?php _e('Select categories', 'pc_ml') ?> .." autocomplete="off">
			  <option value="all" class="pc_all_field" <?php if(isset($pc_hide_comments[0]) && $pc_hide_comments[0]=='all') echo 'selected="selected"'; ?>><?php _e('All', 'pc_ml') ?></option>
	
			  <?php
			  foreach ($user_categories as $ucat) {
				  $selected = (isset($pc_hide_comments[0]) && in_array($ucat->term_id, $pc_hide_comments)) ? 'selected="selected"' : '';
				  
				  echo '<option value="'.$ucat->term_id.'" '.$selected.'>'.$ucat->name.'</option>';  
			  }
			  ?>
			</select> 
			
			<div id="pc_hc_use_warning" <?php if(empty($pc_hide_comments)) {echo 'style="display: none;"';} ?>>
				<p style="margin-bottom: 7px;"><?php _e('Display warning box?', 'pc_ml') ?></p>
			
				<select name="pg_hc_use_warning" class="lcweb-chosen" data-placeholder="<?php _e('Select an option', 'pc_ml') ?> .." style="width: 254px;" autocomplete="off">
				  <option value="default" <?php selected($pc_hc_warn, 'default'); ?>><?php _e('as default', 'pc_ml') ?></option>
				  <option value="yes" <?php selected($pc_hc_warn, 'yes'); ?>><?php _e('yes', 'pc_ml') ?></option>
				  <option value="no" <?php selected($pc_hc_warn, 'no'); ?>><?php _e('no', 'pc_ml') ?></option>
				</select>
			</div>   
		</div>    
    <?php endif; ?>
    
    <?php
    // create a custom nonce for submit verification later
    echo '<input type="hidden" name="pc_redirect_noncename" value="' . wp_create_nonce(__FILE__) . '" />';
	?>
    
	<script src="<?php echo PC_URL; ?>/js/chosen/chosen.jquery.min.js" type="text/javascript"></script>
    <script type="text/javascript" charset="utf8">
	jQuery(document).ready(function($) {
		
		// all/unlogged toggles
		jQuery('body').delegate('.pc_tax_cat_list select', 'change', function() {
			var pc_sel = jQuery(this).val();
			if(!pc_sel) {pc_sel = jQuery.makeArray();}
			
			// if ALL is selected, discard the rest
			if(jQuery.inArray("all", pc_sel) >= 0) {
				jQuery(this).children('option').prop('selected', false);
				jQuery(this).children('.pc_all_field').prop('selected', true);
				
				jQuery(this).trigger("chosen:updated");
			}
			
			// if UNLOGGED is selected, discard the rest
			else if(jQuery.inArray("unlogged", pc_sel) >= 0) {
				jQuery(this).children('option').prop('selected', false);
				jQuery(this).children('.pc_unl_field').prop('selected', true);
				
				jQuery(this).trigger("chosen:updated");
				var unlogged_chosen = true;
			}	

			// unloggged redirect toggle
			if(jQuery(this).attr('name') == 'pg_redirect[]') {
				if(typeof(unlogged_chosen) != 'undefined') {
					jQuery('#pc_unl_redir_wrap').slideDown();	
				} else {
					jQuery('#pc_unl_redir_wrap').slideUp();	
				}
			}
			
			// hidden comments warning
			if(jQuery(this).attr('name') == 'pg_hide_comments[]') {
				if(typeof(pc_sel[0]) != 'undefined') {
					jQuery('#pc_hc_use_warning').slideDown();	
				} else {
					jQuery('#pc_hc_use_warning').slideUp();	
				}
			}
		});
		
		// chosen
		jQuery('.lcweb-chosen').each(function() {
			var w = jQuery(this).css('width');
			jQuery(this).chosen({width: w}); 
		});
		jQuery(".lcweb-chosen-deselect").chosen({allow_single_deselect:true});
	});
	</script>
    
    <?php
}
 
 
// save restrictions
function pc_redirect_meta_save($post_id) {
	if(isset($_POST['pc_redirect_noncename'])) {
		global $pc_users;
		
		// authentication checks
		if (!wp_verify_nonce($_POST['pc_redirect_noncename'], __FILE__)) return $post_id;

		// check user permissions
		if ($_POST['post_type'] == 'page') {
			if (!current_user_can('edit_page', $post_id)) return $post_id;
		} else {
			if (!current_user_can('edit_post', $post_id)) return $post_id;
		}

		//// handle data - if all is selected, discard the rest
		// redirect
		if(!isset($_POST['pg_redirect'])) {$pc_redirect = array();}
		else {
			$pc_redirect = (array)$_POST['pg_redirect'];
			$unlogged_redir = $_POST['pg_unlogged_redirect']; 
			
			if($pc_redirect[0] == 'all') {$pc_redirect = array('all');}	
			if($pc_redirect[0] == 'unlogged') {$pc_redirect = array('unlogged');}	
		}

		update_post_meta($post_id, 'pg_redirect', $pc_redirect);
		update_post_meta($post_id, 'pg_unlogged_redirect', $unlogged_redir);
		
		
		// comments restriction
		if($pc_users->wp_user_sync) {
			if(!isset($_POST['pg_hide_comments'])) {$pc_hide_comments = array();}
			else {
				$pc_hide_comments = (array)$_POST['pg_hide_comments'];
				if($pc_hide_comments[0] == 'all') {$pc_hide_comments = array('all');}	
			}
			
			update_post_meta($post_id, 'pg_hide_comments', $pc_hide_comments);
			update_post_meta($post_id, 'pg_hc_use_warning', $_POST['pg_hc_use_warning']);
		}
	}
}
add_action('save_post', 'pc_redirect_meta_save');


/////////////////////////////////////////////////////////////////////


// add column in post type table
add_action('admin_init', 'pc_custom_cols_init'); 
function pc_custom_cols_init() { 
	include_once(PC_DIR . '/functions.php');
	
	foreach(pc_affected_pt() as $type){ 
		add_filter('manage_edit-'.$type.'_columns', 'pc_redirect_table_col', 999); 
		add_action('manage_'.$type.'_posts_custom_column', 'show_pc_redirect_table_col', 10, 2);
	}
}

function pc_redirect_table_col($columns) {
    $columns['pc_redirect'] = '<span title="'. __('Which PrivateContent users can access?') .'">'. __('PC Redirect', 'pc_ml') .'</span>';
    return $columns;
}

function show_pc_redirect_table_col($column, $post_id){
  if($column == 'pc_redirect') {	
	  $restr = (array)get_post_meta($post_id, 'pg_redirect', true);
	  
	  if(count($restr)) {	
		  $cat_allowed = $restr;
		  
		  if($cat_allowed[0] == 'all') { _e('Any user', 'pc_ml'); }
		  else if($cat_allowed[0] == 'unlogged') { _e('Unlogged', 'pc_ml'); }
		  else {
			  $allow_string = '<ul style="margin: 0;">';
			  
			  foreach($cat_allowed as $allow) {
				  $term_data = get_term( $allow, 'pg_user_categories'); 
				  
				  if(!is_wp_error($term_data)) {
					  $allow_string .= '<li>'.$term_data->name.'</li>'; 	
				  }
			  }
			  
			  echo $allow_string . '</ul>';
		  }  
	  }
	  else {echo'&nbsp;';}
  }
}
