<?php
require 'BaseController.php';

class ContactsController extends BaseController
{
	public function init()
	{
		$this->view->title = 'CONTACTS';
		$this->view->page = 'contacts';
		$this->view->mycontroller = 'ContactsController';
		
		$this->view->language = 'th';
		
		parent::init();
	}
	public function indexAction()
	{
		$db_static = ($this->view->config->db->static->enable == 'yes' ? Zend_Registry::get('db_static') : null);
	}
}