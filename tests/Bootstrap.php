<?php
// --process-isolation

if ( ! $path = getenv('XFTESTPATH') AND file_exists(getenv('HOME') . DIRECTORY_SEPARATOR . '.xfcli-testPath'))
{
	$path = file_get_contents(getenv('HOME') . DIRECTORY_SEPARATOR . '.xfcli-testPath');
}

if ( ! $path)
{
	echo 'Missing XenForo path parameter (-d xfPath="/path/to/xenforo")';
	die();
}

chdir(trim($path));

require_once(dirname(__FILE__) . '/../library/XfCli/Autoloader.php');
require_once(dirname(__FILE__) . '/../library/XfCli/Application.php');
require_once(dirname(__FILE__) . '/../library/PHP-CLI/cli.php');

$xfLibrary = XfCli_Application::xfBaseDir() . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR;
 
// Initialize the xenforo autoloader
require_once($xfLibrary . 'XenForo/Autoloader.php');
XenForo_Autoloader::getInstance()->setupAutoloader($xfLibrary);
 
// Initialize XenForo App
XenForo_Application::initialize($xfLibrary, $xfLibrary);
$dependencies = new XenForo_Dependencies_Public();
$dependencies->preLoadData();
 
unset($xfLibrary);

XfCli_Application::initialize();

restore_error_handler();
restore_error_handler();

restore_exception_handler();
restore_exception_handler();

ob_start();