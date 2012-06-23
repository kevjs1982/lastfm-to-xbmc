<?php
class Web
{
	function FetchPage($url)
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$rsvp = curl_exec($ch);
		curl_close($ch);
		return $rsvp;
	}
}
?>