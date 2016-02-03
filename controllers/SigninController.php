<?php
require 'BaseController.php';

class SigninController extends BaseController
{
	public function init()
	{
		$this->view->title = 'Sign in';
		$this->view->page = 'signin';
		$this->view->mycontroller = 'SigninController';
		
		parent::init();
		$this->network_api = Zend_Registry::get('network_api');
	}
	public function indexAction()
	{		
		$me = Zend_Registry::get('me');
		$this->db_static = ($this->view->config->db->static->enable == 'yes' ? Zend_Registry::get('db_static') : null);
		$this->view->id = $this->getParam('id');
		$command = $this->getParam('command');
		//echo '[Sign in] Command : ' . $command . '<br/>';
		if(isset($_REQUEST['code'])) {
			$command = 'fblogin';
		}
		if( $me == null ) {
			if($command == 'login')
			{
				$this->loginProcess();
			} elseif ($command == 'register') {
				$this->registerProcess();
			} elseif ($command == 'fblogin') {
				$this->fbloginProcess();
			}
		} else {
			$this->readyProcess($me);
		}
	}
	public function forgetAction()
	{
		$this->view->email_has_been_sent = false;
		$this->view->email_not_found = false;
		
		$command = $this->getParam('command');
		$this->view->email = $this->getParam('email');

		if( $command == 'forget' )
		{
			$input = array(
				'language'	=> $this->view->language,
				'email' 	=> $this->view->email,
			);
			$result = $this->network_api->call($this->view->config->api_domain . "account/forget", $input);
			
			if( $result['status'] == 200 ) {
				$this->view->email_has_been_sent = true;
				$this->view->email = $result['data']['email'];
			} else {
				$this->view->email_not_found = true;
			}
			$this->view->result = $result;
		}
	}
	public function registerAction()
	{
		$me = Zend_Registry::get('me');
		$this->db_static = ($this->view->config->db->static->enable == 'yes' ? Zend_Registry::get('db_static') : null);
		$this->view->id = $this->getParam('id');
		$command = $this->getParam('command');
		//echo '[Register] Command : ' . $command . '<br/>';
		if(isset($_REQUEST['code'])) {
			$command = 'fblogin';
		}
		if($me == null) {
			if($command == 'login')
			{
				$this->loginProcess();
			} elseif ($command == 'register') {
				$this->registerProcess();
			} elseif ($command == 'fblogin') {
				$this->fbloginProcess();
			}
		} else {
			$this->readyProcess($me);
		}		
	}
	private function loginProcess()
	{
		$username = $this->getParam('email');
		$password = $this->getParam('password');
		$input = array(
			"username" => $username,
			"password" => $password,
		);
		$result = $this->network_api->call($this->view->config->api_domain."account/login",$input);
		if($result["status"] == 200) {
			$me = $result["data"]["user"];
			setcookie('api_token', $result['data']['token'], time()+60*60*24*365);
			$_SESSION['api_token' . $this->view->config->codename] = $result['data']['token'];
			$_SESSION['me'] = $me;

			$this->readyProcess($me);
		}  else {
			$this->view->email = $username;
			$this->view->password = $password;
			$this->view->error = '<p><font color=red>' . $this->view->dict['ERROR_SIGNIN.DETAIL'] . '</font></p>';
			$this->view->page = "signin";
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
		$result = $this->network_api->call($this->view->config->api_domain."account/register",$input);
		//echo '[Result]<br/>';
		//print_r($result);
		if($result["status"] == 200) {
			$me = $result["data"]["user"];
			setcookie('api_token', $result['data']['token'], time()+60*60*24*365);
			$_SESSION['api_token' . $this->view->config->codename] = $result['data']['token'];
			$_SESSION['me'] = $me;
			
			$this->readyProcess($me);
		} elseif ($result["status"] == 504) {
			//Incorrect username or password case
			$this->view->error = '<p><font color="red">' . str_replace('[EMAIL]', $username, $this->view->dict['ALREADY_USED_EMAIL']) . '</font></p>';
			$this->view->page = "signin";
		} else {
			$this->view->first_name 	= $first_name;
			$this->view->last_name 		= $last_name;
			$this->view->email 			= $username;
			$this->view->password 		= $password;
			$this->view->year_of_birth 	= $year_of_birth;
			$this->view->nationality	= $nationality;
			$this->view->error = '<p><font color=red>' . $this->view->dict['ERROR_REGISTER.DETAIL'] . '</font></p>';
			$this->view->page = "signin";
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
				"auth" => $access_token					
			);
			$result = $this->network_api->call($this->view->config->api_domain."account/fblogin",$input);
			if($result["status"] == 200) {
				$me = $result["data"]["user"];
				setcookie('api_token', $result['data']['token'], time()+60*60*24*365);
				$_SESSION['api_token' . $this->view->config->codename] = $result['data']['token'];
				$_SESSION['me'] = $me;
				
				$this->readyProcess($me);
			} else {
				$this->view->error = "<p><font color=red>Internal Server Error!!! Please try again later.</font></p>";
			}			
		} else {			
			$login_url = $facebook->getLoginUrl();
			header( "location: " . $login_url );
		}
	}
	private function readyProcess($me)
	{
		$this->view->message = '<br/>' . $this->view->dict['HELLO'] . ' ' . $me['first_name'] . ' ' . $me['last_name'] . '! ' . $this->view->dict['WELCOME_TO_SERVICE'];
		$this->view->message .= '<br/><br/><a href="' . $this->view->self_root . '" class="ui-btn ui-btn-c ui-corner-all" style="max-width:330px;">' . $this->view->dict['START'] . '</a>';
	}
}