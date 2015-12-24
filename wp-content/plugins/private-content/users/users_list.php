<?php 
include_once(PC_DIR . '/classes/paginator.php'); 
include_once(PC_DIR . '/functions.php'); 
global $pc_users, $pc_wp_user;

// base page URL
$base_page_url = admin_url() . 'admin.php?page=pc_user_manage';

// first/last name flag 
$fist_last_name = get_option('pg_use_first_last_name');

// user categories
$user_categories = pc_user_cats();

// WP user sync check
$wp_user_sync = $pc_users->wp_user_sync;


// minimum level to manage users
$au_cap = get_option('pg_min_role_tmu', get_option('pg_min_role', 'upload_files'));
$cuc = current_user_can($au_cap);


// micro helper function to know if GET field exists
function pc_get_param_exists($param_name) {
	return (isset($_GET[$param_name]) && !empty($_GET[$param_name])) ? true : false;	
}


// table columns
$table_cols = array(
	'id' => array(
		'name' 		=> 'ID',
		'sortable' 	=> true,
		'width'		=> '45px'
	),
	'username' => array(
		'name' 		=> __('Username', 'pc_ml'),
		'sortable' 	=> true
	),
	'name' => array(
		'name' 		=> ($fist_last_name) ? __('First name', 'pc_ml') : __('Name', 'pc_ml'),
		'sortable' 	=> true
	),
	'surname' => array(
		'name' 		=> ($fist_last_name) ? __('Last name', 'pc_ml') : __('Surname', 'pc_ml'),
		'sortable' 	=> true
	),
	'email' => array(
		'name' 		=> __('E-mail', 'pc_ml'),
		'sortable' 	=> true
	),
	'tel' => array(
		'name' 		=> __('Telephone', 'pc_ml'),
		'sortable' 	=> true,
		'width'		=> '120px'
	),
	'categories' => array(
		'name' 		=> __('Categories', 'pc_ml'),
		'sortable' 	=> false
	),
	'insert_date' => array(
		'name' 		=> __('Registered on', 'pc_ml'),
		'sortable' 	=> true,
		'width'		=> '152px'
	),
	'last_access' => array(
		'name' 		=> __('Last access', 'pc_ml'),
		'sortable' 	=> false,
		'width'		=> '110px'
	)
);

// PC-FILTER - additional fields for users list - must comply with initial structure
$table_cols = apply_filters('pc_users_list_table_fiels', $table_cols);


// QUERY SETUP AND PAGINATOR
$p = new pc_paginator;

// USER MANAGEMENT ACTIONS (REMOVE - DISABLE - ENABLE)
if(pc_get_param_exists('ucat_action') && pc_get_param_exists('pc_users') && $cuc) {
	if (!isset($_GET['pc_nonce']) || !wp_verify_nonce($_GET['pc_nonce'], __FILE__)) {die('<p>Cheating?</p>');};
	
	$action = $_GET['ucat_action'];
	$users_involved = (array)$_GET['pc_users'];

	switch($action) {
		case 'delete' : 
			$act_q = 0;
			$act_message = __('User deleted', 'pc_ml');
			
			foreach($users_involved as $uid) {
				$pc_users->delete_user($uid);
			}
			break;
			
		case 'disable' : 
			$act_q = 2;
			$act_message = __('User disabled', 'pc_ml');
			break;
			
		default : // activation
			$act_q = 1;	
			$act_message = __('User enabled', 'pc_ml');
			break;
	}
	
	if($act_q === 1 || $act_q === 2) {
		$pc_users->change_status($users_involved, $act_q);
	}
}


/////////////////////////////////////////////////

// GET param 
$p->pag_param = 'pagenum';
$p->limit = 20; // limit
$p->curr_pag = (isset($_GET['pagenum'])) ? (int)$_GET['pagenum'] : 1; // curr page

// current viewing status
if(!isset($_GET['status']) || isset($_GET['status']) && $_GET['status'] == 1) {$status = '1';}
elseif($_GET['status'] == 'disabled') {$status = '2';}
else {$status = '3';}

//////////////////////////////////////////


// total rows for active users
$total_act_rows = $pc_users->get_users(array('status' => 1, 'count' => true));

// total rows for disabled users
$total_dis_rows = $pc_users->get_users(array('status' => 2, 'count' => true));

// total rows for pending users
$total_pen_rows = $pc_users->get_users(array('status' => 3, 'count' => true));


if 		($status == 1) 	{$total_rows = $total_act_rows;}
elseif 	($status == 2) 	{$total_rows = $total_dis_rows;}
else 					{$total_rows = $total_pen_rows;}

$p->total_rows = $total_rows;
$offset = $p->get_offset(); // offset


///////////////////////////////////////////
// QUERY ARGS /////////////////////////////

$init_status_cond = (pc_get_param_exists('basic_search') || pc_get_param_exists('pc_cat') || pc_get_param_exists('advanced_search')) ? 'AND' : '';

$args = array(
	'to_get'	=> array_merge(array_keys($table_cols), array('page_id', 'disable_pvt_page', 'wp_user_id')),
	'limit' 	=> $p->limit,
	'offset' 	=> $offset,
	'custom_search'		=> $init_status_cond.' status = '.(int)$status,
	'search_operator' 	=> 'AND',
	'search'	=> array()
);


// basic search
if(pc_get_param_exists('basic_search') || pc_get_param_exists('pc_cat')) {
	$args['search_operator'] = 'OR';
	
	if(pc_get_param_exists('pc_cat')) {
		$init_cat_cond = (pc_get_param_exists('basic_search')) ? ' AND ' : '';
		$args['custom_search'] = $init_cat_cond . $pc_users->categories_query($_GET['pc_cat']) .' '. $args['custom_search'];
	}	
	
	if(pc_get_param_exists('basic_search')) {
		$search_in = array('username', 'name', 'surname', 'email');
		foreach($search_in as $key) {
			$args['search'][] = array('key'=>$key, 'val'=>'%'. $_GET['basic_search'] .'%', 'operator' => 'LIKE');	
		}
	}
}

// advanced search
elseif(pc_get_param_exists('advanced_search')) {
	$sanitized_str = str_replace(array('%5B', '%5D'), array('[', ']'), $_GET['advanced_search']);
	parse_str($sanitized_str, $as_params);
	
	$args['search_operator'] = $as_params["pc_as_global_cond"];

	// search structure
	for($a=0; $a < count($as_params['pc_as_fields']); $a++) {	
		
		// operator translation
		switch($as_params['pc_as_cond'][$a]) {
			case 'different': $op = '!='; break;
			case 'bigger'	: $op = '>'; break;
			case 'smaller'	: $op = '<'; break;
			case 'like'		: $op = 'LIKE'; break;
			case '=' : 
			default: $op = '='; break;	
		}
		
		$as_val = ($op == 'LIKE') ? '%'. $as_params['pc_as_val'][$a] .'%' : $as_params['pc_as_val'][$a];
 	
		$args['search'][] = array(
			'key' => $as_params['pc_as_fields'][$a], 
			'val' => stripslashes($as_val), 
			'operator' => $op
		);
	}
}



// sorting
if(isset($_GET['orderby']) && !empty($_GET['orderby'])) {
	$args['orderby'] = $_GET['orderby'];	
}

if(isset($_GET['order']) && in_array(strtolower($_GET['order']), array('asc', 'desc')) ) {
	$args['order'] = strtolower($_GET['order']);	
} else {
	$args['order'] = 'desc';	
}

$user_query = $pc_users->get_users($args);
?>

<div class="wrap pc_form">  
	<div class="icon32" id="icon-pc_user_manage"><br></div>
    
	<?php
	// page title
	echo '
	<h2 class="pc_page_title">' . 
		__( 'PrivateContent Users', 'pc_ml' ) . 
		' <a class="add-new-h2" href="admin.php?page=pc_add_user">'. __( 'Add New', 'pc_ml') .'</a>
	</h2>'; 

	// MESSAGE IN RELATION TO THE ACTION PERFORMED
    if(isset($act_message)) { 
    	echo '<div class="updated"><p><strong>'. $act_message .'</strong></p></div>';	
	}
	
	// STATUS LINKS ?>
    <ul class="subsubsub">
        <li id="pc_active_users">
            <a href="admin.php?page=pc_user_manage&status=1" <?php if($status == 1) echo 'class="current"'; ?>>
                <?php _e('actives', 'pc_ml') ?> (<span><?php echo $pc_users->get_users(array('status' => 1, 'count' => true)); ?></span>)
            </a>
        </li> | 
        <li id="pc_disabled_users">
            <a href="admin.php?page=pc_user_manage&status=disabled" <?php if($status == 2) echo 'class="current"'; ?>>
                <?php _e('disabled', 'pc_ml') ?> (<span><?php echo $pc_users->get_users(array('status' => 2, 'count' => true)); ?></span>)
            </a>
        </li> | 
        <li id="pc_pending_users">
            <a href="admin.php?page=pc_user_manage&status=pending" <?php if($status == 3) echo 'class="current"'; ?>>
                <?php _e('pending', 'pc_ml') ?> (<span><?php echo $pc_users->get_users(array('status' => 3, 'count' => true)); ?></span>)
            </a>
        </li>
    </ul>
    
    
    <?php // TABLE START ?>
    <form method="get" id="pc_user_list_form" action="<?php echo $base_page_url ?>">
        <div class="tablenav pc_users_list_navbar">
            <?php
            echo $p->get_pagination('<div class="tablenav-pages">', '</div>');
            ?>
        
        	<input type="hidden" name="pc_nonce" value="<?php echo wp_create_nonce(__FILE__) ?>" />
        	<input type="hidden" name="page" value="pc_user_manage" />
            <input type="hidden" name="pagenum" value="1" /> <!-- set to  one to reset pagination -->
            <input type="hidden" name="status" value="<?php 
				if($status == 1) 	{echo 1;}
				elseif($status == 2){echo 'disabled';}
				else 				{echo 'pending';}
				?>" 
            />
            
            <?php if($cuc) { ?>
            	<select name="ucat_action" id="pc_ulist_action" autocomplete="off">
                    <option value=""><?php _e('Bulk Actions', 'pc_ml') ?></option>
					
					<?php if(isset($_GET['status']) && ($_GET['status'] == 'disabled' || $_GET['status'] == 'pending')): ?>
                        <option value="enable"><?php echo __('Enable', 'pc_ml').' '.__('Users', 'pc_ml'); ?></option>
                    <?php else : ?>
                        <option value="disable"><?php echo __('Disable', 'pc_ml').' '.__('Users', 'pc_ml'); ?></option>
                    <?php endif; ?>

                    <option value="delete"><?php echo __('Delete', 'pc_ml').' '.__('Users', 'pc_ml'); ?></option>
                    
                    <?php if(!isset($_GET['status']) || $_GET['status'] != 'pending'): ?>
                   		<option value="cat_change"><?php _e('Change categories', 'pc_ml') ?></option>
					<?php endif; ?>   
                </select>
                <input type="button" value="<?php _e('Apply', 'pc_ml'); ?>" class="button-secondary pc_submit" name="ucat_action" style="margin-right: 15px;">
            <?php } ?>
        	
            <?php if(!pc_get_param_exists('advanced_search') ) : ?>
                <label for="basic_search"><?php _e('Search', 'pc_ml') ?></label>
                <input type="text" name="basic_search" value="<?php echo (isset($_GET['basic_search'])) ? pc_sanitize_input($_GET['basic_search']) : ''; ?>" size="25" class="pc_ulist_search_field" placeholder="<?php _e('username, name, surname, e-mail', 'pc_ml') ?>" autocomplete="off" />
                
                <select name="pc_cat" id="pc_ulist_filter" style="margin-left: 15px;" autocomplete="off">
                    <option value=""><?php _e('All Categories', 'pc_ml') ?></option>
                    <?php
                    foreach ($user_categories as $cat_id => $cat_name) {
                        $ucat_sel = (isset($_GET['pc_cat']) && (int)$_GET['pc_cat'] == $cat_id) ? 'selected="selected"' : '';
                        echo '<option value="'. $cat_id .'" '.$ucat_sel.'>'. $cat_name .'</selected>';	
                    }
                    ?>
                </select>
            <?php else : ?>    
            	<input type="button" value="<?php _e('Clean advanced search', 'pc_ml'); ?>" class="button-secondary pc_clean_advanced_search" />
            <?php endif; ?>
      		
            <?php if(!isset($_GET['advanced_search']) || empty($_GET['advanced_search'])) : ?>
            	<input type="submit" value="<?php _e('Filter', 'pc_ml'); ?>" class="button-secondary" name="ucat_filter">
            <?php endif; ?>
            
            <input type="button" value="<?php _e('Advanced search', 'pc_ml'); ?>" class="<?php echo (isset($as_params)) ? 'button-primary' : 'button-secondary'; ?> pc_advanced_search_btn" data-mfp-src="#pc_adv_search" />
    	</div>
        
        <?php 
		/************************************************************
		************************************************************/
		?>
        
    	<table class="widefat pc_table pc_users_list">
        <thead>
            <tr>
              <th id="cb" class="manage-column column-cb check-column" scope="col" style="padding: 11px 0 0;">
                <?php if($cuc) : ?><input type="checkbox" autocomplete="off" /><?php endif; ?>
              </th>
              <th style="width: 100px;">&nbsp;</th>
              <th style="width: 35px;"><a class="pc_filter_th" rel="id">ID</a></th>
              <th style="width: 1px;">&nbsp;</th> <?php // user badges ?>
              
			  <?php 
			  foreach($table_cols as $key => $data) {
				  if($key == 'id') {continue;}
				  
				  $width = (isset($data['width'])) ? 'style="width: '.$data['width'].';"' : '';
				  $sortable = (isset($data['sortable']) && $data['sortable']) ? '<a class="pc_filter_th" rel="'. $key .'">'. $data['name'] .'</a>' : $data['name'];
				  echo '<th '.$width.'>'. $sortable .'</th>';	
			  }
			  ?>
            </tr>
        </thead>
        <tfoot>
            <tr>
              <th id="cb" class="manage-column column-cb check-column" scope="col" style="padding: 11px 0 0;">
                <?php if($cuc) : ?><input type="checkbox" autocomplete="off" /><?php endif; ?>
              </th>
              <th>&nbsp;</th>
              <th><a class="pc_filter_th" rel="id">ID</a></th>
              <th>&nbsp;</th> <?php // user badges ?>
              
			  <?php 
			  foreach($table_cols as $key => $data) {
				  if($key == 'id') {continue;}
				  
				  $width = (isset($data['width'])) ? 'style="width: '.$data['width'].';"' : '';
				  $sortable = (isset($data['sortable']) && $data['sortable']) ? '<a class="pc_filter_th" rel="'. $key .'">'. $data['name'] .'</a>' : $data['name'];
				  echo '<th '.$width.' class="pc_ul_'.$key.'_th">'. $sortable .'</th>';	
			  }
			  ?>
            </tr>
        </tfoot>
        <tbody>
		  <?php 
		  foreach($user_query as $u) : 
		  ?>
            <tr class="content_row">
          	 <td class="uca_bulk_input_wrap">
                <input type="checkbox" name="pc_users[]" value="<?php echo $u['id'] ?>" autocomplete="off" />
             </td>
            
             <td class="pc_ulist_icons">
                <div style="width: 100px;">
				<?php if($cuc) { ?>
                	<?php // DELETE USER ?>
                    <span class="pc_trigger del_pc_user" id="dpgu_<?php echo $u['id'] ?>">
                        <img src="<?php echo PC_URL; ?>/img/delete_user.png" alt="del_user" title="<?php _e('Delete', 'pc_ml'); ?>" />
                    </span>
                    <span class="v_divider">|</span> 
                     
                    <?php // ENABLE / ACTIVATE / DISABLE USER ?>
                    <?php if(isset($_GET['status']) && $_GET['status'] == 'disabled') : // enable ?>
                        <a href="<?php echo $p->getManager('ucat_action=enable&pc_nonce='.wp_create_nonce(__FILE__).'&pc_users[]='.$u['id']) ?>">
                            <img src="<?php echo PC_URL; ?>/img/enable_user.png" alt="ena_user" title="<?php _e('Enable', 'pc_ml'); ?>" />
                        </a>
                    
                    <?php elseif(isset($_GET['status']) && $_GET['status'] == 'pending') : // activate ?>
                        <a href="<?php echo $p->getManager('ucat_action=activate&pc_nonce='.wp_create_nonce(__FILE__).'&pc_users[]='.$u['id']) ?>">
                            <img src="<?php echo PC_URL; ?>/img/enable_user.png" alt="act_user" title="<?php _e('Activate', 'pc_ml'); ?>" />
                        </a>
                        
                    <?php else: // disable ?>
                        <a href="<?php echo $p->getManager('ucat_action=disable&pc_nonce='.wp_create_nonce(__FILE__).'&pc_users[]='.$u['id']) ?>">
                            <img src="<?php echo PC_URL; ?>/img/disable_user.png" alt="dis_user" title="<?php _e('Disable', 'pc_ml'); ?>" />
                        </a>
                    <?php endif; ?>
             	<?php } // end cuc (curr user can) ?>
                
                <?php // EDIT USER PAGE ?>
             	<?php 
				if($cuc && empty($u['disable_pvt_page']) && (!isset($_GET['status']) || $_GET['status'] != 'pending') ) : ?>
                <span class="v_divider">|</span>
				<a href="<?php echo get_admin_url(); ?>post.php?post=<?php echo $u['page_id'] ?>&action=edit">
					<img src="<?php echo PC_URL; ?>/img/user_page.png" alt="user_page" title="<?php _e('Edit user page', 'pc_ml'); ?>" />
                </a>
				<?php endif; ?>  
                </div>     
             </td>
             
             <td><?php echo $u['id'] ?></td>
             
             <td class="pc_ulist_badges">
             	<?php 
				$badges = '';
				
				if($wp_user_sync && !empty($u['wp_user_id'])) {
                     $badges = '<img src="'.PC_URL.'/img/wp_synced.png" title="'. __('Synced with WP user', 'pc_ml').' - ID '. $u['wp_user_id'].'" />';
				}
				
				// PC-FILTER - users list badges - show an image badge relatd to an user
				echo apply_filters('pc_users_list_badges', $badges, $u['id']);
				?>
             </td>
             <td id="pguu_<?php echo $u['id'] ?>">
			 	<a href="<?php echo get_admin_url(); ?>admin.php?page=pc_add_user&user=<?php echo $u['id'] ?>" class="pc_edit_user_link" title="<?php _e('edit user', 'pc_ml') ?>">
					<strong><?php echo $u['username'] ?></strong>
                </a>
             </td>
             
             <?php 
			 foreach($table_cols as $key => $data) {
				if(in_array($key, array('id', 'username'))) {continue;}
				echo '<td class="pc_ul_'.$key.'_td">'. $pc_users->data_to_human($key, $u[$key]) .'</td>'; 	 
			 }
			 
			 ?>
          <?php endforeach; ?>
        </tbody>
        </table>
	</form>
    
    <?php
	echo $p->get_pagination('<div class="tablenav pc_users_list_navbar pc_bottom_navbar"><div class="tablenav-pages">', '</div></div>');
	?>
	
    <div id="pc_users_table"></div>    
</div>


<?php // ADVANCED SEARCH FORM ?>
<div style="display: none;">
<form id="pc_adv_search">
	<button class="mfp-close" type="button" title="Close (Esc)">×</button>
	<table class="widefat pc_table">
      <tr>
      	<td colspan="2" style="width: 52%;"><?php _e('Conditions matching', 'pc_ml') ?></td>
        <td>
        	<select name="pc_as_global_cond" autocomplete="off">
            	<option value="OR"><?php _e('at least one must match', 'pc_ml') ?></option>
                <option value="AND" <?php if(isset($as_params) && $as_params["pc_as_global_cond"] == 'AND') {echo 'selected="selected"';} ?>><?php _e('every condition must match', 'pc_ml') ?></option>
            </select>
        </td>
      </tr>
      <tr>
      	<td colspan="2">
        	<select name="pc_as_fields" class="pc_as_fields" autocomplete="off" style="width: 100%;">
            	<?php 
				foreach($table_cols as $key => $val) {
					if(!isset($val['sortable']) || !$val['sortable']) {continue;} // only sortable/searchable fields
					if(in_array($key, array('id', 'insert_date', 'last_access'))) {continue;} // discard date fields for now
					
					echo '<option value="'. $key .'">'. $val['name'] .'</option>';
				}		
				?>
            </select>
        </td>
        <td><input type="button" name="pc_as_add_cond" value="<?php _e('Add condition', 'pc_ml') ?>" class="button-secondary pc_as_add_cond" /></td>
      </tr>
	</table>
    
    <table id="pc_as_conds" class="widefat pc_table" <?php if(!isset($as_params)) {echo 'style="display: none;"';} ?>>
    <?php 
	if(isset($as_params)) {
		for($a=0; $a < count($as_params['pc_as_fields']); $a++) {	
			$f_name = $table_cols[ $as_params['pc_as_fields'][$a] ]['name'];
			$f_class = str_replace(' ', '_', $as_params['pc_as_fields'][$a]);
			$op = $as_params['pc_as_cond'][$a];
			?>
            <tr class="<?php echo $f_class ?>"><td>
				<span class="pc_as_remove_cond" title="<?php _e('remove condition', 'pc_ml') ?>">&#10006;</span>
                <h4><?php echo $f_name ?></h4>
                <div>
                    <input type="hidden" name="pc_as_fields[]" value="<?php echo $as_params['pc_as_fields'][$a] ?>" autocomplete="off" />
                    <select name="pc_as_cond[]" style="width: 140px; margin-top: -1px;">
                        <option value="equal" <?php if($op == 'equal') {echo 'selected="selected"';} ?>><?php _e('is equal to', 'pc_ml') ?></option>
                        <option value="different" <?php if($op == 'different') {echo 'selected="selected"';} ?>><?php _e('is different from', 'pc_ml') ?></option>	
                        <option value="bigger" <?php if($op == 'bigger') {echo 'selected="selected"';} ?>><?php _e('is greater than', 'pc_ml') ?></option>	
                        <option value="smaller" <?php if($op == 'smaller') {echo 'selected="selected"';} ?>><?php _e('is lower than', 'pc_ml') ?></option>
                        <option value="like" <?php if($op == 'like') {echo 'selected="selected"';} ?>><?php _e('contains', 'pc_ml') ?></option>	
                    </select>
                    <input type="text" name="pc_as_val[]" value="<?php echo stripslashes($as_params['pc_as_val'][$a]) ?>" autocomplete="off" style="width: 320px; margin-left: 15px;" />
                </div>
            </td></tr>
            <?php
		}
	}
	?>
    </table>
    <input type="button" name="pc_as_submit" value="<?php _e('Search', 'pc_ml') ?>" id="pc_as_submit" class="button-primary" style=" <?php if(!isset($as_params)) {echo 'display: none;';} ?> margin-top: 10px;" />
</form>
</div>


<?php // BULK CATEGORIES CHANGE FORM ?>
<div style="display: none;">
<form id="pc_bulk_cat_change">
	<button class="mfp-close" type="button" title="Close (Esc)">×</button>
	
    <select name="pc_bcc_cats" class="lcweb-chosen pc_bcc_cats" data-placeholder="<?php _e('Select categories', 'pc_ml') ?> .." autocomplete="off" multiple="multiple" style="width: 100%;">
    	<?php 
		foreach($user_categories as $cat_id => $cat_name) {
			echo '<option value="'. $cat_id .'">'. $cat_name .'</option>';	
		}
		?>
    </select>
    <br/>
    <input type="button" value="<?php _e('Set', 'pc_ml') ?>" class="button-primary pc_bcc_submit" style="margin-top: 10px;" />
    <span class="pc_bcc_response" style="padding-left: 15px;"></span>
</form>
</div>


<!-- magnific popup - for advanced search -->
<link rel="stylesheet" href="<?php echo PC_URL; ?>/js/magnific_popup/magnific-popup.css" media="all" />
<script src="<?php echo PC_URL; ?>/js/magnific_popup/magnific-popup.pckg.js" type="text/javascript"></script>

<script src="<?php echo PC_URL; ?>/js/chosen/chosen.jquery.min.js" type="text/javascript"></script>
<script type="text/javascript" >
jQuery(document).ready(function($) {
	var base_url = '<?php echo $base_page_url ?>';
	
	
	/* bulk cat change - perform */
	<?php if($cuc) : ?>
	jQuery('body').delegate('.pc_bcc_submit', 'click', function() {
		var val = jQuery('.pc_bcc_cats').val();
		if(!val) {return false;}
		
		if(confirm("<?php _e('Existing user categories will be overwritten. Continue?') ?>")) {
			jQuery('.pc_bcc_response').html('<i class="pc_loading" style="margin-bottom: -6px;"></i>');

			var data = {
				action: 'pc_bulk_cat_change',
				users: pc_bulk_users,
				cats: val,
				pc_nonce: '<?php echo wp_create_nonce('lcwp_ajax') ?>'
			};
			jQuery.post(ajaxurl, data, function(response) {
				if(jQuery.trim(response) != 'success'){
					jQuery('.pc_bcc_response').html(response);
					return false;	
				}
				
				jQuery('.pc_bcc_response').html("<?php _e('Done', 'pc_ml') ?>!");
				location.reload(); 
			});	
		}	
	});
	<?php endif; ?>
	
	
	/* advanced search - submit form */
	jQuery(document.body).delegate('#pc_as_submit', 'click', function() {
		// check value fields - must have something in
		var val_f_check = true;
		jQuery('#pc_adv_search div input[type=text]').each(function() {
			if(jQuery.trim( jQuery(this).val()) == '') {val_f_check = false;}
        });
		if(!val_f_check) {
			alert("<?php _e('insert a value for each condition', 'pc_ml') ?>");
			return false;
		}
		
		// clean previous search parameters
		var url_arr = window.location.href.split('&');
		var new_arr = jQuery.makeArray();
		
		jQuery.each(url_arr, function(i, v) {
			if(typeof(v) != 'undefined') {
				if(v.indexOf('advanced_search=') === -1 && v.indexOf('basic_search=') === -1 && v.indexOf('pc_cat=') === -1) {
					new_arr.push(v);
				}
			}
		});
		
		window.location.href = new_arr.join('&') + '&advanced_search=' + encodeURIComponent(jQuery('#pc_adv_search').serialize()); 
	});
	
	
	/* advanced search - remove condition */
	jQuery(document.body).delegate('.pc_as_remove_cond', 'click', function() {
		if(confirm("<?php _e('Remove condition?', 'pc_ml') ?>")) {
			jQuery(this).parents('tr').fadeOut(300, function() {
				jQuery(this).remove();
				
				if(!jQuery('#pc_as_conds tr').size()) {
					jQuery('#pc_as_conds, #pc_as_submit').fadeOut(300); 
				}
			});
		}
	});
	
	
	/* advanced search - add condition */
	jQuery(document.body).delegate('.pc_as_add_cond', 'click', function() {
		var val = jQuery('.pc_as_fields').val();
		var f_name = jQuery('.pc_as_fields option[value="'+ val +'"]').html();
		var f_class = val.replace(/ /g, '_');
		
		if(jQuery('#pc_as_conds tr.'+f_class).size()) {
			alert("<?php _e('Field already used', 'pc_ml') ?>");
			return false;	
		}
		
		var code = '<tr class="'+ f_class +'"><td>'+
			'<span class="pc_as_remove_cond" title="<?php _e('remove condition', 'pc_ml') ?>">&#10006;</span>'+
			'<h4>'+ f_name +'</h4>'+
			'<div>'+
				'<input type="hidden" name="pc_as_fields[]" value="'+ val +'" autocomplete="off" />'+
				'<select name="pc_as_cond[]" style="width: 140px; margin-top: -1px;">'+
					'<option value="equal" ><?php _e('is equal to', 'pc_ml') ?></option>'+
					'<option value="different" ><?php _e('is different from', 'pc_ml') ?></option>'+	
					'<option value="bigger" ><?php _e('is greater than', 'pc_ml') ?></option>'+	
					'<option value="smaller" ><?php _e('is lower than', 'pc_ml') ?></option>'+
					'<option value="like"><?php _e('contains', 'pc_ml') ?></option>'+	
				'</select>'+
				'<input type="text" name="pc_as_val[]" value="" autocomplete="off" style="width: 320px; margin-left: 15px;" />'+
			'</div>'+
		'</td></tr>';
		
		jQuery('#pc_as_conds, #pc_as_submit').fadeIn(150); 
		jQuery('#pc_as_conds').append(code);
	});
	
	
	/* show lightbox for advanced search */
	jQuery('.pc_advanced_search_btn').magnificPopup({
          type: 'inline',
          preloader: false,
          callbacks: {
            beforeOpen: function() {
              if(jQuery(window).width() < 800) {
                this.st.focus = false;
              }
            }
          }
    });
		
	
	/* clean advanced search */
	jQuery('body').delegate('.pc_clean_advanced_search', 'click', function() {
		var url_arr = window.location.href.split('&');
		var new_url = jQuery.makeArray();
		
		jQuery.each(url_arr, function(i, v) {
			if(typeof(v) != 'undefined') {
				if(v.indexOf('advanced_search=') === -1) {
					new_url.push(v);	
				}
			}
		});
		
		window.location.href = new_url.join('&'); 
	});

	
	/* sorting system */
	var order = '<?php echo (isset($_GET['order'])) ? $_GET['order'] : 'desc'; ?>';
	var orderby = '<?php echo (isset($_GET['orderby'])) ? $_GET['orderby'] : 'id'; ?>';
	
	jQuery('.pc_filter_th[rel='+orderby+']').addClass('active_'+order);
	
	jQuery('body').delegate('.pc_filter_th', 'click', function() {
		var new_orderby = jQuery(this).attr('rel');
		
		if(new_orderby == orderby) {
			var new_order = (order == 'asc') ? 'desc' : 'asc';	
		} else {
			var new_order = 'asc';	
		}

		var sort_url = window.location.href;
		
		if(sort_url.indexOf('orderby='+orderby) != -1) {
			sort_url = sort_url.replace('orderby='+orderby, 'orderby='+new_orderby).replace('order='+order, 'order='+new_order);
		} else {
			sort_url = sort_url + '&orderby='+ new_orderby +'&order='+ new_order;	
		}
		
		<?php if(isset($_GET['pagenum'])) : ?>
		sort_url = sort_url.replace('pagenum=<?php echo $_GET['pagenum'] ?>', 'pagenum=1'); // back to page 1
		<?php endif; ?>
		
		window.location.href = sort_url;
	});
	
	
	/********************************************/
	
	// select/deselect all
	jQuery('#cb input').click(function() {
		if(jQuery(this).is(':checked')) {
			jQuery('.uca_bulk_input_wrap input').attr('checked', 'checked');	
		}
		else {jQuery('.uca_bulk_input_wrap input').removeAttr('checked');}
	});
	
	
	// bulk disabling / deleting confirm / change category
	jQuery('#pc_user_list_form .pc_submit').click(function() {
		var act = jQuery('#pc_ulist_action').val();
		
		// check how many useres are affected
		pc_bulk_users = jQuery.makeArray();
		jQuery('.pc_users_list tbody input[type=checkbox]').each(function() {
            if(jQuery(this).is(':checked')) {
				pc_bulk_users.push( jQuery(this).val() );
			}	
        });
		
		if(!act || !pc_bulk_users.length) {return false;}
		
		/////////////////////
		
		// disable
		if(act == 'disable') {
			jQuery('#pc_user_list_form').submit();	
		}
		
		// delete
		else if(act == 'delete') {
			if(confirm("<?php _e('Do you really want to delete these users?', 'pc_ml'); ?> ")) {
				jQuery('#pc_user_list_form').submit();
			}
		}
		
		// change categories
		else if(act == 'cat_change') {
			jQuery.magnificPopup.open({
				  items: {
					  src: '#pc_bulk_cat_change',
					  type: 'inline'
				  },
				  preloader: false,
				  callbacks: {
					beforeOpen: function() {
					  if(jQuery(window).width() < 800) {
						this.st.focus = false;
					  }
					},
					open: function() {
						// chosen
						jQuery('.lcweb-chosen').each(function() {
							var w = jQuery(this).css('width');
							jQuery(this).chosen({width: w}); 
						});
						jQuery(".lcweb-chosen-deselect").chosen({allow_single_deselect:true});	
					}
				  }
			});
		}
	});
	
	
	// ajax delete
	<?php if($cuc) : ?>
	jQuery('body').delegate('.del_pc_user', 'click', function() {
		var user_id = jQuery(this).attr('id').substr(5);
		var user_username = jQuery.trim( jQuery('#pguu_' + user_id).text());
		
		if(confirm('<?php _e('Do you really want to delete ', 'pc_ml') ?> ' + user_username + '?')) {
			jQuery(this).parents('tr').fadeTo(200, 0.45);

			var data = {
				action: 'delete_pc_user',
				pc_user_id: user_id,
				pc_nonce: '<?php echo wp_create_nonce('lcwp_ajax') ?>'
			};
			jQuery.post(ajaxurl, data, function(response) {
				if(jQuery.trim(response) != 'success'){
					alert(response);
					return false;	
				}
				
				jQuery('#pguu_' + user_id).parent().slideUp(function() {
					jQuery(this).remove();
					
					// decrease number in header
					jQuery('.subsubsub a').each(function() {
						if(jQuery(this).hasClass('current')) {
							var curr_num = jQuery(this).children('span').html();
							var new_num = parseInt(curr_num) - 1;	
							jQuery(this).children('span').html(new_num);
						}
					});
				});
			});	
		}
	});
	<?php endif; ?>
});
</script>
