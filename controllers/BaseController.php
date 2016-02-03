<?php

class BaseController extends Zend_Controller_Action
{
	public function init()
	{
// FACEBOOK ------------------------------------------------------------------------------------------------
		// [OBSOLETED] and ->dict use for queried db_dictionary
		//$this->view->dict 			= Zend_Registry::get('dict');
		$this->view->config 			= Zend_Registry::get('config');	
		$this->view->metadata 	 		= Zend_Registry::get('metadata');
		$this->view->image_root			= Zend_Registry::get('image_root');	
		$this->view->self_root			= Zend_Registry::get('self_root');
		$this->view->self 				= $this->view->self_root . $this->view->page;
		$this->view->request_uri 		= getenv('REQUEST_URI');
		$this->view->client				= Zend_Registry::get('client');	
		$this->view->action				= $this->getRequest()->getActionName();

		$this->view->language 			= $_SESSION['display_language'];
		$this->view->mobile_view 		= !isset($_SESSION['screen_width']) ? false : ((isset($_SESSION['screen_width']) && $_SESSION['screen_width'] > 600) ? false : true);
		
		$db_static = ($this->view->config->db->static->enable == 'yes' ? Zend_Registry::get('db_static') : null);
		$db_main = ($this->view->config->db->main->enable == 'yes' ? Zend_Registry::get('db_main') : null);

		// Load DICTIONARY from database.
		$tag = 'tag_' . $this->view->language;
		$this->view->dict = array();
		$result = $db_static->fetchAll('SELECT codename,' . $this->view->language . ',' . $tag . ' FROM db_dictionary WHERE enable=? AND ' . $this->view->mycontroller . '=?', array('yes', 'yes'));
		foreach( $result as $dict )
		{
			$content = $dict[$tag] != 'none' ? '<' . $dict[$tag] . '>' : '';
			$content .=	$dict[$this->view->language];
			$content .= $dict[$tag] != 'none' ? '</' . $dict[$tag] . '>' : '';
			$this->view->dict[$dict['codename']] = $content;
		}
		
		if( !isset($_SESSION['all_countries']) )
		{
			$rest_countries = @file_get_contents($this->view->config->rest_countries_api);
			$_SESSION['all_countries'] = @json_decode($rest_countries, true);
		}
		$this->view->all_countries = isset($_SESSION['all_countries']) ? $_SESSION['all_countries'] : '';
		
		if( $this->view->config->maintainance == 'yes' )
		{
			include 'maintenance.html';
			exit(0);
			return false;
		}
		
		return true;
	}
	public function getParam($inKey)
	{
		return ($this->_getParam($inKey) == '') ? '' : $this->_getParam($inKey);
	}
	public function getParamEx($inKey, $inToken)
	{
		$token = base64_decode($inToken);
		
		return $token;
	}
	public function getUrl($inArray, $inUrl)
	{
		/*
		$token = '';
		if( sizeof($inArray) > 0 )
		{
			foreach( $inArray as $key => $value )
			{
				$token .= '&' . $key . '=' . $value;
			}
		}
		else
		{
			$token = 'none';
		}
		return $inUrl . '?token=' . base64_encode($token);
		*/
		return $inUrl;
	}
	public function sendResponse($inResponse)
	{
		//$inResponse["execute_time"] =  microtime (true) - $this->view->start_time;
		//$inResponse["rand"] = microtime(true) . "";
		$content = json_encode($inResponse);
		echo Utils::rijndaelEncrypt( 
			$content , 
			$this->view->config->aes_key , 
			$this->view->config->aes_iv
		);
		exit();
	}
}
