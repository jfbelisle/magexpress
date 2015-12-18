<?php

// Shortcode Processing

function vntd_social_icons($atts, $content = null) {
	$attss = extract(shortcode_atts(array(
		"style" => 'square',
		"facebook" => '',
		"twitter" => '',
		"googleplus" => '',
		"rss" => '',
		"linkedin" => '',
		"pinterest" => '',
		"email" => '',
		"dribbble" => '',
		"instagram" => '',
		"youtube" => '',
		"tumblr" => ''
	), $atts));
	
	$icons = '';
	$icon_arr = array('facebook','twitter','google_plus','rss','tumblr','linkedin','vimeo','pinterest','instagram','dribbble','skype','flickr','dropbox','youtube','mail','dribbble','rss');
	
	$style_class = '';
	if($style == 'round') {
		$style_class = ' round';
	}
	foreach($icon_arr as $icon_name) {	
		if(array_key_exists($icon_name,$atts)) {			
			$icons .= '<a href="'.$atts[$icon_name].'" class="social '.$icon_name.$style_class.'" target="_blank"><i class="fa fa-'.$icon_name.'"></i></a>';
		}
	}	
			
	return '<div class="vntd-social-icons vntd-social-icons-'.$style.'">'.$icons.'</div>';
}
remove_shortcode('social_icons');
add_shortcode('social_icons', 'vntd_social_icons');