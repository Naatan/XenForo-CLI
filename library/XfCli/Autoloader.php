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
		$file = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . self::getFileName($className);
		
		if ( ! file_exists($file))
		{
			return false;
			
		}
		
		require_once $file;	
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