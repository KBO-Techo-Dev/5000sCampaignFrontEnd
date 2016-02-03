<?php
require 'BaseController.php';

class TermsController extends BaseController
{
	public function init()
	{
		$this->view->title = 'Terms & Conditions';
		$this->view->page = 'terms';
		$this->view->mycontroller = 'TermsController';
		
		$this->view->language = 'th';
		
		parent::init();
	}
	public function indexAction()
	{
		$db_static = ($this->view->config->db->static->enable == 'yes' ? Zend_Registry::get('db_static') : null);
	}
}