<?php
require 'BaseController.php';

class IndexController extends BaseController
{
	public function init()
	{
		$this->view->title = 'Index';
		$this->view->page = 'index';
		$this->view->mycontroller = 'IndexController';
		
		$this->view->language = 'th';
		
		parent::init();
		$this->network_api = Zend_Registry::get('network_api');
	}
	public function indexAction()
	{
		$db_static = ($this->view->config->db->static->enable == 'yes' ? Zend_Registry::get('db_static') : null);
		$this->view->id = $this->getParam('id');
		
		//$api_token = isset($_COOKIE['api_token']) ? $_COOKIE['api_token'] : '';
		$api_token = isset($_SESSION['api_token' . $this->view->config->codename]) ? $_SESSION['api_token' . $this->view->config->codename] : '';
		
		// RETRIEVE CAMPAIGN --------------------------------------------------------------------------------------------------------------------
		$this->view->campaign = array();
		$input = array(
			'token'		=> $api_token,
			'language'	=> $this->view->language,
			'command'	=> 'campaign',
		);
		$result = $this->network_api->call($this->view->config->api_domain . 'info/query', $input);
		if( $result['status'] == 200 )
		{
			$length = sizeof($result['data']);
			if( $this->view->id == 0 && $length > 0 )
				$this->view->id = $result['data'][0]['campaign_id'];
			
			foreach( $result['data'] as $i => $data )
			{
				array_push( $this->view->campaign, array(
					'campaign_id'	=> $data['campaign_id'],
					'author_url'	=> $data['author_id'] > 0 ? '' : '#',
					'author'		=> $data['author_id'] > 0 ? $data['author_name'] : '',
					'date'			=> $data['date'],
					'image'			=> $this->view->image_root . 'campaign/' . $data['campaign_id'] . '/1.jpg',
					'title'			=> $data['title'],
					'brief'			=> $data['brief'],
					'detail'		=> $data['detail'],
					
					'target'		=> $data['target'],
					'signatures'	=> $data['signatures'],
				));
			}
		}
		else
		{
			
		}
		// RETRIEVE NEWS --------------------------------------------------------------------------------------------------------------------
		$this->view->news = array();
		$input = array(
			'token'		=> $api_token,
			'language'	=> $this->view->language,
			'command'	=> 'news',
		);
		$result = $this->network_api->call($this->view->config->api_domain . 'info/query', $input);
		if( $result['status'] == 200 )
		{
			$length = sizeof($result['data']);
			if( $this->view->id == 0 && $length > 0 )
				$this->view->id = $result['data'][0]['news_id'];
			
			foreach( $result['data'] as $i => $data )
			{
				array_push( $this->view->news, array(
					'news_id'		=> $data['news_id'],
					'author_url'	=> $data['author_id'] > 0 ? '' : '#',
					'author'		=> $data['author_id'] > 0 ? $data['author_name'] : '',
					'date'			=> date( 'd M Y', strtotime($data['date']) ),
					'image'			=> $this->view->image_root . 'news/' . $data['news_id'] . '/1.jpg',
					'title'			=> $data['title'],
					'brief'			=> $data['brief'],
					'detail'		=> $data['detail'],
				));
				if( $data['news_id'] == $this->view->id && $length > 1 )
				{
					if( isset($result['data'][$i - 1]) )
						$this->view->prev_id = $result['data'][$i - 1]['news_id'];
					if( isset($result['data'][$i + 1]) )
						$this->view->next_id = $result['data'][$i + 1]['news_id'];
				}
			}
		}
		else
		{
			
		}
	}
}