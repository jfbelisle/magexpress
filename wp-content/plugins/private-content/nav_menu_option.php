<?php
// add visibility option to each menu block


// Saves new field to postmeta for navigation
function pc_custom_nav_update($menu_id, $menu_item_db_id, $args ) {
	if(!isset($GLOBALS['pc_menu_restrictions'])) {$GLOBALS['pc_menu_restrictions'] = array();}
	
	if(isset($_REQUEST['menu-item-'.$menu_item_db_id.'-pc-hide']) ) {
        $restr = $_REQUEST['menu-item-'.$menu_item_db_id.'-pc-hide'];
		if(!is_array($restr)) {$restr = array();}
		
		if(in_array('all', $restr)) {$restr = array('all');} 
		if(in_array('unlogged', $restr)) {$restr= array('unlogged');} 
		
		$GLOBALS['pc_menu_restrictions'][$menu_item_db_id] = (count($restr)) ? $restr : array('');
        update_post_meta( $menu_item_db_id, '_menu_item_pg_hide', $restr);
    }
	else {
		$GLOBALS['pc_menu_restrictions'][$menu_item_db_id] = array('');
		update_post_meta( $menu_item_db_id, '_menu_item_pg_hide', '');
	}
}
add_action('wp_update_nav_menu_item', 'pc_custom_nav_update',10, 3);


// Adds value of new field to $item object that will be passed to frontend menu object
function pg_custom_nav_item($menu_item) {
    $menu_item->pc_hide_item = get_post_meta($menu_item->ID, '_menu_item_pg_hide', true);
    return $menu_item;
}
add_filter('wp_setup_nav_menu_item','pg_custom_nav_item');


// javscript implementation of restriction wizard
function pc_menu_restriction_wizard() {
	global $current_screen;
	if($current_screen->base == 'nav-menus') {
		
		?>
        <script src="<?php echo PC_URL; ?>/js/chosen/chosen.jquery.min.js" type="text/javascript"></script>
        <script type="text/javascript">
		jQuery(document).ready(function(e) {
			if(jQuery('#update-nav-menu').size()) {			
           		var menu_id = jQuery('#menu').size();
				var to_query = jQuery.makeArray(); 
				var tot_items = jQuery('.menu-item-page').size();
				
				var saved_vals = jQuery.parseJSON('<?php echo (isset($GLOBALS['pc_menu_restrictions'])) ? json_encode($GLOBALS['pc_menu_restrictions']) : json_encode(''); ?>');
				
				var base_code = 
				'<p class="field-custom description description-wide pc_menu_restr_wrap">'+
					'<label><?php _e('Which PrivateContent user categories can see the page?', 'pc_ml') ?></label>'+
					
					'<select name="menu-item-%MENU-ITEM-ID%-pc-hide[]" rel="%MENU-ITEM-ID%" multiple="multiple" class="lcweb-chosen pc_menu_hide_dd" data-placeholder="<?php _e('Select categories', 'pc_ml') ?> .." style="width: 390px;">'+
					  '<option value="all"><?php _e('Any logged user', 'pc_ml') ?></option>'+
					  '<option value="unlogged"><?php _e('Unlogged Users', 'pc_ml') ?></option>'+
					  <?php 
					  $user_categories = get_terms('pg_user_categories', 'orderby=name&hide_empty=0');
					  foreach ($user_categories as $ucat) : ?>
						'<option value="<?php echo $ucat->term_id ?>"><?php echo str_replace("'", "&rsquo;", $ucat->name) ?></option>'+  
					  <?php 
					  endforeach; 
					  ?>
					'</select>'+
				'</p>';
				
				
				// fetch values for every menu item
				var pc_fetch_values = function() {
					if(!saved_vals && to_query.length) {
						var data = {
							action: 'pc_menu_item_restrict',
							menu_items: to_query,
							pc_nonce: '<?php echo wp_create_nonce('lcwp_ajax') ?>'
						};
						jQuery.post(ajaxurl, data, function(response) {
							var resp = jQuery.parseJSON(response);
	
							jQuery('#update-nav-menu .menu-item').each(function() {
								var $subj = jQuery(this);
				   				var item_id = $subj.find('.menu-item-data-db-id').val();

								if(typeof(resp[item_id]) != 'undefined') {
									jQuery.each( resp[item_id], function(iid, val) {
										if(val) {
											$subj.find('.pc_menu_hide_dd option[value='+ val +']').attr('selected', 'selected');	
										}
									});
								}
							});
							
							pc_live_chosen();
						});	
					}
					else {
						pc_live_chosen();
					}
				}
				
				
				// detect new menu additions
				var pc_add_menu_detect = function() {
					pc_add_menu_intval = setInterval(function() {
						if(jQuery('.menu-item-page').size() < tot_items) {
							tot_items = jQuery('.menu-item-page').size();	
						}
						else if(jQuery('.menu-item-page').size() > tot_items) {
							tot_items = jQuery('.menu-item-page').size();
							
							jQuery('#update-nav-menu .menu-item').each(function(i, v) {
								var $subj = jQuery(this);
								if(!$subj.find('.pc_menu_hide_dd').size()) {
									var item_id = $subj.find('.menu-item-data-db-id').val();
									to_query.push(item_id);
									
									var item_code = base_code.replace(/%MENU-ITEM-ID%/g, item_id);
									$subj.find('.menu-item-actions').before(item_code);
								}
							});
							
							pc_live_chosen();
						}
					}, 100);
				}
				
				
				// initialize
				var a = 0;
				jQuery('#update-nav-menu .menu-item').each(function(i, v) {
                	var $subj = jQuery(this);
				    var item_id = $subj.find('.menu-item-data-db-id').val();
					to_query.push(item_id);
					
					var item_code = base_code.replace(/%MENU-ITEM-ID%/g, item_id);
					$subj.find('.menu-item-actions').before(item_code);
					
					// set value if just saved
					if(saved_vals) {
						jQuery.each( saved_vals[item_id], function(i,v) {
							if(v) {
								$subj.find('.pc_menu_hide_dd option[value='+ v +']').attr('selected', 'selected');	
							}
						});
					}
					
					if(a == tot_items) {
						pc_add_menu_detect();
						pc_fetch_values();	
					}
					
					a++;
                });
			}
        });
		
		var pc_live_chosen = function() {
			jQuery('.lcweb-chosen').each(function() {
				var w = jQuery(this).css('width');
				jQuery(this).chosen({width: w}); 
			});
			jQuery(".lcweb-chosen-deselect").chosen({allow_single_deselect:true});	
		}
		</script>
        <?php	
	}
}
add_action('admin_footer', 'pc_menu_restriction_wizard');

