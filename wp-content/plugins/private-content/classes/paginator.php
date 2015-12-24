<?php

class pc_paginator {
	
	// total query rows
	public $total_rows = 0;

	// current page number
	public $curr_pag = 1; 
	
	// number of rows per page
	public $limit = 10;
	
	// GET parameter to use to create links
	public $pag_param = 'page';
	
	// link to pages? (set false for ajax)
	public $link = true; 
	
	// current page link
	private $current_url;
	
	
	/* CONSTRUCTOR
	 * take the url of th page where the class is called
	 */
	public function __construct() {
		$pageURL = 'http';
		
		if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
		$pageURL .= "://" . $_SERVER['HTTP_HOST'].$_SERVER["REQUEST_URI"];

		$this->current_url = $pageURL;
		
		return true;
	}
	
	
	/* OFFSET CALCULATOR
	 * calculate the offest for queries
	 */
	public function get_offset() {
		$cur_page = (int)$this->curr_pag - 1;
		$offset = $this->limit * $cur_page;
		
		return $offset;
	}
	
	
	/* $_GET PARAMETER MANAGER
	 * given a GET parameter, rre-create the current url to fit to it
	 *
	 * @param $get_value = GET parameter (index=value)
	 */
	public function getManager($get_value) {
		// recupero l'url corrente della pagina
		$pageURL = $this->current_url;
		
		// verifico che nn ci sia già un get
		$get_exists = strpos($pageURL, '?');
		
		if($get_exists == false) {$link = $pageURL.'?'.$get_value;}
		else {
			// creo un array con i nuovi elementi
			$new_get_array = explode('&', $get_value);
			// array di indici puliti
			foreach($new_get_array as $new_get) {
				$new_get = explode('=', $new_get);
				$new_get_index[] = $new_get[0];
			}
			
			
			// creo un array con i get esistenti
			$post_link = substr($pageURL, $get_exists + 1);
			$exist_get_array = explode('&', $post_link);
			
			// lascio solo gli indici non sovrascritti
			foreach($exist_get_array as $exists_get) {
				$exists_get_clean = explode('=', $exists_get);
				
				if(!in_array($exists_get_clean[0], $new_get_index)) {
					$remaining_get[] = $exists_get;
				}
			}
			
			
			// fondo il nuovo array con i get rimasti
			if(isset($remaining_get)) {
				$final_get_array = array_merge($new_get_array, $remaining_get);
			}
			else {$final_get_array = $new_get_array;}
			$final_get = implode('&', $final_get_array);
			
			// ricostruisco l'url
			$pre_link = substr($pageURL, 0, $get_exists);
			$link = $pre_link.'?'.$final_get;	
		}
		
		return $link;
	}
	
	
	/* HREF ATTRIBUTE CREATOR
	 * if paginator has to link, create the href attribute
	 *
	 * @param pagenum = page number to link
	 */
	private function get_link($pagenum) {
		if(!$this->link) {return false;}
		else {
			$link = $this->getManager($this->pag_param . '=' . $pagenum);
			$href = 'href="'.$link.'"';
			return $href;	
		}
	}
	
	
	/* GET PAGINATION
	 * @param return = return type
	 * @params pre/after = code to put before and after the paginator
	 */
	public function get_pagination($pre='', $after='', $return = 'html') {
		
		// calculate total pages 
		$num_pags = ceil((int)$this->total_rows / (int)$this->limit);		
		$pc_list = '';
		
		/////////////////////////////////////////////////////
		// se è nelle prime 10 pagine stampo i primi risultati
		if((int)$this->curr_pag <= 6 || $num_pags <= 10) {
			for($a=1; $a <= 10; $a++) {
				// controllo che la numerazione iniziale non superi quella totale
				if($a > $num_pags) {break;}
				
				// se sto stampando la pagina corrente, non metto link 
				if($this->curr_pag == $a) {$pc_list .='<a id="curr_pag">'.$a.'</a>';}
				else {$pc_list .='<a '.$this->get_link($a).' id="'.$this->pag_param.'_'.$a.'" class="goto_pag" title="'. __('go to page', 'pc_ml').' '.$a.'">'.$a.'</a>';}
			}
			// stampo l'ultimo link che porta all'ultima pagina se le pagine son più di 10
			if($num_pags > 10) {$pc_list .='<a '.$this->get_link($num_pags).' id="'.$this->pag_param.'_'.$num_pags.'" class="goto_pag" title="'. __('go to the last page', 'pc_ml').'">&raquo;</a>';}
		}
		
		/////////////////////////////////////////////////////////
		// se è nelle ultime 10 pagine stampo gli ultimi risultati
		elseif($num_pags > 10 && (int)$this->curr_pag <= $num_pags && (int)$this->curr_pag >= ($num_pags - 6)) {
			$lp_start = $num_pags - 10;
			
			// stampo il primo link che porta alla prima pagina
			$pc_list .='<a '.$this->get_link(1).' id="'.$this->pag_param.'_1" class="goto_pag" title="'. __('go to the first page', 'pc_ml').'">&laquo;</a>';
			
			for($a=$lp_start; $a <= $num_pags; $a++) {
				// se sto stampando la pagina corrente, non metto link 
				if($this->curr_pag == $a) {$pc_list .='<span id="curr_pag">'.$a.'</span>';}
				else {$pc_list .='<a '.$this->get_link($a).' id="pag_'.$a.'" class="goto_pag" title="'. __('go to page', 'pc_ml').' '.$a.'">'.$a.'</a>';}
			}
		}
			
		/////////////////////////////////////////////////////////
		// altrimenti stampo le 3 pagine antecedenti e precedenti
		else {	
			$lp_start = $this->curr_pag - 4;
			$lp_end = $lp_start + 9;
			
			// stampo il primo link che porta alla prima pagina
			$pc_list .='<a '.$this->get_link(1).' id="pag_1" class="goto_pag" title="'. __('go to the first page', 'pc_ml').'">&laquo;</a>';
			
			for($a=$lp_start; $a <= $lp_end; $a++) {
				// se sto stampando la pagina corrente, non metto link 
				if($this->curr_pag == $a) {$pc_list .='<span id="curr_pag">'.$a.'</span>';}
				else {$pc_list .='<a '.$this->get_link($a).' id="pag_'.$a.'" class="goto_pag" title="'. __('go to page', 'pc_ml').' '.$a.'">'.$a.'</a>';}
			}
			
			// stampo l'ultimo link che porta all'ultima pagina se le pagine son più di 10
			$pc_list .='<a '.$this->get_link($num_pags).' id="pag_'.$num_pags.'" class="goto_pag" title="'. __('go to the last page', 'pc_ml').'">&raquo;</a>';
		}
		
		
		if($pre != '') {$pc_list = $pre . $pc_list;}
		if($after != '') {$pc_list = $pc_list . $after;}
		
		return $pc_list;
	}
	
}
