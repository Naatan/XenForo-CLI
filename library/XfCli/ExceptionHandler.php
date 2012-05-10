<?php

class XfCli_ExceptionHandler
{
	
	public static function handleException($exception)
	{
		$bt = '';
		$backtrace = debug_backtrace();
		
		foreach ($backtrace AS $a)
		{
			$bt .= '{'.$a['function'].'}()'.(isset($a['file']) ? '('.$a['file'].':{'.$a['line'].'})' : '') . PHP_EOL;
		}
			
		if (CLI::getInstance())
		{
			echo "\n" . CLI::getInstance()->colorText('EXCEPTION: ', CLI::RED) . $exception->getMessage() . "\n\n";
			
			CLI::getInstance()->printDebug($bt);
		}
		else
		{
			echo 'EXCEPTION: ' . $exception->getMessage() . PHP_EOL . PHP_EOL;
			echo $bt;
		}
		die();
	}
	
	public static function handleError($errNo, $errStr, $errFile, $errLine, $errContext)
	{
		if (CLI::getInstance())
		{
			echo "\n" . CLI::getInstance()->colorText('ERROR: ', CLI::RED) . $errStr . "\n\n";
			CLI::getInstance()->printDebug('File: ' . $errFile . ', line: ' . $errLine);
		}
		else
		{
			echo "\nERROR: " . $errStr . "\n\n";
			echo 'File: ' . $errFile . ', line: ' . $errLine;
		}
		die();
	}
	
}