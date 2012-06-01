<?php

class CLI_Xf_Addon_Update extends CLI
{
	
	/**
	 * Update command is just the install command with an option for the selected addon
	 * 
	 * @return void
	 */
	public function run()
	{
		$config = XfCli_Application::getConfig();
		if ( ! $config OR empty($config->addon_config))
		{
			$this->bail('There is no addon selected to update');
		}

		$addonConfig = XfCli_Application::loadConfigJson($config->addon_config);
		$pathForRepo = false;
		if (isset($addonConfig->importUrl))
		{
			$path = $addonConfig->importUrl;
			$pathForRepo = $addonConfig->importPath;
		}
		else if (isset($addonConfig->importPath))
		{
			$path = $addonConfig->importPath;
		}
		else
		{
			$this->bail('There is no addon selected that was imported in the first place');
		}

		$this->manualRun('addon import ' . $path, true, array(), array('addon-config' => $config->addon_config, 'path-for-repo' => $pathForRepo));
	}
	
}