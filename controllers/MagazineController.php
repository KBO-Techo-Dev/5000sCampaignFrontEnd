<?php
require 'BaseController.php';

class MagazineController extends BaseController
{
	public function init()
	{
		$this->view->title = 'Magazine';
		$this->view->page = 'magazine';
		$this->view->mycontroller = 'MagazineController';
		
		$this->view->language = 'th';
		
		parent::init();
	}
	public function indexAction()
	{
		
	}
}