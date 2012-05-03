<?php

class CLI_Xf_Listener_Add extends CLI
{
	
	protected $_help = '
		Example: xf listener add load_class_controller MyAddonName
	';
	
	public function run()
	{
		
		if ( ! $event = $this->getArgumentAt(0))
		{
			$this->showHelp(true);
		}
		
		$addonName 	= $this->getArgumentAt(1);
		$addonName  = XfCli_Application::getAddonName($addonName);
		
		if (empty($addonName))
		{
			$this->bail('Could not detect addon name');
		}
		
		$this->addListenerToFile($addonName, $event);
		$this->addListenerToDb($addonName, $event);
		
		echo 'Listener Added';
		
	}
	
	protected function addListenerToDb($addonName, $listener)
	{
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
					return;
				}
			}
		}
		
		$dwInput = array(
			'event_id'			=> $listener,
			'execute_order' 	=> 10,
			'description' 		=> '',
			'callback_class' 	=> $addonName . '_Listen',
			'callback_method' 	=> $listener,
			'active' 			=> 1,
			'addon_id' 			=> $addonName
		);
		
		try
		{
			$dw = XenForo_DataWriter::create('XenForo_DataWriter_CodeEventListener');
			$dw->bulkSet($dwInput);
			$dw->save();
		}
		catch (Exception $e)
		{
			$this->bail($e->getMessage());
		}
	}
	
	protected function addListenerToFile($addonName, $listener)
	{
		$className 		= $addonName . '_Listen';
		$methodName 	= $listener;
		
		$params = array();
		$body = '';
		
		XfCli_ClassGenerator::create($className);
		XfCli_ClassGenerator::appendMethod($className, $methodName, $body, $params, array('static'));
	}
	
}