<?php
class Coins_Model extends CI_Model {
	function select(){
		return $this->db->order_by('currencyName')->get('coins')->result_array();
	}
}
