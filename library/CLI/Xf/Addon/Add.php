<?php

/**
 * Add a new addon
 */
class CLI_Xf_Addon_Add extends CLI
{
	protected $_help = '
		usage: addon add <addon_name> [<addon_id>] [--type=bare|normal|full] [--include-examples] [--path=PATH]

		<addon_id>
			The addon ID by default is the same as the <addon_name> except with the first letter lower case and non alphanumeric characters stripped. You can overwrite it with this option.

		--type (not yet implemented)
			There are 3 types of directories we can make:
				bare: just the add-on folder in library,
				normal (default): add-on folder with most commonly used folders (ControllerPublic, ControllerAdmin, Model, DataWriter, ViewPublic, ViewAdmin, Route, Route/Prefix, Route/PrefixAdmin)
				full: this adds all the folders you could ever need. Handlers, Helpers, BbCode etc..
			Note: normal and full will 

		--path
			By default the add-on will be made in the library folder. You can overwrite this with --path
			
		--skip-select
			Do not select the addon as after creation

		--include-examples (not yet implemented)
			TODO!
	';
	
	/**
	 * Run the command
	 * 
	 * @param	string			$addonId
	 * 
	 * @return	void							
	 */
	public function run($addonName, $addonId = null)
	{
		if ($addonId === null)
			$addonId = $addonName;

		// Prepare default data
		$addon = (object) array(
			'id' 		=> $addonId,
			'name'		=> $addonName,
			'namespace' => ucfirst($addonId),
			'path'		=> null
		);
		
		$base = XfCli_Application::xfBaseDir();
		
		// Parse addon ID
		if (empty($addon->id))
		{
			$addon->id 			= strtolower(preg_replace('/[^a-z0-9]/i', '', $addon->name));
			$addon->namespace 	= ucfirst($addon->id);
		}
		
		// Add addon to DB
		$this->addToDb($addon);
		$this->createStructure($addon);
		
		// Write addon config file
		XfCli_Application::writeConfig(array("addon" => $addon), $base . $addon->path . DIRECTORY_SEPARATOR . '.xfcli-config');
		
		// Select addon
		if ( ! $this->hasFlag('skip-select'))
		{
			$this->getParent()->selectAddon($addon->path);
		}
		
		echo 'Addon "' . $addon->name . '" created' . PHP_EOL;
	}
	
	/**
	 * Add Addon to DB
	 * 
	 * @param	object			$addon
	 * 
	 * @return	void							
	 */
	protected function addToDb($addon)
	{
		$this->printInfo('Adding addon "' . $addon->name. '" to database.. ', false);
		
		$addonModel = XenForo_Model::create('XenForo_Model_AddOn');
		if ( ! $addonModel->getAddOnById($addon->id))
		{
			
			$dw = XenForo_DataWriter::create('XenForo_DataWriter_AddOn');
			$dw->bulkSet(array(
				'addon_id' 	=> $addon->id,
				'title' 	=> $addon->name
			));
			$dw->save();
			
			$this->printInfo('ok');
			
		}
		else
		{
			$this->printInfo('skipped (already exists)');
		}
	}
	
	/**
	 * Create files and folders
	 * 
	 * @param	object			$addon
	 * 
	 * @return	void							
	 */
	protected function createStructure(&$addon)
	{
		$this->printInfo('creating folder structure.. ', false);
		
		$base = XfCli_Application::xfBaseDir();
		
		if ($this->getOption('path'))
		{
			$addon->path = $this->getOption('path');
		}
		else
		{
			$addon->path = 'library' . DIRECTORY_SEPARATOR . $addon->namespace;
		}
		
		$addon->path = realpath($base . $addon->path);
		
		if (strpos($addon->path, realpath($base)) === 0)
		{
			$addon->path = substr($addon->path, strlen(realpath($base)) + 1);
		}
		
		if( ! is_dir($base . $addon->path))
		{
			if ( ! mkdir($base . $addon->path, 0755, true))
			{
				$this->bail('Could not locate or create addon directory: ' . $addon->path);
			}
			
			$this->printInfo('ok');
		}
		else
		{
			$this->printInfo('skipped (already exists)');
		}
		
		$addon->path .= DIRECTORY_SEPARATOR;
	}
	
}