<?php

class CLI_Xf_Addon extends CLI
{
	
	protected $_help = '
		Possible commands:
		
		(you can excute these commands with --help to view their instructions)
		
		xf addon ..
			- add
			- import
			- install
			- list
			- select
			- show
			- uninstall
	';
	
	/**
	 * @var array	Cached addons whose details have already been retreived
	 */
	protected $_addons = array();
	
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
		if ($addon = $this->getAddonByInput($addonId))
		{
			return $addon;
		}
		
		if ( ! $autoCreate)
		{
			$this->bail('Could not detect addon: ' . $addonId);
		}
		else
		{
			$this->manualRun('addon add', true, array('skip-select'));
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
		
		if ( ! isset($addon['config_file']))
		{
			$this->bail('Could not detect addon: ' . $addonId);
		}
		
		$config = array("addon_config" => $addon['config_file']);
		
		XfCli_Application::writeConfig($config);
	}
	
	/**
	 * Get addon by user input, user input can be many different things, so try to handle this as well as possible
	 * 
	 * @param		string		$addonId
	 * 
	 * @return		bool|array						
	 */
	public function getAddonByInput($addonId)
	{
		if (isset($this->_addons[$addonId]))
		{
			return $this->_addons[$addonId];
		}
		
		if (
			! $addon = $this->getAddonById($addonId) AND
			! $addon = $this->getAddonByName($addonId) AND
			! $addon = $this->getAddonByPath($addonId)
		)
		{
			return false;
		}
		
		if ( ! isset($addon['config_file']) AND $file = $this->getAddonConfigFile($addon))
		{
			$addon['config_file'] = $file;
		}
		
		$this->_addons[$addonId] = $addon;
		
		return $addon;
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
	
	/**
	 * Attempt to retreive addon config file for given addon
	 * 
	 * @param		array|string		$addon
	 * 
	 * @return		bool|string
	 */
	public function getAddonConfigFile($addon)
	{
		// Convert string to the array input we're expecting
		if (is_string($addon))
		{
			$addon = array(
				'addon_id' 	=> $addon,
				'title' 	=> $addon
			);
		}
		
		// Validate input
		if ( ! is_array($addon) OR ! isset($addon['addon_id'], $addon['title']))
		{
			return false;
		}
		
		// Define the addon folder names we will be checking for
		$names = array(
			$addon['addon_id'],
			strtolower($addon['addon_id']),
			XfCli_Helpers::camelcaseString($addon['addon_id'], false),
			XfCli_Helpers::camelcaseString($addon['title'], false)
		);
		
		// If title contains the '-' character, turn it into folder bits
		$bits = explode('-', $addon['title']);
		if (count($bits) > 1)
		{
			foreach ($bits AS &$bit)
			{
				$bit = XfCli_Helpers::camelcaseString($bit, false);
			}
			
			$names[] = implode('/', $bits);
			$names[] = strtolower(implode('/', $bits));
		}
		
		// Set variations (with and without library folder)
		$variations = array();
		foreach ($names AS $name)
		{
			$variations = array_merge($variations, array(
				$name,
				'library/'.$name
			));
		}
		
		// Locate the config file
		$base = XfCli_Application::xfBaseDir();
		return XfCli_Helpers::locate('.xfcli-config', $variations, $base, array($base));
	}
	
}