<?php

class CLI_Xf_Extend extends CLI
{
	
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
	
	protected $_help = '
		Examples:
			xf extend XenForo_PublicController_Account MyAddon_Controller_Account
			xf extend XenForo_PublicController_Account MyAddonName (creates MyAddonName_Controller_Account)
			xf extend XenForo_PublicController_Account (Must be executed in addon folder, creates MyAddonName_Controller_Account)
	';
	
	public function run()
	{
		$this->assertNumArguments(1);
		
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
		
		$addonName = XfCli_Application::getAddonName($extendWith);
		
		if ($addonName == $extendWith)
		{
			$extendWith = $addonName . substr($extend, strpos($extend, '_'));
		}
		
		if (empty($addonName))
		{
			$this->bail('Could not detect addon name from class name: ' . $extendWith);
		}
		
		$this->addExtendToFile($addonName, $extend, $extendWith);
		
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
	
	protected function addExtendToFile($addonName, $extend, $extendWith)
	{
		$className 		= $addonName . '_Listen';
		$classType 		= $this->getClassType($extend);
		$methodName 	= 'load_class_' . $classType;
		
		$params = array();
		
		$param = new Zend_CodeGenerator_Php_Parameter;
		$param->setName('class');
		$params[] = $param;
		
		$param = new Zend_CodeGenerator_Php_Parameter;
		$param->setName('extend');
		$param->setType('array');
		$param->setPassedByReference(true);
		$params[] = $param;
		
		$body  = "\n";
		$body .= "if (\$class == '$extend' AND ! in_array('$extendWith', \$extend))";
		$body .= "\n{\n";
		$body .= "	\$extend[] = '$extendWith';";
		$body .= "\n}";
		
		$ignoreRegex = '/\$extend\[\]\s*\=\s*(?:\'|\")'.$extendWith.'(?:\'|\")/';
		
		XfCli_ClassGenerator::create($className);
		XfCli_ClassGenerator::appendMethod($className, $methodName, $body, $params, array('static'), $ignoreRegex);
	}
	
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