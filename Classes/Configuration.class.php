<?php
class Configuration
{
	const USER_CONF = 'userconf.ini';
	const USER_DATA = 'data';
	private $BASE_DIR = "";
	public $configuration = array();
	public $cache = "";
	
	public function __construct()
	{
		// Get the base Folder we are running from
		$this->BASE_DIR =  dirname(realpath($_SERVER["SCRIPT_FILENAME"])) . DIRECTORY_SEPARATOR;
		
		$this->CreateUserDataIfNeeded();
		
		$this->ReadConfiguration();
		
		$this->FindMyMusicDatabase();
		
		$this->CreateUpdateCacheDatabase();
		
		Strings::Debug('Gathered information about to process with the following paramaters',true);
		Strings::Debug('Last FM Username  : ' . $this->configuration['last_fm']['user_name'] ,true);
		Strings::Debug('My Music Database : ' . $this->configuration['xbmc']['MyMusic']   ,true);
		
	}
	/**
	 * Creates the local Last.FM cache if it doesn't already exist
	 * If it does exist it checks the current version number and tries to upgrade it to the latest version
	 */
	private function CreateUpdateCacheDatabase()
	{
		$cache_fn = $this->BASE_DIR . Configuration::USER_DATA . DIRECTORY_SEPARATOR . "last_fm_cache.db";
		$CURRENT_DB_VERSION = "0.0";
		if (file_exists($cache_fn) )
		{
			$this->cache = new Database(new Database_Credentials("sqlite:{$cache_fn}","",""));
		}
		else
		{
			$this->cache = new Database(new Database_Credentials("sqlite:{$cache_fn}","",""));
			$this->cache->performQuery("CREATE TABLE Information (info_id INTEGER PRIMARY KEY  NOT NULL  UNIQUE	, info_desc TEXT, info_value TEXT)");
			$this->cache->performQuery('CREATE  TABLE LastFMCache ("date_uts" DATETIME PRIMARY KEY  NOT NULL  UNIQUE , "artist_mbid" TEXT, "artist" TEXT, "title" TEXT, "mbid" TEXT)');
			$this->cache->performQuery("REPLACE INTO Information (info_id, info_desc,info_value) VALUES (1,'Database Version',{$CURRENT_DB_VERSION});");
		}

		$this->cache->performQuery("SELECT info_value FROM Information WHERE info_id = 1");
		$database_version = $this->cache->topResult('info_value');
		if ($database_version == "")
		{
			$database_version = 0;
		}
		Strings::Debug("Current Database Version = $database_version");
		if ($database_version === Null || $database_version <= 0.1) // Upgrade to 0.2
		{
			Strings::StatusMessage("Upgrading Last.FM Cache Database to version 0.2",false);
			$this->cache->performQuery("REPLACE INTO Information (info_id, info_desc,info_value) VALUES (1,'Database Version',0.2);");
			Strings::Status("Done");
		}
	}
	/**
	 * Checks if the User Data (cache) folder exists, and attempts to create it
	 * On failure terminates the script
	 */
	private function CreateUserDataIfNeeded()
	{
		Strings::StatusMessage("Checking if user data folder exists",false);
		if (file_exists($this->BASE_DIR . Configuration::USER_DATA))
		{
			Strings::Status("Yes");
		}
		else
		{
			Strings::Status("No");
			Strings::StatusMessage("Creating user data folder",false);
			mkdir($this->BASE_DIR . Configuration::USER_DATA);
			if (file_exists($this->BASE_DIR . Configuration::USER_DATA))
			{
				Strings::Status("Success");	
			}
			else
			{
				Strings::Status("Failed");	
				die("Unable to create user data folder");
			}
		}
	}
	/**
	 * Attempts to find the MyMusic database in common locations
	 * On failure terminates the script
	 */
	public function FindMyMusicDatabase()
	{
		$S = DIRECTORY_SEPARATOR;
		$match = false;
		if ( isset($_SERVER['APPDATA'])) // Windows Vista
		{
			$match = $this->FindMyMusicDatabaseInFolder($_SERVER['APPDATA'] . "{$S}XBMC{$S}userdata{$S}Database{$S}");
		}
		elseif ( isset($_SERVER['HOME'])) // Linux
		{
			$match = $this->FindMyMusicDatabaseInFolder($_SERVER['APPDATA'] . ".xbmc{$S}userdata{$S}Database{$S}");
		}
		
		if ($match !== false)
		{
			$this->configuration['xbmc'] = array('MyMusic'=>$match);
			copy($match,$this->BASE_DIR . Configuration::USER_DATA . $S . "backup-MyMusic." . date('Y-m-d_His') . ".db");
			Helper::WriteIniFile($this->configuration,$this->BASE_DIR . Configuration::USER_CONF);
		}
		else
		{
			die("Unable to find MyMusic Database - add to config file");
		}
	}
	/**
	 * Given a folder try and find a MyMusic#.db file
	 */
	public function FindMyMusicDatabaseInFolder($folder)
	{
		$dir = Helper::FilesInFolder($folder);
		foreach($dir as $filename)
		{
			$lc_filename = strtolower($filename);
			if ( preg_match("/^mymusic[0-9]+.db$/", $lc_filename) == 1)
			{
				return "{$folder}{$filename}";
			}
		}
		return false;
	}
	/**
	 * Reads the userconf.ini file
	 */
	private function LoadConfiguration()
	{
		$this->configuration = parse_ini_file($this->BASE_DIR . Configuration::USER_CONF, true);
		if ($this->configuration['xbmc']['MyMusic'] == 'INSERT PATH HERE')
		{
			die("Unable to find MyMusic Database - add to config file manually");
		}
	}
	/**
	 * Prompts the user for their Last FM credentials then stores in the ini file
	 */
	private function PromptForConfiguration()
	{
		$last_fm_un = Strings::PromptForInput("What is your Last.FM Username?",true,"",5);
		$last_fm_api_key = Strings::PromptForInput("What is your Last.FM API Key?",true,"",5);
		
		$this->configuration = array('last_fm'=>array('user_name'=>$last_fm_un,'api_key'=>$last_fm_api_key),'xbmc'=>array('MyMusic'=>'INSERT PATH HERE'));
		Helper::WriteIniFile($this->configuration,$this->BASE_DIR . Configuration::USER_CONF);
	}
	/**
	 * Checks if the userconf.ini file exists and then prompts the user for various bits of information to create it if not
	 */
	public function ReadConfiguration()
	{
		Strings::StatusMessage("Checking if " . Configuration::USER_CONF . " exists",false);
		if (file_exists($this->BASE_DIR . Configuration::USER_CONF))
		{
			Strings::Status("Yes");
			$this->LoadConfiguration();
		}
		else
		{
			Strings::Status("No");
			$this->PromptForConfiguration();
		}
	}
	
	
	
	
	
	
	
	
	
	
}
?>