<?php
class Strings
{
	public static function Debug($message,$always = false)
	{
		if (DEBUG || $always)
		{
			echo $message . PHP_EOL;
		}
	}
	
	public static function StatusMessage($message,$status = false)
	{
		$message = Strings::TruncateAndPad($message,68) . " ";
		if ($status === false)
		{
			echo "{$message}";
		}
		else
		{
			$status  = "[ " . Strings::TruncateAndPad($status,8) . " ]";
			echo "{$message}{$status}" . PHP_EOL;
		}
	}
	public static function Status($status)
	{
		$status  = "[ " . Strings::TruncateAndPad($status,8,STR_PAD_BOTH) . " ]";
		echo "{$status}" . PHP_EOL;
	}
	
	public static function TruncateAndPad($message,$length,$pad=STR_PAD_RIGHT)
	{
		return str_pad(substr($message,0,$length),$length," ",$pad);
	}
	
	public static function PromptForInput($question,$valid_answers,$default,$min_len = 5)
	{
		$valid = false;
		$my_answer = "";
		while ($valid == false)
		{
			echo "\n" . $question . " : ";
			$reply = strtolower(trim(fgets(STDIN)));
			$reply = ($reply == "") ? strtolower(trim($default)) : $reply;
			if ($valid_answers === true	&& ( (strlen(trim($reply)) >= $min_len) || (trim($reply) == $default)))
			{
				return trim($reply);
			}
			elseif($valid_answers === true)
			{
				echo "\t\tNeed to enter a minimum of $min_len characters\n";
			}
			else
			{
				
				foreach($valid_answers as $valid_answer)
				{
					if (trim(strtolower($valid_answer)) == trim(strtolower($reply)))
					{
						$valid = true;
						$my_answer = trim(strtolower($reply));
					}
					
				}
			}
		}
		return $my_answer;
	}
	
	
	public static  function AlphaNumericSpacesExtended($string)
	{
		return self::Clean("/[^a-zA-Z0-9_-\s]/", $string);
	}
	
	public static  function AlphaNumericExtended($string)
	{
		return self::Clean("/[^a-zA-Z0-9_\-]/", $string);
	}

	public static  function AlphaNumeric($string)
	{
		return self::Clean("/[^a-zA-Z0-9]/", $string);
	}
	
	public static  function AlphaNumericSpaces($string)
	{
		return self::Clean("/[^a-zA-Z0-9\s]/", $string);
	}
	
	public static  function Clean($preg,$string)
	{
		return preg_replace($preg, "", $string);
	}
	
	public static  function RomanNumber($num)
	{
		// Make sure that we only use the integer portion of the value
		$n = intval($num);
		$result = '';

		// Declare a lookup array that we will use to traverse the number:
		$lookup = array(
			'M'  => 1000,
			'CM' => 900,
			'D'  => 500,
			'CD' => 400,
			'C'  => 100,
			'XC' => 90,
			'L'  => 50,
			'XL' => 40,
			'X'  => 10,
			'IX' => 9,
			'V' => 5,
			'IV' => 4,
			'I' => 1
		);

		foreach ($lookup as $roman => $value)
		{
			// Determine the number of matches
			$matches = intval($n / $value);
			// Store that many characters
			$result .= str_repeat($roman, $matches);
			// Substract that from the number
			$n = $n % $value;
		}
		// The Roman numeral should be built, return it
		return $result;
	}
	
	public static function ToFile($string,$file)
	{
		$fh = fopen($file, 'w');
		fwrite($fh, $string);
		fclose($fh);
	}
}
?>