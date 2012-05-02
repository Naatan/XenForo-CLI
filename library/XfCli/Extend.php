<?php

class XfCli_Extend extends CLI
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
		Example: xf extend XenForo_PublicController_Account MyAddon_Controller_Account
	';
	
	public function run()
	{
		
		if ( ! $extend = $this->getArgumentAt(0) OR  ! $extendWith = $this->getArgumentAt(1))
		{
			$this->showHelp(true);
		}
		
		$addonName = substr($extendWith, 0, strpos($extendWith, '_'));
		
		if (empty($addonName))
		{
			$this->die('Could not detect addon name from class name: ' . $extendWith);
		}
		
		$this->addExtendToFile($addonName, $extend, $extendWith);
		$this->addExtendToDb($addonName, $extend, $extendWith);
		
		echo 'Class Extended';
	}
	
	protected function addExtendToDb($addonName, $extend, $extendWith)
	{
		$classType = $this->getClassType($extend);
		
		$eventModel = new XenForo_Model_CodeEvent;
		$events 	= $eventModel->getEventListenersByAddOn($addonName);
		
		if ($events)
		{
			foreach ($events AS $event)
			{
				if (
					$event['event_id'] 			== 'load_class_' . $classType AND
					$event['callback_class'] 	== $addonName . '_Listen' AND
					$event['callback_method'] 	== 'load_class_' . $classType
				)
				{
					return;
				}
			}
		}
		
		$dwInput = array(
			'event_id'			=> 'load_class_' . $classType,
			'execute_order' 	=> 10,
			'description' 		=> '',
			'callback_class' 	=> $addonName . '_Listen',
			'callback_method' 	=> 'load_class_' . $classType,
			'active' 			=> 1,
			'addon_id' 			=> $addonName
		);
		
		$dw = XenForo_DataWriter::create('XenForo_DataWriter_CodeEventListener');
		$dw->bulkSet($dwInput);
		$dw->save();
	}
	
	protected function addExtendToFile($addonName, $extend, $extendWith)
	{
		$className 		= $addonName . '_Listen';
		$classType 		= $this->getClassType($extend);
		$fileGenerator 	= XfCli::getFileGenerator($className);
		$classGenerator = $fileGenerator->getClass($className);
		$filePath 		= XfCli::getClassPath($className);
		$methodName 	= 'load_class_' . $classType;
		$method 		= $classGenerator->getMethod($methodName);
		
		$fileGenerator->setIndentation('	');
		$classGenerator->setIndentation('	');
		
		if ( ! $method)
		{
			$body 	= '';
		}
		else
		{
			$body 	= $method->getBody() . "\n";
			if (preg_match('/\$extend\[\]\s*\=\s*(?:\'|\")'.$extendWith.'(?:\'|\")/', $body))
			{
				return false;
			}
		}
		
		$method = new Zend_CodeGenerator_Php_Method(array(
			'name' => $methodName
		));
		$method->setIndentation('	');
		
		$param = new Zend_CodeGenerator_Php_Parameter;
		$param->setName('class');
		$method->setParameter($param);
		
		$param = new Zend_CodeGenerator_Php_Parameter;
		$param->setName('extend');
		$param->setType('array');
		$param->setPassedByReference(true);			
		$method->setParameter($param);
		
		$body .= "\n";
		$body .= "if (\$class == '$extend' AND ! in_array('$extendWith', \$extend))";
		$body .= "\n{\n";
		$body .= "	\$extend[] = '$extendWith';";
		$body .= "\n}\n";
		
		$method->setBody($body);
		
		$classGenerator->setMethod($method);
		$fileGenerator->setClass($classGenerator);
		
		file_put_contents($filePath, $fileGenerator->generate());
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