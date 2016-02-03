<?php
require 'BaseController.php';

class PrivacyController extends BaseController
{
	public function init()
	{
		$this->view->title = 'Privacy';
		$this->view->page = 'terms';
		$this->view->mycontroller = 'PrivacyController';
		
		$this->view->language = 'th';
		
		parent::init();
	}
	public function indexAction()
	{
		$db_static = ($this->view->config->db->static->enable == 'yes' ? Zend_Registry::get('db_static') : null);
	}
}