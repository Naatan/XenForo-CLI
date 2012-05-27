<?php

/**
 * List installed addons
 */
class CLI_Xf_Addon_Show extends CLI
{
	
	/**
	 * Run the command
	 *
	 * @return	void							
	 */
	public function run($addonId)
	{
		if ( ! $addon = $this->getParent()->getAddonById($addonId))
		{
			$this->bail('Could not find addon: ' . $addonId);
		}
		
		$haddon = array(
			array('Title:', 	$addon['title']),
			array('ID:',		$addon['addon_id']),
			array('Version:', 	$addon['version_string']),
			array('Status:',	$addon['active'] ? 'Enabled' : $this->colorText('Disabled', self::RED))
		);
		
		$this->printTable($haddon, '', false);
		$this->printEmptyLine();
		
		$stats = array(
			array('Admin Navigation:', 		XenForo_Model::create('XenForo_Model_AdminNavigation')->getAdminNavigationEntriesInAddOn($addonId)),
			array('Admin Permissions:', 	XenForo_Model::create('XenForo_Model_Admin')->getAdminPermissionsForAddOn($addonId)),
			array('Admin Templates:', 		XenForo_Model::create('XenForo_Model_AdminTemplate')->getAdminTemplatesByAddOn($addonId)),
			array('Code Events:',			XenForo_Model::create('XenForo_Model_CodeEvent')->getEventsByAddOn($addonId)),
			array('Code Event Listeners:',	XenForo_Model::create('XenForo_Model_CodeEvent')->getEventListenersByAddOn($addonId)),
			array('Cron Job:',				XenForo_Model::create('XenForo_Model_Cron')->getCronEntriesByAddOnId($addonId)),
			array('Email Templates:',		XenForo_Model::create('XenForo_Model_EmailTemplate')->getMasterEmailTemplatesByAddOn($addonId)),
			array('Options:',				XenForo_Model::create('XenForo_Model_Option')->getOptionsByAddOn($addonId)),
			array('Option Groups:',			XenForo_Model::create('XenForo_Model_Option')->getOptionGroupsByAddOn($addonId)),
			array('Permissions:',			XenForo_Model::create('XenForo_Model_Permission')->getPermissionsByAddOn($addonId)),
			array('Permission Groups:',		XenForo_Model::create('XenForo_Model_Permission')->getPermissionGroupsByAddOn($addonId)),
			array('Phrases:',				XenForo_Model::create('XenForo_Model_Phrase')->getMasterPhrasesInAddOn($addonId)),
			array('Routes:',				XenForo_Model::create('XenForo_Model_RoutePrefix')->getPrefixesByAddOnGroupedByRouteType($addonId)),
			array('Style Properties:',		XenForo_Application::getDb()->fetchAll('SELECT * FROM xf_style_property_group WHERE addon_id = ?', $addonId)),
			array('Templates:',				XenForo_Model::create('XenForo_Model_Template')->getMasterTemplatesInAddOn($addonId)),
			array('BBCode\'s:',				XenForo_Model::create('XenForo_Model_BbCode')->getBbCodeMediaSitesByAddOnId($addonId)),
		);
		
		foreach ($stats AS $k => &$val)
		{
			if ( ! $val[1])
			{
				unset ($stats[$k]);
			}
			
			$val[1] = count($val[1]);
		}
		
		$this->printTable($stats, '', false);
	}
	
}
