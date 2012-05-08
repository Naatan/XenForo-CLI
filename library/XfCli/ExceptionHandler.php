<?php

class XfCli_ExceptionHandler
{
	
	public static function handleException($exception)
	{
		CLI::getInstance()->bail($exception->getMessage(), 'Exception');
	}
	
	public static function handleError($errNo, $errStr, $errFile, $errLine, $errContext)
	{
		echo "\n" . CLI::getInstance()->colorText('ERROR: ', CLI::RED) . $errStr . "\n\n";
		CLI::getInstance()->printDebug('File: ' . $errFile . ', line: ' . $errLine);
		die();
	}
	
}