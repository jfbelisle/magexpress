<?php

/* GET CURRENT USER DATA (fixed fields or metas)
 * @param (string/bool) field = name of the field to return - use false to get everything
 *
 * @return (string/array) value found - could be a string or an array or object (depending on what is stored)
 */
function pcud_get_user_data($field = false) {
	include_once(PC_DIR . '/public_api.php');
	
	if($field === false) {$field = true;} // invert bool to be compatible with v5
	return pc_user_logged($field);
}

