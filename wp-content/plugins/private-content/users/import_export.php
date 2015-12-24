<?php
include_once(PC_DIR . '/classes/pc_form_framework.php');	
include_once(PC_DIR . '/functions.php');	
global $pc_users;

//////////////////////////////////////// 
// IMPORT SCRIPT
if(isset($_POST['pc_import_users'])) {
	require_once(PC_DIR . '/users/import_script.php');
}

//////////////////////////////////////
// EXPORT SCRIPT
if(isset($_POST['pc_export_user_data'])) {
	require_once(PC_DIR . '/users/export_script.php');	
}
?>

<div class="wrap pc_form lcwp_form">  
	<div class="icon32" id="icon-pc_user_manage"><br></div>
    <?php echo '<h2 class="pc_page_title">' . __( 'Import & Export Users', 'pc_ml' ) . "</h2>"; ?>  
    <?php if(isset($error)) {echo $error;} ?>
    <?php if(isset($success)) {echo $success;} ?>
    
    
    <h3><?php _e('Import Users', 'pc_ml') ?></h3>
    <?php
	if(!ini_get('allow_url_fopen')) :
		echo '<div class="error"><p>' . __("Your server doesn't give the permissions to manage files. Please enable the fopen() function", 'pc_ml') .'</p></div>';
	else :
	?>
    <form method="post" class="form-wrap" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>" enctype="multipart/form-data">
    	<table class="widefat pc_table" style="margin-bottom: 15px;">
          <thead>
            <tr>  
              <th colspan="3"><?php _e('Import Options', 'pc_ml') ?></th>
            </tr>  
          </thead>
          <tbody>
            <tr>
               <td class="pc_label_td"><?php _e('CSV file', 'pc_ml'); ?></td>
               <td class="pc_field_td"><input type="file" name="pc_imp_file" value="" /></td>
               <td><span class="info"><?php _e('Select a valid CSV file containing users', 'pc_ml'); ?></span></td>
            </tr>
            <tr>
               <td class="pc_label_td"><?php _e("Fields Delimiter", 'pc_ml'); ?></td>
               <td class="pc_field_td">
                <?php (isset($fdata['pc_imp_separator']) && $fdata['pc_imp_separator']) ? $val = $fdata['pc_imp_separator'] : $val = ';'; ?>
                <input type="text" name="pc_imp_separator" value="<?php echo pc_sanitize_input($val); ?>" maxlength="1" style="text-align: center; width: 30px;" />
               </td>
               <td><span class="info"><?php _e('CSV fields delimiter (normally is ";")', 'pc_ml'); ?></span></td>
            </tr>
            <tr>
               <td class="pc_label_td"><?php _e("Enable private page?", 'pc_ml'); ?></td>
               <td class="pc_field_td">
                <?php (isset($fdata['pc_imp_pvt_page']) && $fdata['pc_imp_pvt_page']) ? $checked= 'checked="checked"' : $checked = ''; ?>
                <input type="checkbox" name="pc_imp_pvt_page" value="1" <?php echo $checked; ?> class="ip_checks" />
               </td>
               <td><span class="info"><?php _e('If checked, enable private page for imported users', 'pc_ml'); ?></span></td>
            </tr>
            <tr>
               <td class="pc_label_td"><?php _e("Default category for imported users", 'pc_ml'); ?></td>
               <td>
                  <select name="pc_imp_cat[]" class="lcweb-chosen" data-placeholder="<?php _e("Select a category", 'pc_ml'); ?> .." multiple="multiple" autocomplete="off" style="width: 98%;">
                      <?php
                      // all user categories
                      $user_categories = get_terms('pg_user_categories', 'orderby=name&hide_empty=0');
                      
                      foreach ($user_categories as $ucat) {
                        $sel = (isset($fdata['pc_imp_cat']) && in_array($ucat->term_id, (array)$fdata['pc_imp_cat'])) ? 'selected="selected"' : '';
						echo '<option value="'.$ucat->term_id.'" '.$sel.'>'.$ucat->name.'</option>';
                      }
                      ?>
                  </select> 
               </td>
               <td><span class="info"><?php _e("Choose category that will be assigned to imported users", 'pc_ml'); ?></span></td>
             </tr>
             <tr>
               <td class="pc_label_td"><?php _e("Ignore first row?", 'pc_ml'); ?></td>
               <td class="pc_field_td">
                <?php (isset($fdata['pc_imp_ignore_first']) && $fdata['pc_imp_ignore_first']) ? $checked= 'checked="checked"' : $checked = ''; ?>
                <input type="checkbox" name="pc_imp_ignore_first" value="1" <?php echo $checked; ?> class="ip_checks" />
               </td>
               <td><span class="info"><?php _e("If checked, ignore first CSV row (normally used for headings)", 'pc_ml'); ?></span></td>
             </tr>
             <tr>
               <td class="pc_label_td"><?php _e("Abort if errors are found?", 'pc_ml'); ?></td>
               <td class="pc_field_td">
                <?php (isset($fdata['pc_imp_error_stop']) && $fdata['pc_imp_error_stop']) ? $checked= 'checked="checked"' : $checked = ''; ?>
                <input type="checkbox" name="pc_imp_error_stop" value="1" <?php echo $checked; ?> class="ip_checks" />
               </td>
               <td><span class="info"><?php _e("If checked, abort import process whether an error is found", 'pc_ml'); ?></span></td>
            </tr>
            <tr>
               <td class="pc_label_td"><?php _e("Abort if duplicated are found?", 'pc_ml'); ?></td>
               <td class="pc_field_td">
                <?php (isset($fdata['pc_imp_existing_stop']) && $fdata['pc_imp_existing_stop']) ? $checked= 'checked="checked"' : $checked = ''; ?>
                <input type="checkbox" name="pc_imp_existing_stop" value="1" <?php echo $checked; ?> class="ip_checks" />
               </td>
               <td><span class="info">
               		<?php $masm = (defined('PCMA_DIR') && get_option('pcma_mv_duplicates')) ? ' '.__('or e-mail', 'pc_ml') : ''; ?>
			   		<?php echo __('If checked, abort import process whether a duplicated username', 'pc_ml') .$masm.' '. __('is found', 'pc_ml'); ?>
               </span></td>
            </tr>
            
            <?php
			// WP user sync - if is active add option to abort in case of no sync
			if($pc_users->wp_user_sync) :
			?>
			<tr>
               <td class="pc_label_td"><?php _e("Abort if wordpress sync fails?", 'pc_ml'); ?></td>
               <td class="pc_field_td">
                <?php $checked = (isset($fdata['pc_wps_error_stop']) && $fdata['pc_wps_error_stop']) ? 'checked="checked"' : ''; ?>
                <input type="checkbox" name="pc_wps_error_stop" value="1" <?php echo $checked; ?> class="ip_checks" />
               </td>
               <td><span class="info"><?php _e("If checked, abort import process whether a mirrored user already exists", 'pc_ml'); ?></span></td>
            </tr>
			<?php
			endif;

			// PC-ACTION - add fields in import form - must comply with table code
			do_action('pc_import_form');
			?>
          </tbody>
        </table>
       	
        <?php
		//// custom fields importing
		// PC-FILTER - allow custom fields to be imported - passes an array, use only registered field indexes
		$cust_fields = apply_filters('pc_import_custom_fields', array());
		
		if(count((array)$cust_fields)) :
			$f_fw = new pc_form;
			?>
			<table class="widefat pc_table pc_cfi_table" style="margin-bottom: 15px; max-width: 500px;">
              <thead>
                <tr>  
                  <th colspan="3">
                    <?php _e('Custom fields import', 'pc_ml') ?>  
                    <a href="javascript:void(0)" id="pc_cfi_btn" class="add_option add-opt-h3" style="margin-left: 10px;"><?php _e('add field', 'pc_ml') ?></a>
                  </th>
                </tr>  
              </thead>
              <tbody>
              <?php
              if(isset($_POST['pc_cfi_import'])) {
                $col_num = 7;
                
                foreach($_POST['pc_cfi_import'] as $cf) {
                    echo '
                    <tr>
                      <td style="width: 15px;"><span class="pc_del_field"></span></td>
                      <td style="width: 100px;">'. __('column', 'pc_ml').' <span class="pc_cfi_col_num">'. $col_num .'<span></td>
                      <td>
                      	<select name="pc_cfi_import[]" class="lcweb-chosen" data-placeholder="'. __('Select field', 'pc_ml') .'.." style="width: 95%;">';
        
						  foreach($cust_fields as $index) {
							  if(!isset($f_fw->fields[$index]) || in_array($index, $pc_users->fixed_fields)) {continue;}
							  
							  $sel = ($cf == $index) ? 'selected="selected"' : '';
							  echo '<option value="'.$index.'" '.$sel.'>'. $f_fw->fields[$index]['label'] .'</option>';	
						  }
        
                     echo '
                        </select>
                      </td>	
                    </tr>';	
                    
                    $col_num++;
                }
              }
              ?>
              </tbody>
            </table>
            
            <script type="text/javascript">
			jQuery(document).ready(function(e) {
				var init_col = 6; // start from 6+1 column - first 6 are for standard fields
				
				var fields_dd = 
				'<select name="pc_cfi_import[]" class="lcweb-chosen" data-placeholder="<?php _e('Select field', 'pc_ml') ?>.." style="width: 95%;">' +
				<?php
				foreach($cust_fields as $index) {
					if(!isset($f_fw->fields[$index]) || in_array($index, $pc_users->fixed_fields)) {continue;}
					echo '\'<option value="'.$index.'">'. str_replace("'", "\'", $f_fw->fields[$index]['label']) .'</option>\'+';	
				}
				?>
				'</select>';
				
				// add
				jQuery('body').delegate('#pc_cfi_btn', 'click', function(){
					var col_count = init_col + jQuery('.pc_cfi_table tbody tr').size();
					
					jQuery('.pc_cfi_table tbody').append(
					'<tr>' +
						'<td style="width: 15px;"><span class="pc_del_field"></span></td>' +	
						'<td style="width: 100px;"><?php _e('column', 'pcud_ml') ?> <span class="pc_cfi_col_num">'+ (col_count + 1) +'<span></td>' +
						'<td>'+ fields_dd +'</td>' +	
					'</tr>');
					
					pc_live_chosen();
				});
				
				// remove
				jQuery('body').delegate('.pc_cfi_table .pc_del_field', 'click', function(){
					jQuery(this).parents('tr').slideUp(200, function() {
						jQuery(this).remove();
					});
					
					// recalculate column number
					setTimeout(function() {
						var col_count = init_col;
						jQuery('.pc_cfi_table td .pc_cfi_col_num').each(function() {
							col_count++;
							jQuery(this).text(col_count);
						});
					}, 250);
				});
			});
			</script>
			<?php		
		endif; // end if custom fields import
		?>
        
        <input type="hidden" name="pc_nonce" value="<?php echo wp_create_nonce('lcwp_nonce') ?>" /> 
      	<input type="submit" name="pc_import_users" value="<?php _e('Import', 'pc_ml') ?>" class="button-primary" />  
    </form>
    <br />
    <?php endif; ?>
    
    
      
    
    <!-- ****************************************************************************** -->
    
      
    
    
    <h3><?php _e('Export Users', 'pc_ml') ?></h3>
    <form method="post" class="form-wrap" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>" target="_blank">
    	<table class="widefat pc_table" style="margin-bottom: 15px;">
          <thead>
            <tr>  
              <th colspan="2"><?php _e('Choose what to export', 'pc_ml') ?></th>
            </tr>  
          </thead>
          <tbody>
          <tr>
            <td class="pc_label_td"><?php _e("Users type" ); ?></td>
            <td class="pc_field_td">
            	<select name="users_type" class="lcweb-chosen" data-placeholder="<?php _e('Select an option', 'pc_ml') ?> .." autocomplete="off">
                  <option value="all"><?php _e('Any', 'pc_ml') ?></option>
                  <option value="actives"><?php _e('Only actives', 'pc_ml') ?></option>
                  <option value="disabled"><?php _e('Only disabled', 'pc_ml') ?></option>
                </select>
            </td>
          </tr>
          <tr>
            <td class="pc_label_td"><?php _e("Export as", 'pc_ml') ?></td>
            <td class="pc_field_td">
            	<select name="export_type" class="lcweb-chosen" data-placeholder="<?php _e('Select an option', 'pc_ml') ?> .." autocomplete="off">
                  <option value="excel"><?php _e('Excel', 'pc_ml') ?> (.xls)</option>
                  <option value="csv">CSV</option>
                </select>
            </td>
          </tr>
          <tr>
            <td class="pc_label_td" rowspan="2"><?php _e("Categories", 'pc_ml'); ?></td>
            <td class="pc_field_td" >
                <label style="display:inline; padding: 0 30px 0 0;"><?php _e('Export all?', 'pc_ml') ?></label>
                <input type="checkbox" name="pc_all_cats" id="pc_export_all_cat" value="all" class="ip_checks" autocomplete="off" />
            </td>
          </tr>
          <tr>
		  	<td class="pc_cat_lists">
                <select name="pc_categories[]" class="lcweb-chosen" data-placeholder="<?php _e('Select categories', 'pc_ml') ?> .." multiple="multiple" autocomplete="off" style="width: 90%;; max-width: 400px;">
                <?php
                $user_categories = get_terms('pg_user_categories', 'orderby=name&hide_empty=0');
                foreach ($user_categories as $ucat) {
                    echo '<option value="'.$ucat->term_id.'">'.$ucat->name.'</option>'; 
                }
                ?>
                </select>
            </td>
          </tr>
          </tbody>
        </table>
      
       <input type="hidden" name="pc_nonce" value="<?php echo wp_create_nonce('lcwp_nonce') ?>" /> 
       <input type="submit" name="pc_export_user_data" value="<?php _e('Export', 'pc_ml') ?>" class="button-primary" />  
    </form>
</div>  

<?php // SCRIPTS ?>
<script src="<?php echo PC_URL; ?>/js/lc-switch/lc_switch.min.js" type="text/javascript"></script>
<script src="<?php echo PC_URL; ?>/js/chosen/chosen.jquery.min.js" type="text/javascript"></script>

<script type="text/javascript" >
jQuery(document).ready(function($) {
	
	// lc switch
	jQuery('.ip_checks').lc_switch('YES', 'NO');
	
	// select/deselect all
	jQuery('body').delegate('#pc_export_all_cat', 'lcs-statuschange', function(){
		if( jQuery(this).is(':checked') ) {
			jQuery('.pc_cat_lists').slideUp();	
		}
		else {jQuery('.pc_cat_lists').slideDown();}
    });

	// chosen
	pc_live_chosen = function() {
		jQuery('.lcweb-chosen').each(function() {
			var w = jQuery(this).css('width');
			jQuery(this).chosen({width: w}); 
		});
		jQuery(".lcweb-chosen-deselect").chosen({allow_single_deselect:true});
	}
	pc_live_chosen();
});
</script>


