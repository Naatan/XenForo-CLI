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
	 * @var array	Config (if any)
	 */
	protected static $_config 	= null;
	
	/**
	 * Initialize XfCli
	 * 
	 * @return	void							
	 */
	public static function initialize()
	{
		self::registerAutoloader();
		self::setIncludePaths();
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
		
		set_exception_handler(array('XfCli_ExceptionHandler', 'handleException'));
		set_error_handler(array('XfCli_ExceptionHandler', 'handleError'));
		
		CLI::$_useExceptions 	= true;
		CLI::$_exceptionClass 	= 'XfCli_Exception';
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
		$ds 		= DIRECTORY_SEPARATOR;
		$baseDir 	= getcwd() . $ds;
		
		// Are we in the basedir already
		if (file_exists($baseDir . 'library' . $ds . 'XenForo' . $ds . 'Application.php'))
		{
			self::$_baseDir = $baseDir;
		}
		
		// Are we in the library folder
		else if (file_exists($baseDir . '..' . $ds . 'library' . $ds . 'XenForo' . $ds . 'Application.php'))
		{
			self::$_baseDir = $baseDir . '..' . $ds;
		}
		
		// Are we in an addon folder
		else if (file_exists($baseDir . '..' . $ds . '..' . $ds . 'library' . $ds . 'XenForo' . $ds . 'Application.php'))
		{
			self::$_inAddonDir 	= true;
			self::$_baseDir 	= $baseDir . '..' . $ds . '..' . $ds;
		}
		
		// Can't detect XF install folder
		else
		{
			echo 'Could not detect XenForo install dir';
			die();
		}
	}
	
	/**
	 * Get config from filesystem
	 * 
	 * @return	Object
	 */
	public static function getConfig()
	{
		if ( ! empty(self::$_config))
		{
			var_dump(self::$_config);
			return self::$_config;
		}
		
		$config = self::loadConfigJson(dirname(__FILE__) . '/../../.xfcli-config');
		
		// TODO: ability to overwrite this with --addon-config=path option. Useful for one off changes to something
		$config = XfCli_Helpers::objectMerge($config, self::loadConfigJson(self::xfBaseDir() . '.xfcli-config'));
		
		if ( ! empty($config->addon_config))
		{
			$config = XfCli_Helpers::objectMerge($config, self::loadConfigJson(self::xfBaseDir() . $config->addon_config));
		}
		
		return $config;
	}
	
	/**
	 * Loads the JSON config from a file into an array which it returns
	 * 
	 * @param  string $filepath
	 * 
	 * @return array           
	 */
	public static function loadConfigJson($filepath)
	{
		if ( ! file_exists($filepath))
		{
			return (object) array();
		}
		
		$config = file_get_contents($filepath);
		$config = json_decode($config);
		
		if ( ! $config)
		{
			return (object) array();
		}
		
		return $config;
	}
	
	/**
	 * Write config to file
	 * 
	 * @param	object			$config			
	 * @param	null|string		$file
	 * 
	 * @return	object							
	 */
	public static function writeConfig($config, $file = null)
	{
		if ( ! is_array($config) AND ! is_object($config))
		{
			return false;
		}
		
		if (empty($file))
		{
			$file = self::xfBaseDir() . DIRECTORY_SEPARATOR .'.xfcli-config';
		}
		
		$existingConfig = self::loadConfigJson($file);
		$config 		= XfCli_Helpers::objectMerge($existingConfig, $config);
		
		if ( ! file_put_contents($file, XfCli_Helpers::jsonEncode($config)))
		{
			return false;
		}
		
		return $config;
	}
	
	/**
	 * Write addon config to file
	 * 
	 * @param	object			$config
	 * 
	 * @return	object							
	 */
	public static function writeAddonConfig($config)
	{
		$folder = self::getConfig()->addon->folder;
		
		if (empty($folder))
		{
			return false;
		}
		
		$file = $folder . DIRECTORY_SEPARATOR . '.xfcli-config';
		
		return self::writeConfig($config, $file);
	}
	
}