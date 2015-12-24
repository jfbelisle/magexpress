<?php
// WIDGET RESTRICTION - BACKEND AND FRONTEND

// Add fields fields
function pc_widget_restriction_fields($t, $return, $instance){
    include_once(PC_DIR . '/functions.php'); 

	$field_id = $t->get_field_id('pc_allow');
	$pc_cats = pc_user_cats();
	$instance = wp_parse_args( (array)$instance, array( 'title' => '', 'text' => '', 'pc_allow' => array()));
    
	if(!isset($instance['pc_allow']) || !is_array($instance['pc_allow'])) {$instance['pc_allow'] = array();}
	//if(!isset($instance['pc_block'])) {$instance['pc_block'] = array();}
    ?>
    <p class="pc_widget_control_wrap">
        <label  for="<?php echo $field_id; ?>"><?php _e('Which PrivateContent user categories can see the page?', 'pc_ml') ?>:</label>
        <select id="<?php echo $field_id ?>" name="pc_allow[]" multiple="multiple"  class="lcweb-chosen" data-placeholder="<?php _e('select a category', 'pc_ml') ?> .." autocomplete="off" style="width: 388px;">
        	<option value="all" <?php if(in_array('all', $instance['pc_allow'])) {echo 'selected="selected"';} ?>><?php _e('Any logged user', 'pc_ml') ?></option>
			<option value="unlogged" <?php if(in_array('unlogged', $instance['pc_allow'])) {echo 'selected="selected"';} ?>><?php _e('Unlogged Users', 'pc_ml') ?></option>
			<?php 
			foreach($pc_cats as $cat_id => $name) {
				 $sel = (in_array($cat_id, $instance['pc_allow'])) ? 'selected="selected"' : '';
				 echo '<option value="'.$cat_id.'" '.$sel.'>'. $name .'</option>';
			}
           	?>
        </select>
    </p>
    
    <script type="text/javascript">
    jQuery(document).ready(function(e) {
		// set dropdown width
		jQuery('.pc_widget_control_wrap select').css('width', (jQuery('.sidebars-column-1').width() - 48));
		
		pc_live_chosen();
	}); 
	</script>
    
	<?php
    $retrun = null;
    return array($t, $return, $instance);
}
add_action('in_widget_form', 'pc_widget_restriction_fields', 999, 3);


// Callback - save fields (ajax passed data - save and returns to in_widget_form)
function pc_widget_restriction_save($instance, $new_instance, $old_instance, $widget){
	
	// sanitize value
	$allow = (isset($_POST['pc_allow'])) ? (array)$_POST['pc_allow'] : array(); 
	$instance['pc_allow'] = $allow;
	
	if(in_array('all', $allow)) {$allow = array('all');} 
	if(in_array('unlogged', $allow)) {$allow = array('unlogged');} 
	
	// save in WP options to be faster
	$data = array(
		'allow' => $allow
	);
	update_option('pg_widget_control_'.$widget->id, $data);
	
	return $instance;
}
add_filter('widget_update_callback', 'pc_widget_restriction_save', 5, 4);


// add chosen script into page
function pc_wc_chosen() {
	global $current_screen;
	if($current_screen->base == 'widgets') :
	?>
    <script src="<?php echo PC_URL; ?>/js/chosen/chosen.jquery.min.js" type="text/javascript"></script>
	<script type="text/javascript">
    jQuery(document).ready(function(e) {
        var dd_count = jQuery('#widgets-right select.lcweb-chosen').size();
		pc_live_chosen();
		
		jQuery(document).delegate('.widgets-chooser-actions .button-primary', 'click', function() {
			pc_live_chosen_intval = setInterval(function() {
				var new_count = jQuery('#widgets-right select.lcweb-chosen').size();
				
				if(new_count != dd_count) {
					dd_count = new_count;
					clearInterval(pc_live_chosen_intval);
					pc_live_chosen();
				}
			}, 100); 
		});
    });
	
	
	var pc_live_chosen = function() {
		jQuery('#widgets-right, #wp_inactive_widgets').find('.lcweb-chosen').each(function() {
            var w = jQuery(this).css('width');
            jQuery(this).chosen({width: w}); 
        });
        jQuery(".lcweb-chosen-deselect").chosen({allow_single_deselect:true});	
    }
    </script>
    <?php
	endif;
}
add_action('admin_footer', 'pc_wc_chosen');




// widget deletion - clean database
function pc_wc_delete_action() {
	if ( isset($_POST['widget-id']) ) {
		$widget_id = $_POST['widget-id'];

        if (isset( $_POST['delete_widget']) && $_POST['delete_widget']) {
        	delete_option('pg_widget_control_'.$widget_id);
		}
	}
}
add_action( 'sidebar_admin_setup', 'pc_wc_delete_action');




///////////////////////////////////////////////////////////////////////////////////////////


// APPLY - frontend implementation
function pc_do_widget_restriction($sidebars_widgets) {
	$filtered_widgets = $sidebars_widgets;
	
	// in frontend and only if WP user functions are registered
	if(!is_admin() && defined('PC_WP_USER_PASS')) {
		if(!isset($GLOBALS['pc_widget_control_opts'])) {$GLOBALS['pc_widget_control_opts'] = array();}
		$stored = $GLOBALS['pc_widget_control_opts'];
		
		foreach($sidebars_widgets as $widget_area => $widget_list) {
			if ($widget_area == 'wp_inactive_widgets' || empty($widget_list)) {continue;}
	
			foreach($widget_list as $pos => $widget_id) {
				if(isset($stored[$widget_id])) {
					$opt = $stored[$widget_id];	
				} else {
					$opt = get_option('pg_widget_control_'.$widget_id); 
					$GLOBALS['pc_widget_control_opts'][$widget_id] = $opt;
				}
				
				if($opt) {
					if(isset($opt['allow']) && is_array($opt['allow']) && count($opt['allow']))	{
						
						$val = implode(',', $opt['allow']);
						if(pc_user_check($val, $blocked = '', $wp_user_pass = true) !== 1) {
							unset( $filtered_widgets[$widget_area][$pos] );	
						}	
					}
				}
			}
		}
	}
	
	return $filtered_widgets;
}
add_filter('sidebars_widgets', 'pc_do_widget_restriction', 999);
