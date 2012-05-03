<?php

class XfCli extends CLI
{
	
	protected $_nameSpace 	= 'XfCli';
	
	public static $_inAddonDir 	= false;
	public static $_baseDir 	= null;

	public function __init()
	{
		$this->detectXenForo();
	}
	
	public static function baseDir()
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
			self::bail('Could not detect XenForo install dir');
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