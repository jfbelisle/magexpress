<?php 
include_once(PC_DIR . '/functions.php'); 
include_once(PCUD_DIR . '/functions.php'); 

$forbidden = pcud_index_ignore(); // forbidden fields
?>

<style type="text/css">
.pcud_fb_table {
	margin-bottom: 30px;	
}
.pcud_fb_table td {
	vertical-align: top !important;	
}
.pcud_fb_table th:first-child > *:not(input) {
	bottom: -4px;
    position: relative;	
}
.pcud_f_label {
	padding: 3px 5px !important;	
	min-width: 200px;
	width: 31%;
	font-weight: bold;
}
.pcud_f_index {
    margin-left: 4.5%;
}
.pcud_cmd > span {
    display: inline-block;
    margin: 0 3px -4px 7px !important;
}
.pcud_ec a:focus, .pcud_ec a:active {
	box-shadow: none;	
}

.pcud_field {
	-moz-box-sizing: border-box;
	box-sizing: border-box;	
	
	min-height: 75px;
	display: inline-block;
	width: 24.85%;
	min-width: 250px;
	padding: 8px 18px 14px;
    vertical-align: bottom;
	border-bottom: 1px solid #e5e5e5;
}
@media screen and (max-width: 1450px) { 
	.pcud_field {
		width: 49.8%;
	}
}
@media screen and (max-width: 1150px) { 
	.pcud_field {
		width: 100%;
	}
}
.pcud_field label {
	font-size: 12px;	
    width: 100%;
	-moz-box-sizing: border-box;
	box-sizing: border-box;
}
.pcud_field:not(.pcud_maxlength) label {
	padding-bottom: 8px;
}
.pcud_field input[type=text],
.pcud_field select,
.pcud_field textarea {
	width: 98%;	
}
.pcud_field textarea {
	height: 32px;	
}
.pcud_field input[type="text"] {
    padding: 6px 7px 5px;
}
.pcud_field .lcwp_slider_input {
	width: 50px;
	max-width: 50px;
}
.pcud_field .ui-slider {
	margin-top: 16px !important;
    width: 52% !important;
	margin-left: 3px !important;
}	
.pcud_field .lcs_wrap {
	margin-top: 6px;	
}
.pcud_num_range input {
	max-width: 55px;	
	text-align: center;
}
#pcud_new_f_create_index {
	position: relative;
    text-align: center;
    top: 20px;
    width: 115px;	
}
</style>

<div class="wrap pc_form lcwp_form" style="clear: both;">  
	<div class="icon32" id="icon-pc_user_manage"><br></div>
	<h2 class="pc_page_title">PrivateContent - <?php _e('Custom Fields Builder', 'pcud_ml') ?>
    	<a href="javascript:void(0)" class="add-new-h2" id="pcud_show_add_field"><?php _e('Add new field', 'pcud_ml')?></a>
    </h2>  
	
    
    <?php
	// HANDLE DATA
	if(isset($_POST['pcud_cf_submit'])) { 
		include(PC_DIR . '/classes/simple_form_validator.php');		
		
		$validator = new simple_fv;
		$indexes = array();
		
		$indexes[] = array('index'=>'pcud_f_id', 'label'=>__('Field ID', 'pcud_ml'), 'required'=>true, 'type'=>'int');
		$indexes[] = array('index'=>'pcud_f_index', 'label'=>__('Field Index', 'pcud_ml'), 'required'=>true, 'forbidden'=>$forbidden);
		$indexes[] = array('index'=>'pcud_f_label', 'label'=>__('Field Label', 'pcud_ml'), 'required'=>true);
		$indexes[] = array('index'=>'pcud_f_type', 'label'=>__('Field Type', 'pcud_ml'), 'required'=>true);
		$indexes[] = array('index'=>'pcud_f_subtype', 'label'=>__('Field Subtype', 'pcud_ml'));
		$indexes[] = array('index'=>'pcud_f_maxlen', 'label'=>__('Field max-length', 'pcud_ml'), 'type'=>'int', 'min_val'=>0, 'max_val'=>255);
		$indexes[] = array('index'=>'pcud_f_regex', 'label'=>'Regex custom validation');
		$indexes[] = array('index'=>'pcud_f_range_from', 'label'=>__('Allowed number from', 'pcud_ml'), 'type'=>'float', 'max_len'=>9);
		$indexes[] = array('index'=>'pcud_f_range_to', 'label'=>__('Allowed number to', 'pcud_ml'), 'type'=>'float', 'max_len'=>9);
		$indexes[] = array('index'=>'pcud_f_options', 'label'=>'Field options');
		$indexes[] = array('index'=>'pcud_f_placeh', 'label'=>'Field placeholder');
		$indexes[] = array('index'=>'pcud_f_multi_select', 'label'=>'Multi-select?');
		$indexes[] = array('index'=>'pcud_f_check_txt', 'label'=>'Checkbox text');
		$indexes[] = array('index'=>'pcud_f_disclaimer', 'label'=>'Use as disclaimer?');
		$indexes[] = array('index'=>'pcud_f_note', 'label'=>'Field note', 'max_len'=>255);
		$indexes[] = array('index'=>'pcud_fields_in_users_list', 'label'=>'Field in users list?');
		
		$validator->formHandle($indexes);
		$error = $validator->getErrors();
		$fdata = $validator->form_val;
		
		if($error) {echo '<div class="error"><p>'.$error.'</p></div>';}
		else {
			// clean data
			foreach($fdata as $key=>$val) {
				if(!is_array($val)) {
					$fdata[$key] = stripslashes($val);
				} else {
					$fdata[$key] = array();
					foreach($val as $arr_val) {$fdata[$key][] = stripslashes($arr_val);}
				}
			}

			// save 	
			update_option('pcud_custom_fields_order', (array)$fdata['pcud_f_index']);
			update_option('pcud_fields_in_users_list', (array)$fdata['pcud_fields_in_users_list']);
			
			// update terms
			foreach($fdata['pcud_f_index'] as $key => $fid) {
				$args = array(
					'type' 		=> $fdata['pcud_f_type'][$key],
					'subtype' 	=> $fdata['pcud_f_subtype'][$key],
					'maxlen'	=> $fdata['pcud_f_maxlen'][$key],
					'regex'		=> $fdata['pcud_f_regex'][$key],
					'range_from'=> $fdata['pcud_f_range_from'][$key],
					'range_to'	=> $fdata['pcud_f_range_to'][$key],
					'opt'		=> $fdata['pcud_f_options'][$key],
					'placeh'	=> $fdata['pcud_f_placeh'][$key],
					'multi_select'=> $fdata['pcud_f_multi_select'][$key], 
					'check_txt'	=> $fdata['pcud_f_check_txt'][$key], 
					'disclaimer'=> $fdata['pcud_f_disclaimer'][$key], 
					'note'		=> $fdata['pcud_f_note'][$key],
				);	
				
				wp_update_term($fdata['pcud_f_id'][$key], 'pcud_fields', array(
				  'name' => $fdata['pcud_f_label'][$key],
				  'description' => base64_encode(serialize($args))
				));
			}

			// WPML SYNC
			pcud_fields_wpml_sync($fdata);
			
			echo '<div class="updated"><p><strong>'. __('Fields saved', 'pcud_ml') .'</strong></p></div>';
		}
	}
	else {
		$fdata = array();
		$fdata['pcud_fields_in_users_list'] = (array)get_option('pcud_fields_in_users_list', array()); 	
	}

	
	$sorted_fields = pcud_get_sorted_fields();
	?>
    
    <div id="warning_wrap"></div>

    <form name="pcud_form" method="post" class="form-wrap" action="<?php echo admin_url() ?>admin.php?page=pcud_fields_builder">  

		<div id="pcud_add_f_wrap" style="display: none;">
            <h3 style="margin: 10px 0 0;"><?php _e('Add new field', 'pcud_ml') ?>
            	<small style="padding-left: 8px;">(<?php _e('field index must to be unique and cannot be changed', 'pcud_ml') ?>)</small>
            </h3>
            
            <table class="widefat pc_table pcud_fb_table" style="margin-bottom: 10px;">
              <tr><td style="padding: 0;">
                <div class="pcud_field">
                    <label><?php _e('Field label', 'pcud_ml') ?></label>
                    <input type="text" name="new_f_label" id="pcud_new_f_label" value="" autocomplete="off" />
                </div>  
                
                <div class="pcud_field">
                    <label><?php _e('Field index', 'pcud_ml') ?></label>
                    <input type="text" name="new_f_index" id="pcud_new_f_index" value="" placeholder="<?php _e('leave empty to auto-generate from label', 'pcud_ml') ?>" autocomplete="off" />
                </div>
                <div class="pcud_field">
                	<input type="text" id="pcud_new_f_create_index" class="button-secondary" value="<?php _e('generate index', 'pcud_ml'); ?>" />
                </div>
             </td></tr>
            </table> 
        
       		<input type="button" name="pcud_add_field" id="pcud_add_field" value="<?php _e('Add Field', 'pcud_ml') ?>" class="button-primary" style="margin-bottom: 15px;" />
            <span id="pcud_add_f_response" style="padding-left: 20px;"></span>
        </div>    


        <h3><?php _e('Custom Fields', 'pcud_ml') ?></h3>  
        <div id="pcuf_cf_wrap">
			<?php 
            foreach($sorted_fields as $term) :
                $val = unserialize(base64_decode($term->description));	
				
				// stored values manag for old versions data (from v1.5)				
				if(!isset($val['regex'])) {
					$val['regex'] = '';
					$val['range_from'] = 0; 
					$val['range_to'] = 100; 
					$val['check_txt'] = ''; 
					$val['multi_select'] = ''; 
					$val['disclaimer'] = ''; 
					
					if(!isset($val['placeh'])) {$val['placeh'] = '';}
				}
            ?>
                <table class="widefat pc_table pcud_fb_table" f_id="<?php echo $term->term_id ?>" f_slug="<?php echo $term->slug ?>">
                  <thead>
                  <tr>
                    <th>
                    	<input type="hidden" name="pcud_f_id[]" value="<?php echo $term->term_id; ?>" />
                        <input type="hidden" name="pcud_f_index[]" value="<?php echo $term->slug; ?>" />
                        <input type="text" name="pcud_f_label[]" class="pcud_f_label" value="<?php echo pc_sanitize_input($term->name); ?>" maxlength="255" placeholder="<?php _e('field label', 'pcud_ml') ?>" autocomplete="off" />
                        
                        <small class="pcud_f_index"><?php _e('field index', 'pcud_ml') ?>: <em><?php echo $term->slug ?></em></small>
                    </th>
                    <th style="text-align: right;">
                        <div class="pcud_cmd">
                            <small class="pcud_ec">
                                ( <a href="javascript:void(0)" class="collapse"><?php _e('collapse', 'pcud_ml') ?></a>
    <a href="javascript:void(0)" style="display: none;"><?php _e('expand', 'pcud_ml') ?></a> )
                            </small>
                            <span class="pc_move_field"></span>
                            <span class="pc_del_field pcud_del_field"></span>
                        </div>
                    </th>
                  </tr>
                  </thead>
                  
                  <tbody>
                    <tr>
                    	<td colspan="2" style="padding: 0;">
                        	<div class="pcud_field">
                                <label><?php _e('Field type', 'pcud_ml') ?></label>
                                
                                <select name="pcud_f_type[]" class="lcweb-chosen f_type" data-placeholder="<?php _e('Select type', 'pcud_ml') ?> .." autocomplete="off">
                                  <?php 
                                  foreach(pcud_field_types() as $key => $opt) {
                                      $sel = ($val['type'] == $key) ? 'selected="selected"' : '';
                                      echo '<option value="'.$key.'" '.$sel.'>'.$opt.'</option>';	
                                  }
                                  ?>
                                </select>
                            </div>
                            
  
                            <div class="pcud_field pcud_subtype" <?php if($val['type'] != 'text') {echo 'style="display: none;"';} ?>>
                                <label><?php _e('Subtype', 'pcud_ml') ?></label>
                              
                                <select name="pcud_f_subtype[]" class="lcweb-chosen f_subtype" data-placeholder="<?php _e('Select subtype', 'pcud_ml') ?> .." autocomplete="off">
                                  <?php 
                                  foreach(pcud_field_subtypes() as $key=>$opt) {
                                      $sel = ($val['subtype'] == $key) ? 'selected="selected"' : '';
                                      echo '<option value="'.$key.'" '.$sel.'>'.$opt.'</option>';	
                                  }
                                  ?>
                                </select>
                            </div>
                            
                            
                            <div class="pcud_field pcud_maxlength" <?php if($val['type'] == 'text' && empty($val['subtype'])) {} else {echo 'style="display: none;"';} ?>>
                                <label><?php _e('Max length', 'pcud_ml') ?></label>
								
                                <div class="lcwp_slider" step="1" max="255" min="1"></div>
                                <input type="text" value="<?php echo (int)$val['maxlen']; ?>" name="pcud_f_maxlen[]" class="lcwp_slider_input" autocomplete="off" />
                                <span>chars</span>
                            </div>
                            
                            
                            <div class="pcud_field pcud_regex" <?php if(!in_array($val['type'], array('text', 'textarea'))) {echo 'style="display: none;"';} ?>>
                                <label><?php _e('Custom regexp validation', 'pcud_ml') ?></label>
                                <input type="text" name="pcud_f_regex[]" value="<?php echo pc_sanitize_input($val['regex']) ?>" autocomplete="off" />
                            </div>  
                            
                            
                            <div class="pcud_field pcud_num_range" <?php if($val['type'] == 'text' && in_array($val['subtype'], array('int', 'float'))) {} else {echo 'style="display: none;"';} ?>>
                                <label><?php _e('Allowed values', 'pcud_ml') ?></label>
								
                                <span style="padding-right: 5px;"><?php _e('From', 'pcud_ml') ?></span> 
                                <input type="text" name="pcud_f_range_from[]" value="<?php echo $val['range_from'] ?>" autocomplete="off" />
                                <span style="padding: 0 5px;"><?php _e('to', 'pcud_ml') ?></span> 
                                <input type="text" name="pcud_f_range_to[]" value="<?php echo $val['range_to'] ?>" autocomplete="off" />
                            </div>
                            
                          
                            <div class="pcud_field pcud_options" <?php if(!in_array($val['type'], array('select', 'checkbox'))) {echo 'style="display: none;"';} ?>>
                                <label><?php _e('Options (comma split)', 'pcud_ml') ?></label>
                                <textarea name="pcud_f_options[]" rows="1" autocomplete="off"><?php echo $val['opt'] ?></textarea>
                            </div>
                                
                             
                            <div class="pcud_field pcud_f_multi_select" <?php if($val['type'] != 'select') {echo 'style="display: none;"';} ?>>
								<label><?php _e('Multi-choice?', 'pcud_ml') ?></label>
                              	 <select name="pcud_f_multi_select[]" class="lcweb-chosen" data-placeholder="<?php _e('Select an option', 'pcud_ml') ?> .." autocomplete="off" style=" width: 80px;">
                                 	<option value=""><?php _e('no', 'pcud_ml') ?></option>
								    <option value="1" <?php if(!empty($val['multi_select'])) {echo 'selected="selected"';} ?>><?php _e('yes', 'pcud_ml') ?></option>
                                </select>
                            </div>     
                                
                                
                            <div class="pcud_field pcud_placeh" <?php if(!in_array($val['type'], array('text', 'textarea'))) {echo 'style="display: none;"';} ?>>
                                <label><?php _e('Field Placeholder', 'pcud_ml') ?></label>
                                <input type="text" name="pcud_f_placeh[]" value="<?php echo pc_sanitize_input($val['placeh']) ?>" maxlength="200" autocomplete="off" />
                            </div>    
                                
                                
                             <div class="pcud_field pcud_f_check_txt" <?php if($val['type'] != 'single_checkbox') {echo 'style="display: none;"';} ?>>
								<label><?php _e('Checkbox text', 'pcud_ml') ?></label>
                                <textarea name="pcud_f_check_txt[]" rows="1" placeholder="<?php _e('Text used in forms (supports HTML)', 'pcud_ml') ?>" autocomplete="off"><?php echo $val['check_txt'] ?></textarea>
                            </div>   
                            
                            
                            <div class="pcud_field pcud_f_disclaimer" <?php if($val['type'] != 'single_checkbox') {echo 'style="display: none;"';} ?>>
								<label><?php _e('Use as disclaimer?', 'pcud_ml') ?></label>
                              	 <select name="pcud_f_disclaimer[]" class="lcweb-chosen" data-placeholder="<?php _e('Select an option', 'pcud_ml') ?> .." autocomplete="off" style=" width: 80px;">
                                 	<option value=""><?php _e('no', 'pcud_ml') ?></option>
								    <option value="1" <?php if(!empty($val['disclaimer'])) {echo 'selected="selected"';} ?>><?php _e('yes', 'pcud_ml') ?></option>
                                </select>
                            </div> 
                            
                                
                            <div class="pcud_field">
								<label><?php _e('Internal note', 'pcud_ml') ?></label>
                                <textarea name="pcud_f_note[]" rows="1" autocomplete="off"><?php echo $val['note'] ?></textarea>
                            </div>
                            
                            <div class="pcud_field">
                            	<label><?php _e('Show in users list?', 'pcud_ml') ?></label>
                                
                                <?php $checked = (in_array($term->slug, (array)$fdata['pcud_fields_in_users_list'])) ? 'checked="checked"' : ''; ?>
            					<input type="checkbox" name="pcud_fields_in_users_list[]" value="<?php echo $term->slug ?>" <?php echo $checked; ?> class="ip_checks" />
                            </div>
                        </td>
                    </tr>
                  </tbody>	
                </table>
                            
            <?php endforeach; ?>
            
        </div>
        <input type="submit" name="pcud_cf_submit" value="<?php _e('Update Fields', 'pcud_ml' ) ?>" id="pcud_cf_submit" class="button-primary" />  
    </form>
</div> 

<?php // SCRIPTS ?>
<script src="<?php echo PC_URL; ?>/js/lc-switch/lc_switch.min.js" type="text/javascript"></script>
<script src="<?php echo PC_URL; ?>/js/chosen/chosen.jquery.min.js" type="text/javascript"></script>

<script type="text/javascript">
jQuery(document).ready(function($) {
	var pcud_is_acting = false;
	
	// in case of successful reply - scroll to latest answer
	<?php if(isset($_GET['pcud_add_ok'])) : ?>
	var last_field_top_pos = jQuery('.pcud_fb_table').last().offset().top
	jQuery('html, body').animate({'scrollTop': last_field_top_pos - 15}, 600);
	<?php endif; ?>
	
	
	// show add-field box
	jQuery('body').delegate('#pcud_show_add_field', 'click', function() {
		if(jQuery('#pcud_add_f_wrap').is(':hidden')) {
			jQuery('#pcud_add_f_wrap').slideDown();	
			jQuery(this).fadeOut();
		}
	});
	
	
	// generate index via ajax
	jQuery('body').delegate('#pcud_new_f_create_index', 'click', function() {
		if(!pcud_is_acting) {
			var val = jQuery.trim( jQuery('#pcud_new_f_label').val());
			if(!val) {
				alert("<?php _e('Insert something in label field', 'pcud_ml') ?>");
				return false;	
			}
			
			jQuery(this).attr('disabled', 'disabled');
			pcud_is_acting = true;
			
			var data = {
				action: 'pcud_generate_index',
				f_label: val,
				pcud_nonce: '<?php echo wp_create_nonce('lcwp_ajax') ?>'
			};
			jQuery.post(ajaxurl, data, function(response) {
				jQuery('#pcud_new_f_index').val(response);
				jQuery('#pcud_new_f_create_index').removeAttr('disabled');
				
				pcud_is_acting = false;
			});
		}
	});
		
	
	
	// delete field
	jQuery('body').delegate('.pcud_del_field', 'click', function() {
		if(confirm('<?php echo addslashes( __('Removing the field ANY related user data will be lost DEFINITIVELY, continue?', 'pcud_ml')) ?>') ) {
			
			var $subj = jQuery(this).parents('table');
			var f_id = $subj.attr('f_id');
			var f_slug = $subj.attr('f_slug');
			
			$subj.animate({opacity : 0.7}, 400);
			jQuery('#pcud_cf_submit').attr('disabled', 'disabled');
			
			var data = {
				action: 'pcud_del_field',
				f_id: f_id,
				f_slug: f_slug,
				pcud_nonce: '<?php echo wp_create_nonce('lcwp_ajax') ?>'
			};
			jQuery.post(ajaxurl, data, function(response) {
				if(jQuery.trim(response) == 'success') {
					$subj.fadeOut(function() {
						jQuery(this).remove();
					});
				}
				else {alert(response);}
				
				jQuery('#pcud_cf_submit').removeAttr('disabled');
			});
		}
	});
	
	
	// add new field 
	jQuery('#pcud_add_field').click(function() {
		var f_label = jQuery.trim( jQuery('#pcud_new_f_label').val() );
		var f_index = jQuery.trim( jQuery('#pcud_new_f_index').val() );
		
		if(!pcud_is_acting && f_label) {
			pcud_is_acting = true;
			jQuery('#pcud_add_f_response').html('<i class="pc_loading" style="bottom: -6px; position: relative;"></i>');
				
			var data = {
				action: 'pcud_add_field',
				f_label: f_label,
				f_index: f_index,
				pcud_nonce: '<?php echo wp_create_nonce('lcwp_ajax') ?>'
			};
			jQuery.post(ajaxurl, data, function(response) {
				if(jQuery.trim(response) == 'success') {
					jQuery('#pcud_add_f_response').html("<?php _e('Field added successfully!', 'pcud_ml'); ?>");
					
					setTimeout(function() {
						window.location.href = "<?php echo admin_url() ?>admin.php?page=pcud_fields_builder&pcud_add_ok";
					}, 1000);
				}
				else {
					jQuery('#pcud_add_f_response').html(response);
				}
				
				pcud_is_acting = false;
				jQuery('#pcud_cf_submit').removeAttr('disabled');
			});
		}
	});
	
	
	// collapse fields
	jQuery('body').delegate('.pcud_ec a', 'click', function() {
		if(jQuery(this).parent().find('.collapse').is(':hidden')) {
			jQuery(this).parent().find('.collapse').show();
			jQuery(this).parent().find('a:not(.collapse)').hide();
			jQuery(this).parents('table').find('tbody').slideDown();	
		}
		else {
			jQuery(this).parent().find('.collapse').hide();
			jQuery(this).parent().find('a:not(.collapse)').show();
			jQuery(this).parents('table').find('tbody').slideUp();	
		}
	});
	
	
	// toggles based on type + subtype
	jQuery('body').delegate('.f_type, .f_subtype', 'change', function() {
		$parent = jQuery(this).parents('table');
		
		var type = $parent.find('.f_type').val();
		var subtype = $parent.find('.f_subtype').val();
		
		// subtype by type
		if(type == 'text') {
			$parent.find('.pcud_subtype').fadeIn('fast');
		} else {
			$parent.find('.pcud_subtype').hide();
		}
		
		// max-length
		if(
			(type != 'text' && type != 'textarea') || 
			(type == 'text' && subtype != '')
		) {
			$parent.find('.pcud_maxlength').hide();	
		}
		else {
			$parent.find('.pcud_maxlength').fadeIn('fast');	
		}
		
		// num range
		if(type != 'text' || jQuery.inArray(subtype, ['int', 'float']) == -1) {
			$parent.find('.pcud_num_range').hide();	
		} else {
			$parent.find('.pcud_num_range').fadeIn('fast');	
		}
				
		// regex by type
		if(type == 'text' || type == 'textarea') {
			$parent.find('.pcud_regex').fadeIn('fast');
		} else {
			$parent.find('.pcud_regex').hide();
		}
		
		// options by type
		if(jQuery.inArray(type, ['select', 'checkbox']) == -1) {
			$parent.find('.pcud_options').hide();
		} else {
			$parent.find('.pcud_options').fadeIn('fast');
		}
		
		// multi-select by type
		if(type != 'select') {
			$parent.find('.pcud_f_multi_select').hide();
		} else {
			$parent.find('.pcud_f_multi_select').fadeIn('fast');
		}
		
		// placeholder by type
		if(jQuery.inArray(type, ['text', 'textarea']) == -1) {
			$parent.find('.pcud_placeh').hide();
		} else {
			$parent.find('.pcud_placeh').fadeIn('fast');
		}
		
		// single checkbox/disclaimer text by type
		if(type != 'single_checkbox') {
			$parent.find('.pcud_f_check_txt, .pcud_f_disclaimer').hide();
		} else {
			$parent.find('.pcud_f_check_txt, .pcud_f_disclaimer').fadeIn('fast');
		}
	});
	
	
	//////////////////////////////////////////////////////////
	
	// sort rows
	function pcud_live_sort() {
		jQuery("#pcuf_cf_wrap").sortable({
			handle: '.pc_move_field',
			items:  '.pcud_fb_table'  
		});
		jQuery( "#pcuf_cf_wrap .pc_move_field" ).disableSelection();
	}
	pcud_live_sort();
	
	
	// lc switch
	var pc_live_checks = function() { 
		jQuery('.ip_checks').lc_switch('YES', 'NO');
	}
	pc_live_checks();
	
	// chosen
	var pc_live_chosen = function() { 
		jQuery('.lcweb-chosen').each(function() {
			var w = jQuery(this).css('width');
			jQuery(this).chosen({width: w}); 
		});
		jQuery(".lcweb-chosen-deselect").chosen({allow_single_deselect:true});
	}
	pc_live_chosen();
	
	// sliders
	pcud_slider_opt = function() {
		var a = 0; 
		$('.lcwp_slider').each(function(idx, elm) {
			var sid = 'slider'+a;
			jQuery(this).attr('id', sid);	
		
			svalue = parseInt(jQuery("#"+sid).next('input').val());
			minv = parseInt(jQuery("#"+sid).attr('min'));
			maxv = parseInt(jQuery("#"+sid).attr('max'));
			stepv = parseInt(jQuery("#"+sid).attr('step'));
			
			jQuery('#' + sid).slider({
				range: "min",
				value: svalue,
				min: minv,
				max: maxv,
				step: stepv,
				slide: function(event, ui) {
					jQuery('#' + sid).next().val(ui.value);
				}
			});
			jQuery('#'+sid).next('input').change(function() {
				var val = parseInt(jQuery(this).val());
				var minv = parseInt(jQuery("#"+sid).attr('min'));
				var maxv = parseInt(jQuery("#"+sid).attr('max'));
				
				if(val <= maxv && val >= minv) {
					jQuery('#'+sid).slider('option', 'value', val);
				}
				else {
					if(val <= maxv) {jQuery('#'+sid).next('input').val(minv);}
					else {jQuery('#'+sid).next('input').val(maxv);}
				}
			});
			
			a = a + 1;
		});
	}
	pcud_slider_opt();
});
</script>