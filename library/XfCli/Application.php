<?php

class XfCli_Application
{
	
	public static $_inAddonDir 	= false;
	public static $_baseDir 	= null;
	
	public static function initialize()
	{
		self::registerAutoloader();
		self::setIncludePaths();
		
		new CLI_Xf();
	}
	
	protected static function registerAutoloader()
	{
		spl_autoload_register(array('XfCli_Autoloader', 'run'));
	}
	
	protected static function setIncludePaths()
	{
		set_include_path(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . PATH_SEPARATOR . '.' . PATH_SEPARATOR . get_include_path());
	}
	
	public static function xfBaseDir()
	{
		if (self::$_baseDir == null)
		{
			self::detectXenForo();
		}
		
		return self::$_baseDir;
	}
	
	protected static function detectXenForo()
	{
		$baseDir = getcwd();
		
		if (file_exists($baseDir . '/library/XenForo/Application.php'))
		{
			self::$_baseDir = $baseDir;
		}
		else if (file_exists($baseDir . '/../library/XenForo/Application.php'))
		{
			self::$_baseDir = $baseDir . '/../';
		}
		else if (file_exists($baseDir . '/../../library/XenForo/Application.php'))
		{
			self::$_inAddonDir 	= true;
			self::$_baseDir 	= $baseDir . '/../../';
		}
		else
		{
			CLI::getInstance()->bail('Could not detect XenForo install dir');
		}
	}
	
	public static function getAddonName($className = null)
	{
		if ( ! empty($className))
		{
			if (preg_match('/^[a-z]*$/i', $className))
			{
				return $className;
			}
			
			return substr($className, 0, strpos($className, '_'));
		}
		else if (self::$_inAddonDir)
		{
			$baseDir = getcwd();
			return basename($baseDir);
		}
		
		return false;
	}
	
}