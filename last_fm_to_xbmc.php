<?php
	$options = getopt("",array('skip-last-fm','debug','skip-xbmc','skip-playlists','skip-tz-check'));
	// Flick some switches
	if (isset($options['debug']))			{	define ('DEBUG', true );			}else{	define ('DEBUG', false );			}
	if (isset($options['skip-last-fm']))	{	define ('REFRESH_LAST_FM', false );	}else{	define ('REFRESH_LAST_FM', true );	}
	if (isset($options['skip-xbmc']))		{	define ('REFRESH_XBMC', false );	}else{	define ('REFRESH_XBMC', true );		}
	if (isset($options['skip-playlists']))		{	define ('REFRESH_PLAYLISTS', false );	}else{	define ('REFRESH_PLAYLISTS', true );		}
	if (isset($options['skip-tz-check']))		{	define ('SKIP_TZ_CHECK', true );	}else{	define ('SKIP_TZ_CHECK', false );		}

	if (DEBUG)
	{
		error_reporting(E_ALL);
		ini_set('display_errors',1);
	}
	
	include('autoload.inc.php');
	
	echo PHP_EOL;
	echo Strings::TruncateAndPad("+------------------------------------------------------------------------------+",80) . PHP_EOL;
	echo Strings::TruncateAndPad("| Transfering XBMC Playcounts from Last.FM to XBMC                             |",80) . PHP_EOL;
	echo Strings::TruncateAndPad("| Make Sure you have scroblled all offline devices!                            |",80) . PHP_EOL;
	echo Strings::TruncateAndPad("+------------------------------------------------------------------------------+",80) . PHP_EOL;
	echo PHP_EOL;
	Strings::Debug("Your timezone is set to " . date_default_timezone_get() , true);
	
	if (SKIP_TZ_CHECK == false)
	{
		$tzok = Strings::PromptForInput("Is this correct? [Y/n]",array('y','n'),'y',1);
		if ($tzok == 'n')
		{
			die("Please amend your local timezone in php.ini" . PHP_EOL);
		}
	}
	
	$local_timezone = new datetimezone(date_default_timezone_get());
	
	Strings::Debug("Loading Configuration");
	$config = new Configuration();
	
	// We don't want to miss stuff while we are playing so what we want to do is cap the requests at tracks played more than 15 minutes ago...
	$newest_track = new DateTime();
	$newest_track->modify('-15 minutes');
	$newest_track->setTimeZone(new DateTimeZone('UTC'));
	
	// There is also little point repeating ourselves - we'll grab everything played after one week before the newest track in the database
	// This allows cached entries (say from Android) to be caught up with later on!
	$config->cache->performQuery("SELECT MAX(date_uts) as newest FROM LastFMCache");
	$oldest_track = $config->cache->topResult('newest');
	if ($oldest_track == "")
	{
		$oldest_track = '2000-01-01 00:00:00';
	}
	$oldest_track = new datetime($oldest_track);
	$oldest_track->modify('-1 week');
	

	Strings::Debug("Fetching tracks played between " .$oldest_track->format('d/m/Y H:i') . " and " . $newest_track->format('d/m/Y H:i'));
	
	if (REFRESH_LAST_FM == true)
	{
		$last_fm = new LastFM($config->configuration['last_fm']['api_key'],$newest_track->format('U'),$oldest_track->format('U'));
		$page_count = $last_fm->PageCount($config->configuration['last_fm']['user_name']);
		
		echo Strings::Debug("We have to fetch $page_count pages, why not go and grab a nice cuppa while you wait?",true);
		$current_page = $page_count;
		$sql = "REPLACE INTO LastFMCache (date_uts,artist_mbid,artist,title,mbid) VALUES(:d,:am,:a,:t,:tm)";
		
		while($current_page >= 1)
		{
			Strings::Debug("Pages Remaing $current_page",true);
			$tracks = $last_fm->UserPlayed($config->configuration['last_fm']['user_name'],$current_page);
			echo "Got " . count($tracks) . "\n";
			foreach($tracks as $track)
			{
				$lt = new datetime((string) $track->date_uts,new datetimezone('UTC'));
				$lt->setTimeZone($local_timezone);
				$lt = $lt->format('d/m/Y H:i:s');
				Strings::Debug("\t{$lt} - {$track->artist} - {$track->name}",true);
				
				$config->cache->performQuery($sql, array(
					'd'=>  $track->date_uts,
					'a'=>  $track->artist,
					'am'=> $track->artist_mbid,
					't'=>  $track->name,
					'tm'=> $track->mbid
					));
			}
			$current_page = $current_page - 1;
		}
	}
	else
	{
		echo Strings::TruncateAndPad("+------------------------------------------------------------------------------+",80) . PHP_EOL;
		echo Strings::TruncateAndPad("+ SKIPPING LastFM Update!                                                      +",80) . PHP_EOL;
		echo Strings::TruncateAndPad("+------------------------------------------------------------------------------+",80) . PHP_EOL;
	}
	
	$xbmc = new XBMC($config->configuration['xbmc']['MyMusic']);
	if (REFRESH_XBMC)
	{
		// Select all titles from the Last FM Cache
		
		// Get all the combos...
		$config->cache->performQuery("
			SELECT 
				MAX(date_uts) as last_played,
				COUNT(*) as play_count,
				artist,
				title
			FROM LastFMCache 
			WHERE date_uts > '1970-01-01' 
			GROUP BY artist, title
			ORDER BY artist, title
			
		");
		$last_fm_info = $config->cache->results();
		
		// Now try and link with XBMC!
		foreach($last_fm_info as $last_fm_item)
		{
			$matches = $xbmc->Find($last_fm_item->artist,$last_fm_item->title);
			echo Strings::TruncateAndPad($last_fm_item->artist,20) . " " . Strings::TruncateAndPad($last_fm_item->title,20)  . " ". Strings::TruncateAndPad($last_fm_item->last_played,20) . " ". Strings::TruncateAndPad($last_fm_item->play_count,3) ;
			
			if (count($matches) > 0)
			{
				foreach($matches as $match)
				{
					echo ".";
					$xbmc->UpdatePlayRecords($match->idSong,$last_fm_item->play_count,$last_fm_item->last_played);
				}
			}
			
			echo PHP_EOL;
		}
		
		
		
		
	}
	else
	{
		
		echo Strings::TruncateAndPad("+------------------------------------------------------------------------------+",80) . PHP_EOL;
		echo Strings::TruncateAndPad("+ SKIPPING XBMC Update!                                                      +",80) . PHP_EOL;
		echo Strings::TruncateAndPad("+------------------------------------------------------------------------------+",80) . PHP_EOL;
	}
	
	
	if (REFRESH_PLAYLISTS)
	{
		for($year=2000;$year<=date('Y');$year++)
		{
			$config->GetTop100($xbmc,$year);
		}
	}
	else
	{
		echo Strings::TruncateAndPad("+------------------------------------------------------------------------------+",80) . PHP_EOL;
		echo Strings::TruncateAndPad("+ SKIPPING Playlists Update!                                                   +",80) . PHP_EOL;
		echo Strings::TruncateAndPad("+------------------------------------------------------------------------------+",80) . PHP_EOL;
	}
	
		// populate it with common aliases
	
		// populate it with Last FM data...
		
// grab each song in turn
	// Does the musicbrainz value exist?  Use that
	// Exact match
	// Punctuation free match

// Generate some statistics

	Strings::Debug("Finished",true);
	echo PHP_EOL;
?>