<?php

/**
 * List installed addons
 */
class CLI_Xf_Addon_List extends CLI
{
	
	/**
	 * Run the command
	 *
	 * @return	void							
	 */
	public function run()
	{
		$addonModel = XenForo_Model::create('XenForo_Model_AddOn');
		$addons 	= $addonModel->getAllAddOns();
		$haddons 	= array();
		
		foreach ($addons AS $addon)
		{
			$haddons[] = array(
				'Title'		=> $addon['title'],
				'ID'		=> $addon['addon_id'],
				'Version'	=> $addon['version_string'],
				'Status'	=> $addon['active'] ? 'Enabled' : $this->colorText('Disabled', self::RED)
			);
		}
		
		$this->printTable($haddons);
	}
	
}
