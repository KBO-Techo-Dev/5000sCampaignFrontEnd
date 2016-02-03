<?php
require 'BaseController.php';

class ReportController extends BaseController
{
	public function init()
	{
		$this->view->title = 'Report Case';
		$this->view->page = 'report';
		$this->view->mycontroller = 'ReportController';
		
		$this->view->language = 'th';
		
		parent::init();
	}
	public function indexAction()
	{
		$db_static = ($this->view->config->db->static->enable == 'yes' ? Zend_Registry::get('db_static') : null);
	}
}