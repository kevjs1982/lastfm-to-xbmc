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
		
		if (	!isset($this->configuration['xbmc']['MyMusic'])		||
				$this->configuration['xbmc']['MyMusic'] == ""		||
				$this->configuration['xbmc']['MyMusic'] == 'INSERT PATH HERE'
			)
		{
			$this->FindMyMusicDatabase();
		}
		else
		{	
			// Backup the database!
			copy($this->configuration['xbmc']['MyMusic'],$this->BASE_DIR . Configuration::USER_DATA . DIRECTORY_SEPARATOR . "backup-MyMusic." . date('Y-m-d_His') . ".db");
		}
		
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
			$this->configuration['xbmc']['MusicPlaylists'] = $_SERVER['APPDATA'] . "XBMC{$S}userdata{$S}playlists{$S}music{$S}";
		}
		elseif ( isset($_SERVER['HOME'])) // Linux
		{
			$match = $this->FindMyMusicDatabaseInFolder($_SERVER['HOME'] . ".xbmc{$S}userdata{$S}Database{$S}");
			$this->configuration['xbmc']['MusicPlaylists'] = $_SERVER['HOME'] . ".xbmc{$S}userdata{$S}playlists{$S}music{$S}";
		}
		
		if ($match !== false)
		{
			$this->configuration['xbmc']['MyMusic'] = $match;
			
			
			
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
	
	
	public function GetTop100($xbmc,$year)
	{
		
		$top_100_dir = $this->configuration['xbmc']['MusicPlaylists'] .DIRECTORY_SEPARATOR .  "Top 100 Of The Year" . DIRECTORY_SEPARATOR;
		if (!file_exists($top_100_dir ))
		{
			mkdir($top_100_dir);
			if (!file_exists($top_100_dir ))
			{
				die("Unable to make the top 100 playlist folder");
			}
		}
		$start = new datetime("{$year}-01-01 00:00:00");
		$start->setTimeZone(new DateTimeZone('UTC'));
		$end   = new datetime("{$year}-12-31 23:59:59");
		$end->setTimeZone(new DateTimeZone('UTC'));
		$this->cache->performQuery("SELECT artist, title, COUNT(*) playcount
				FROM LastFMCache 
				WHERE date_uts BETWEEN :s AND :e
				GROUP BY artist, title
				ORDER BY COUNT(*) DESC
				",array('s'=>$start->format('Y-m-d H:i:s'),'e'=>$end->format('Y-m-d H:i:s')));
		$top100 = $this->cache->results();
		Strings::Debug("$year : Found " . count($top100) . " Tracks",true);
		$chart_pos = 1;
		$file_contents = "";
		if (count($top100) > 0)
		{
			Strings::Debug("\tNo. 1 : " . $top100[0]->artist  . " with " . $top100[0]->title,true);
			$idx = 0;
			while(($idx < count($top100)) && ($chart_pos <= 100))
			{
				$title = $top100[$idx];
				$info = $xbmc->Find($title->artist,$title->title);
				if (count($info) > 0)
				{
					Strings::Debug("\tNo. {$chart_pos} : " . $title->artist  . " with " . $title->title,true);
					$fn = $info[0]->strPath . $info[0]->strFileName;
					$file_contents .= $fn . PHP_EOL;
					$chart_pos++;
				}
				$idx++;
			}
			echo "{$top_100_dir}{$year}.m3u";
			Strings::ToFile($file_contents,"{$top_100_dir}{$year}.m3u");
		}
		
	}
	
	
	
	
	
	
	
}
?>