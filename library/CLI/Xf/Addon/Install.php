<?php

class CLI_Xf_Addon_Install extends CLI
{
	protected $_help = '
usage: addon install pathToXmlFile|pathToDevelopmentXmlFolder';

	public function run($path)
	{
		$addonModel = XenForo_Model::create('XenForo_Model_AddOn');
		try 
		{
			$this->printInfo('Installing addon...');

			$caches = $addonModel->installAddOnXmlFromFile($path);

			// TODO: call properly and not with cmd
			shell_exec('xf rebuild ' . implode(' ', $caches));

			$this->manualRun('rebuild ' . implode(' ', $caches), false, false, false);

			$this->printInfo('Addon installed');
		} 
		catch (XenForo_Exception $e)
		{
			$this->bail($e->getMessage());
		}
	}
}