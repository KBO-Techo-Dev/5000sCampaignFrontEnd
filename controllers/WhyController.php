<?php
require 'BaseController.php';

class WhyController extends BaseController
{
	public function init()
	{
		$this->view->title = 'WHY PROTECT?';
		$this->view->page = 'why';
		$this->view->mycontroller = 'WhyController';
		
		$this->view->language = 'th';
		
		parent::init();
	}
	public function indexAction()
	{
		$db_static = ($this->view->config->db->static->enable == 'yes' ? Zend_Registry::get('db_static') : null);
	}
}