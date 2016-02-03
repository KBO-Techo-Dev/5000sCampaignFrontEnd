<?php
require 'BaseController.php';

class SettingsController extends BaseController
{
	public function init()
	{
		$this->view->title = 'Settings';
		$this->view->page = 'settings';
		$this->view->mycontroller = 'SettingsController';
		
		$this->view->language = 'th';
		
		parent::init();
	}
	public function indexAction()
	{
		$db_static = ($this->view->config->db->static->enable == 'yes' ? Zend_Registry::get('db_static') : null);		
	}
}