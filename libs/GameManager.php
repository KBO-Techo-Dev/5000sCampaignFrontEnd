<?php
class GameManager
{
	const UA_CLASS_WEB = 1;
	const UA_CLASS_HIGH = 2;
	const UA_CLASS_MIDDLE = 3;
	const UA_CLASS_LOW = 4;
	const SESSION_EXPIRE = "+1 day";
	const TOTAL_SECOND_PER_DAY = 86400;
	var $_included;

	public function __construct() { $this->_included = array();}
	public static function getNonFinalChargeResult() { 	return array(100, 101, 102, 103); }
	public function getObject($inName)
	{
		$object = '';
		try {
			$object = Zend_Registry::get($inName);
		} catch (Exception $e) {
			if (!isset($this->_included[$inName])) {
				$this->_included[$inName] = TRUE;
				require $inName . '.php';
			}
			$object = new $inName;
			Zend_Registry::set($inName, $object);
		}
		return $object;
	}
	
	public static function getUserObject($inUid)
	{
		$users = new Users();
		$me = $users->init($inUid);
		return $me;
	} 

	public static function getUidByToken($inToken)
	{
		$uid = 0;
		$db_share = Zend_Registry::get('db_share');
		$db_static = Zend_Registry::get('db_static');
		$session = $db_share->fetchRow("select * from session_pool where token=? and expire_datetime > NOW()" , $inToken);
		if($session==null) $uid = 0;
		else {
			//check event
			$lastest_event_time = $db_static->fetchOne("select MIN(start_datetime) from db_event where enable=1 and NOW() between start_datetime and end_datetime");
			if($lastest_event_time != null)
			{
				$diff = Utils::getDefferentTimestamp($lastest_event_time, $session["expire_datetime"]);
				if( $diff >= 0 && $diff <= GameManager::TOTAL_SECOND_PER_DAY )
				{
					$uid = 0;
				} else {
					$uid = $session["uid"];
				}
			} else {
				$uid = $session["uid"];
			}
		}
		return $uid;
	}
	
	public static function getUserDataByUsrPwd($inUserName,$inPassword,$inDbConn,$inSchemaConn)
	{
		$result = array();		
		$user_tables = $inSchemaConn->fetchCol("SELECT TABLE_NAME FROM `TABLES` WHERE TABLE_NAME LIKE '%users%'");
		$sql = "";
		$condition = array();
		foreach($user_tables as $table_name)
		{
			$sql .= "select * from " . $table_name . " where username=? and password=? union ";
			$condition[] = $inUserName;
			$condition[] = $inPassword;
		}
		$sql = substr($sql, 0 , -6);
		$result = $inDbConn->fetchRow($sql,$condition);
		return $result;
	}
	
	public static function getUserDataByUid($inUid , $inDbConn , $inSchemaConn) 
	{
		$result = array();
		$user_tables = $inSchemaConn->fetchCol("SELECT TABLE_NAME FROM `TABLES` WHERE TABLE_NAME LIKE '%users%'");
		$sql = "";
		$condition = array();
		foreach($user_tables as $table_name)
		{
			$sql .= " select * from ". $table_name . " where uid=? union ";	
			$condition[] = $inUid;
		}
		$sql = substr($sql, 0 , -6);
		$result = $inDbConn->fetchRow($sql,$condition);
		return $result;
	}
	
	public static function checkUserDuplicate($inUserName,$inDbConn,$inSchemaConn)
	{
		$result = array();
		$user_tables = $inSchemaConn->fetchCol("SELECT TABLE_NAME FROM `TABLES` WHERE TABLE_NAME LIKE '%users%'");
		$sql = "";
		$condition = array();
		foreach($user_tables as $table_name)
		{
			$sql .= "select * from " . $table_name . " where username=? union ";
			$condition[] = $inUserName;
		}
		$sql = substr($sql, 0 , -6);
		$result = $inDbConn->fetchRow($sql,$condition);
		if($result) return true;
		else return false;
	}	
	
	const USERNAME_MINIMUM_LENGTH = 6;
	const USERNAME_MAXIMUM_LENGTH = 256;
	const PASSWORD_MINIMUM_LENGTH = 4;
	const PASSWORD_MAXIMUM_LENGTH = 12;
	public static function verifyNewUserChar($inUserName,$inPassword)
	{
		//verify email in pattern abc@home.com
		if(filter_var($inUserName, FILTER_VALIDATE_EMAIL)) $result = true;
		else $result = false;
		
		$result = $result && preg_match("/^[A-Za-z0-9]*$/",$inPassword);	
		//check username length
		$result = (($result) && (strlen($inUserName) >= GameManager::USERNAME_MINIMUM_LENGTH) && (strlen($inUserName) <= GameManager::USERNAME_MAXIMUM_LENGTH));
		//check password length
		$result = (($result) && (strlen($inPassword) >= GameManager::PASSWORD_MINIMUM_LENGTH) && (strlen($inPassword) <= GameManager::PASSWORD_MAXIMUM_LENGTH));
		return $result;		
	}
	
	public static function getDbConnection($config,$dbName)
	{
		$connection = null;
		if(Zend_Registry::isRegistered($dbName))
		{
			$connection = Zend_Registry::get($dbName);			
		} 
		else 
		{
			$connection = Zend_Db::factory($config->database);					
			Zend_Registry::set($dbName, $connection);
		}		
		$connection->getConnection()->exec("SET NAMES UTF8");
		$connection->getProfiler()->setEnabled(true);
		$connection->setFetchMode(Zend_Db::FETCH_ASSOC);
		return $connection;
	}
	
	public static function getCacheName($inName) {
		$config = Zend_Registry::get('config');
		return $config->cache_prefix . $config->version . $inName;
	}
	
	// use with x-sdk login only!!!	
	public static function getFBUserData($inUserName,$inDbConn,$inSchemaConn)
	{
		$result = array();
		$user_tables = $inSchemaConn->fetchCol("SELECT TABLE_NAME FROM `TABLES` WHERE TABLE_NAME LIKE '%users%'");
		$sql = "";
		$condition = array();
		foreach($user_tables as $table_name)
		{
			$sql .= "select * from " . $table_name . " where username = ? union ";
			$condition[] = $inUserName;
		}
		$sql = substr($sql, 0 , -6);
		$result = $inDbConn->fetchRow($sql,$condition);
		return $result;
	}
	
	public static function createSession($inUser)
	{
		$config = Zend_Registry::get('config');
		$db_share = Zend_Registry::get('db_share');	
		if($session = $db_share->fetchRow("select * from session_pool where uid=?",$inUser->_data["uid"]))
		{
			$db_share->insert("session_log",$session);
			$db_share->delete("session_pool",array("id=?"=>$session["id"]));
			$session = array();
		}
		if(!$session)
		{
			$suffixSig = sprintf("%06d",rand(0,999999));
			$token_data = $inUser->_data["uid"]."-".date("Y-m-d G:i:s")."-".$suffixSig;
			$session = array (
					"uid" 				=> $inUser->_data["uid"],
					"token" 			=> hash_hmac("md5",$token_data,$config->signature_salt),
					"date"				=> date("Y-m-d"),
					"time"				=> date("G:i:s"),
					"create_datetime" 	=> date('Y-m-d G:i:s'),
					"expire_datetime" 	=> date('Y-m-d G:i:s',strtotime(GameManager::SESSION_EXPIRE))
			);
			$db_share->insert("session_pool" , $session );
		}
		return  $session;		
	}
	
	public static function storeDeviceInfo($inUser,$inDeviceInfo)
	{
		if($inDeviceInfo)
		{
			$db_crm = Zend_Registry::get('db_crm');
			$detect_device = $db_crm->fetchOne("select count(id) from device_info where udid=? and uid=?",array($inDeviceInfo["udid"],$inUser->_data["uid"]));
			if($detect_device == 0)
			{
				$device_data = array("uid" => $inUser->_data["uid"]);
				$device_data = array_merge($device_data,$inDeviceInfo);
				$device_data["date"] = date("Y-m-d");
				$device_data["time"] = date("G:i:s");
				$db_crm->insert("device_info",$device_data);
			}
		}
	}
	
	const PLAYERNAME_MINIMUM_LENGTH = 4;
	const PLAYERNAME_MAXIMUM_LENGTH = 32;
	public static function ValidatePlayerName($inPlayerName)
	{
		$db_static = Zend_Registry::get('db_static');
		//check character
		$result = preg_match("/^[A-Za-z0-9]*$/",$inPlayerName) &&  (strlen($inPlayerName) >= GameManager::PLAYERNAME_MINIMUM_LENGTH  && strlen($inPlayerName) <= GameManager::PLAYERNAME_MAXIMUM_LENGTH);
		
		//check rude word
		$inPlayerName = strtolower($inPlayerName);
		$found = $db_static->fetchOne("select word from db_rude_word where ? like CONCAT('%', word, '%')  limit 1" , $inPlayerName);
		if($found) return false;
		else return $result;
	}
	
	public static function findUserFromFBID ($inFBID,$inDbConn , $inSchemaConn)
	{
		$result = array();
		$user_tables = $inSchemaConn->fetchCol("SELECT TABLE_NAME FROM `TABLES` WHERE TABLE_NAME LIKE '%users%'");
		$sql = "";
		$condition = array();
		foreach($user_tables as $table_name)
		{
			$sql .= "select * from " . $table_name . " where facebook_id = ? and facebook_id > 0 union ";
			$condition[] = $inFBID;
		}
		$sql = substr($sql, 0 , -6);
		$result = $inDbConn->fetchRow($sql,$condition);
		return $result;
	} 
	
	public static function getUserByPlayerName($playername)
	{
		$config = Zend_Registry::get("config");
		$sql = " select uid from player where playername = ? " ;
		$playerData = array();
		//find user by name
		for($i=1; $i<=$config->total_channel; $i++)
		{
			$chkey = "channel$i";
			$config_temp = new Zend_Config(
						array(
								'database' => array(
								'adapter' 	=> 'pdo_mysql',
								'params' 	=> array(
										'host'		=> $config->db->$chkey->params->host,
										'dbname' 	=> $config->db->$chkey->params->dbname,
										'username' 	=> $config->db->$chkey->params->username,
										'password' 	=> $config->db->$chkey->params->password,
								)
						)
				)
			);
			$db_channel = GameManager::getDbConnection($config_temp,$config->db->$chkey->params->dbname);
			$playerData = $db_channel->fetchRow($sql,$playername);
			if($playerData) break;			
		}
		if($playerData)
			return self::getUserObject($playerData["uid"]);
		else 
			return null;
	}
	
	public static function checkPlayerNameAvailable($playerName , $inDb , $inSchemaConn)
	{
		//check valid name
		$user_tables = self::getAllUserTables($inSchemaConn);
		$dup_sql = "select uid from [USER_TABLE] where LOWER(playername) = ? ";
		$condition_value = array();
		$final_sql = "";	
		foreach($user_tables as $table)
		{
			$final_sql.= str_replace("[USER_TABLE]", $table, $dup_sql) . " UNION ";
			$condition_value[] = strtolower($playerName);
		}
		$final_sql = substr($final_sql, 0 , -6);
		$found = $inDb->fetchRow($final_sql,$condition_value);
		return $found == null ;
	}
	
	public static function getAllUserTables($inSchemaConn)
	{
		$user_tables = $inSchemaConn->fetchCol("SELECT TABLE_NAME FROM `TABLES` WHERE TABLE_NAME LIKE '%users%'");
		return $user_tables;
	}
	
	public static function getPlayerNameLength($playerName)
	{
		return strlen(utf8_decode($playerName));
	}
}
