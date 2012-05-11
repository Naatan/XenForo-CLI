<?php

/**
 * Helpers that don't really belong elsewhere (naww)
 */
class XfCli_Helpers
{
	
	/**
	 * Merge 2 objects (basically array_merge_recursive for objects)
	 * 
	 * @param	Object			$ob1			
	 * @param	Object			$ob2
	 * 
	 * @return	object							
	 */
	public static function objectMerge($ob1, $ob2)
	{
		$result = self::arrayMerge(self::convertToArray($ob1), self::convertToArray($ob2));
		
		return json_decode(json_encode($result)); // convert it back to an object
	}
	
	/**
	 * Merge arrays recursively
	 *
	 * Credits: http://ca.php.net/manual/en/function.array-merge-recursive.php#104145
	 * 
	 * @return	array
	 */
	public static function arrayMerge()
	{
		if (func_num_args() < 2)
		{
			trigger_error(__FUNCTION__ .' needs two or more array arguments', E_USER_WARNING);
			return;
		}
		
		$arrays = func_get_args();
		$merged = array();
		
		while ($arrays)
		{
			$array = array_shift($arrays);
			
			if ( ! is_array($array))
			{
				trigger_error(__FUNCTION__ .' encountered a non array argument', E_USER_WARNING);
				return;
			}
			
			if ( ! $array)
			{
				continue;
			}
			
			foreach ($array as $key => $value)
			{
				
				if (is_string($key))
				{
					
					if (is_array($value) && array_key_exists($key, $merged) && is_array($merged[$key]))
					{
						$merged[$key] = self::arrayMerge($merged[$key], $value);
					}
					else
					{
						$merged[$key] = $value;
					}
					
				}
				else
				{
					$merged[] = $value;
				}
				
			}
		}
		
		return $merged;	
	}
	
	/**
	 * Convert an object to an array recursively
	 * 
	 * @param	Object			$ob
	 * 
	 * @return	mixed		If input was neither an object nor an array it returns the original input
	 */
	public static function convertToArray($ob)
	{
		if ( ! is_array($ob) AND ! is_object($ob))
		{
			return $ob;
		}
		
		foreach ($ob AS $k => &$v)
		{
			if (is_array($v) OR is_object($v))
			{
				$v = self::convertToArray($v);
			}
		}
		
		return (array) $ob;
	}
	
	/**
	 * Json encode string and make it human readable
	 *
	 * Credits: http://snipplr.com/view/60559/prettyjson/
	 * 
	 * @param	mixed			$input
	 * 
	 * @return	string							
	 */
	public static function jsonEncode($input)
	{
		
		$json 		 = json_encode($input);
		$json 		 = str_replace('\\/', '/', $json); // json_encode escapes forward slashes for whatever reason
		$result      = '';
		$pos         = 0;
		$strLen      = strlen($json);
		$indentStr   = '	';
		$newLine     = "\n";
		$prevChar    = '';
		$outOfQuotes = true;
	
		for ($i=0; $i<=$strLen; $i++)
		{
	
			// Grab the next character in the string.
			$char = substr($json, $i, 1);
	
			// Are we inside a quoted string?
			if ($char == '"' AND $prevChar != '\\')
			{
				$outOfQuotes = !$outOfQuotes;
			}
			
			// If this character is the end of an element, 
			// output a new line and indent the next line.
			else if(($char == '}' || $char == ']') AND $outOfQuotes)
			{
				$result .= $newLine;
				$pos --;
				for ($j=0; $j<$pos; $j++) {
					$result .= $indentStr;
				}
			}
			
			// Add the character to the result string.
			$result .= $char;
			
			if ($char == ':' AND $outOfQuotes)
			{
				$result .= $indentStr;
			}
			
			// If the last character was the beginning of an element, 
			// output a new line and indent the next line.
			if (($char == ',' || $char == '{' || $char == '[') AND $outOfQuotes)
			{
				$result .= $newLine;
				if ($char == '{' || $char == '[')
				{
					$pos ++;
				}
				
				for ($j = 0; $j < $pos; $j++)
				{
					$result .= $indentStr;
				}
			}
			
			$prevChar = $char;
		}
	
		return $result;
	
	}
	
	/**
	 * Convert string to camelcase
	 * 
	 * @param	string			$string			
	 * @param	bool			$lowerFirst
	 * 
	 * @return	string							
	 */
	public static function camelcaseString($string, $lowerFirst = true)
	{
		$string = preg_replace('/[^a-z0-9]/i', '', ucwords(strtolower($string)));
		
		if ($lowerFirst)
		{
			$string = lcfirst($string);
		}
		
		return $string;
	}
	
	/**
	 * Search for the given file in the cwd and the given folder variations
	 * 
	 * @param	string			$file			
	 * @param	array			$folders		
	 * @param	string			$strip			
	 * @param	array			$ignoreFolders
	 * 
	 * @return	string|bool
	 */
	public static function locate($file, array $folders = array(), $strip = null, array $ignoreFolders = array())
	{
		// Variable shortcuts
		$ds 	= DIRECTORY_SEPARATOR;
		$cwd 	= getcwd() . $ds;
		$up 	= '..' . $ds;
		
		// Set default variations
		$variations = array($file, "", $up, $up . $up);
		
		// Append given folder variations
		foreach ($folders AS $folder)
		{
			$folder = str_replace('/', $ds, $folder); // Windows compatibility
			
			$variations[] = $folder;
			$variations[] = $up . $folder;
			$variations[] = $up . $up . $folder;
		}
		
		// Prepend cwd if the file is not an absolute path
		if (substr($file, 0, 1) != DIRECTORY_SEPARATOR)
		{
			$v = $variations;
			for ($c=count($v)-1;$c>=0; $c--) {
				array_unshift($variations, $cwd . $v[$c]);
			}
		}
		
		// Add realpath values for ignored folder
		foreach ($ignoreFolders AS $ignore)
		{
			$ignoreFolders[] = realpath($ignore);
		}
		
		// iterate through variations and check for matches
		foreach ($variations AS $variation)
		{
			// Check if this variation should be ignored
			if (in_array($variation, $ignoreFolders) OR in_array(realpath($variation), $ignoreFolders))
			{
				continue;
			}
			
			// Check if we have a match
			if (file_exists($variation . $ds . $file))
			{
				$result = realpath($variation . $ds . $file);
				
				// Check if we need to strip the given prefix
				if ($strip != null AND strpos($result, $strip) === 0)
				{
					$result = substr($result, strlen($strip));
				}
				
				return $result;
			}
		}
		
		return false;
	}
	
}