<?php

function vntd_cta($atts, $content=null) {
	extract(shortcode_atts(array(
		"button1_title" => 'Click me!',
		"button1_subtitle" => '',
		"button1_url" => '',
		"button2_title" => '',
		"button2_subtitle" => '',
		"button2_url" => '',		
		"text_color" => '',
		"button_color" => 'dark',
		"style" => 'default',
		"subtitle" => '',
		"heading" => '',
		"margin_bottom" => '0',
		"extra_class" => ''
	), $atts));
	
		
	$bg_color = $custom_button_color = $return = $fullwidth_class = '';
	$extra_class = ' style="';

	if($bg_color) { $extra_class .= 'background-color:'.$bg_color.';'; }
	if($text_color) { $extra_class .= 'color:'.$text_color.' !important;'; }
	if($margin_bottom != 30) { $extra_class .= 'margin-bottom:'.$margin_bottom.'px;'; }
	
	$button2 = $subtitle_extra_class = ' cta-no-subtitle';		
	if($subtitle) {
		$subtitle = '<p class="subtitle-text">'.$subtitle.'</p>';
		$subtitle_extra_class = '';
	}
	
	if($button2_title) {
		$button2 = '<a href="'.$button2_url.'" class="page-content-button scroll font-primary uppercase white">'.$button2_title.'</a>';
	}
	
	$button_color_class = 'vntd-button-dark';
	if($button_color == 'white') $button_color_class = 'vntd-button-white';
	
	$return .= '<div class="vntd-cta vntd-cta-style-'.$style.'">';
	
	if($style != 'centered') {
	
		$return .= '<div class="content-left white'.$subtitle_extra_class.'"><h1 class="content-head">'.$heading.'</h1>'.$subtitle.'</div>';
		$return .= '<div class="content-right white"><a href="'.$button1_url.'" class="page-content-button scroll font-primary uppercase white">'.$button1_title.'</a>'.$button2.'</div>';
	
	} else {
	
		$return .= '<h1 class="f-head mini-header">'.$heading.'</h1>';
		$return .= '<p class="f-text">'.$subtitle.'</p>';
		$return .= '<a href="'.$button1_url.'" class="vntd-cta-button scroll '.$button_color_class.'"><h3>'.$button1_title.'</h3>';
		if($button1_subtitle) $return .= '<p class="semibold">'.$button1_subtitle.'</p>';
		$return .= '</a>';
		if($button2_title) {
			$return .= '<a href="'.$button2_url.'" class="vntd-cta-button scroll '.$button_color_class.'"><h3>'.$button2_title.'</h3>';
			if($button2_subtitle) $return .= '<p class="semibold">'.$button2_subtitle.'</p>';
			$return .= '</a>';
		}
		
	}
	
	$return .= '</div>';
	
	return $return;
}
remove_shortcode('cta');
add_shortcode('cta', 'vntd_cta');  