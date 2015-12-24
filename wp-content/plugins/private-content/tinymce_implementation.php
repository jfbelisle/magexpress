<?php
// implement tinymce button

add_action('init', 'pc_action_admin_init');	
function pc_action_admin_init() {
	if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
		return;

	if ( get_user_option('rich_editing') == 'true') {
		add_filter( 'mce_external_plugins', 'pc_filter_mce_plugin');
		add_filter( 'mce_buttons', 'pc_filter_mce_button');
	}
}
	
function pc_filter_mce_button( $buttons ) {
	array_push( $buttons, '|', 'pc_btn' );
	return $buttons;
}

function pc_filter_mce_plugin( $plugins ) {
	if( (float)substr(get_bloginfo('version'), 0, 3) < 3.9) {
		$plugins['PrivateContent'] = PC_URL . '/js/tinymce_btn_old_wp.js';
	} else {
		$plugins['PrivateContent'] = PC_URL . '/js/tinymce_btn.js';	
	}
	return $plugins;
}



add_action('admin_footer', 'pc_editor_btn_content');
function pc_editor_btn_content() {
	global $current_screen;
	$user_categories = get_terms('pg_user_categories', 'orderby=name&hide_empty=0');

	if(
		strpos($_SERVER['REQUEST_URI'], 'post.php') || 
		strpos($_SERVER['REQUEST_URI'], 'post-new.php') || 
		$current_screen->id == 'privatecontent_page_pcma_settings' ||
		$current_screen->id == 'privatecontent_page_pc_settings' ||
		$current_screen->id == 'privatecontent_page_pcma_quick_mail'
	) :
	?>
    
    <div id="privatecontent-form" style="display:none;">
    	<div id="pc_sc_tabs">
        	
            <ul class="tabNavigation" id="pc_sc_tabs_wrap">
                <li><a href="#pc_sc_main"><?php _e('PrivateContent', 'pc_ml') ?></a></li>
                <li><a href="#pc_sc_reg"><?php _e('Registration', 'pc_ml') ?></a></li>
                <?php 
				// PC-ACTION - add tabs in shortcode wizard - must print html code in proper format 
				do_action('pc_tinymce_tabs_list');
				?>
            </ul> 
            
            <script type="text/javascript">
			// adjust tabs size
            jQuery(document).ready(function(e) {
            	if(jQuery('#pc_sc_tabs_wrap li').size() > 2) {
					var new_w = (100 / jQuery('#pc_sc_tabs_wrap li').size()) - 0.4;
					jQuery('#pc_sc_tabs_wrap li').css('width', new_w+'%');	
				}
            });
            </script>
        
            <div id="pc_sc_main">
                <table class="form-table pc_tinymce_table">
                    <tr class="tbl_last">
                      <td style="width: 50%; text-align: center;">
                        <input type="button" id="pg-loginform-submit" class="button-primary" value="<?php _e('Insert Login Form', 'pc_ml') ?>" name="submit" />
                      </td>
                      <td style="width: 50%; text-align: center;">
                        <input type="button" id="pg-logoutbox-submit" class="button-primary" value="<?php _e('Insert Logout Box', 'pc_ml') ?>" name="submit" />
                      </td>
                    </tr>
                </table>
                
                <hr />
                
                <table class="form-table pc_tinymce_table">
                    <tr class="tbl_last">
                      <td style="padding-bottom: 0px; font-size: 15px;" colspan="2">
                        <strong><?php _e('Private block', 'pc_ml') ?></strong>
                      </td>
                    </tr>
                    <tr>
                        <td colspan="2" id="pg-all-cats_wrap">
                        	<select name="pc_sc_type" id="pc_sc_type" class="lcweb-chosen" data-placeholder="<?php _e('Select an option', 'pc_ml') ?> .." autocomplete="off">
							  <option value="some"><?php _e('Content visible by one or more user categories', 'pc_ml') ?></option>
							  <option value="all"><?php _e('Content visible by all the categories', 'pc_ml') ?></option>
                              <option value="unlogged"><?php _e('Content visible by unlogged users', 'pc_ml') ?></option>
                            </select>
                        </td>
                    </tr>                
                    <tr id="pc_user_cats_row">
                        <td colspan="2" style="min-height: 100px;">
                            <label><?php _e('Choose the user categories allowed to view the content', 'pc_ml') ?></label>
							<select name="pc_sc_cats" id="pc_sc_cats" multiple="multiple" class="lcweb-chosen" data-placeholder="<?php _e('Select categories', 'pc_ml') ?> .." autocomplete="off">
							  <?php 
							  foreach ($user_categories as $ucat) {
								echo '<option value="'.$ucat->term_id.'">'.$ucat->name.'</option>';		
							  }	
							  ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label style="position: relative; bottom: -3px;"><?php _e('Hide warning box?', 'pc_ml') ?></label>
                        </td>
                        <td>    
                            <input type="checkbox" id="pg-hide-warning" name="pg-hide-warning" value="1"  class="ip_checks" autocomplete="off" />
                        </td>
                    </tr>
                    <tr class="tbl_last">
                        <td colspan="2">
                        	<div id="pg-text_wrap">
                                <label><?php _e('Custom message for not allowed users', 'pc_ml') ?></label>
                                <textarea id="pg-text" name="pg-text" style="height: 28px;"></textarea>
                            </div>
                            <br/>
                            <input type="button" id="pg-pvt-content-submit" class="button-primary" value="<?php _e('Insert', 'pc_ml') ?>" name="submit" />
                        </td>
                    </tr>
                </table>
            </div>
            
            
            <div id="pc_sc_reg">
                <table class="form-table pc_tinymce_table">
                    <tr>
                    	<td colspan="2">
                        	<label><?php _e('Which form to use?', 'pc_ml') ?></label>
                            <select name="pc_sc_rf_id" id="pc_sc_rf_id" class="lcweb-chosen" data-placeholder="<?php _e('Select an option', 'pc_ml') ?> .." autocomplete="off">
							  <?php 
							  $reg_forms = get_terms('pc_reg_form', 'hide_empty=0&order=DESC');
							  foreach($reg_forms as $rf) {
								  echo '<option value="'.$rf->term_id.'">'.$rf->name.'</option>';
							  }
							  ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                    	<td colspan="2">
                        	<label><?php _e('Layout', 'pc_ml') ?></label>
                            <select name="pc_sc_rf_layout" id="pc_sc_rf_layout" class="lcweb-chosen" data-placeholder="<?php _e('Select an option', 'pc_ml') ?> .." autocomplete="off">
								<option value="" selected="selected"><?php _e('Default one', 'pc_ml') ?></option>
                            	<option value="one_col"><?php _e('Single column', 'pc_ml') ?></option>
                				<option value="fluid"><?php _e('Fluid (multi column)', 'pc_ml') ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                    	<td colspan="2">
                        	<label><?php _e('Custom categories assignment (ignored if field is in form)', 'pc_ml') ?></label>
                            <select name="pc_sc_rf_cat" id="pc_sc_rf_cat" multiple="multiple" class="lcweb-chosen" data-placeholder="<?php _e('Select categories', 'pc_ml') ?> .." autocomplete="off">
							  <?php 
							  foreach ($user_categories as $ucat) {
								echo '<option value="'.$ucat->term_id.'">'.$ucat->name.'</option>';		
							  }	
							  ?>
                            </select>
                        </td>
                    </tr>
                    <tr class="tbl_last">
                    	<td colspan="2" >
                        	<label><?php _e('Custom redirect (use a valid URL)', 'pc_ml') ?></label>
                            <input type="text" name="pc_sc_rf_redirect" id="pc_sc_rf_redirect" value="" autocomplete="off" />
                            
                            <br/><br/>
                            <input type="button" id="pg-regform-submit" class="button-primary" value="<?php _e('Insert form', 'pc_ml') ?>" name="submit" />
                        </td>
                    </tr>
                </table>
            </div>
            
            
            
            <?php 
			// PC-ACTION - add tabbed content in shortcode wizard - must print html code in proper format 
			do_action('pc_tinymce_tab_contents');
			?>
		</div>
    </div>    
    
    
    <?php // SCRIPTS ?>
    <script src="<?php echo PC_URL; ?>/js/chosen/chosen.jquery.min.js" type="text/javascript"></script>
    <script src="<?php echo PC_URL; ?>/js/lc-switch/lc_switch.min.js" type="text/javascript"></script>
    
    <?php
	endif;
	return true;
}


