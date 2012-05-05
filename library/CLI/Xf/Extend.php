<?php

/**
 * XenForo CLI - Extend command (ie. xf extend)
 */
class CLI_Xf_Extend extends CLI
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
			xf extend XenForo_PublicController_Account MyAddonName (creates MyAddonName_Controller_Account)
			xf extend XenForo_PublicController_Account (Must be executed in addon folder, creates MyAddonName_Controller_Account)
	';
	
	/**
	 * Default run method
	 * 
	 * @return	void							
	 */
	public function run()
	{
		// Requires at least 1 argument (ie. class to extend)
		$this->assertNumArguments(1);
		
		// detect addon name and class that we are extending with
		if ( ! $extendWith = $this->getArgumentAt(1))
		{
			if ( ! $addonName = XfCli_Application::getAddonName())
			{
				$this->showHelp(true);
			}
			else
			{
				$extendWith = $addonName . substr($extend, strpos($extend, '_'));
			}
		}
		
		// Get addon name from class we are extending with
		// todo: this is fairly redundant and should probably be improved
		$addonName = XfCli_Application::getAddonName($extendWith);
		if ($addonName == $extendWith)
		{
			$extendWith = $addonName . substr($extend, strpos($extend, '_'));
		}
		
		// addon name required
		if (empty($addonName))
		{
			$this->bail('Could not detect addon name from class name: ' . $extendWith);
		}
		
		// Write the class extend to the listener file
		$this->addExtendToFile($addonName, $extend, $extendWith);
		
		// Auto create extend class if it doesn't exist et
		if ( ! XfCli_ClassGenerator::classExists($extendWith))
		{
			$class 	= new Zend_CodeGenerator_Php_Class();
			$class->setName($extendWith);
			$class->setExtendedClass('XFCP_' . $extendWith);
			
			XfCli_ClassGenerator::create($extendWith, $class);
		}
		
		// Add the listener in a seperate process as this one has the old version of the class loaded
		$classType = $this->getClassType($extend);
		echo shell_exec('xf --skip-files --not-final listener add load_class_' . $classType . ' ' . $addonName);
		
		echo 'Class Extended';
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
	protected function addExtendToFile($addonName, $extend, $extendWith)
	{
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
		$body .= "if (\$class == '$extend' AND ! in_array('$extendWith', \$extend))";
		$body .= "\n{\n";
		$body .= "	\$extend[] = '$extendWith';";
		$body .= "\n}";
		
		// Write regex that should trigger the ignore should this code already be present
		$ignoreRegex = '/\$extend\[\]\s*\=\s*(?:\'|\")'.$extendWith.'(?:\'|\")/';
		
		// Create the class and append / modify the method
		XfCli_ClassGenerator::create($className);
		XfCli_ClassGenerator::appendMethod($className, $methodName, $body, $params, array('static'), $ignoreRegex);
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