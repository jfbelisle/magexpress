<?php 
include_once(PC_DIR . '/classes/pc_form_framework.php');
include_once(PC_DIR . '/functions.php');
global $pc_users, $pc_wp_user;

$form_fw = new pc_form;

// first/last name flag 
$fist_last_name = get_option('pg_use_first_last_name');

// current user can edit - flag
$cuc = get_option('pg_min_role_tmu', get_option('pg_min_role', 'upload_files'));

// WP user sync check
$wp_user_sync = $pc_users->wp_user_sync;

// check if are updating
$upd = (isset($_GET['user'])) ? true : false;	
if($upd) { 

	// if update - get the user ID and if is WP synced
	$user_id = (int)addslashes($_GET['user']);
	$is_wp_synced = ($wp_user_sync && $pc_wp_user->pvtc_is_synced($user_id)) ? true : false;
}
else {$is_wp_synced = false;} 

/***********************************************************************/

// DISABLE / ENABLE / ACTIVATE / DELETE
if(isset($_GET['new_status'])) {
	$ns = (int)$_GET['new_status'];
	
	if (!isset($_GET['pc_nonce']) || !wp_verify_nonce($_GET['pc_nonce'], __FILE__)) {die('<p>Cheating?</p>');};	
	if(!in_array($ns, array(0,1,2))) {die('<p>Wrong status value</p>');}
	
	// delete
	if($ns == 0) {
		if($pc_users->delete_user($user_id)) {
			echo '
				<p><br/></p>
				<div class="updated"><p><strong>'. __('User deleted', 'pc_ml') .'</strong></p></div>
			
				<script type="text/javascript">
				window.location.href = "'. admin_url() .'admin.php?page=pc_user_manage";
				</script>';
			exit;
		}
	}
	else {
		$result = $pc_users->change_status($user_id, $ns);	
		
		if($result) {
			$txt = ($ns == 1) ?  __('User enabled', 'pc_ml') : __('User disabled', 'pc_ml');
			$html_message = '<div class="updated"><p><strong>'. $txt .'</strong></p></div>';	
		}
	}
}


/***********************************************************************/

// SUBMIT HANDLE DATA
if(isset($_POST['pc_man_user_submit'])) { 
	if (!isset($_POST['pc_nonce']) || !wp_verify_nonce($_POST['pc_nonce'], __FILE__)) {die('<p>Cheating?</p>');};
	if(!$cuc) {die("You don't have permissions to manage users");}
	 
	include_once(PC_DIR . '/classes/pc_form_framework.php');
	
	$form_structure = array(
		'include' => array('name', 'surname', 'username', 'tel', 'email', 'psw', 'disable_pvt_page', 'categories')
	);
	
	// PC-FILTER - add fields to validate and save in "add user" page - passes form structure (must comply with form framework)
	$form_structure = apply_filters('pc_add_user_form_validation', $form_structure);
	
	
	if($is_wp_synced) {unset($form_structure['include'][2]);} // if WP synced, can't change username
	
	//////////////////////////////////////////////////////////////
	// ADD CUSTOM FIELDS TO BE SAVED - USER DATA ADD-ON //////////
	$pcud_fields = do_action('pcud_list_fields');
	if(!empty($pcud_fields)) {$form_structure['include'] = array_merge($form_structure['include'], $pcud_fields);}
	//////////////////////////////////////////////////////////////
	
	// setup validation
	$fdata = $form_fw->get_fields_data($form_structure['include']);
	
	// INSERT
	if(!$upd) {
		$user_id = $pc_users->insert_user($fdata, $status = 1, $allow_wp_sync_fail = true);
		$error = $pc_users->validation_errors;
		$wp_sync_error = $pc_users->wp_sync_error; 
	}
	
	// UPDATE
	else {
		$result = $pc_users->update_user($user_id, $fdata);
		$error = $pc_users->validation_errors;
	}
	
	
	// messages
	if(!empty($error)) {
		$html_message = '<div class="error"><p>'.$error.'</p></div>';	
	} else {
		$pcwp_warn = (!empty($wp_sync_error)) ? ' <span style="font-weight: normal;">('.__('WP sync error', 'pc_ml').': '.$wp_user_sync.')</span>' : '';
		$mess = (!$upd) ?  __('User saved', 'pc_ml') : __('User updated', 'pc_ml');
		
		$html_message = '<div class="updated"><p><strong>'. $mess .$pcwp_warn.'</strong></p></div>';	
		
		// set upd to true - user is updated from now
		if(!$upd) {$upd = true;}
	}
	
	// values not passed through forms
	if($upd) {
		$nptf = $pc_users->get_user($user_id, array('to_get' => array('username', 'page_id', 'status', 'wp_user_id'))); 
		foreach($nptf as $key => $val) {
			if(!isset($fdata[$key])) {
				$fdata[$key] = $val;	
			}
		}
	}
}


// if updating - retrieve data 
if($upd && !isset($_POST['pc_man_user_submit'])) {
	$args = array(
		'to_get' => array_merge(array_keys($form_fw->fields), array('page_id', 'status', 'disable_pvt_page', 'wp_user_id'))
	);
	$fdata = $pc_users->get_user($user_id, $args);
	
	if(!$fdata) {
		echo '<div class="error"><p>'. __('User does not exists', 'pc_ml') .'</p></div>'; 
		exit;
	}
}


// utility to print form values easily
$print_val = ($upd || isset($error)) ? true : false;
?>
    
    
<div class="wrap pc_form lcwp_form">  
	<div class="icon32" id="icon-pc_user_manage"><br></div>
    <?php 
	$fp_title = ($upd) ? 'PrivateContent - '. __('Edit', 'pc_ml').' '.$fdata['username'] : __('Add PrivateContent User', 'pc_ml');
	echo '<h2 class="pc_page_title">' .$fp_title. "</h2>"; 
	?>  
    
    <?php if(isset($html_message)) {echo $html_message;} ?>
    <br/>
    
    <?php 
	$form_target = str_replace( '%7E', '~', $_SERVER['REQUEST_URI']);
	$form_target = ($upd && !isset($_GET['user'])) ? $form_target.'&user='.$user_id : $form_target; 
	?>
    <form name="pc_user" method="post" action="<?php echo $form_target; ?>" class="form-wrap">  
	
    <table class="widefat pc_table pc_add_user" style="margin-bottom: 25px;">
      <thead>
      <tr>  
        <th colspan="2" style="width: 50%;"><?php _e("User Data", 'pc_ml'); ?></th>
        <th colspan="2" style="width: 50%;">&nbsp;</th>
      </tr>  
      </thead>
   
      <tbody>
      <tr>
      	<td class="pc_label_td"><?php _e("Username", 'pc_ml'); ?> <span class="pc_req_field">*</span></td>
        <td class="pc_field_td">
        	<?php 
			// lock username if is synced
			if($wp_user_sync && $upd && !empty($fdata['wp_user_id'])) : ?>
			
            	<?php echo $fdata['username'] ?><br/><small>( <?php _e('detach from WP sync to change username', 'pc_ml') ?>)</small>
				<input type="hidden" name="wp_user_id" value="<?php echo $user_id ?>" />
                
			<?php else : ?>
            	<input type="text" name="username" value="<?php if($print_val) {echo pc_sanitize_input($fdata['username']);} ?>"  maxlength="150" autocomplete="off" />
            <?php endif; ?>
        </td>
        
        <td class="pc_label_td" style="border-left: 1px solid #DFDFDF;"><?php _e("E-mail", 'pc_ml'); ?></td>
        <td class="pc_field_td">
            <input type="text" name="email" value="<?php if($print_val) {echo pc_sanitize_input($fdata['email']);} ?>" maxlength="255" autocomplete="off" />
        </td>
      </tr>
      
      <tr>
      	<td class="pc_label_td"><?php ($fist_last_name) ? _e('First name', 'pc_ml') : _e('Name', 'pc_ml') ?></td>
        <td class="pc_field_td">
        	<input type="text" name="name" value="<?php if($print_val) {echo pc_sanitize_input($fdata['name']);} ?>" maxlength="150" autocomplete="off" />
        </td>
        
        <td class="pc_label_td" style="border-left: 1px solid #DFDFDF;"><?php _e("Telephone", 'pc_ml'); ?></td>
        <td class="pc_field_td">
        	<input type="text" name="tel" value="<?php if($print_val) {echo pc_sanitize_input($fdata['tel']);} ?>" maxlength="20" autocomplete="off" />
        </td>
      </tr>
      <tr>
      	<td class="pc_label_td"><?php ($fist_last_name) ? _e('Last name', 'pc_ml') : _e('Surname', 'pc_ml') ?></td>
        <td class="pc_field_td">
        	<input type="text" name="surname" value="<?php if($print_val) {echo pc_sanitize_input($fdata['surname']);} ?>" maxlength="150" autocomplete="off" />
        </td>
        
        <td class="pc_label_td" style="border-left: 1px solid #DFDFDF;"><?php _e("Disable user private page", 'pc_ml'); ?>?</td>
        <td class="pc_field_td">
        	<input type="checkbox" name="disable_pvt_page" value="1" <?php if($upd && $fdata['disable_pvt_page'] == 1) echo 'checked="checked"' ?> class="ip_checks" />
            <?php if($upd && !$fdata['disable_pvt_page'] && (int)$fdata['status'] != 3) : ?>
            <a href="<?php echo get_admin_url(); ?>post.php?post=<?php echo $fdata['page_id'] ?>&action=edit" style="padding-left: 15px;">(<?php _e('edit page', 'pc_ml') ?>)</a>
            <?php endif; ?>
        </td>
      </tr>
      <tr>
      	<td class="pc_label_td"><?php echo ($upd) ? __("Update password", 'pc_ml') : __("Password", 'pc_ml'); ?> <?php if(!$upd) : ?><span class="pc_req_field">*</span><?php endif; ?></td>
        <td class="pc_field_td">
        	<input type="password" name="psw" value="" maxlength="100" autocomplete="off" />
        </td>
        
        <td class="pc_label_td" rowspan="2" style="border-left: 1px solid #DFDFDF;"><?php _e("Categories", 'pc_ml'); ?> <span class="pc_req_field">*</span></td>
        <td class="pc_field_td" rowspan="2">
        	<?php
			$user_categories = get_terms('pg_user_categories', 'orderby=name&hide_empty=0');
			
			if(count($user_categories) == 0) {
				echo '<li><a href="edit-tags.php?taxonomy=pg_user_categories" style="color: red;">'. __('Create at least an user category', 'pc_ml') .'</a></li>';
			}
            else {
            	echo '
				<select name="categories[]" multiple="multiple" class="lcweb-chosen pc_menu_select" data-placeholder="'. __('Select categories', 'pc_ml') .' .." autocomplete="off">';

                  foreach(pc_user_cats() as $cat_id => $cat_name) {
					  $selected = ($print_val && is_array($fdata['categories']) && in_array($cat_id, $fdata['categories'])) ?  'selected="selected"' : '';
                      echo '<option value="'. $cat_id .'" '.$selected.'>'. $cat_name .'</option>';  
                  }

                echo '</select>';  
			}
			?>
        </td>
      </tr>
      <tr>
      	<td class="pc_label_td"><?php _e("Repeat password", 'pc_ml'); ?> <?php if(!$upd) : ?><span class="pc_req_field">*</span><?php endif; ?></td>
        <td class="pc_field_td">
        	<input type="password" name="check_psw" value="" maxlength="100" autocomplete="off" />
        </td>
      </tr>
      
      <tr>
      	<td class="pc_label_td" style="paddin-top: 22px;">
			<?php if(!$upd || ($cuc && (int)$fdata['status'] != 3)) : ?>
				<?php $btn_val = ($upd) ? __('Update User', 'pc_ml') : __('Add User', 'pc_ml'); ?>
                <input type="submit" name="pc_man_user_submit" value="<?php echo $btn_val; ?>" class="button-primary" />  
        	<?php endif; ?>
            
        	<?php if($upd) : ?> 
            <span class="alignright pc_eus_legend"><?php _e('status', 'pc_ml') ?></span>
			<?php endif; ?>
        </td>
        
        <td class="pc_field_td" style="paddin-top: 22px;">
        	<?php if($upd) : 
				switch((int)$fdata['status']) {
					case 1 : $txt = __('active', 'pc_ml'); break;
					case 2 : $txt = __('disabled', 'pc_ml'); break;
					case 3 : $txt = __('pending', 'pc_ml'); break;
					default: $txt = __('deleted', 'pc_ml'); break;	
				}
				?>
				<div class="pc_edit_user_status pc_eus_<?php echo (int)$fdata['status']; ?>"><?php echo $txt; ?></div>
            <?php endif; ?>
        </td>
        <td></td>
        <td>
        	<div id="pc_man_user_edit_status_wrap">
			<?php if($upd && $cuc) : ?>
                <?php if(in_array((int)$fdata['status'], array(2, 3))) : ?>
                <a href="<?php echo $form_target . '&pc_nonce='. wp_create_nonce(__FILE__) .'&new_status=1' ?>" title="<?php _e('enable user', 'pc_ml') ?>">
                    <img src="<?php echo PC_URL; ?>/img/enable_user.png" alt="ena_user" />
                </a>
                <?php else : ?>
                <a href="<?php echo $form_target . '&pc_nonce='. wp_create_nonce(__FILE__) .'&new_status=2' ?>" title="<?php _e('disable user', 'pc_ml') ?>">
                    <img src="<?php echo PC_URL; ?>/img/disable_user.png" alt="dis_user" />
                </a>
                <?php endif; ?>
                
                 | <a href="<?php echo $form_target . '&pc_nonce='. wp_create_nonce(__FILE__) .'&new_status=0' ?>" title="<?php _e('delete user', 'pc_ml') ?>" class="pc_del_user">
                    <img src="<?php echo PC_URL; ?>/img/delete_user.png" alt="del_user" />
                </a>
            <?php endif; ?>
            </div>
        </td>
      </tr>
      </tbody>  
    </table>  
    
    
    <?php 
	///////////////////////////////////////
	// WP USERS SYNC
	if($upd && $wp_user_sync && $cuc) {
		echo '<h3 style="border: none !important;">'. __('Wordpress user sync', 'pc_ml') .'</h3>';	
    	
        //if doesn't have mail
		if(empty($fdata['email'])) {
			echo '
			<div class="pc_warn pc_error">
				<p>'.__("User cannot be sinced, e-mail is required", 'pc_ml').'</p>
			</div>';
		}
		else {
			
			// if not synced
			if(!$is_wp_synced) {
				echo '
				<div class="pc_warn pc_wps_warn pc_warning">
					<p>'.__("User not synced", 'pc_ml').' - <a href="javascript:void(0)" id="pc_sync_with_wp">'.__('sync', 'pc_ml').'</a><span id="pc_wps_result" style="padding-left: 20px;"></span></p>
				</div>';
			}
			else {
				echo '
				<div class="pc_warn pc_wps_warn pc_success">
					<p><span title="WP user ID '.$fdata['wp_user_id'].'">'.__("User synced", 'pc_ml').'</span> - <a href="javascript:void(0)" id="pc_detach_from_wp">'.__('detach', 'pc_ml').'</a><span id="pc_wps_result" style="padding-left: 20px;"></span></p>
				</div>';
			}
		}
    }
		
	
	// PC-ACTION - add code blocks in "add user" page - eventually passes user data and editing user id 
	$action_fdata = (isset($fdata)) ? $fdata : false;
	$action_user_id = (isset($user_id)) ? $user_id : false;
	do_action('pc_add_user_body', $action_fdata, $action_user_id);
    ?>

  	<input type="hidden" name="pc_nonce" value="<?php echo wp_create_nonce(__FILE__) ?>" />  
  </form>
</div>  

<?php // SCRIPTS ?>
<script src="<?php echo PC_URL; ?>/js/lc-switch/lc_switch.min.js" type="text/javascript"></script>
<script src="<?php echo PC_URL; ?>/js/chosen/chosen.jquery.min.js" type="text/javascript"></script>

<script type="text/javascript" >
jQuery(document).ready(function($) {
	<?php if($upd && $wp_user_sync && $cuc) : ?>
	
	// user deletion - ask forconfirmation
	jQuery('body').delegate('.pc_del_user', 'click', function(e) {
		if(!confirm("<?php _e('Do you really want to delete this user?', 'pc_ml') ?>")) {
			e.preventDefault();
		}
	});
	
	/////////////////////
	
	// WP user sync
	jQuery('body').delegate('#pc_sync_with_wp', 'click', function(e) {
		e.preventDefault();
		
		if(confirm('<?php _e('A mirror wordpress user will be created. Continue?', 'pc_ml') ?>')) {
			jQuery('#pc_wps_result').html('<div class="pc_loading" style="margin-bottom: -7px;"></div>');
			
			var data = {
				action: 'pc_wp_sync_single_user',
				pc_user_id: <?php echo $user_id; ?>,
				pc_nonce: '<?php echo wp_create_nonce('lcwp_ajax') ?>'
			};
			jQuery.post(ajaxurl, data, function(response) {
				if(jQuery.trim(response) == 'success') {
					jQuery('.pc_wps_warn').removeClass('pc_warning').addClass('pc_success');
					jQuery('.pc_wps_warn p').html("<?php _e('User synced successfully!', 'pc_ml') ?>");
					setTimeout(function() {
						window.location.href = '<?php echo $form_target ?>';
					}, 1000);
				}
				else { jQuery('#pc_wps_result').html(response); }
			});
		}	
	});
	
	// WP user detach
	jQuery('body').delegate('#pc_detach_from_wp', 'click', function(e) {
		e.preventDefault();
		
		if(confirm('<?php _e('WARNING: this will delete connected wordpres user and any related content will be lost. Continue?', 'pc_ml') ?>')) {
			jQuery('#pc_wps_result').html('<div class="pc_loading" style="margin-bottom: -7px;"></div>');
			
			var data = {
				action: 'pc_wp_detach_single_user',
				pc_user_id: <?php echo $user_id; ?>,
				pc_nonce: '<?php echo wp_create_nonce('lcwp_ajax') ?>'
			};
			jQuery.post(ajaxurl, data, function(response) {
				if(jQuery.trim(response) == 'success') {
					jQuery('.pc_wps_warn').removeClass('pc_success').addClass('pc_warning');
					jQuery('.pc_wps_warn p').html("<?php _e('User detached successfully!', 'pc_ml') ?>");
					setTimeout(function() {
						window.location.href = '<?php echo $form_target ?>';
					}, 1000);
				}
				else { jQuery('#pc_wps_result').html(response); }
			});
		}	
	});
	<?php endif; ?>
	
	///////////////////////////////////////////
	
	// if is in pending status - disable all the fields and remove buttons
	<?php if((isset($fdata['status']) && $fdata['status'] == 3) || (get_option('pc_min_role_tmu') && !current_user_can( get_option('pc_min_role_tmu') ))) : ?>
	jQuery('.pc_form').find('input, textarea, button, select').attr('disabled','disabled');
	jQuery('#pcma_mv_validate, .pc_form input[type=submit]').remove();
	<?php endif; ?>
	
	///////////////////////////
	
	// lc switch
	jQuery('.ip_checks').lc_switch('YES', 'NO');
	
	// chosen
	jQuery('.lcweb-chosen').each(function() {
		var w = jQuery(this).css('width');
		jQuery(this).chosen({width: w}); 
	});
	jQuery(".lcweb-chosen-deselect").chosen({allow_single_deselect:true});
});
</script>