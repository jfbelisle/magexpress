jQuery(document).ready(function() {
	pc_login_is_acting = false; // security var to avoid multiple calls
	pc_curr_url = window.location.href;
	var pre_timestamp = (pc_curr_url.indexOf('?') !== -1) ? '&' : '?';
	
	
	/**************************
			 LOGIN
	**************************/
	
	// show login form for inline restrictions
	jQuery(document).delegate('.pc_login_trig', 'click', function() {
		var $subj = jQuery(this).parents('.pc_login_block');
		$subj.slideUp(350);
		
		setTimeout(function() {
			$subj.next('.pc_inl_login_wrap').slideDown(450);
		}, 350);
	});	
	
	
	// triggers
	jQuery(document).delegate('.pc_auth_btn', 'click', function() {	
		var $target_form = jQuery(this).parents('form');
		var f_data = $target_form.serialize();

		pc_submit_login($target_form, f_data);
	});
	jQuery('.pc_login_row input').keypress(function(event){
		if(event.keyCode === 13){
			var $target_form = jQuery(this).parents('form');
			var f_data = $target_form.serialize();

			pc_submit_login($target_form, f_data);
		}
		
		event.cancelBubble = true;
		if(event.stopPropagation) event.stopPropagation();
   	});
	
	
	// handle form
	pc_submit_login = function($form, f_data) {
		if(!pc_login_is_acting) {
			pc_login_is_acting = true;
			var forced_redirect = $form.attr('pc_redirect');

			$form.find('.pc_auth_btn').addClass('pc_loading_btn');
			$form.find('.pcma_psw_recovery_trigger').fadeTo(200, 0);

			jQuery.ajax({
				type: "POST",
				url: pc_curr_url,
				dataType: "json",
				data: "type=js_ajax_auth&" + f_data,
				success: function(pc_data){
					pc_login_is_acting = false;
					$form.find('.pc_auth_btn').removeClass('pc_loading_btn');
			
					if(pc_data.resp == 'success') {
						$form.find('#pc_auth_message').empty().append('<span class="pc_success_mess">' + pc_data.mess + '</span>');
						
						if(typeof(forced_redirect) == 'undefined') {
							if(pc_data.redirect == '') {var red_url = pc_curr_url + pre_timestamp + new Date().getTime();}
							else {var red_url = pc_data.redirect;}
						}
						else {red_url = forced_redirect;}
						
						setTimeout(function() {
						  window.location.href = red_url;
						}, 1000);
					}
					else {
						$form.find('#pc_auth_message').empty().append('<span class="pc_error_mess">' + pc_data.mess + '</span>');	
						$form.find('.pcma_psw_recovery_trigger').fadeTo(200, 1);
					}
				}
			});
		}
	}
	
	
	/* check to avoid smalls over button on small screens - only for remember me + password recovery */
	pc_login_display_check = function() {
		jQuery('.pc_rm_login .pcma_psw_recovery_trigger').each(function() {
            var $form = jQuery(this).parents('.pc_login_form');
			
			if( 
				($form.width() - ($form.find('.pcma_psw_recovery_trigger').outerWidth(true) + $form.find('.pc_login_remember_me').outerWidth(true))) < 
				($form.find('.pc_auth_btn').outerWidth(true) + 10)
			) {
				$form.addClass('pc_mobile_login');
			} else {
				$form.removeClass('pc_mobile_login');
			}
        });
	}
	pc_login_display_check();
	jQuery(window).resize(function() { pc_login_display_check(); });
	

	/* LONG LABELS CHECK */
	var pc_lf_labels_h_check = function() {
		jQuery('.pc_login_form').not('.pc_lf_long_labels').each(function() {
			var user_h = jQuery(this).find('label[for=pc_auth_username]').height();
			var psw_h = jQuery(this).find('label[for=pc_auth_psw]').height();
			
			if((user_h > 27 || psw_h > 27) && jQuery(window).width() >= 440) {
				jQuery(this).addClass('pc_lf_long_labels');		
			} else {
				jQuery(this).removeClass('pc_lf_long_labels');
			}
        });	
	}
	pc_lf_labels_h_check();
	
	
	
	/**************************
			 LOGOUT
	**************************/
	
	// execute logout		 
	jQuery(document).delegate('.pc_logout_btn', 'click', function(e) {	
		e.preventDefault();
		var forced_redirect = jQuery(this).attr('pc_redirect');
		jQuery(this).addClass('pc_loading_btn');
		
		jQuery.ajax({
			type: "POST",
			url: pc_curr_url,
			data: "type=pc_logout",
			success: function(response){
				resp = jQuery.trim(response);
				
				if(typeof(forced_redirect) == 'undefined') {
					if(resp == '') {window.location.href = pc_curr_url + pre_timestamp + new Date().getTime();}
					else {window.location.href = resp;}
				}
				else {window.location.href = forced_redirect;}
			}
		});
	});
	
			
		
	/**************************
		   REGISTRATION
	**************************/	
	
	// triggers
	jQuery(document).delegate('.pc_reg_btn', 'click', function() {	
		var $target_form = jQuery(this).parents('form');
		var f_data = $target_form.serialize();
		$target_form.find('.pc_reg_btn').addClass('pc_loading_btn');
		
		pc_submit_registration($target_form, f_data);
	});
	jQuery('.pc_registration_form input, .pc_registration_form textarea').keypress(function(event){
		if(event.keyCode === 13){
			var $target_form = jQuery(this).parents('form');
			var f_data = $target_form.serialize();
			$target_form.find('.pc_reg_btn').addClass('pc_loading_btn');
			
			pc_submit_registration($target_form, f_data);
		}
		
		event.cancelBubble = true;
		if(event.stopPropagation) event.stopPropagation();
   	});
	
	
	// handle form
	pc_submit_registration = function($form, f_data) {
		var cc = (typeof($form.attr('pc_cc')) == 'undefined') ? '' : $form.attr('pc_cc');
		var redir = $form.attr('pc_redirect');
		
		var data = 
			'type=pc_registration'+
			'&form_id=' + $form.attr('rel') +
			'&pc_cc='+ cc +
			'&' + $form.serialize()
		;
		jQuery.ajax({
			type: "POST",
			url: pc_curr_url,
			data: data,
			dataType: "json",
			success: function(pc_data){
				if(pc_data.resp == 'success') {
					$form.find('#pc_reg_message').empty().append('<span class="pc_success_mess">' + pc_data.mess + '</span>');
					
					// redirect
					var redirect = (typeof(redir) != 'undefined') ? redir : pc_data.redirect;
					if(redirect) {
						setTimeout(function() {
						  window.location.href = redirect;
						}, 1000);	
					}
				}
				else {
					$form.find('#pc_reg_message').empty().append('<span class="pc_error_mess">' + pc_data.mess + '</span>');
					
					// if exist recaptcha - reload
					if( jQuery('#recaptcha_response_field').size() > 0 ) {
						Recaptcha.reload();	
					}
				}
				
				$form.find('.pc_reg_btn').removeClass('pc_loading_btn');
			}
		});
	}
	
	
	// setup multiple select plugin
	if(jQuery('.pc_multiselect').size() && typeof(jQuery.fn.multipleSelect) == 'function') {
		jQuery('.pc_multiselect select').multipleSelect( {
			selectAll: false,
			countSelected: pc_ms_countSelected,
			allSelected: pc_ms_allSelected	
		});	
	}
	
	
	/* fluid forms - columnizer */
	pc_fluid_form_columnizer = function(first_check) {
		jQuery('.pc_fluid_form').each(function() {
			// calculate
			var form_w = jQuery(this).width();

			var col = Math.round( form_w / 315 );
			if(col > 5) {col = 5;}
			if(col < 1) {col = 1;}

			// if is not first check - remove past column 
			if(typeof(first_check) == 'undefined') {
				var curr_col = jQuery(this).attr('pc_col');
				if(col != curr_col) {
					jQuery(this).removeClass('pc_form_'+curr_col+'col');	
				}
			}
			
			// apply
			jQuery(this).attr('pc_col', col);
			jQuery(this).addClass('pc_form_'+col+'col');		
        });	
	}
	pc_fluid_form_columnizer(true);
	
	jQuery(window).resize(function() { 
		if(typeof(pc_ffc) != 'undefined') {clearTimeout(pc_ffc);}
		pc_ffc = setTimeout(function() {
			pc_fluid_form_columnizer();
		}, 50);
	});
	
	
	
	/* ONE-COL LABEL HEIGHT CHECK */
	var pc_rf_labels_h_check = function() {
		jQuery('.pc_vcl .pc_one_col_form .pc_rf_field > label').each(function() {
			var h = jQuery(this).outerHeight(true);
			var wrap_h = jQuery(this).parents('li').height();
			
			if(h >= wrap_h || jQuery(window).width() <= 450) {
				//jQuery(this).addClass('pc_high_label');	
				jQuery(this).parents('li').css('min-height', h);
			} else {
				//jQuery(this).removeClass('pc_high_label');	
				jQuery(this).parents('li').css('min-height', 0);
			}
			
        });	
	}
	pc_rf_labels_h_check();
	

	/////////////////////////////////////////////////////////////////////
	
	// on resize
	jQuery(window).resize(function() {
		if(typeof(pc_is_resizing) != 'undefined') {clearTimeout(pc_is_resizing);}
		
		pc_is_resizing = setTimeout(function() {
			pc_lf_labels_h_check();
			pc_rf_labels_h_check();
		}, 50);
	});
});



// flag to center vertically labels in one-col forms
if(	navigator.appVersion.indexOf("MSIE 8.") == -1 ) {
	window.onload = function() {
		var pc_init_class = setInterval(function() {
			if(document.body) {
				document.body.className += ' pc_vcl';
				clearInterval(pc_init_class);
			}
		}, 50);
	}
} 