<?php
// Database stuff
class Database_Credentials
{
	var $dsn;
	var $user_name;
	var $password;
	public function __construct($dsn,$un,$pw)
	{
		$this->dsn			= $dsn;
		$this->user_name 	= $un;
		$this->password 	= $pw;
	}
}

class Database
{
	public $_is_oracle = false;
	public $_connection = null;
	private $_errorInfo = null;
	private $_affectedRows = null;

	public $_queryHandle = null;
	public $_sql = null;
	public $_dsn = null;
	
	public function beginTransaction()
	{
		$this->_connection->beginTransaction();
	}
	
	public function commit()
	{
		$this->_connection->commit();
	}
	
	public function rollBack()
	{
		$this->_connection->rollBack();
	}
	
	public function prepare($sql_data)
	{
		$this->_sql = $sql_data;
		$this->_queryHandle = $this->_connection->prepare($sql_data);
	}
	
	public function __construct($credentials)
	{
		$this->_connection = $this->database_connect($credentials);
	}
	/**
	 * Get Database Connection
	 * Returns a db_connection with sufficint privilages to perfom the
	 * appropriate commands
	 * @param mixed $type Type of connection <ul><li>DB_SELECT</li><li>DB_INSERT</li><li>DB_DELETE</li><li>DB_UPDATE</li></ul>
	 */
	private function database_connect($credentials)
	{
		$this->_dsn = $credentials->dsn;
		return $this->_database_connect($credentials->dsn,$credentials->user_name,$credentials->password);
	}
	
	
	/**
	 * Get Database Connection
	 * Returns a db_connection with sufficint privilages to perfom the
	 * appropriate commands, initalising it into UTF-8 format.
	 * @param string $dsn Connection string
	 * @param string $username Username
	 * @param string $password Password
	 * @access private
	*/
	private function _database_connect($dsn,$username,$password)
	{
			try
			{
					$dbh = new PDO($dsn, $username, $password);
					$dbh->query('SET NAMES utf8');
					return $dbh;
			}
			catch (PDOException $e)
			{
					die(PHP_EOL . "Error!: " . $e->getMessage() . "");
					
					return FALSE;
			}
	}
	
	 
	 
	public function performQuery($sql,$params=null)
	{
			$this->_queryHandle = $this->_connection->prepare($sql);
			$this->_sql = $sql;
			$this->_queryHandle->execute($params);
			$this->_errorInfo 		= $this->_queryHandle->errorInfo();
			
			//error_log(print_r($this->_errorInfo),3,PROJ_LOGS . 'sql.log');
			
			if ($this->_errorInfo[0] != '00000')
			{
					$log = "\n---" . date('Y-m-d H:i:s') . " ----[" . $session . "]--------------------------------------------------------\n";
					$log .= "$filename\n";
					$log .= "-------\nSQL was\n-------\n" . $sql . "\n";
					$log .= "\tError [" . $this->_errorInfo[1] . "] " . $this->_errorInfo[2];
					$log .= "\n-------\nParameters were\n--------\n";
					$log .= print_r($params,true);
					$log .= "\n------------------------------------------------------------------------------------------------------------------------\n";
					
					Strings::Debug("DATABASE ERROR!!!",true);
					Strings::Debug("$log",true);
					
			}
			$this->_affectedRows 	= $this->_queryHandle->rowCount();
	}
	
	public function performTransactionQuery($params)
	{
		$this->_queryHandle->execute($params);
		$this->_errorInfo 		= $this->_queryHandle->errorInfo();
		
		if ($this->_errorInfo[0] != '00000')
			{
				$log .= "-------\nSQL was\n-------\n" . $sql . "\n";
				$log .= "\tError [" . $this->_errorInfo[1] . "] " . $this->_errorInfo[2];
				$log .= "\n-------\nParameters were\n--------\n";
				$log .= print_r($params,true);
				$log .= "\n------------------------------------------------------------------------------------------------------------------------\n";
				Strings::Debug("DATABASE ERROR!!!",true);
				Strings::Debug("$log",true);
			}
		$this->_affectedRows 	= $this->_queryHandle->rowCount();
	}
	public function lastInsertID()
	{
		return $this->_lastInsertID;
	}
	public function errorInfo()
	{
		return $this->_errorInfo;
	}
	public function queryOkay()
	{
		return ($this->_errorInfo[0] == '00000') ? true : false;
	}
	public function results()
	{
		//error_log($this->rowCount());
		return $this->_queryHandle->fetchAll(PDO::FETCH_OBJ);
	}
	
	public function topResult($field=NULL)
	{
		$r = $this->_queryHandle->fetchAll(PDO::FETCH_OBJ);
		if (!isset($r[0]))
		{
			return null;
		}
		if ($field === NULL)
		{
			return $r[0];
		}
		else
		{
			return $r[0]->{$field};
		}
	}
	public function affectedRows()
	{
		return $this->_affectedRows;
	}
	public function rowCount()
	{
		return $this->_affectedRows;
	}
	
	
	
	
}
?>