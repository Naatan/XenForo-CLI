<?php

class XfCli_ClassGenerator
{
	
	public static function get($className)
	{
		if ( ! self::classExists($className))
		{
			return false;
		}
		
		$filePath = self::getClassPath($className);
		
		$file = Zend_CodeGenerator_Php_File::fromReflectedFileName(
			$filePath, false
		);
		
		if ( ! $file)
		{
			return false;
		}
		
		$class = $file->getClass($className);
		
		if ( ! $class)
		{
			return false;
		}
		
		$class->setIndentation('	');
		
		return $class;
	}
	
	public static function save(Zend_CodeGenerator_Php_Class $class)
	{
		$className 	= $class->getName();
		return self::create($className, $class);
	}
	
	public static function classExists($className, $createXfcp = true)
	{
		if ($createXfcp)
		{
			$xfcpClass = 'XFCP_' . $className;
			
			if ( ! self::classExists($xfcpClass, false))
			{
				eval("class $xfcpClass {}");
			}
		}
		
		try // Workaround XF's Autoloader that doesn't play nice
		{
			return class_exists($className);
		} catch (Exception $e) {}
		
		return false;
	}
	
	public static function getClassPath($className, $relative = false)
	{
		$fileName = XfCli_Autoloader::getFileName($className);
		
		if ($relative)
		{
			return $fileName;
		}
		
		return XfCli_Application::xfBaseDir() . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . $fileName;
	}
	
	public static function appendMethod($className, $methodName, $append, array $params, $flags = null, $ignoreRegex = null)
	{
		CLI::getInstance()->printInfo('Appending data to method "' . $methodName . '", in class "' . $className . '".. ', false);
		
		if ( ! $class = self::get($className))
		{
			CLI::getInstance()->bail('Could not append method "'.$methodName.'" to nonexistant class "' . $className . '"');
		}
		
		$method 		= $class->getMethod($methodName);
		
		$body = '';
		if ($method)
		{
			$body 	= $method->getBody() . "\n";
			
			if ($indent = preg_match('/^(\s*)/', $body, $match))
			{
				$indent = $match[1];
				$body  = preg_replace('/^'.$indent.'/m', '', $body);
			}
			
			if ($ignoreRegex != null AND preg_match($ignoreRegex, $body))
			{
				CLI::getInstance()->printInfo('skipped (already exists)');
				return;
			}
		}
		
		$body .= $append;
		
		$method = new Zend_CodeGenerator_Php_Method(array(
			'name' => $methodName
		));
		
		if (!empty($params))
		{
			foreach ($params AS $param)
			{
				$method->setParameter($param);
			}
		}
		
		$method->setBody($body);
		
		if (is_array($flags))
		{
			if (in_array('static', $flags))
			{
				$method->setStatic(true);
			}
		}
		
		$class->setMethod($method);
		
		CLI::getInstance()->printInfo('Ok');
		
		return self::save($class);
		
	}
	
	public static function create($className, Zend_CodeGenerator_Php_Class $class = null)
	{
		if ($class == null AND self::classExists($className))
		{
			return self::get($className);
		}
		
		$filePath = self::getClassPath($className);
		
		if ( ! file_exists($filePath) OR $class != null)
		{
			if (file_exists($filePath))
			{
				CLI::getInstance()->printInfo('Updating class "' . $className . '".. ', false);
			}
			else
			{
				CLI::getInstance()->printInfo('Creating class "' . $className . '".. ', false);
			}
			
			$file 	= new Zend_CodeGenerator_Php_File();
			
			if ($class == null)
			{
				$class 	= new Zend_CodeGenerator_Php_Class();
				$class->setName($className);
			}
			
			$file->setClass($class);
			
			if ( ! is_dir(dirname($filePath)))
			{
				mkdir(dirname($filePath), 0755, true);
			}
			
			if ( ! is_dir(dirname($filePath)) OR ! file_put_contents($filePath, trim($file->generate())))
			{
				CLI::getInstance()->bail("File could not be created: " . $filePath);
			}
		}
		else
		{
			CLI::getInstance()->bail("File already exists: " . $filePath);
		}
		
		CLI::getInstance()->printInfo('Ok');
		
		return self::get($className);
	}
	
}