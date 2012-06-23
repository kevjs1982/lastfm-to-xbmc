<?php
	define ('DEBUG', true );
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
	
	$last_fm = new LastFM($config->configuration['last_fm']['api_key'],$newest_track->format('U'),$oldest_track->format('U'));
	$page_count = $last_fm->PageCount($config->configuration['last_fm']['user_name']);
	
	echo Strings::Debug("We have to fetch $page_count pages, why not go and grab a nice cuppa while you wait?",true);
	$current_page = $page_count;
	$sql = "REPLACE INTO LastFMCache (date_uts,artist_mbid,artist,title,mbid) VALUES(:d,:am,:a,:t,:tm)";
	$local_timezone = new datetimezone(date_default_timezone_get());
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