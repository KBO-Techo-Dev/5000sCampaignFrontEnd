<?php
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// PHP common configurations & path settings
header('Cache-Control: no-cache');
// server should keep session data for AT LEAST 1 hour
ini_set('session.gc_maxlifetime', 108000);
// each client should remember their session id for EXACTLY 1 hour
session_set_cookie_params(108000);

session_start();

// Change this variable to your web application directory relative to APP_ROOT
$dir_array = explode('/', getcwd());
//$app_folder = $dir_array[count($dir_array)-1]; 
$app_folder = "";
$configuration_folder = 'configuration';
define('APP_FOLDER', $app_folder . '/');
define('ABSOLUTE_PATH', '/var/www/5000s.org/' . APP_FOLDER);
define('APP_ROOT', $_SERVER['DOCUMENT_ROOT'] . '/'); // Change this variable to absolute path (/var/...) when execute by shell
define('CONFIG_FOLDER', $configuration_folder . '/');

set_include_path(
	'.' . PATH_SEPARATOR .
	APP_ROOT . "$app_folder/framework/" . PATH_SEPARATOR .
	APP_ROOT . "$app_folder/libs" . PATH_SEPARATOR .
	APP_ROOT . "$app_folder/views/scripts" . PATH_SEPARATOR .	
	APP_ROOT . "$app_folder/facebook-php-sdk-master/src/" . PATH_SEPARATOR .
	get_include_path()
);

// Zend Framework utility classes.
require 'Zend/Loader.php';
require 'Zend/Config/Ini.php';
require 'Zend/Registry.php';
require 'Zend/Config/Xml.php';
require 'Zend/Log/Writer/Stream.php';
require 'Zend/Log.php';
require 'facebook-php-sdk-master/src/facebook.php';

$dict = new Zend_Config_Ini(APP_ROOT . APP_FOLDER  . CONFIG_FOLDER . 'dictionary.ini', 'th'); // Load dictionary.ini from root.
Zend_Registry::set('dict', $dict);
$config = new Zend_Config_Ini(APP_ROOT . APP_FOLDER  . CONFIG_FOLDER . 'config.ini', 'production'); // Load config.ini from root.
Zend_Registry::set('config', $config);
($config->display_compile_error == 1) ? error_reporting(E_ALL) : error_reporting(0);
//date_default_timezone_set($config->timezone);
ini_set('display_startup_errors', $config->display_runtime_error);
ini_set('display_errors', $config->display_runtime_error);
// Initial Objects ////////////////////////////////////////////////////////////////////////////////////////////////////////
	require 'GameManager.php';	
	require 'Utils.php';	
	require 'NetworkAPI.php';
	$network_api = new NetworkAPI();
	$network_api->setConfig($config->aes_key, $config->aes_iv, $config->signature_salt);
	Zend_Registry::set('network_api', $network_api);
	$utils = new Utils();
	$facebook = new Facebook(array(
			'appId'  => $config->facebook_app_id,
			'secret' => $config->facebook_app_secret,
	));	
	Zend_Registry::set('facebook', $facebook);
// MEMCACHE SETUP  /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Setup memcache if enabled.
//if ($config->memcahce_enable == 'yes') {	
	
	require 'Zend/Cache.php';
	$frontendOptions = array(
	   'lifetime' => $config->memcache->frontend->lifetime->days,
	   'automatic_serialization' => true
	);
	$backendOptions = array(
		'servers' => array(
			array(
			  'host' => $config->memcache->servers->ip,
			  'port' => $config->memcache->servers->port, 
			  'persistent' => true
			)
		),
		'compression' => false
	);
	try {
		$cache = Zend_Cache::factory('Core', 'Memcached', $frontendOptions, $backendOptions);
		Zend_Registry::set('cache', $cache);
		$cache_hour = Zend_Cache::factory(
			'Core',
			'Memcached', 
			array(
			   'lifetime' => $config->memcache->frontend->lifetime->hours,
			   'automatic_serialization' => true			
			),
			$backendOptions
		);
		Zend_Registry::set('cache_hour', $cache_hour);
		$cache_minute = Zend_Cache::factory(
			'Core',
			'Memcached', 
			array(
			   'lifetime' => $config->memcache->frontend->lifetime->minutes,
			   'automatic_serialization' => true			
			),
			$backendOptions
		);
		Zend_Registry::set('cache_minute', $cache_minute);
		$cache_output = Zend_Cache::factory('Output', 'Memcached', $frontendOptions, $backendOptions);
		Zend_Registry::set('cache_output', $cache_output);
	} catch (Zend_Exception $e) {	}
//}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// If call existing module's name (?module=[name]) then call that module instead of normally start Zend Framwork
if (isset($_REQUEST['module'])) {
	if (file_exists(ABSOLUTE_PATH . $_REQUEST['module'] . '.php')) {
		include $_REQUEST['module'] . '.php';
	}
	else {
		echo 'Module "' . $_REQUEST['module'] . '" does not exists.';
	}
} else {
		
	// Connect to DB to validate username & password
	// Notes: *** NOT IMPLEMENT YET ***
	//    Configuration of DB users/game is divided into location.
	//    They will point to appropiate server location with "load balance" logic.	
	
//Database Connection ////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Original DB
	require 'Zend/Db.php';
	// Static DB
	if( $config->db->static->enable == 'yes' )
	{
		try {
			$db_static = Zend_Db::factory($config->db->static->adapter, $config->db->static->config->toArray());
			$db_static->getProfiler()->setEnabled($config->db->static->params->profiler);
			$db_static->setFetchMode(Zend_Db::FETCH_ASSOC);
			$db_static->getConnection()->exec("SET NAMES UTF8");
			Zend_Registry::set('db_static', $db_static);
		} catch (Zend_Exception $e) {
			echo $e;
		}
	}
	// Main DB
	if( $config->db->main->enable == 'yes' )
	{
		try {
			$db_main = Zend_Db::factory($config->db->main->adapter, $config->db->main->config->toArray());
			$db_main->getProfiler()->setEnabled($config->db->main->params->profiler);
			$db_main->setFetchMode(Zend_Db::FETCH_ASSOC);
			$db_main->getConnection()->exec("SET NAMES UTF8");
			Zend_Registry::set('db_main', $db_main);
		} catch (Zend_Exception $e) {
			echo $e;
		}
	}
//Load ip's description //////////////////////////////////////////////////////////////////////////////////////////////////	
	$client = array(
		'ip' => Utils::getRealIpAddr(),
		'country_code' => 'N/A',
	);
	//unset( $_SESSION['client'] );
	if(isset($_SESSION['client'])) {
		$client = $_SESSION['client'];
	}	
	else {
		//echo '[CALL] Utils::getClientInfo()';
		$client = Utils::getClientInfo($config, ($config->ipinfodb->enable == "yes"));		
		$client['x_wap_profile'] = isset($_SERVER['HTTP_X_WAP_PROFILE']) ? getenv('HTTP_X_WAP_PROFILE') : 'none';
		$client['ua']			 = getenv('HTTP_USER_AGENT');	
		$client['mobile_data']	 = array();		
		if($client['x_wap_profile'] and ($client['x_wap_profile'] != 'none')) {
			$client['mobile_data'] = Utils::readXWapProfile($client['x_wap_profile']);	
		}
		$_SESSION['client']  = $client;	
	}
	Zend_Registry::set('game', new GameManager());
	Zend_Registry::set('client', $client);
//Checking Login ///////////////////////////////////////////////////////////////////////////////////////////////////////
	$controller_name = Utils::extractControllerName(APP_FOLDER, getenv('REQUEST_URI'));
	
	/*
	if( $controller_name == 'mag' || $controller_name == 'magazine')
	{
		$magazine_uri = "http://wwww.facebook.com";
		header( "location: " . $magazine_uri );
		exit(0);		
	}
	*/
	$api_token = '';
	$me = null;
	$hasSession = false;
	// NOTES: $_COOKIE['api_token'] has not been used because of PHPSESSID keep it alive even $_COOKIE is deleted.
	//        In the mean time, i use $_SESSION['api_token' . codename] instead.
	// UPDATE: 2015-05-13 17:10 PM
	//if (isset($_COOKIE['api_token'])) {
	if (isset($_SESSION['api_token' . $config->codename])) {
		try {
			//$api_token = $_COOKIE['api_token'];
			$api_token = $_SESSION['api_token' . $config->codename];
			if(!isset($_SESSION['me'])) {
				$response = $network_api->call($config->api_domain. 'account/info/', array('token' => $api_token));
				if($response["status"] == 200) {
					$me = $response["data"];
					$_SESSION['me'] = $me;
					$hasSession = true;
				} else {
					setcookie('api_token', '', time()-3600);
				}
			} else {
				$me = $_SESSION['me'];
				$hasSession = true;
			}
		} catch (Exception $e) { 
			echo $e;
		}
	}
	// LANGUAGE set and/or switch
	$available_language = array('th', 'en');
	if( isset($_REQUEST['language'])
		&& in_array( $_REQUEST['language'], $available_language )
		&& isset($_SESSION['display_language'])
		&& $_REQUEST['language'] != $_SESSION['display_language']  )
	{
		$_SESSION['display_language'] = $_REQUEST['language'];
		$refresh_uri = 'http://' . $config->domain . '/' . $controller_name;
		header( "location: " . $refresh_uri );
		exit(0);
	}
	else if( !isset($_SESSION['display_language']) )
	{
		$_SESSION['display_language'] = ($_SESSION['client']['country_code']) == 'TH' ? 'th' : 'en';
	}
	// Force 'REFRESH' if parameter 'logout' is found
	if(isset($_REQUEST['logout']) and $_REQUEST['logout'] == 1)
	{
		//$hasSession = false;
		//$controller_name = 'unknown';
		//unset($_SESSION['display_language']);
		unset($_SESSION['api_token' . $config->codename]);
		unset($_SESSION['me']);
		unset($_COOKIE['api_token']);
		setcookie('api_token' , '', time()-3600);
		
		$home_uri = 'http://' . $config->domain;
		header( "location: " . $home_uri );
		exit(0);
	}
	Zend_Registry::set('api_token', $api_token);
	Zend_Registry::set('me',$me);
//LOADING USER DATA AND FRAMEWORK /////////////////////////////////////////////////////////////////////////////////	
	include 'framework.php';
}