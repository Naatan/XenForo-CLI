<?php

/**
 * XenForo CLI - Add Listener command (ie. xf listener add)
 */
class CLI_Xf_Listener_Add extends CLI
{
	
	/**
	 * @var string	Help text
	 */
	protected $_help = '
		Example: xf listener add load_class_controller MyAddonName
	';
	
	/**
	 * Default run method
	 * 
	 * @return	void							
	 */
	public function run()
	{
		
		// Requires at least 1 argument (ie. event to create listener for)
		$this->assertNumArguments(1);
		
		// detect addon name
		$addonName 	= $this->getArgumentAt(1);
		$addonName  = XfCli_Application::getAddonName($addonName);
		
		if (empty($addonName))
		{
			$this->bail('Could not detect addon name');
		}
		
		// Append listener to file (unless we want to skip it)
		if ( ! $this->hasFlag('skip-files'))
		{
			$this->addListenerToFile($addonName, $event);
		}
		
		// Add listener to database
		$this->addListenerToDb($addonName, $event);
		
		if ( ! $this->hasFlag('not-final'))
		{
			echo 'Listener Added';
		}
		
	}
	
	/**
	 * Add listener to database
	 * 
	 * @param	string			$addonName		
	 * @param	string			$listener
	 * 
	 * @return	void							
	 */
	protected function addListenerToDb($addonName, $listener)
	{
		$this->printInfo("Adding event listener to database.. ", false);
		
		// Validate if listener already exists
		$eventModel = new XenForo_Model_CodeEvent;
		$events 	= $eventModel->getEventListenersByAddOn($addonName);
		
		if ($events)
		{
			foreach ($events AS $event)
			{
				if (
					$event['event_id'] 			== $listener AND
					$event['callback_class'] 	== $addonName . '_Listen' AND
					$event['callback_method'] 	== $listener
				)
				{
					$this->printInfo("skipped (already exists)");
					return;
				}
			}
		}
		
		// Prepare data for insert
		$dwInput = array(
			'event_id'			=> $listener,
			'execute_order' 	=> 10,
			'description' 		=> '',
			'callback_class' 	=> $addonName . '_Listen',
			'callback_method' 	=> $listener,
			'active' 			=> 1,
			'addon_id' 			=> $addonName
		);
		
		// Perform the actual insert
		try
		{
			$dw = XenForo_DataWriter::create('XenForo_DataWriter_CodeEventListener');
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
	 * Add listener to file
	 * 
	 * @param	string			$addonName		
	 * @param	string			$listener
	 * 
	 * @return	void							
	 */
	protected function addListenerToFile($addonName, $listener)
	{
		$className 		= $addonName . '_Listen';
		$methodName 	= $listener;
		
		$params = array();
		$body = '';
		
		XfCli_ClassGenerator::create($className);
		XfCli_ClassGenerator::appendMethod($className, $methodName, $body, $params, array('static'));
	}
	
	/**
	 * Get method params for specified event
	 * 
	 * @param	string			$event
	 * 
	 * @return	array
	 */
	public function getEventParams($event)
	{
		
		$params = array();
		
		switch ($event)
		{
			case 'load_class_controller':
			case 'load_class_bb_code':
			case 'load_class_datawriter':
			case 'load_class_importer':
			case 'load_class_model':
			case 'load_class_route_prefix':
			case 'load_class_search_data':
			case 'load_class_view':
			case 'load_class_mail':
				
				$param = new Zend_CodeGenerator_Php_Parameter;
				$param->setName('class');
				$params[] = $param;
				
				$param = new Zend_CodeGenerator_Php_Parameter;
				$param->setName('extend');
				$param->setType('array');
				$param->setPassedByReference(true);
				$params[] = $param;
				
				break;
		}
		
		// todo: fire code event to allow third party event params
		
		return $params;
		
	}
	
}