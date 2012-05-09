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
		$base = XfCli_Application::xfBaseDir();
		
		if (is_file($base . $addonId . DIRECTORY_SEPARATOR . '.xfcli-config'))
		{
			$configFile = $base . $addonId . DIRECTORY_SEPARATOR . '.xfcli-config';
		}
		else if (is_file($base . $addonId))
		{
			$configFile = $base . $addonId;
		}
		else if (is_file($addonId . DIRECTORY_SEPARATOR . '.xfcli-config'))
		{
			$configFile = $addonId . DIRECTORY_SEPARATOR . '.xfcli-config';
		}
		else if (is_file($addonId))
		{
			$configFile = $addonId;
		}
		
		if (isset($configFile))
		{
			$config 	= XfCli_Application::loadConfigJson($configFile);
			$addonId 	= $config->addon->id;
		}
		
		$addonModel = XenForo_Model::create('XenForo_Model_AddOn');
		$addon 		= $addonModel->getAddOnById($addonId);
		
		if ($addon AND (isset($configFile) OR self::configFile($configFile)))
		{
			$configFile = isset($configFile) ? realpath($configFile) : self::configFile($configFile);
			
			if (strpos($configFile, realpath($base)) === 0)
			{
				$configFile = substr($configFile, strlen(realpath($base)) + 1);
			}
			
			$addon['config_file'] = $configFile;
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
		$config = array("addon_config" => $addon['config_file']);
		
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