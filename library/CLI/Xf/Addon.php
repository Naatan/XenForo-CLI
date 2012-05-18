<?php

class CLI_Xf_Addon extends CLI
{
	
	/**
	 * Default run method
	 * 
	 * @return	void							
	 */
	public function run()
	{
		$this->manualRun('addon select');
	}
	
	/**
	 * Alias for "Add"
	 * 
	 * @return	void							
	 */
	public function runCreate()
	{
		$this->manualRun('addon add');
	}
	
	/**
	 * Get addon details
	 * 
	 * @param	string			$addonId		
	 * @param	bool			$autoCreate
	 * 
	 * @return	array|void
	 */
	public function getAddon($addonId, $autoCreate = true)
	{
		$base 		= XfCli_Application::xfBaseDir();
		$variations = array($addonId, 'library/'.$addonId, ucfirst(strtolower($addonId)), 'library/' . ucfirst(strtolower($addonId)));
		$configFile = XfCli_Helpers::locate('.xfcli-config', $variations, $base, array($base));
		
		if ($configFile)
		{
			$config 	= XfCli_Application::loadConfigJson($base . $configFile);
			$addonId 	= $config->addon->id;
		}
		
		$addonModel = XenForo_Model::create('XenForo_Model_AddOn');
		$addon 		= $addonModel->getAddOnById($addonId);
		
		if ($addon AND $configFile)
		{
			$addon['config_file'] = $configFile;
			return $addon;
		}
		
		if ( ! $autoCreate)
		{
			$this->bail('Could not detect addon: ' . $addonId);
		}
		else
		{
			$this->manualRun('addon add', tue, array('skip-select'));
			return $this->getAddon($addonId, false);
		}
	}
	
	/**
	 * Select an addon for usage with relevant commands
	 * 
	 * @param	string			$addonId
	 * 
	 * @return	void							
	 */
	public function selectAddon($addonId)
	{
		$addon 	= $this->getAddon($addonId, $this->hasFlag('auto-create'));
		$config = array("addon_config" => $addon['config_file']);
		
		XfCli_Application::writeConfig($config);
	}
	
}