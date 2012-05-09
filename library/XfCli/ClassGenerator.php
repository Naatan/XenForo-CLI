<?php

/**
 * Class Generator, wraps CodeGenerator methods in a more accessible way specific to XfCli
 */
class XfCli_ClassGenerator
{
	
	/**
	 * Get CodeGenerator instance for specified class
	 * 
	 * @param	string			$className
	 * 
	 * @return	bool|Zend_CodeGenerator_Php_Class							
	 */
	public static function get($className)
	{
		if ( ! self::classExists($className))
		{
			return false;
		}
		
		// Load class file
		$filePath = self::getClassPath($className);
		$file = Zend_CodeGenerator_Php_File::fromReflectedFileName(
			$filePath, false
		);
		
		if ( ! $file)
		{
			return false;
		}
		
		// Get class from file
		$class = $file->getClass($className);
		
		if ( ! $class)
		{
			return false;
		}
		
		// Set tab indentation
		$class->setIndentation('	');
		
		return $class;
	}
	
	/**
	 * Save specified class to it's relative file
	 * Basically a wrapper for self::create(), which updates the file if it already exists
	 * 
	 * @param	Zend_CodeGenerator_Php_Class	$class
	 * 
	 * @return	Zend_CodeGenerator_Php_Class
	 */
	public static function save(Zend_CodeGenerator_Php_Class $class)
	{
		$className 	= $class->getName();
		return self::create($className, $class);
	}
	
	/**
	 * Check if given class exists and creates dummy XFCP class to prevent errors if necessary
	 * 
	 * @param	string			$className		
	 * @param	bool			$createXfcp
	 * 
	 * @return	bool							
	 */
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
	
	/**
	 * Get path of file the class is stored in
	 * 
	 * @param	string			$className		
	 * @param	bool			$relative
	 * 
	 * @return	string							
	 */
	public static function getClassPath($className, $relative = false)
	{
		$fileName = XfCli_Autoloader::getFileName($className);
		
		if ($relative)
		{
			return $fileName;
		}
		
		return XfCli_Application::xfBaseDir() .
				'library' . DIRECTORY_SEPARATOR . $fileName;
	}
	
	/**
	 * Append method to class
	 * 
	 * @param	string			$className		
	 * @param	string			$methodName		
	 * @param	string			$append			
	 * @param	array			$params			
	 * @param	null|array		$flags			
	 * @param	string			$ignoreRegex
	 * 
	 * @return	bool|Zend_CodeGenerator_Php_Class
	 */
	public static function appendMethod($className, $methodName, $append, array $params,
										$flags = null, $ignoreRegex = null)
	{
		CLI::getInstance()->printInfo(
			'Appending data to method "' . $methodName . '", in class "' . $className . '".. ', false
		);
		
		// Get class that method belogs to
		if ( ! $class = self::get($className))
		{
			CLI::getInstance()->bail(
				'Could not append method "'.$methodName.'" to nonexistant class "' . $className . '"'
			);
		}
		
		// Get existing method (if any)
		$method = $class->getMethod($methodName);
		$body 	= '';
		
		// If method already exists, set existing body that is to be appended to and check ignoreRegex
		if ($method)
		{
			$body 	= $method->getBody() . "\n";
			
			// Trim indentation to avoid recursion
			if ($indent = preg_match('/^(\s*)/', $body, $match))
			{
				$indent = $match[1];
				$body  = preg_replace('/^'.$indent.'/m', '', $body);
			}
			
			// Check if ignoreregex matches and if so skip append
			if ($ignoreRegex != null AND preg_match($ignoreRegex, $body))
			{
				CLI::getInstance()->printInfo('skipped (already exists)');
				return false;
			}
		}
		
		// Append body to method
		$body .= $append;
		
		// Generate method anew (only the body is inherited)
		$method = new Zend_CodeGenerator_Php_Method(array(
			'name' => $methodName
		));
		
		// Set params (if any)
		if (!empty($params))
		{
			foreach ($params AS $param)
			{
				$method->setParameter($param);
			}
		}
		
		// Append body to method structure
		$method->setBody($body);
		
		// Check for flags
		if (is_array($flags))
		{
			if (in_array('static', $flags))
			{
				// Method is static
				$method->setStatic(true);
			}
		}
		
		// Append method to class
		$class->setMethod($method);
		
		CLI::getInstance()->printInfo('Ok');
		
		// Save class
		return self::save($class);
		
	}
	
	/**
	 * Create (or modify) given class
	 * 
	 * @param	string							$className		
	 * @param	Zend_CodeGenerator_Php_Class	$class
	 * 
	 * @return	Zend_CodeGenerator_Php_Class											
	 */
	public static function create($className, Zend_CodeGenerator_Php_Class $class = null)
	{
		
		// If no class data is given and the class already exists there's no point in "creating" it
		if ($class == null AND self::classExists($className))
		{
			return self::get($className);
		}
		
		// Only create class if the file is available or we have class data
		$filePath 		= self::getClassPath($className);
		$fileContents 	= file_exists($filePath) ? file_get_contents($filePath) : false;
		if (empty($fileContents) OR $class != null)
		{
			
			// Print update or create message
			if (file_exists($filePath))
			{
				CLI::getInstance()->printInfo('Updating class "' . $className . '".. ', false);
			}
			else
			{
				CLI::getInstance()->printInfo('Creating class "' . $className . '".. ', false);
			}
			
			// Load blank CodeGenerator file
			$file 	= new Zend_CodeGenerator_Php_File();
			
			// Create CodeGenerato Class if it wasn't provided as a parm
			if ($class == null)
			{
				$class 	= new Zend_CodeGenerator_Php_Class();
				$class->setName($className);
			}
			
			// Append class to file
			$file->setClass($class);
			
			// Create parent folder structure if necessary
			if ( ! is_dir(dirname($filePath)))
			{
				mkdir(dirname($filePath), 0755, true);
			}
			
			// Write to file
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
	
	/**
	 * Create a CodeGenerator parameter
	 * 
	 * @param	string				$name			
	 * @param	null|string			$type			
	 * @param	null|bool			$reference
	 * 
	 * @return	Zend_CodeGenerator_Php_Parameter
	 */
	public static function createParam($name, $type = null, $reference = null)
	{
		$param = new Zend_CodeGenerator_Php_Parameter;
		$param->setName($name);
		
		if ( ! empty($type))
		{
			$param->setType($type);
		}
		
		if ($reference === true)
		{
			$param->setPassedByReference(true);
		}
		
		return $param;
	}
	
	/**
	 * Create multiple CodeGenerator params
	 * 
	 * @param	array			$data
	 * 
	 * @return	array
	 */
	public static function createParams(array $data)
	{
		$params = array();
		
		foreach ($data AS $args)
		{
			$params[] = call_user_func_array(array('XfCli_ClassGenerator', 'createParam'), $args);
		}
		
		return $params;
	}
	
}