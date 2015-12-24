<style type="text/css">
#pcud_form_builder_top .pc_table {
	border: none !important;
	margin: 0;	
}
#pcud_form_builder_top td {
	background-color: #fff !important;
	border: none;
}
.pcud_form_content > h3 {
	border: medium none !important;
    margin: 5px 0 4px !important;	
}
.pcud_del_field  {
	background: url("<?php echo PCUD_URL; ?>/img/del_icons_small.png") no-repeat scroll left bottom transparent;
    cursor: pointer;
    display: block;
    height: 15px;
    margin: auto;
    width: 15px;		
}
.pcud_del_field:hover {
	background: url("<?php echo PCUD_URL; ?>/img/del_icons_small.png") no-repeat scroll left top transparent;
}

.chosen-container {
	min-width: 210px;	
}
.chosen-drop {
	min-width: 208px;	
}
.chosen-search input {
	min-width: 180px;	
}

#man_form_box .misc-pub-section-last {
    border-bottom: 1px solid #DFDFDF;
    padding: 8px 0;
}
#man_form_box .misc-pub-section-last:last-child {
	border: none;	
}
#pcud_form_table {
	margin-bottom: 20px;	
}
#pcud_form_table textarea {
	width: 51%;
	min-width: 300px;
	height: 28px;	
}
#save_form_box {
	box-shadow: none;
}
</style>


<div class="wrap pc_form lcwp_form">  
	<div class="icon32" id="icon-pc_user_manage"><br></div>
	<?php echo '<h2 class="pc_page_title">PrivateContent - ' . __('Custom Forms Builder', 'pcud_ml') . "</h2>"; ?>   

	<div id="ajax_mess"></div>
	
    <div id="poststuff" class="metabox-holder has-right-sidebar" style="overflow: visible;">
    	
        <?php // SIDEBAR ?>
        <div id="side-info-column" class="inner-sidebar">
          <form class="form-wrap">	
           
            <div id="add_grid_box" class="postbox lcwp_sidebox_meta">
            	<h3 class="hndle"><?php _e('Add Form', 'pcud_ml') ?></h3> 
				<div class="inside">
                  <div class="misc-pub-section-last">
					<label style="padding-right: 0;"><?php _e('Form Name', 'pcud_ml') ?></label>
                	<input type="text" name="pcud_cells_margin" value="" id="add_form" maxlenght="100" style="width: 180px;" />
                    <input type="button" name="add_form_btn" id="add_form_btn" value="<?php _e('Add', 'pcud_ml') ?>" class="button-primary" style="margin-left: 5px;" />
                  </div>  
                </div>
            </div>
            
            <div id="man_form_box" class="postbox lcwp_sidebox_meta">
            	<h3 class="hndle"><?php _e('Form List', 'pcud_ml') ?></h3> 
				<div class="inside"></div>
            </div>
            
            <div id="save_form_box" class="postbox lcwp_sidebox_meta" style="display: none; background: none; border: none;">
            	<input type="button" name="save-form" value="<?php _e('Save form', 'pcud_ml') ?>" class="button-primary" />
                <div style="width: 30px; padding: 0 0 0 7px; float: right;"></div>
            </div>
          </form>	
            
        </div>
    	
        <?php // PAGE CONTENT ?>
        <form class="form-wrap" id="form_items_list">  
          <div id="post-body">
          <div id="post-body-content" class="pcud_form_content">
              <p><?php _e('Select a form', 'pcud_ml') ?> ..</p>
          </div>
          </div>
        </form>
        
        <br class="clear">
    </div>
    
</div>  

<?php // SCRIPTS ?>
<script src="<?php echo PC_URL; ?>/js/lc-switch/lc_switch.min.js" type="text/javascript"></script>
<script src="<?php echo PC_URL; ?>/js/chosen/chosen.jquery.min.js" type="text/javascript"></script>

<script type="text/javascript" charset="utf8" >
jQuery(document).ready(function($) {
	  
	// selected form vars
	var is_acting = false;
	pcud_sel_form = 0;	
	pcud_load_forms();

	// add field to form
	jQuery('body').delegate('#add_field_btn > input', 'click', function() {
		var f_val = jQuery('#pcud_fields_list').val();
		var f_name = jQuery('#pcud_fields_list option[value="'+ f_val +'"]').text();
		
		if(f_val != 'custom|||text' && jQuery('#pcud_form_table tr[rel="'+ f_val +'"]').size()) {
			alert("<?php _e('Field already in the form', 'pcud_ml') ?>");
			return false;	
		}
		
		var required = (f_val == 'psw') ? 'checked="checked"' : '';
		var disabled = (f_val == 'psw') ? 'disabled="disabled"' : ''; 
		
		if(f_val == 'custom|||text') {
			var code = 
			'<td colspan="2">'+
				'<input type="hidden" name="pcud_include_field[]" value="'+ f_val +'" class="pcud_incl_f" />'+
				'<textarea name="pcud_form_texts[]" placeholder="<?php _e('Supports HTML and shortcodes', 'pc_ml') ?>"></textarea>'+
			'</td>';
		}
		else {
			var code = 
			'<td>'+
				'<input type="hidden" name="pcud_include_field[]" value="'+ f_val +'" class="pcud_incl_f" />'+
				f_name +
			'</td>'+
			'<td>'+
				'<input type="checkbox" name="pcud_require_field[]" value="'+ f_val +'" '+required+' '+disabled+' class="ip_checks pcud_req_f" autocomplete="off" />'+
			'</td>';	
		}
		
		jQuery('#pcud_form_table').first().find('tbody').append(
		'<tr rel="'+ f_val +'">'+
			'<td><span class="pc_del_field" title="<?php _e('remove field', 'pcud_ml') ?>"></span></td>'+
			'<td><span class="pc_move_field" title="<?php _e('sort field', 'pcud_ml') ?>"></span></td>'+
			code +
		'</tr>');
		
		pcud_live_ip_checks();
	});
	

	// update form structure 
	jQuery('body').delegate('#save_form_box input', 'click', function() {
		if(is_acting) {return false;}
		
		is_acting = true;
		jQuery('#save_form_box div').html('<span class="pc_loading"></span>');
		
		// create fields + required array
		var included = jQuery.makeArray();
		var required = jQuery.makeArray();
		var texts 	= jQuery.makeArray();
		
		jQuery('#pcud_form_table tbody tr').each(function(i,v) {
        	var f = jQuery(this).find('.pcud_incl_f').val();
		    included.push(f);
			
			if(f == 'custom|||text') {
				texts.push( jQuery(this).find('textarea').val() );	
			}
			else {
				if( jQuery(this).find('.pcud_req_f').is(':checked') ) {
					required.push(f);	
				}
			}
        });
		
		var data = {
			action: 'pcud_save_form',
			form_id: pcud_sel_form,
			fields_included: included,
			fields_required: required,
			texts: texts,
			redirect: jQuery('#pcud_redirect').val(),
			cust_redir: jQuery('#pcud_cust_redir_wrap input').val(),
			pcud_nonce: '<?php echo wp_create_nonce('lcwp_ajax') ?>'
		};
		jQuery.post(ajaxurl, data, function(response) {
			jQuery('#save_form_box div').empty();
			is_acting = false;
			
			if(jQuery.trim(response) == 'success') {
				jQuery('#ajax_mess').empty().append('<div class="updated"><p><strong><?php echo addslashes( __('Form saved', 'pcud_ml')) ?></strong></p></div>');	
				pcud_hide_wp_alert();
			}
			else {
				jQuery('#ajax_mess').empty().append('<div class="error"><p>'+resp+'</p></div>');
			}
			
		});	
	});
	
	
	
	// delete field
	jQuery('body').delegate('.pc_del_field', 'click', function() {
		if(confirm("<?php _e('Do you want to remove this field?', 'pcud_ml') ?>")) {
			jQuery(this).parents('tr').slideUp(function(){
				jQuery(this).remove();
			});	
		}
	});
		
	
	// select form
	jQuery('body').delegate('#man_form_box input[type=radio]', 'click', function() {
		pcud_sel_form = parseInt(jQuery(this).val());
		var form_title = jQuery(this).parent().siblings('.pcud_form_tit').text();

		jQuery('.pcud_form_content').html('<div style="height: 30px;" class="lcwp_loading"></div>');

		var data = {
			action: 'pcud_form_builder',
			form_id: pcud_sel_form 
		};
		
		jQuery.post(ajaxurl, data, function(response) {
			jQuery('.pcud_form_content').html(response);
			
			// saveform box
			jQuery('#save_form_box').fadeIn();
			
			pcud_live_chosen();
			pcud_live_ip_checks();
			pcud_live_sort();
		});	
	});
	
	
	// add form
	jQuery('#add_form_btn').click(function() {
		var form_name = jQuery('#add_form').val();
		
		if( jQuery.trim(form_name) != '' ) {
			var data = {
				action: 'pcud_add_form',
				form_name: form_name
			};
			
			jQuery.post(ajaxurl, data, function(response) {
				var resp = jQuery.trim(response); 
				
				if(resp == 'success') {
					jQuery('#ajax_mess').empty().append('<div class="updated"><p><strong><?php echo addslashes( __('Form added', 'pcud_ml')) ?></strong></p></div>');	
					jQuery('#add_form').val('');
					
					pcud_load_forms();
					pcud_hide_wp_alert();
				}
				else {
					jQuery('#ajax_mess').empty().append('<div class="error"><p>'+resp+'</p></div>');
				}
			});	
		}
	});
	
	
	// load forms list
	function pcud_load_forms() {
		jQuery('#man_form_box .inside').html('<div style="height: 30px;" class="lcwp_loading"></div>');
		
		jQuery.ajax({
			type: "POST",
			url: ajaxurl,
			data: "action=pcud_get_forms",
			dataType: "json",
			success: function(response){	
				jQuery('#man_form_box .inside').empty();
				
				var a = 0;
				jQuery.each(response, function(k, v) {	
					if( pcud_sel_form == v.id) {var sel = 'checked="checked"';}
					else {var sel = '';}
				
					jQuery('#man_form_box .inside').append('<div class="misc-pub-section-last">\
						<span><input type="radio" name="gl" value="'+ v.id +'" '+ sel +' /></span>\
						<span class="pcud_form_tit" title="ID #'+v.id+'" style="padding-left: 7px;">'+ v.name +'</span>\
						<span class="pcud_del_form" id="fdel_'+ v.id +'"></span>\
					</div>');
					
					a = a + 1;
				});
				
				if(a == 0) {jQuery('#man_form_box .inside').html('<p><?php echo addslashes( __('No existing forms', 'pcud_ml')) ?></p>');}
			}
		});	
	}
	
	
	// delete form
	jQuery('body').delegate('.pcud_del_form', 'click', function() {
		$target_form_wrap = jQuery(this).parent(); 
		var form_id  = jQuery(this).attr('id').substr(5);
		
		if(confirm('<?php echo addslashes( __('Delete definitively the form?', 'pcud_ml')) ?>')) {
			var data = {
				action: 'pcud_del_form',
				form_id: form_id
			};
			
			jQuery.post(ajaxurl, data, function(response) {
				var resp = jQuery.trim(response); 
				
				if(resp == 'success') {
					// if is this one opened
					if(pcud_sel_form == form_id) {
						jQuery('.pcud_form_content').html('<p><?php echo addslashes( __('Select a form', 'pcud_ml')) ?> ..</p>');
						pcud_sel_form = 0;
						
						// saveform box
						jQuery('#save_form_box').fadeOut();
					}
					
					$target_form_wrap.slideUp(function() {
						jQuery(this).remove();
						
						if( jQuery('#man_form_box .inside .misc-pub-section-last').size() == 0) {
							jQuery('#man_form_box .inside').html('<p><?php echo addslashes( __('No existing forms', 'pcud_ml')) ?></p>');
						}
					});	
				}
				else {alert(resp);}
			});
		}
	});
	
	
	// form redirect - custom URL switch
	jQuery('body').delegate('#pcud_redirect', 'change', function() {
		if(jQuery(this).val() == 'custom') {
			jQuery('#pcud_cust_redir_wrap td').slideDown();
		} else {
			jQuery('#pcud_cust_redir_wrap td').slideUp();	
		}
	});
	

	// lc switch
	function pcud_live_ip_checks() {
		jQuery('.ip_checks').lc_switch('YES', 'NO');
	}
	pcud_live_ip_checks();
	
	// chosen
	function pcud_live_chosen() {
		jQuery('.lcweb-chosen').each(function() {
			var w = jQuery(this).css('width');
			jQuery(this).chosen({width: w}); 
		});
		jQuery(".lcweb-chosen-deselect").chosen({allow_single_deselect:true});
	}
	pcud_live_chosen();
	
	
	// hide message after 3 sec
	function pcud_hide_wp_alert() {
		setTimeout(function() {
		 jQuery('#ajax_mess').empty();
		}, 3500);	
	}
	
	
	/*** sort formbuilder rows ***/
	function pcud_live_sort() {
		jQuery( "#pcud_form_table tbody" ).sortable({ handle: '.pc_move_field' });
		jQuery( "#pcud_form_table tbody td .pc_move_field" ).disableSelection();
	}
	
});
</script>
