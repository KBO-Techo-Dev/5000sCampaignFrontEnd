<?php
require 'BaseController.php';

class SignatureController extends BaseController
{
	public function init()
	{
		$this->view->title = 'Signature';
		$this->view->page = 'signature';
		$this->view->mycontroller = 'SignatureController';
		
		$this->view->language = 'th';
		
		parent::init();
		$this->network_api = Zend_Registry::get('network_api');
	}
	public function indexAction()
	{
		$db_static = ($this->view->config->db->static->enable == 'yes' ? Zend_Registry::get('db_static') : null);
		
		$this->view->id = $this->getParam('id');
		$command = $this->getParam('command');
		if(isset($_REQUEST['code'])) {
			$command = 'fblogin';
		}
		
		$this->view->campaign = array();
		$this->view->comments = array();
		$this->view->recent_signatures = array();
		$this->view->timeline = array();
		$this->view->letters = array();
	
		//$api_token = isset($_COOKIE['api_token']) ? $_COOKIE['api_token'] : '';
		$api_token = isset($_SESSION['api_token' . $this->view->config->codename]) ? $_SESSION['api_token' . $this->view->config->codename] : '';
		
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
					'campaign_id'		=> $data['campaign_id'],
					'author_url'		=> $data['author_id'] > 0 ? '' : '#',
					'author'			=> $data['author_id'] > 0 ? $data['author_name'] : '',
					'date'				=> $data['date'],
					'image'				=> $this->view->image_root . 'campaign/' . $data['campaign_id'] . '/1.jpg',
					'title'				=> $data['title'],
					'brief'				=> $data['brief'],
					'detail'			=> $data['detail'],
					'copied_head'		=> $data['copied_head'],
					'copied_full'		=> $data['copied_full'],
					
					'target'			=> $data['target'],
					'signatures'		=> $data['signatures'],
					
					'already_support'	=> $data['already_support'],
				));
				if( $data['campaign_id'] == $this->view->id )
				{
					if( isset($data['comments'][0]) )
					{
						foreach( $data['comments'] as $c )
						{
							array_push($this->view->comments, array(
								'name'		=> $c['display_name'],
								'comment' 	=> $c['display_comment'],
							));
						}
					}
					if( isset($data['recent_signatures'][0]) )
					{
						foreach( $data['recent_signatures'] as $c )
						{
							array_push($this->view->recent_signatures, array(
								'name'		=> $c['first_name'] . ' ' . $c['last_name'],
								'period' 	=> $c['date'] . ' ' . $c['time'],
							));
						}
					}
					if( isset($data['timeline'][0]) )
					{
						foreach( $data['timeline'] as $c )
						{
							array_push($this->view->timeline, array(
								'name'			=> $c['date'],
								'content' 		=> $c['display_content'],
								'icon'			=> $c['icon'],
								'icon_color'	=> $c['icon_color'],
								'image'			=> $c['image'],
								'image_url'		=> $c['image_url'],
							));
						}
					}
					if( isset($data['letters'][0]) )
					{
						foreach( $data['letters'] as $c )
						{
							array_push($this->view->letters, array(
								'name'		=> $c['name'],
								'content' 	=> $c['content'],
							));
						}
					}					
				}
			}
		}
		else
		{
			
		}
		
		$this->view->thankyou = false;
		$this->view->error = '';
		
		$me = Zend_Registry::get('me');
		if( $me == null ) {
			if ($command == 'register') {
				$this->registerProcess();
			} elseif ($command == 'fblogin') {
				$this->fbloginProcess();
			}
		}
		else
		{
			if ($command == 'support')
			{
				$this->view->thankyou = true;
				$this->supportProcess($me, $api_token);
			}
		}
		//$this->view->debug = 'command=' . $command . ', token=' . $api_token;
		//$this->view->debug = $_COOKIE;
		//$this->view->debug = $me;
		//$this->view->debug = $_REQUEST;
	}
	private function supportProcess($me, $inToken)
	{
		$input = array(
			'token'			=> $inToken,
			'campaign_id'	=> $this->getParam('id'),
			'uid'			=> $me['uid'],
			'comment'		=> $this->getParam('textarea-comment'),
			'fb_share'		=> $this->getParam('checkbox-fbshare') == 'on' ? 'yes' : 'no',
			'show_name'		=> $this->getParam('checkbox-showname') == 'on' ? 'yes' : 'no',
		);
		// $this->view->debug = $input; // [DEBUG]
		$result = $this->network_api->call($this->view->config->api_domain . 'signature/sign', $input);
		if( $result['status'] == 200 )
		{

		}
		else
		{
			$this->view->error = str_replace( '[ERROR_CODE]', $result['status'], $this->view->dict['ERROR_SUPPORT.DETAIL'] );
		}
	}
	private function registerProcess()
	{
		$first_name = $this->getParam('first_name');
		$last_name = $this->getParam('last_name');
		$username = $this->getParam('email');
		$password = $this->getParam('password');
		$year_of_birth = $this->getParam('yearofbirth');
		$nationality = $this->getParam('select-native-nationality');
		
		$input = array(
			"first_name" 	=> $first_name,
			"last_name" 	=> $last_name,
			"username" 		=> $username,
			"password" 		=> $password,
			"year_of_birth"	=> $year_of_birth,
			"nationality"	=> $nationality,
			"client" 		=> $this->view->client
		);
		$result = $this->network_api->call($this->view->config->api_domain . 'account/register', $input);
		//echo '[Result]<br/>';
		//print_r($result);
		if( $result["status"] == 200 ) {
			$me = $result["data"]["user"];
			setcookie("api_token", $result['data']['token'], time()+60*60*24*365);
			$_SESSION['api_token' . $this->view->config->codename] = $result['data']['token'];
			$_SESSION['me'] = $me;

			$this->view->thankyou = true;
			$this->supportProcess($me, $result['data']['token']);
		} elseif ($result["status"] == 504) {	// ReturnStats::DUPLICATED_USER
			$this->view->error = str_replace('[EMAIL]', $username, $this->view->dict['ALREADY_USED_EMAIL']);
		} else {
			$this->view->first_name = $first_name;
			$this->view->last_name = $last_name;
			$this->view->email = $username;
			$this->view->password = $password;
			$this->view->year_of_birth 	= $year_of_birth;
			$this->view->nationality	= $nationality;
			$this->view->error = $this->view->dict['ERROR_REGISTER.DETAIL'];
		}
	}
	private function fbloginProcess()
	{
		$facebook = Zend_Registry::get("facebook");		
		
		$user = $facebook->getUser();
				
		if($user)
		{	
			$access_token = $facebook->getAccessToken();
			$input = array(
				'auth' => $access_token					
			);
			$result = $this->network_api->call($this->view->config->api_domain . 'account/fblogin', $input);
			if($result['status'] == 200) {
				$me = $result["data"]["user"];
				setcookie('api_token', $result['data']['token'], time()+60*60*24*365);
				$_SESSION['api_token' . $this->view->config->codename] = $result['data']['token'];
				$_SESSION['me'] = $me;
				
				header("location: " . $this->view->self . '/index/id/' . $this->view->id);
			} else {
				$this->view->error = str_replace( '[ERROR_CODE]', $result['status'], $this->view->dict['ERROR_SUPPORT.DETAIL'] );
			}			
		} else {			
			$login_url = $facebook->getLoginUrl();
			header( "location: " . $login_url );
		}		
	}
}