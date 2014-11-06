<?php 
class App extends CI_Controller {

	function __construct() {
		parent::__construct();	
		
	}
	
	function index() {
	}
	
	// TODO: cachear este metodo; que devuelva un archivo en los assetss
	function selectMenuAndTranslations() {
//		$this->output->cache(50);
		
		$this->load->driver('cache', array('adapter' => 'file'));
		$groups = $this->session->userdata('groups');
		
		if (!is_array($this->cache->file->get('MENU_PROFILE_'.json_encode($groups)))) {
			$this->load->model('Menu_Model');
			$this->Menu_Model->createMenuCache($groups);
		}
		
		$aMenu = array(
			'MENU_PROFILE'  => array(
				'items'     => $this->cache->file->get('MENU_PROFILE_'.json_encode($groups)), 
				'className' => 'menuProfile nav navbar-nav navbar-right',
				'parent'    => '.navbar-ex1-collapse'
			),
			'MENU_PUBLIC'   => array(
				'items'     => $this->cache->file->get('MENU_PUBLIC_'.json_encode($groups)), 
				'className' =>'menuPublic',
				'parent'    => '.menu.label-primary div',
			)
		);
		
		$lines = array_keys($this->lang->language);
		
		$aLangs = array();
		foreach ((array)$lines as $line) {
			$aLangs[$line] = $this->lang->line($line);
		}

		return loadViewAjax(true, array(
			'aMenu'   => $aMenu,
			'aLangs'  => $aLangs,
		));
	}
	
	function uploadFile() {
		$this->load->view('includes/uploadfile', array( 'fileupload' => array ()));
	}
}

