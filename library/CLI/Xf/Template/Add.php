<?php

/**
 * XenForo CLI - Add Template command (ie. xf template add)
 */
class CLI_Xf_Template_Add extends CLI
{
	
	/**
	 * @var string	Help text
	 */
	protected $_help = '
		Example: xf template add <name> <boolean>
			<boolean> - whether this is an admin template, any value that doesn\t match false will work to enable
	';
	
	/**
	 * Default run method
	 * 
	 * @return	void							
	 */
	public function run($name, $admin = false)
	{
		$addon = XfCli_Application::getConfig()->addon;
		
		if ( ! $addon->id)
		{
			$this->showHelp();
			$this->bail('No addon selected');
		}
		
		$this->addToDb($addon, $name, $admin);
		
		echo 'Template Added';
	}
	
	/**
	 * Add template to database
	 * 
	 * @param	object			$addon
	 * @param	string			$prefix
	 * @param 	string 			$type
	 * 
	 * @return	void							
	 */
	protected function addToDb($addon, $name, $admin)
	{
		$this->printInfo("Adding template to database.. ", false);
		
		// Validate if listener already exists
		$templateModel 	= XenForo_Model::create('XenForo_Model_' . ($admin ? 'AdminTemplate' : 'Template'));
		
		if ($admin)
		{
			$template = $templateModel->getAdminTemplateByTitle($name);
		}
		else
		{
			$template = $templateModel->getTemplateInStyleByTitle($name);
		}
		
		if ($template)
		{
			$this->printInfo("skipped (already exists)");
			return;
		}
		
		// Prepare data for insert
		$dwInput = array(
			'title' 		=> $name,
			'addon_id'		=> $addon->id,
			'style_id'		=> 0,
			'template' 		=> ''
		);
		
		// Perform the actual insert
		try
		{
			$dw = XenForo_DataWriter::create('XenForo_DataWriter_' . ($admin ? 'AdminTemplate' : 'Template'));
			$dw->bulkSet($dwInput);
			$dw->save();
			
			$this->printInfo("ok");
		}
		catch (Exception $e)
		{
			$this->bail($e->getMessage());
		}
	}
	
}