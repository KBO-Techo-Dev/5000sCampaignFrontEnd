<?php
// Change this variable to your web application directory relative to APP_ROOT
$dir_array = explode('/', $argv[0]);
$max_array = count($dir_array);
//Calling "php -f" inside of project directory
if ($max_array <= 1) {	
	$dir_array = explode('/', getcwd());
	$max_array = count($dir_array);
	$target_str = $dir_array[$max_array-1];
} else {
	$target_str = $dir_array[$max_array-2];
}
$app_folder = $target_str; // Change this variable to your web application directory relative to APP_ROOT
$configuration_folder = 'configuration';
define('APP_FOLDER', $app_folder . '/');
define('APP_ROOT', '/var/www/kreatorian/'); // Change ABSOLUTE_ROOT
define('CONFIG_FOLDER', $configuration_folder . '/');
set_include_path(
	'.' . PATH_SEPARATOR .
	APP_ROOT . "$app_folder/framework/" . PATH_SEPARATOR .
	APP_ROOT . "$app_folder/gamemaker/" . PATH_SEPARATOR .
	APP_ROOT . "$app_folder/libs" . PATH_SEPARATOR .
	APP_ROOT . "$app_folder/views/scripts" . PATH_SEPARATOR .
	APP_ROOT . "$app_folder/facebook-php-sdk-master/src/" . PATH_SEPARATOR .
	get_include_path()
);
require 'Zend/Loader.php';
require 'Zend/Config/Ini.php';
require 'Zend/Registry.php';
require 'Zend/Config/Xml.php';
require 'Zend/Log/Writer/Stream.php';
require 'Zend/Log.php';

$config = new Zend_Config_Ini(APP_ROOT . APP_FOLDER . CONFIG_FOLDER . 'config.ini', 'production');
Zend_Registry::set('config', $config);
//date_default_timezone_set($config->timezone);
error_reporting(E_ALL);
ini_set('display_startup_errors', $config->display_error);
ini_set('display_errors', $config->display_error);
// URL SETUP //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
Zend_Registry::set('self_root' , 'https://'.$config->domain.'/' . $app_folder .'/');
// MEMCACHE SETUP  /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
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
} catch (Zend_Exception $e) {
	echo $e->getMessage() . "\r\n";
}
Zend_Registry::set('image_root', 'https://' . $config->domain . '/' . APP_FOLDER . 'views/images/');
// MSISDN DETECTION  /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
require 'gamemaker/gfw/Utils.php';
$utils = new Utils();
// DATABASE SETUP  /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
require 'Zend/Db.php';
	
try {
	$db_main = Zend_Db::factory($config->db->main->adapter,$config->db->main->config->toArray());
	$db_main->getProfiler()->setEnabled($config->db->main->params->profiler);
	$db_main->query("SET QUOTED_IDENTIFIER ON");
	$db_main->setFetchMode(Zend_Db::FETCH_ASSOC);
	Zend_Registry::set('db_main', $db_main);
} catch (Zend_Exception $e) { 
	echo $e; 
}
