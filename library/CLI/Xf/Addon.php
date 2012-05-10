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
		$base 	= XfCli_Application::xfBaseDir();
		$ds  	= DIRECTORY_SEPARATOR;
		
		$dirs 	= array(
			$base . $addonId,
			$base . 'library' . DIRECTORY_SEPARATOR . $addonId,
			$addonId
		);
		
		foreach ($dirs AS $dir)
		{
			if (is_file($dir. DIRECTORY_SEPARATOR . '.xfcli-config'))
			{
				$configFile = $dir. DIRECTORY_SEPARATOR . '.xfcli-config';
			}
			else if (is_file($dir. DIRECTORY_SEPARATOR))
			{
				$configFile = $dir. DIRECTORY_SEPARATOR;
			}
		}
		
		if (isset($configFile))
		{
			$config 	= XfCli_Application::loadConfigJson($configFile);
			$addonId 	= $config->addon->id;
		}
		
		$addonModel = XenForo_Model::create('XenForo_Model_AddOn');
		$addon 		= $addonModel->getAddOnById($addonId);
		
		if ($addon AND (isset($configFile) OR $configFile = self::configFile($addonId)))
		{
			$configFile = realPath($configFile);
			
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
			$callStructure = $this->_callStructure;
			$callStructure[] = $this;
			new CLI_Xf_Addon_Add('CLI_Xf_Addon_Add', $this->getArguments(), $this->getFlags(), $this->getOptions(), $callStructure);
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
		$file = XfCli_Application::xfBaseDir() . 'library' . DIRECTORY_SEPARATOR . ucfirst(strtolower($addonId)) . DIRECTORY_SEPARATOR . '.xfcli-config';
		return file_exists($file) ? $file : false;
	}
	
}