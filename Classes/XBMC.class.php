<?php
class XBMC
{
	private $db = '';
	function __construct($db)
	{
		$this->db 	= new Database(new Database_Credentials("sqlite:{$db}","",""));;
	}
	function Find($artist,$title)
	{
		$this->db->performQuery("SELECT idSong, S.idArtist, strTitle, lastplayed, iTimesPlayed, A.strArtist, strPath, strFileName
								FROM song S
								JOIN artist A ON A.idArtist = S.idArtist
								JOIN path P ON P.idPath = S.idPath
								WHERE strArtist = :a COLLATE NOCASE AND strTitle = :t COLLATE NOCASE",array('a'=>$artist,'t'=>$title));
		return $this->db->results();
	}
	
	function UpdatePlayRecords($song_id,$play_count,$last_played)
	{
		$this->db->performQuery("
			UPDATE song SET iTimesPlayed = :pc, lastplayed = :lp WHERE idSong = :s",
			array(
				'pc'=>$play_count,
				'lp'=>$last_played,
				's'=>$song_id
			)
		);
	}
}	

?>