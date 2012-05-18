<?php

/**
 * XfCli Class Autoloader
 */
class XfCli_Autoloader
{
	
	/**
	 * Run the autoloader (require class file)
	 * 
	 * @param	string			$className
	 * 
	 * @return	void							
	 */
	public static function run($className)
	{
		if ( ! $file = self::getClassPath($className))
		{
			return false;
		}
		
		require_once $file;	
	}
	
	/**
	 * Get file path for class
	 * 
	 * @param		string		$className
	 * 
	 * @return		string|bool						
	 */
	public static function getClassPath($className)
	{
		$fileName 	= self::getFileName($className);
		$dirs 		= array(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);
		
		if (class_exists('XfCli_Application', false))
		{
			$xfBase = XfCli_Application::xfBaseDir();
			array_unshift($dirs, $xfBase . 'library' . DIRECTORY_SEPARATOR);
		}
		
		foreach ($dirs AS $dir)
		{
			if (file_exists($dir . $fileName))
			{
				return $dir . $fileName;
			}
		}
		
		return false;
	}
	
	/**
	 * Get file name based on class name
	 * 
	 * @param	string			$className
	 * 
	 * @return	string							
	 */
	public static function getFileName($className)
	{
		$fileParts = explode('\\', ltrim($className, '\\'));
		
		if (false !== strpos(end($fileParts), '_'))
		{
			array_splice($fileParts, -1, 1, explode('_', current($fileParts)));
		}
			
		return implode(DIRECTORY_SEPARATOR, $fileParts) . '.php';
	}
	
}