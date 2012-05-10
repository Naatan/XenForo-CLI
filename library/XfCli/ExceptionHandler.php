<?php

class XfCli_ExceptionHandler
{
	
	public static function handleException($exception)
	{
		if (CLI::getInstance())
		{
			CLI::getInstance()->bail($exception->getMessage(), 'EXCEPTION');
		}
		else
		{
			echo 'EXCEPTION: ' . $exception->getMessage();
		}
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