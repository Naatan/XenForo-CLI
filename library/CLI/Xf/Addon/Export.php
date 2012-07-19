<?php

class CLI_Xf_Addon_Export extends CLI
{
	protected $_help = '
		Simply exports an addon like you would from the ACP.

		usage: addon export path --addon-id=id

		path
			Where the xml will go, either path/to/location or path/to/location/and_name.xml

		--addon-id
			if specified will export the addon with this id, otherwise will use whatever addon is selected
	';

	public function run($path)
	{
		$addonId = $this->getOption('addon-id');
		if ( ! $addonId)
		{
			$config = XfCli_Application::getConfig();
			if ( ! $config OR empty($config->addon_config))
			{
				$this->bail('There is no addon selected and the --addon-id is not set');
			}

			$addonConfig = XfCli_Application::loadConfigJson($config->addon_config);
			$addonId = $addonConfig->addon->id;
		}

		$addonModel = XenForo_Model::create('XenForo_Model_AddOn');
		$addon = $addonModel->getAddonById($addonId);
		if ( ! $addon)
		{
			$this->bail('No addon exists with that ID (' . $addonId . ')');
		}

		$xml = $addonModel->getAddOnXml($addon)->saveXml();

		if (XenForo_Helper_File::getFileExtension($path) != 'xml')
		{
			$path .= DIRECTORY_SEPARATOR . 'addon-' . $addon['addon_id'] . '.xml';
		}

		// TODO: permissions checks, folder exists checks etc
		file_put_contents($path, $xml);
		$this->printInfo('File written to ' . $path);
	}
}