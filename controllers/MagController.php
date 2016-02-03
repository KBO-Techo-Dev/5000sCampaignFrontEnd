<?php
require 'BaseController.php';

class MagController extends BaseController
{
	public function init()
	{
		$this->view->title = 'Mag';
		$this->view->page = 'mag';
		$this->view->mycontroller = 'MagController';
		
		$this->view->language = 'th';
		
		parent::init();
	}
	public function indexAction()
	{
		$magazine_uri = $this->view->config->magazine_fanpage;
		header( "location: " . $magazine_uri );	
	}
}