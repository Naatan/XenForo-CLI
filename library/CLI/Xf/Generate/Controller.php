<?php

class CLI_Xf_Generate_Controller extends CLI
{
	
	public function run($name)
	{
		$this->printMessage('Creating Controller File.. ', false);
		
		$name 		= ucfirst($name);
		$addon 		= XfCli_Application::getConfig()->addon;
		$type 		= $this->hasFlag('admin') ? 'Admin' : 'Public';
		$namespace 	= $addon->namespace . '_Controller' . $type . '_';
		
		if (strpos($name, $namespace) === 0)
		{
			$namespace = '';
		}
		
		$className 	= $namespace . $name;
		
		// Auto create controller class if it doesn't exist et
		if ( ! XfCli_Helpers::classExists($className))
		{
			$extendName = 'XenForo_Controller' . XfCli_Helpers::camelcaseString($type, false) . '_Abstract';
			
			$class 	= new Zend_CodeGenerator_Php_Class();
			$class->setName($className);
			$class->setExtendedClass($extendName);
			
			XfCli_ClassGenerator::create($className, $class);
			
			$this->printMessage('ok');
		}
		else
		{
			$this->printMessage('skipped (already exists)');
		}
	}
	
}