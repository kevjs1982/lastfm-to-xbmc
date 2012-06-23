<?php

class Helper
{
	public static function WriteIniFile($array, $file)
	{
		$res = array();
		foreach($array as $key => $val)
		{
			if(is_array($val))
			{
				$res[] = "[$key]";
				foreach($val as $skey => $sval) $res[] = "$skey = ".(is_numeric($sval) ? $sval : '"'.$sval.'"');
			}
			else $res[] = "$key = ".(is_numeric($val) ? $val : '"'.$val.'"');
		}
		Helper::SafeFileRewrite($file, implode("\r\n", $res));
	}
	public static function SafeFileRewrite($fileName, $dataToSave)
	{    if ($fp = fopen($fileName, 'w'))
		{
			$startTime = microtime();
			do
			{            $canWrite = flock($fp, LOCK_EX);
			   // If lock not obtained sleep for 0 - 100 milliseconds, to avoid collision and CPU load
			   if(!$canWrite) usleep(round(rand(0, 100)*1000));
			} while ((!$canWrite)and((microtime()-$startTime) < 1000));

			//file was locked so now we can store information
			if ($canWrite)
			{            fwrite($fp, $dataToSave);
				flock($fp, LOCK_UN);
			}
			fclose($fp);
		}

	}
	public static function FilesInFolder($folder,$extension = "")
	{
		$results = array();
		$handler = opendir($folder);
		while ($file = readdir($handler)) 
		{
			if ($extension <> "")
			{
				$curr_extension = substr($file,0-strlen($extension));
			}
			else
			{
				$curr_extension = "";
			}
			
			if ($file != "." && $file != ".." && $curr_extension == $extension) 
			{
				$results[] = $file;
			}
		}
		closedir($handler);
		return $results;
	}
}

?>