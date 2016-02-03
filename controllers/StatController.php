<?php
require 'BaseController.php';

class StatController extends BaseController
{
	public function init()
	{
		$this->view->title = 'Stat';
		$this->view->page = 'stat';
		$this->view->mycontroller = 'StatController';
		
		$this->view->language = 'th';
		
		parent::init();
		$this->network_api = Zend_Registry::get('network_api');
	}
	public function indexAction()
	{
		$this->view->mode = $this->getParam('mode') ? $this->getParam('mode') : 1;
		$this->view->picked_date = $this->getParam('date') ? $this->getParam('date') : date('Y-m-d');
		
		$api_token = isset($_SESSION['api_token' . $this->view->config->codename]) ? $_SESSION['api_token' . $this->view->config->codename] : '';

		$input = array(
			'token'	=> $api_token,
			'mode'	=> $this->view->mode,
			'date'	=> $this->view->picked_date,
		);
		$this->view->stat = array();
		$total = 0;
		
		$result = $this->network_api->call($this->view->config->api_domain . 'stat/query', $input);
		if( $result['status'] == 200 )
		{
			foreach( $result['data'] as $i => $data )
			{
				array_push( $this->view->stat, array(
					'caption'	=> $data['caption'],
					'nums'		=> $data['nums'],
				));
				$total += $data['nums'];
			}
			//$total += 1000;
		}
		else
		{
			
		}
		$this->view->total = $total;
	}
}