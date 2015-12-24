<?php
// PRIVATECONTENT INTEGRATIONS


// add tab
function pcud_sw_tab() {
	echo '<li><a href="#pcud_sc_wizard">'. __('User Data add-on', 'pc_ml') .'</a></li>';
}
add_action('pc_tinymce_tabs_list', 'pcud_sw_tab');


// contents
function pcud_sw_contents() {
	include_once(PCUD_DIR .'/functions.php');
	include_once(PC_DIR .'/classes/pc_form_framework.php');
	$f_fw = new pc_form;
	
	// get forms
	$forms = get_terms('pcud_forms', 'hide_empty=0');
	$form_fields = $f_fw->fields;
	?>
	<div id="pcud_sc_wizard">
        <table class="form-table pc_tinymce_table">
            <tr>
                <td style="padding-right: 0;"><?php _e('User data to display', 'pcud_ml') ?></td>
                <td>
                    <select name="pcud_fields_list" id="pcud_fields_list" class="lcweb-chosen f_type" data-placeholder="<?php _e('Select field', 'pcud_ml') ?> .." autocomplete="off">
                    <?php
                    foreach($form_fields as $field_id => $data) {
                    	if(!in_array($field_id, pcud_wizards_ignore_fields(true))) {
                       		echo '<option value="'.$field_id.'">'.$data['label'].'</option>';
                    	}
                    }
					?>		
                    </select>
                </td>
                <td><input type="button" id="pcud-user-data-submit" class="button-primary" value="<?php _e('Insert', 'pcud_ml') ?>" name="submit" /></td>
            </tr>
            
            <tr class="tbl_last">
              <td style="padding-bottom: 0px; font-size: 15px;" colspan="2">
              	<strong><?php _e('Custom form', 'pcud_ml') ?></strong>
              </td>
            </tr>
            <tr>
                <td style="padding-right: 0;"><?php _e('Custom form to use', 'pcud_ml') ?></td>
                <td colspan="2">
                    <select name="pcud_form_list" id="pcud_forms_list" class="lcweb-chosen f_type" data-placeholder="<?php _e('Select form', 'pcud_ml') ?> .." autocomplete="off">
                    <?php
                    foreach ($forms as $form) {
                    	echo '<option value="'.$form->term_id.'">'.$form->name.'</option>';
                    }
					?>	
                    </select>
                </td>
            </tr>
            <tr>
                <td><?php _e('Layout', 'pcud_ml') ?></td>
                <td>
                    <select name="pcud_form_layout" id="pcud_forms_layout" class="lcweb-chosen f_type" data-placeholder="<?php _e('Select an option', 'pcud_ml') ?> .." autocomplete="off">
                    	<option value="" selected="selected"><?php _e('Default one', 'pcud_ml') ?></option>
                        <option value="one_col"><?php _e('Single column', 'pcud_ml') ?></option>
                        <option value="fluid"><?php _e('Fluid (multi column)', 'pcud_ml') ?></option>
                    </select>
                </td>
                <td><input type="button" id="pcud-form-submit" class="button-primary" value="<?php _e('Insert form', 'pcud_ml') ?>" name="submit" /></td>
            </tr>
        </table>
        
        <table class="form-table pc_tinymce_table" style="margin-top: 0;">
            <tr class="tbl_last">
              <td style="padding-bottom: 0px; font-size: 14px;" colspan="2">
              	<strong><?php _e('Conditional block', 'pcud_ml') ?></strong>
              </td>
            </tr>
            <tr>
                <td><?php _e('Show content if', 'pcud_ml') ?></td>
                <td>
                    <select name="pcud_cb_field" id="pcud_cb_field" class="lcweb-chosen f_type" data-placeholder="<?php _e('Select field', 'pcud_ml') ?> .." autocomplete="off">
                        <?php
						foreach($form_fields as $field_id => $data) {
							if(!in_array($field_id, pcud_wizards_ignore_fields())) {
								echo '<option value="'.$field_id.'">'.$data['label'].'</option>';
							}
						}
						?>				
                    </select>
                </td>
            </tr>
            <tr class="tbl_last">
                <td>
                    <select name="pcud_cb_condition" id="pcud_cb_condition" class="lcweb-chosen f_type" data-placeholder="'. __('Select a condition', 'pcud_ml') .' .." style="width: 140px;">
                        <option value="=" ><?php _e('is equal to', 'pcud_ml') ?></option>
                        <option value="!=" ><?php _e('is different from', 'pcud_ml') ?></option>	
                        <option value="big" ><?php _e('is greater than', 'pcud_ml') ?></option>	
                        <option value="small" ><?php _e('is lower than', 'pcud_ml') ?></option>
                        <option value="like"><?php _e('contains', 'pcud_ml') ?></option>		
                    </select> 
                </td>
                <td>
                    <input type="text" name="pcud_cb_val" id="pcud_cb_val" value="" autocomplete="off" />
                </td>
            </tr>
            <tr class="tbl_last">
                <td colspan="2"><input type="button" id="pcud_cb_submit" class="button-primary" value="<?php _e('Insert block', 'pcud_ml') ?>" name="submit" /></td>
            </tr>
        </table>
    
    	<script type="text/javascript">
		jQuery(document).ready(function(e) {
			
			// [pcud-user-data] 
			jQuery('body').delegate("#pcud-user-data-submit", "click", function() {
				var fid = jQuery("#pcud_fields_list").val();
				
				if(fid != "") {
					var shortcode = '[pcud-user-data f="'+fid+'"]';
					tinyMCE.activeEditor.execCommand("mceInsertContent", 0, shortcode);
					tb_remove();	
				}
			});
			
			// [pcud-form] 
			jQuery('body').delegate("#pcud-form-submit", "click", function() {
				var fid = jQuery("#pcud_forms_list").val();
				
				if(fid != "") {
					
					// layout
					if(jQuery('#pcud_forms_layout').val()) {
						var f_layout = ' layout="'+ jQuery('#pcud_forms_layout').val() +'" ';	
					}
					else {var f_layout = '';}
					
					var shortcode = '[pcud-form form="'+fid+'"'+f_layout+']';
					tinyMCE.activeEditor.execCommand("mceInsertContent", 0, shortcode);
					tb_remove();	
				}
			});
			
			// [pcud-cond-block] 
			jQuery('body').delegate("#pcud_cb_submit", "click", function() {
				var fid = jQuery("#pcud_cb_field").val();
				var cond = jQuery("#pcud_cb_condition").val();
				var val = jQuery("#pcud_cb_val").val(); 
				val = val.replace(/"/g, '&quot;');
				
				if(fid != "") {
					var shortcode = '[pcud-cond-block f="'+ fid +'" cond="'+ cond +'" val="'+ val +'"][/pcud-cond-block]';
					tinyMCE.activeEditor.execCommand("mceInsertContent", 0, shortcode);
					tb_remove();	
				}
			});
		});
		</script>
	</div>
	<?php
}
add_action('pc_tinymce_tab_contents', 'pcud_sw_contents', 1, 3);


