<?php

/**
 * Main XfCli application
 */
class XfCli_Application
{
	
	/**
	 * @var bool	Whether or not the command is executed from within an addon folder
	 */
	public static $_inAddonDir 	= false;
	
	/**
	 * @var string	XenForo base dir
	 */
	public static $_baseDir 	= null;
	
	/**
	 * Initialize XfCli
	 * 
	 * @return	void							
	 */
	public static function initialize()
	{
		self::registerAutoloader();
		self::setIncludePaths();
		
		new CLI_Xf();
	}
	
	/**
	 * Register Class Autoloader
	 * 
	 * @return	void							
	 */
	protected static function registerAutoloader()
	{
		spl_autoload_register(array('XfCli_Autoloader', 'run'));
	}
	
	/**
	 * Set include paths for Zend libraries
	 * 
	 * @return	void							
	 */
	protected static function setIncludePaths()
	{
		set_include_path(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . PATH_SEPARATOR . '.' . PATH_SEPARATOR . get_include_path());
	}
	
	/**
	 * Get XenForo base dir
	 * 
	 * @return	string							
	 */
	public static function xfBaseDir()
	{
		if (self::$_baseDir == null)
		{
			self::detectXenForo();
		}
		
		return self::$_baseDir;
	}
	
	/**
	 * Detect XenForo installation
	 * 
	 * @return	void							
	 */
	protected static function detectXenForo()
	{
		$baseDir = getcwd();
		
		// Are we in the basedir already
		if (file_exists($baseDir . '/library/XenForo/Application.php'))
		{
			self::$_baseDir = $baseDir;
		}
		
		// Are we in the library folder
		else if (file_exists($baseDir . '/../library/XenForo/Application.php'))
		{
			self::$_baseDir = $baseDir . '/../';
		}
		
		// Are we in an addon folder
		else if (file_exists($baseDir . '/../../library/XenForo/Application.php'))
		{
			self::$_inAddonDir 	= true;
			self::$_baseDir 	= $baseDir . '/../../';
		}
		
		// Can't detect XF install folder
		else
		{
			CLI::getInstance()->bail('Could not detect XenForo install dir');
		}
	}
	
	/**
	 * Detect addon name from class name
	 * 
	 * @param	string|null			$className
	 * 
	 * @return	string							
	 */
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