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
	
	public static function getClassPath($className, $relative = false)
	{
		$fileName = _CLI_Autoloader_FileName($className);
		
		if ($relative)
		{
			return $fileName;
		}
		
		return self::baseDir() . '/library/' . $fileName;
	}
	
	public static function getFileGenerator($className)
	{
		$exists = false;
		
		try // Workaround XF's Autoloader that doesn't play nice
		{
			$exists = class_exists($className);
		} catch (Exception $e) {}
		
		$filePath = self::getClassPath($className);
		
		if ( ! $exists)
		{
			if ( ! file_exists($filePath))
			{
				$file = new Zend_CodeGenerator_Php_File(array(
					'classes' => array(
						new Zend_CodeGenerator_Php_Class(array(
							'name'    => $className
						))
					)
				));
				
				if ( ! is_dir(dirname($filePath)))
				{
					mkdir(dirname($filePath), 0755, true);
				}
				
				if ( ! is_dir(dirname($filePath)) OR ! file_put_contents($filePath, $file->generate()))
				{
					self::bail("Could not generate class '$className' as the file could not be created: " . $filePath);
				}
			}
			else
			{
				self::bail("Could not generate class '$className' as the file it maps to is already in use: " . $filePath);
			}
		}
		
		return Zend_CodeGenerator_Php_File::fromReflectedFileName(
			$filePath, false
		);
	}
	
}