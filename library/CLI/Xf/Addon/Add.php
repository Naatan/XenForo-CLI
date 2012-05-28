<?php

/**
 * Add a new addon
 */
class CLI_Xf_Addon_Add extends CLI
{
	protected $_help = '
		usage: addon add <addon_name> [--id=addon_id] [--path=PATH] [--type=bare|normal|full] [--include-examples] [--skip-select]

		--id
			The addon ID by default is the same as the <addon_name> except with the first letter lower case and non alphanumeric characters stripped. You can overwrite it with this option.
			
		--path
			By default the add-on will be made in the library folder. You can overwrite this with --path
			
		--type (not yet implemented)
			There are 3 types of directories we can make:
				bare: just the add-on folder in library,
				normal (default): add-on folder with most commonly used folders (ControllerPublic, ControllerAdmin, Model, DataWriter, ViewPublic, ViewAdmin, Route, Route/Prefix, Route/PrefixAdmin)
				full: this adds all the folders you could ever need. Handlers, Helpers, BbCode etc..
			Note: normal and full will
			
		--include-examples (not yet implemented)
			TODO!
			
		--skip-select
			Do not select the addon as after creation
	';
	
	/**
	 * Run the command
	 * 
	 * @param	string			$addonName
	 * 
	 * @return	void							
	 */
	public function run($addonName)
	{
		// Prepare default data
		$addon = (object) array(
			'id' 		=> $this->getFlag('id'),
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
		
		$this->printMessage("Addon created");
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
		$this->printInfo('Creating folder structure.. ', false);
		
		$base = XfCli_Application::xfBaseDir();
		
		// Check if path option was given
		if ($this->getOption('path'))
		{
			$path = $this->getOption('path');
			
			// Check if path given is absolute or relative, requires that relative paths start alphabetical or with a dot
			if (preg_match('/[a-z.]/i', substr($path, 0, 1)))
			{
				if (substr($path, 0, 7) != 'library')
				{
					$path = 'library' . DIRECTORY_SEPARATOR . $path; // prepend library folder
				}
			}
			
			$addon->path = $path;
		}
		else
		{
			// Otherwise generate the path based on addon id
			$addon->path = 'library' . DIRECTORY_SEPARATOR . ucfirst(strtolower($addon->id));
		}
		
		// Strip the base path from the addon path
		if (strpos(realpath($addon->path), realpath($base)) === 0)
		{
			$addon->path = substr(realpath($addon->path), strlen(realpath($base)) + 1);
		}
		
		// Check if we need to create the directory
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
		
		// Append directory separator at the end of the path
		if (substr($addon->path, -1) != DIRECTORY_SEPARATOR)
		{
			$addon->path .= DIRECTORY_SEPARATOR;
		}
		
		if ($pos = strpos($addon->path, 'library/') !== false)
		{
			$namespace 			= substr($addon->path, $pos + 7);
			$namespace 			= substr($namespace, 0, strlen($namespace)-1);
			$namespace 			= str_replace(DIRECTORY_SEPARATOR, '_', $namespace);
			$addon->namespace 	= $namespace;
		}
	}
	
}
