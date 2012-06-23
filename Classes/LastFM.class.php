<?php
class LastFM
{
	private $_key = '';
	private $newest = '';
	private $oldest = '';
	private $tracks_per_page = 50;
	function __construct($key,$newest,$oldest)
	{
		$this->_key 	= $key;
		$this->newest 	= $newest;
		$this->oldest 	= $oldest;
	}
	function PageCount($user_name)
	{
		$page = Web::FetchPage("http://ws.audioscrobbler.com/2.0/?method=user.getrecenttracks&limit={$this->tracks_per_page}&user={$user_name}&api_key={$this->_key}&to={$this->newest}&from={$this->oldest}");
		$xml = simplexml_load_string($page);
		$meta = $xml->xpath("/lfm");
		return $xml->recenttracks[0]->attributes()->totalPages;
	}
	function UserPlayed($user_name,$page)
	{
		$page = Web::FetchPage($url = "http://ws.audioscrobbler.com/2.0/?method=user.getrecenttracks&limit={$this->tracks_per_page}&user={$user_name}&api_key={$this->_key}&page={$page}&to={$this->newest}&from={$this->oldest}");
		#echo $url;
		$xml = simplexml_load_string($page);
		//print_r($xml);
		$tracks = $xml->xpath("recenttracks/track");
		//print_r($tracks);
		
		$MyTracks = Array();
		foreach($tracks as $track)
		{
			$trk = new stdClass();
			
			if ($track->attributes()->nowplaying == true)
			{
				echo "Playing";
			}
			else
			{
				foreach($track->album->attributes() as $key => $val)
				{
					if ($key == "mbid")
					{
						$trk->album_mbid = (string) $val;
					}
				}
				foreach($track->artist->attributes() as $key => $val)
				{
					if ($key == "mbid")
					{
						$trk->artist_mbid = (string) $val;
					}
				}
				
				$date_uts = $track->date[0]->attributes()->uts;
				$date_uts = new datetime(date('Y-m-d H:i:s',$date_uts+0),new DateTimeZone('UTC'));
				$date_uts = $date_uts->format('Y-m-d H:i:s');
				$trk->date_uts = (string) $date_uts;
				$trk->artist = (string) $track->artist;
				$trk->artist_mbid = (string) $trk->artist_mbid;
				$trk->name = (string) $track->name;
				$trk->mbid = (string) $track->mbid;
				$MyTracks[] = $trk;
			}
			
		}
		return $MyTracks;
		
	}
}	

?>