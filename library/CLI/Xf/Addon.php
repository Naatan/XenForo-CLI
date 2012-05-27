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
		$this->printMessage("Active Addon: " . XfCli_Application::getConfig()->addon->name);
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
		
		$addon = $this->getAddonById($addonId);
		
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
	
	/**
	 * Get addon by ID
	 * 
	 * @param		string		$addonId
	 * 
	 * @return		array|bool						
	 */
	public function getAddonById($addonId)
	{
		$addonModel = XenForo_Model::create('XenForo_Model_AddOn');
		$addon 		= $addonModel->getAddOnById($addonId);
		
		return $addon;
	}
	
	/**
	 * Get addon by name
	 * 
	 * @param		string		$addonName
	 * 
	 * @return		array|bool						
	 */
	public function getAddonByName($addonName)
	{
		$addonModel = XenForo_Model::create('XenForo_Model_AddOn');
		$addons 	= $addonModel->getAllAddOns();
		
		if ( ! $addons)
		{
			return false;
		}
		
		foreach ($addons AS $addon)
		{
			if ($addon['title'] == $addonName)
			{
				return $addon;
			}
		}
		
		return false;
	}
	
	/**
	 * Get addon by path
	 * 
	 * @param		string		$addonPath
	 * 
	 * @return		array|bool
	 */
	public function getAddonByPath($addonPath)
	{
		$base 	= XfCli_Application::xfBaseDir();
		$file 	= XfCli_Helpers::locate('.xfcli-config', array($addonPath), $base, array($base));
		
		if ( ! $file)
		{
			return false;
		}
		
		$config = XfCli_Application::loadConfigJson($base . $file);
		
		if ( ! isset($config->addon->id))
		{
			return false;
		}
		
		$addon = $this->getAddonById($config->addon->id);
		
		if ( ! $addon)
		{
			return false;
		}
		
		$addon['config_file'] = $file;
		
		return $addon;
	}
	
}