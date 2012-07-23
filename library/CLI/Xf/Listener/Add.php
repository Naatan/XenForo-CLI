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
		Example: xf listener add load_class_controller
	';
	
	/**
	 * Default run method
	 * 
	 * @return	void							
	 */
	public function run($event)
	{
		$addon = XfCli_Application::getConfig()->addon;
		
		if ( ! $addon->id)
		{
			$this->showHelp();
			$this->bail('No addon selected');
		}
		
		// Append listener to file (unless we want to skip it)
		if ($this->hasFlag('skip-file'))
		{
			$this->addToFile($addon, $event);
		}
		
		// Add listener to database
		$this->addToDb($addon, $event);
		
		$this->printMessage('Listener Added');
	}
	
	/**
	 * Add listener to database
	 * 
	 * @param	object			$addon
	 * @param	string			$listener
	 * 
	 * @return	void							
	 */
	protected function addToDb($addon, $listener)
	{
		$this->printMessage("Adding event listener to database.. ", false);
		
		// Validate if listener already exists
		$eventModel = new XenForo_Model_CodeEvent;
		$events 	= $eventModel->getEventListenersByAddOn($addon->id);
		
		if ($events)
		{
			foreach ($events AS $event)
			{
				if (
					$event['event_id'] 			== $listener AND
					$event['callback_class'] 	== $addon->namespace . '_Listen' AND
					$event['callback_method'] 	== $listener
				)
				{
					$this->printMessage("skipped (already exists)");
					return;
				}
			}
		}
		
		// Prepare data for insert
		$dwInput = array(
			'event_id'			=> $listener,
			'execute_order' 	=> 10,
			'description' 		=> '',
			'callback_class' 	=> $addon->namespace . '_Listen',
			'callback_method' 	=> $listener,
			'active' 			=> 1,
			'addon_id' 			=> $addon->id
		);
		
		// Perform the actual insert
		try
		{
			$dw = XenForo_DataWriter::create('XenForo_DataWriter_CodeEventListener');
			$dw->bulkSet($dwInput);
			$dw->save();
			
			$this->printMessage("ok");
		}
		catch (Exception $e)
		{
			$this->bail($e->getMessage());
		}
	}
	
	/**
	 * Add listener to file
	 * 
	 * @param	object			$addon
	 * @param	string			$listener
	 * 
	 * @return	void							
	 */
	protected function addToFile($addon, $listener)
	{
		$this->printMessage('Updating Listener File.. ', false);
		
		$className 		= $addon->namespace . '_Listen';
		$methodName 	= $listener;
		
		$params = $this->getEventParams($listener);
		$body = '';
		
		XfCli_ClassGenerator::create($className);
		$result = XfCli_ClassGenerator::appendMethod($className, $methodName, $body, $params, array('static'));
		
		if ($result)
		{
			$this->printMessage('ok');
		}
		else
		{
			$this->printMessage('skipped (already exists)');
		}
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
			
			/**************************************************************************************/
			
			case 'container_admin_params':
			case 'container_public_params':
				
				$params += XfCli_ClassGenerator::createParams(array(
					array('params', 		'array', 							true),
					array('dependencies', 	'XenForo_Dependencies_Abstract',	true)
				));
				
				break;
			
			/**************************************************************************************/
			
			case 'container_pre_dispatch':
				
				$params += XfCli_ClassGenerator::createParams(array(
					array('controller',	'XenForo_Controller'),
					array('action')
				));
				
				break;
			
			/**************************************************************************************/
			
			case 'criteria_page':
				
				$params += XfCli_ClassGenerator::createParams(array(
					array('rule'),
					array('data', 			'array'),
					array('params', 		'array'),
					array('containerData', 	'array'),
					array('returnValue', 	null, 		true),
				));
				
				break;
			
			/**************************************************************************************/
			
			case 'criteria_user':
				
				$params += XfCli_ClassGenerator::createParams(array(
					array('rule'),
					array('data', 			'array'),
					array('user', 			'array'),
					array('returnValue', 	null, 		true),
				));
				
				break;
			
			/**************************************************************************************/
			
			case 'file_health_check':
				
				$params += XfCli_ClassGenerator::createParams(array(
					array('controller',		'XenForo_ControllerAdmin_Abstract'),
					array('hashes', 		'array', 		true)
				));
				
				break;
			
			/**************************************************************************************/
			
			case 'front_controller_post_view':
				
				$params += XfCli_ClassGenerator::createParams(array(
					array('fc',			'XenForo_FrontController'),
					array('output', 	null, 		true)
				));
				
				break;
			
			/**************************************************************************************/
			
			case 'front_controller_pre_dispatch':
				
				$params += XfCli_ClassGenerator::createParams(array(
					array('fc',				'XenForo_FrontController'),
					array('routeMatch', 	'XenForo_RouteMatch', 		true)
				));
				
				break;
			
			/**************************************************************************************/
			
			case 'front_controller_pre_route':
				
				$params += XfCli_ClassGenerator::createParams(array(
					array('fc',		'XenForo_FrontController')
				));
				
				break;
			
			/**************************************************************************************/
			
			case 'front_controller_pre_view':
				
				$params += XfCli_ClassGenerator::createParams(array(
					array('fc',						'XenForo_FrontController'),
					array('controllerResponse', 	'XenForo_ControllerResponse_Abstract', 	true),
					array('viewRenderer', 			'XenForo_ViewRenderer_Abstract', 		true),
					array('containerParams', 		'array',								true)
				));
				
				break;
			
			/**************************************************************************************/
			
			case 'init_dependencies':
				
				$params += XfCli_ClassGenerator::createParams(array(
					array('dependencies',	'XenForo_Dependencies_Abstract'),
					array('data', 			'array')
				));
				
				break;
			
			/**************************************************************************************/
			
			case 'load_class_controller':
			case 'load_class_bb_code':
			case 'load_class_datawriter':
			case 'load_class_importer':
			case 'load_class_model':
			case 'load_class_route_prefix':
			case 'load_class_search_data':
			case 'load_class_view':
			case 'load_class_mail':
				
				$params += XfCli_ClassGenerator::createParams(array(
					array('class'),
					array('extend', 	'array', 	true),
				));
				
				break;
			
			/**************************************************************************************/
			
			case 'navigation_tabs':
				
				$params += XfCli_ClassGenerator::createParams(array(
					array('extraTabs',		'array', 	true),
					array('selectedTabId')
				));
				
				break;
			
			/**************************************************************************************/
			
			case 'option_captcha_render':
				
				$params += XfCli_ClassGenerator::createParams(array(
					array('extraChoices',		'array'),
					array('view', 				'XenForo_View'),
					array('preparedOption',		'array')
				));
				
				break;
			
			/**************************************************************************************/
			
			case 'search_source_create':
				
				$params += XfCli_ClassGenerator::createParams(array(
					array('class', 	null, 	true)
				));
				
				break;
			
			/**************************************************************************************/
			
			case 'template_create':
				
				$params += XfCli_ClassGenerator::createParams(array(
					array('templateName'),
					array('params', 		'array', 		true),
					array('template', 		'XenForo_Template_Abstract')
				));
				
				break;
			
			/**************************************************************************************/
			
			case 'template_hook':
				
				$params += XfCli_ClassGenerator::createParams(array(
					array('hookName'),
					array('contents', 		null, 		true),
					array('hookParams', 	'array'),
					array('template', 		'XenForo_Template_Abstract')
				));
				
				break;
			
			/**************************************************************************************/
			
			case 'template_post_render':
				
				$params += XfCli_ClassGenerator::createParams(array(
					array('templateName'),
					array('content', 		null, 		true),
					array('containerData', 	'array', 	true),
					array('template', 		'XenForo_Template_Abstract')
				));
				
				break;
			
			/**************************************************************************************/
			
			case 'visitor_setup':
				
				$params += XfCli_ClassGenerator::createParams(array(
					array('visitor', 	'XenForo_Visitor', 	true)
				));
				
				break;
			
			/**************************************************************************************/
		}
		
		// todo: fire code event to allow third party event params
		
		return $params;
		
	}
	
}