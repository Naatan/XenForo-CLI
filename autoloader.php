<?php

function _CLI_Autoloader($className)
{
	$file = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . _CLI_Autoloader_FileName($className);
	
	if ( ! file_exists($file))
	{
		return false;
		
	}
	
    require_once $file;
}

function _CLI_Autoloader_FileName($className)
{
    $fileParts = explode('\\', ltrim($className, '\\'));
	
    if (false !== strpos(end($fileParts), '_'))
	{
        array_splice($fileParts, -1, 1, explode('_', current($fileParts)));
	}
		
	return implode(DIRECTORY_SEPARATOR, $fileParts) . '.php';
}

spl_autoload_register('_CLI_Autoloader');