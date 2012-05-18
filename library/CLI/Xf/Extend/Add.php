<?php

/**
 * XenForo CLI - Extend command (ie. xf extend)
 */
class CLI_Xf_Extend_Add extends CLI
{
	
	/**
	 * @var array 	Class types array, used to auto detect the type of class from user input
	 */
	protected $classTypes = array(
		'bb_code'		=> array('BbCode'),
		'controller'	=> array('Controller'),
		'datawriter'	=> array('DataWriter'),
		'importer'		=> array('Importer'),
		'model'			=> array('Model'),
		'route_prefix'	=> array('RoutePrefix', 'PrefixAdmin'),
		'search_data'	=> array('Search_DataHandler'),
		'view'			=> array('ViewPublic', 'ViewAdmin'),
		'mail'			=> array('Mail')
	);
	
	/**
	 * @var string	Help text
	 */
	protected $_help = '
		Examples:
			xf extend XenForo_PublicController_Account MyAddon_Controller_Account
			xf extend XenForo_PublicController_Account
			
		You must have an addon selected (xf select addon) to use this
	';
	
	/**
	 * Default run method
	 * 
	 * @return	void							
	 */
	public function run($extend, $extendWith = null)
	{
		$addonName = XfCli_Application::getConfig()->addon->namespace;
		
		if ( ! $addonName)
		{
			$this->showHelp();
			$this->bail('No addon selected');
		}
		
		// detect addon name and class that we are extending with
		if ( ! $extendWith = $this->getArgumentAt(1))
		{
			$extendWith = $addonName . substr($extend, strpos($extend, '_'));
		}
		
		// Write the class extend to the listener file
		$this->addToFile($addonName, $extend, $extendWith);
		
		// Add the listener in a seperate process as this one has the old version of the class loaded
		$classType = $this->getClassType($extend);
		$this->manualRun('listener add load_class_' . $classType, false);
		
		$this->printMessage('Class Extended');
	}
	
	/**
	 * Add extend code to listener file
	 * 
	 * @param	string			$addonName		
	 * @param	string			$extend			
	 * @param	string			$extendWith
	 * 
	 * @return	void							
	 */
	protected function addToFile($addonName, $extend, $extendWith)
	{
		$this->printInfo('Updating Listener File.. ', false);
		
		// Detect class info
		$className 		= $addonName . '_Listen';
		$classType 		= $this->getClassType($extend);
		$methodName 	= 'load_class_' . $classType;
		
		// Prepare method params
		$params = array();
		
		$param = new Zend_CodeGenerator_Php_Parameter;
		$param->setName('class');
		$params[] = $param;
		
		$param = new Zend_CodeGenerator_Php_Parameter;
		$param->setName('extend');
		$param->setType('array');
		$param->setPassedByReference(true);
		$params[] = $param;
		
		// Write body
		$body  = "\n";
		$body .= "/* Extend $extend */\n";
		$body .= "if (\$class == '$extend' AND ! in_array('$extendWith', \$extend))";
		$body .= "\n{\n";
		$body .= "	\$extend[] = '$extendWith';";
		$body .= "\n}\n";
		$body .= "/* Extend End */";
		
		// Write regex that should trigger the ignore should this code already be present
		$ignoreRegex = '/\$extend\[\]\s*\=\s*(?:\'|\")'.$extendWith.'(?:\'|\")/';
		
		// Create the class and append / modify the method
		XfCli_ClassGenerator::create($className);
		$result = XfCli_ClassGenerator::appendMethod($className, $methodName, $body, $params, array('static'), $ignoreRegex);
		
		if ($result)
		{
			$this->printInfo('ok');
		}
		else
		{
			$this->printInfo('skipped (already exists)');
		}
		
		$this->printInfo('Creating Class File.. ', false);
		
		// Auto create extend class if it doesn't exist et
		if ( ! XfCli_Helpers::classExists($extendWith))
		{
			$class 	= new Zend_CodeGenerator_Php_Class();
			$class->setName($extendWith);
			$class->setExtendedClass('XFCP_' . $extendWith);
			
			XfCli_ClassGenerator::create($extendWith, $class);
			
			$this->printInfo('ok');
		}
		else
		{
			$this->printInfo('skipped (alread exists)');
		}
	}
	
	/**
	 * Detect class type from class name
	 * 
	 * @param	string			$className
	 * 
	 * @return	string|void							
	 */
	protected function getClassType($className)
	{
		foreach ($this->classTypes AS $classType => $matches)
		{
			foreach ($matches AS $match)
			{
				if (strpos($className, $match))
				{
					return $classType;
				}
			}
		}
		
		$this->bail('Could not detect class type for class: ' . $className);
	}
	
}