<?php

/**
 * XenForo CLI - Add Route command (ie. xf route add)
 */
class CLI_Xf_Route_Add extends CLI
{
	
	/**
	 * @var string	Help text
	 */
	protected $_help = '
		Example: xf route add <prefix>
	';
	
	/**
	 * Default run method
	 * 
	 * @return	void							
	 */
	public function run($prefix, $type = 'public')
	{
		$addon = XfCli_Application::getConfig()->addon;
		
		if ( ! $addon->id)
		{
			$this->showHelp();
			$this->bail('No addon selected');
		}
		
		if ( ! $this->hasFlag('skip-files'))
		{
			$this->addToFile($addon, $prefix, $type);
			
			$controllerName = $this->getClassName($addon, $prefix, $type, 'Controller');
			$extendName = 'XenForo_Controller' . XfCli_Helpers::camelcaseString($type, false) . '_Abstract';
			
			// Auto create controller class if it doesn't exist et
			if ( ! XfCli_ClassGenerator::classExists($controllerName))
			{
				$class 	= new Zend_CodeGenerator_Php_Class();
				$class->setName($controllerName);
				$class->setExtendedClass($extendName);
				
				XfCli_ClassGenerator::create($controllerName, $class);
			}
		}
		
		// Add listener to database
		if ($this->hasFlag('one-process'))
		{
			$this->addToDb($addon, $prefix, $type);
		}
		else
		{
			$this->printInfo( shell_exec('xf --skip-files --not-final --one-process route add ' . $prefix. ' ' . $type) );
		}
		
		if ( ! $this->hasFlag('not-final'))
		{
			$this->printMessage('Route Added');
		}
		
	}
	
	/**
	 * Add prefix to database
	 * 
	 * @param	object			$addon
	 * @param	string			$prefix
	 * @param 	string 			$type
	 * 
	 * @return	void							
	 */
	protected function addToDb($addon, $prefix, $type)
	{
		$this->printInfo("Adding route prefix to database.. ", false);
		
		// Validate if listener already exists
		$routeModel = XenForo_Model::create('XenForo_Model_RoutePrefix');
		$route 		= $routeModel->getPrefixByOriginal($prefix, $type);
		
		if ($route)
		{
			$this->printInfo("skipped (already exists)");
			return;
		}
		
		$addon 		= XfCli_Application::getConfig()->addon;
		$className 	= $this->getClassName($addon, $prefix, $type);
		
		// Prepare data for insert
		$dwInput = array(
			'route_type' 		=> $type,
			'original_prefix'	=> $prefix,
			'route_class' 		=> $className,
			'build_link'		=> 'all',
			'addon_id'			=> $addon->id
		);
		
		// Perform the actual insert
		try
		{
			$dw = XenForo_DataWriter::create('XenForo_DataWriter_RoutePrefix');
			$dw->bulkSet($dwInput);
			$dw->save();
			
			$this->printInfo("ok");
		}
		catch (Exception $e)
		{
			$this->bail($e->getMessage());
		}
	}
	
	/**
	 * Add Prefix to file
	 * 
	 * @param	object			$addon
	 * @param	string			$prefix
	 * @param	string			$type
	 * 
	 * @return	void							
	 */
	protected function addToFile($addon, $prefix, $type)
	{
		$className 		= $this->getClassName($addon, $prefix, $type);
		$controllerName = $this->getClassName($addon, $prefix, $type, 'Controller');
		
		$params = XfCli_ClassGenerator::createParams(array(
			array('routePath'),
			array('request', 	'Zend_Controller_Request_Http'),
			array('router', 	'XenForo_Router')
		));
		
		$body 	= "return \$router->getRouteMatch('$controllerName', \$routePath, '$prefix');";
		
		XfCli_ClassGenerator::create($className);
		XfCli_ClassGenerator::appendMethod($className, 'match', $body, $params, null, "/$controllerName/i");
	}
	
	/**
	 * Parse class name from input
	 * 
	 * @param	Object			$addon			
	 * @param	string			$prefix			
	 * @param	string			$type
	 * 
	 * @return	string							
	 */
	protected function getClassName($addon, $prefix, $type, $class = 'Prefix')
	{
		if ($type == 'public' AND $class == 'Prefix')
		{
			$className = $addon->namespace . '_RoutePrefix_' . XfCli_Helpers::camelcaseString($prefix, false);
		}
		else
		{
			$className = $addon->namespace . '_' . $class . XfCli_Helpers::camelcaseString($type, false) . '_' . XfCli_Helpers::camelcaseString($prefix, false);
		}
		
		return $className;
	}
	
}