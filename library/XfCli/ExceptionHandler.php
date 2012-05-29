<?php

class XfCli_ExceptionHandler
{
	
	public static function handleException($exception)
	{
		if (get_class($exception) == 'XfCli_Exception')
		{
			$title = 'ERROR';
		}
		else
		{
			$title = 'EXCEPTION';
			$bt = self::_backtrace();
		}
			
		self::_print($exception->getMessage() . "\n", $title);
		
		if (isset($bt))
		{
			self::_print($bt);
		}
		
		die();
	}
	
	public static function handleError($errNo, $errStr, $errFile, $errLine, $errContext)
	{
		if (strpos($errStr, 'ob_end_clean') === 0)
		{
			return;
		}
		
		if (strpos($errStr, 'Missing argument') !== false AND $cli = CLI::getInstance())
		{
			$bt = debug_backtrace();
			$e  = $bt[1];
			
			if (
				isset($e['function'], $e['object']) 		AND
				substr($e['function'], 0, 3) == 'run'		AND
				is_subclass_of($e['object'], 'CLI')
			)
			{
				$e['object']->showHelp();
				die();
			}
		}

		throw new Exception($errStr . ' in ' . $errFile . ' on line ' . $errLine);

		self::_print($errStr, 'ERROR');
		self::_print('File: ' . $errFile . ', line: ' . $errLine);
		die();
	}
	
	public static function _backtrace()
	{
		$bt = '';
		$backtrace = debug_backtrace();
		
		foreach ($backtrace AS $a)
		{
			$bt .= '{'.$a['function'].'}()'.(isset($a['file']) ? '('.$a['file'].':{'.$a['line'].'})' : '') . PHP_EOL;
		}
		
		return $bt;
	}
	
	protected static function _print($string, $title = null)
	{
		if ($cli = CLI::getInstance())
		{
			$cli->printMessage('');
			
			if ($title != null)
			{
				$cli->printMessage($cli->colorText($title . ': ', CLI::RED), false);
			}
			
			$cli->printMessage($string);
		}
		else
		{
			echo "\n";
			
			if ($title != null)
			{
				echo $title . ': ';
			}
			
			echo $string . "\n";
		}
	}
	
}