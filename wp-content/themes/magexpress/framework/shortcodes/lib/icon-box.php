<?php

// Icon Box Shortcode

function vntd_icon_box($atts, $content = null) {
	extract(shortcode_atts(array(
		"icon" => 'heart-o',
		"style" => 'default',
		"title" => '',
		"text" => '',	
		"url" => '',	
		"target" => '_self',
		"text_style" => 'fullwidth',
		"link_title" => 'More Info',
		"animated" => '',
		"animation_delay" => 100			
	), $atts));
	
	$icon = str_replace('fa-','',$icon);
	
	$aligned_class = ' box';
	if($style == 'left' || $style == 'right') $aligned_class = ' feature-box';
	
	$animated_class = $animated_data = '';
	
	if($animated != 'no') {
		$animated_class = ' animated';
		$animated_data = ' data-animation="fadeIn" data-animation-delay="'.$animation_delay.'"';
	}
	
	$output = '<div class="vntd-icon-box icon-box-'.$style.$animated_class.$aligned_class.'"'.$animated_data.'>';
	
	if($style == 'centered') {
	
		$output .= '<a target="'.$target.'" title="'.$title.'" class="icon-box-icon about-icon">';
		$output .= '<i class="fa fa-'.$icon.'"></i>';
		$output .= '</a>';
		if($title) $output .= '<h3 class="icon-box-title uppercase normal font-primary">'.$title.'</h3>';	
		$output .= '<p class="icon-description">'.$text.'</p>';
		if($url) $output .= '<a href="'.$url.'" target="'.$target.'" title="'.$title.'">'.$link_title.'</a>';	
	
	} elseif($style == 'default' || !$style) {	
	
		$output .= '<div class="left-icon f-left"><a href="'.$url.'" class="round"><i class="fa fa-'.$icon.'"></i></a></div>';
		$output .= '<div class="right-desc f-left"><h3 class="box-head dark">'.$title.'</h3>';
		$output .= '<p class="box-desc dark">'.$text.'</p></div>';
	
	} else {	
	
		$output .= '<a href="'.$url.'" target="'.$target.'" title="'.$title.'" class="box-icon">';
		$output .= '<i class="fa fa-'.$icon.'"></i></a>';
		$output .= '<div class="feature-texts"><h3 class="box-head uppercase">'.$title.'</h3>';
		$output .= '<p class="box-desc semibold">'.$text.'</p></div>';
	
	}
	
	$output .= '</div>';
	
	return $output;
	
}
remove_shortcode('icon_box');
add_shortcode('icon_box', 'vntd_icon_box');