/**************************
   HANDLING CUSTOM FORMS
**************************/	

jQuery(document).ready(function(){
	jQuery('body').delegate('.pc_custom_form_btn:not(.pc_loading_btn)', 'click', function() {
		$pcud_target_form = jQuery(this).parents('form');
		var f_data = jQuery(this).parents('form').serialize();
		
		$pcud_target_form.find('.pc_custom_form_btn').addClass('pc_loading_btn');
		$pcud_target_form.find('.pc_custom_form_message').empty();
		
		jQuery.ajax({
			type: "POST",
			url: window.location.href,
			data: "type=pcud_cf_submit&" + f_data,
			dataType: "json",
			success: function(pc_data){
				if(pc_data.resp == 'success') {
					$pcud_target_form.find('.pc_custom_form_message').empty().append('<span class="pc_success_mess">' + pc_data.mess + '</span>');
				}
				else {
					$pcud_target_form.find('.pc_custom_form_message').empty().append('<span class="pc_error_mess">' + pc_data.mess + '</span>');
				}
				
				// redirect
				if(typeof(pc_data.redirect) != 'undefined' && pc_data.redirect) {
					setTimeout(function() {
					  window.location.href = pc_data.redirect;
					}, 1000);		
				}
				
				$pcud_target_form.find('.pc_custom_form_btn').removeClass('pc_loading_btn');
			}
		});
	});
	
	// enter key handler
	jQuery('.pc_custom_form .pc_rf_field input').keypress(function(event){
		if(event.keyCode === 13){
			jQuery(this).parents('form').find('.pc_custom_form_btn').trigger('click');
		}
		
		event.cancelBubble = true;
		if(event.stopPropagation) event.stopPropagation();
   	});
	
	
	// datepicker
	if(jQuery('.pcud_datepicker').size() > 0) {
		var pcud_datepicker_init = function(type) {
			return {
				dateFormat : (type == 'eu') ? 'dd/mm/yy' : 'mm/dd/yy',
				beforeShow: function(input, inst) {
					if( !jQuery('#ui-datepicker-div').parent().hasClass('pcud_dp') ) {
						jQuery('#ui-datepicker-div').wrap('<div class="pcud_dp"></div>');
					}
				},
				monthNames: 		pcud_datepick_str.monthNames,
				monthNamesShort: 	pcud_datepick_str.monthNamesShort,
				dayNames: 			pcud_datepick_str.dayNames,
				dayNamesShort: 		pcud_datepick_str.dayNamesShort,
				dayNamesMin:		pcud_datepick_str.dayNamesMin,
				isRTL:				pcud_datepick_str.isRTL
			};	
		}
		
		jQuery('.pcud_dp_eu_date').datepicker( pcud_datepicker_init('eu') );
		jQuery('.pcud_dp_us_date').datepicker( pcud_datepicker_init('us') );
	}
});
	