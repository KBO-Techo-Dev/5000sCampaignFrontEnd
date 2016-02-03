<?php
require 'BaseController.php';

class ArticlesController extends BaseController
{
	public function init()
	{
		$this->view->title = 'ARTICLES';
		$this->view->page = 'articles';
		$this->view->mycontroller = 'ArticlesController';
		
		$this->view->language = 'th';
		
		parent::init();
		$this->network_api = Zend_Registry::get('network_api');
	}
	public function indexAction()
	{
		//$db_static = ($this->view->config->db->static->enable == 'yes' ? Zend_Registry::get('db_static') : null);
		$this->view->id = $this->getParam('id');
		$this->view->prev_id = 0;
		$this->view->next_id = 0;
		
		$this->view->articles = array();
		
		//$api_token = isset($_COOKIE['api_token']) ? $_COOKIE['api_token'] : '';
		$api_token = isset($_SESSION['api_token' . $this->view->config->codename]) ? $_SESSION['api_token' . $this->view->config->codename] : '';

		$input = array(
			'token'		=> $api_token,
			'language'	=> $this->view->language,
			'command'	=> 'articles',
		);
		$result = $this->network_api->call($this->view->config->api_domain . 'info/query', $input);
		if( $result['status'] == 200 )
		{
			$length = sizeof($result['data']);
			if( $this->view->id == 0 && $length > 0 )
				$this->view->id = $result['data'][0]['articles_id'];
			
			foreach( $result['data'] as $i => $data )
			{
				array_push( $this->view->articles, array(
					'articles_id'	=> $data['articles_id'],
					'author_url'	=> $data['author_id'] > 0 ? '' : '#',
					'author'		=> $data['author_id'] > 0 ? $data['author_name'] : '',
					'date'			=> date( 'd M Y', strtotime($data['date']) ),
					'image'			=> $this->view->image_root . 'articles/' . $data['articles_id'] . '/1.jpg',
					'title'			=> $data['title'],
					'brief'			=> $data['brief'],
					'detail'		=> $data['detail'],
				));
				if( $data['articles_id'] == $this->view->id && $length > 1 )
				{
					if( isset($result['data'][$i - 1]) )
						$this->view->prev_id = $result['data'][$i - 1]['articles_id'];
					if( isset($result['data'][$i + 1]) )
						$this->view->next_id = $result['data'][$i + 1]['articles_id'];
				}
			}
		}
		else
		{
			
		}
	}
}