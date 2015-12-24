<?php
// LOGIN WIDGET
 
class PrivateContentLogin extends WP_Widget {
	
	function PrivateContentLogin() {
		$widget_ops = array('classname' => 'PrivateContentLogin', 'description' => __('Displays a login form for PrivateContent users', 'pc_ml'));
		parent::__construct('PrivateContentLogin', __('PrivateContent Login', 'pc_ml'), $widget_ops);
	}
   
   
	function form($instance) {
		$instance = wp_parse_args( (array)$instance);
		$title = $instance['title'];
		?>
		  <p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title', 'pc_ml') ?>:</label> <br />
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
		  </p>
		<?php
	}
	
   
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = $new_instance['title'];
		return $instance;
	}
	
   
	function widget($args, $instance) {
		global $wpdb;
		extract($args, EXTR_SKIP);
	 
		echo $before_widget;
		$title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
	 
		if(!empty($title)) {
		  echo $before_title . $title . $after_title;;
			
			// switch if is logged or not
			$logged_user = pc_user_logged(array('username', 'name', 'surname'));
			
			if($logged_user) :
			?>
				<p><?php _e('Welcome', 'pc_ml') ?> <?php echo (empty($logged_user['name']) && empty($logged_user['surname'])) ? $logged_user['userame'] : ucfirst($logged_user['name']).' '.ucfirst($logged_user['surname']); ?></p>
				
				<form class="pc_logout_widget PrivateContentLogin">
					<input type="button" name="pc_widget_logout" class="pc_logout_btn pc_trigger" value="<?php _e('Logout', 'pc_ml') ?>" />
					<span class="pc_loginform_loader"></span>
				</form>
			
			<?php 
			else :
			  echo pc_login_form();
			endif;
		}
		echo $after_widget;
	}
}
add_action( 'widgets_init', create_function('', 'return register_widget("PrivateContentLogin");') );

