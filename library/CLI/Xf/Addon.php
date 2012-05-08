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
		$structure 		= $this->_callStructure;
		$structure[] 	= $this;
		
		new CLI_Xf_Addon_Select('CLI_Xf_Addon_Select', $this->getArguments(), $this->getFlags(), $this->getOptions(), $structure);
	}
	
	/**
	 * Alias for "Add"
	 * 
	 * @return	void							
	 */
	public function runCreate()
	{
		$structure 		= $this->_callStructure;
		$structure[] 	= $this;
		
		new CLI_Xf_Addon_Add('CLI_Xf_Addon_Add', $this->getArguments(), $this->getFlags(), $this->getOptions(), $structure);
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
		$addonModel = XenForo_Model::create('XenForo_Model_AddOn');
		$addon 		= $addonModel->getAddOnById($addonId);
		
		if ($addon AND self::configFile($addon['addon_id']))
		{
			return $addon;
		}
		
		if ( ! $autoCreate)
		{
			$this->bail('Could not detect addon: ' . $addonId);
		}
		else
		{
			$this->setFlag('skip-select');
			new CLI_Xf_Addon_Add('CLI_Xf_Addon_Add', null, $this->getFlags(), $this->getOptions(), $this->_callStructure);
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
		$config = array("addon_config" => $this->configFile($addon['addon_id']));
		
		XfCli_Application::writeConfig($config);
	}
	
	/**
	 * Get config file for addon
	 * 
	 * @param	string			$addonId
	 * 
	 * @return	string|bool
	 */
	public function configFile($addonId)
	{
		$file = XfCli_Application::xfBaseDir() . 'library' . DIRECTORY_SEPARATOR . ucfirst($addonId) . DIRECTORY_SEPARATOR . '.xfcli-config';
		return file_exists($file) ? $file : false;
	}
	
}