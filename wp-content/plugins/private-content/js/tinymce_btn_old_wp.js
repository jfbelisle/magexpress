(function(){
	var pc_H = 520;
	var pc_W = 560;
	
	// creates the plugin
	tinymce.create('tinymce.plugins.PrivateContent', {
		createControl : function(id, controlManager) {
			if (id == 'pc_btn') {
				// creates the button
				var pc_sc_button = controlManager.createButton('pc_btn', {
					title : 'PrivateContent Shortcode', // title of the button
					image : '../wp-content/plugins/private-content/img/users_icon_tinymce.png',  // path to the button's image
					onclick : function() {
						tb_show( 'PrivateContent Shortcodes', '#TB_inline?width=' + pc_W + '&height=' + pc_H + '&inlineId=privatecontent-form');
						
						jQuery('#TB_ajaxContent').css('padding-left', 0).css('padding-right', 0);
						jQuery("#pc_sc_tabs").tabs();	
						
						pc_scw_setup();
						pc_live_ip_checks();
						pc_live_chosen();
						

						////////////////////////////////////////////////////////////////////
						// CUSTOM JAVASCRIPT - USER DATA ADD-ON
						var data = { action: 'pcud_tinymce_add-on' };
						jQuery.post(ajaxurl, data, function(response) {
							if(response != 0) {
								resp = jQuery.parseJSON(response);
					
								jQuery('#pc_sc_ud').html(resp.html);
								jQuery('#pc_sc_ud > table').addClass('lcwp_tinymce_table');
								jQuery('#pc_sc_ud > table tr td:first-child').css('width', '33%');
								
								jQuery('#pc_sc_ud > hr, #pc_sc_ud > br').remove();
								jQuery('body').append(resp.js);			
							}
						});	
						///////////////////////////////////////////////////////////////////	
					}
				});
				return pc_sc_button;
			}
			return null;
		}
	});
	tinymce.PluginManager.add('PrivateContent', tinymce.plugins.PrivateContent);
	

	
	// manage the lightbox position
	function pc_scw_setup() {
		if( jQuery('#TB_window').is(':visible') ) {
			jQuery('#TB_window').css("height", pc_H);
			jQuery('#TB_window').css("width", pc_W);	
			jQuery('#TB_window, #TB_ajaxContent').css('overflow', 'visible');
			
			jQuery('#TB_window').css("top", ((jQuery(window).height() - pc_H) / 4) + 'px');
			jQuery('#TB_window').css("left", ((jQuery(window).width() - pc_W) / 4) + 'px');
			jQuery('#TB_window').css("margin-top", ((jQuery(window).height() - pc_H) / 4) + 'px');
			jQuery('#TB_window').css("margin-left", ((jQuery(window).width() - pc_W) / 4) + 'px');
			
			
		} else {
			setTimeout(function() {
				pc_scw_setup();
			}, 10);
		}
	}
	jQuery(window).resize(function() {
		if(jQuery('#pc_sc_tabs').is(':visible')) {
			var $pc_sc_selector = jQuery('#pc_sc_tabs').parents('#TB_window');
			
			$pc_sc_selector.css("height", pc_H).css("width", pc_W);	
			
			$pc_sc_selector.css("top", ((jQuery(window).height() - pc_H) / 4) + 'px');
			$pc_sc_selector.css("left", ((jQuery(window).width() - pc_W) / 4) + 'px');
			$pc_sc_selector.css("margin-top", ((jQuery(window).height() - pc_H) / 4) + 'px');
			$pc_sc_selector.css("margin-left", ((jQuery(window).width() - pc_W) / 4) + 'px');
		}
	});
	

	////////////////////////////////////////////////////////
	///// pvt-content
	
	// hide categories if ALL is checked
	jQuery('body').delegate('#pc_sc_type', 'change', function() {
		if( jQuery(this).val() == 'some' ) {jQuery('#pc_user_cats_row').slideDown();} 
		else {jQuery('#pc_user_cats_row').slideUp();}
	});
	
	
	// hide message text if no warning is shown
	jQuery('body').delegate('#pg-hide-warning', 'lcs-statuschange', function(){
		if( jQuery(this).is(':checked') ) {
			jQuery('#pg-text_wrap').slideUp();
		} else {
			jQuery('#pg-text_wrap').slideDown();	
		}
	});
	
	
	// handles the click event of the submit button
	jQuery('body').delegate('#pg-pvt-content-submit', 'click', function(){
		var type = jQuery('#pc_sc_type').val();
		var sc = '[pc-pvt-content';
		
		// allowed
		if(type != 'some') {sc += ' allow="' + type + '"';}
		else {
			if( !jQuery('#pc_sc_cats').val() ) {
				alert('Choose at least one category');	
				return false;
			}
			
			sc += ' allow="' + jQuery('#pc_sc_cats').val() + '"';
		}
		
		// show warning box
		if( jQuery('#pg-hide-warning').is(':checked') ) {
			sc += ' warning="0"';	
		} else {
			sc += ' warning="1"';	
		}
		
		// custom message
		if( !jQuery('#pg-hide-warning').is(':checked') && jQuery('#pg-text').val() != '') {
			sc += ' message="' + jQuery('#pg-text').val() + '"';
		}

		// inserts the shortcode into the active editor
		tinyMCE.activeEditor.execCommand('mceInsertContent', 0, sc + '][/pc-pvt-content]');
		
		// closes Thickbox
		tb_remove();
	});
	
	
	////////////////////////////////////////////////////////
	///// login-form
	jQuery('body').delegate('#pg-loginform-submit', 'click', function(){	
		var shortcode = '[pc-login-form]';
		tinyMCE.activeEditor.execCommand('mceInsertContent', 0, shortcode);
		tb_remove();
	});
	
	
	////////////////////////////////////////////////////////
	///// logout-box
	jQuery('body').delegate('#pg-logoutbox-submit', 'click', function(){	
		var shortcode = '[pc-logout-box]';
		tinyMCE.activeEditor.execCommand('mceInsertContent', 0, shortcode);
		tb_remove();
	});
	
	
	////////////////////////////////////////////////////////
	///// registration-form
	jQuery('body').delegate('#pg-regform-submit', 'click', function(){	
		var sc = '[pc-registration-form';
		
		var form_id = jQuery('#pc_sc_rf_id').val();
		var layout = jQuery('#pc_sc_rf_layout').val();
		var cats = jQuery('#pc_sc_rf_cat').val();
		var redir = jQuery('#pc_sc_rf_redirect').val()
		
		// form id
		if(!form_id) {
			alert("No registration form found");	
			return false;
		}
		else {
			sc += ' id="' + form_id + '"';	
		}
		
		// layout
		if(layout) {
			sc += ' layout="' + layout + '"';		
		}
		
		// cats
		if(cats) {
			sc += ' custom_categories="' + cats.join(',') + '"';			
		}
		
		// redirect
		if(jQuery.trim(redir)) {
			sc += ' redirect="' + redir + '"';			
		}

		// inserts the shortcode into the active editor
		tinyMCE.activeEditor.execCommand('mceInsertContent', 0, sc + ']');
		tb_remove();
	});
	
	///////
	
	// init chosen for live elements
	function pc_live_chosen() {
		jQuery('.lcweb-chosen').each(function() {
			var w = jQuery(this).css('width');
			jQuery(this).chosen({width: w}); 
		});
		jQuery(".lcweb-chosen-deselect").chosen({allow_single_deselect:true});
	}
	
	// init iphone checkbox
	function pc_live_ip_checks() {
		jQuery('.ip_checks').lc_switch('YES', 'NO');	
	}
	
})();
