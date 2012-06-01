<?php

/**
 * List installed addons
 */
class CLI_Xf_Addon_Show extends CLI
{
	
	// Database entries that are related to addons
	protected $_dataEntries = array(
		'AdminNavigation' => array(
			'title' 	=> 'Admin Navigation',
			'method'	=> 'getAdminNavigationEntriesInAddOn'
		),
		'AdminPermission' => array(
			'title' 	=> 'Admin Permissions',
			'model'		=> 'XenForo_Model_Admin',
			'method'	=> 'getAdminPermissionsForAddOn'
		),
		'AdminTemplate' => array(
			'title' 	=> 'Admin Templates',
			'method'	=> 'getAdminTemplatesByAddOn'
		),
		'CodeEvent' => array(
			'title' 	=> 'Code Events',
			'method'	=> 'getEventsByAddOn'
		),
		'CodeEventListener' => array(
			'title' 	=> 'Code Event Listeners',
			'model'		=> 'XenForo_Model_CodeEvent',
			'method'	=> 'getEventListenersByAddOn'
		),
		'Cron' => array(
			'title' 	=> 'Cron Jobs',
			'method'	=> 'getCronEntriesByAddOnId'
		),
		'EmailTemplate' => array(
			'title' 	=> 'E-Mail Templates',
			'method'	=> 'getMasterEmailTemplatesByAddOn'
		),
		'Option' => array(
			'title' 	=> 'Options',
			'method'	=> 'getOptionsByAddOn'
		),
		'OptionGroup' => array(
			'title' 	=> 'Option Groups',
			'model'		=> 'XenForo_Model_Option',
			'method'	=> 'getOptionGroupsByAddOn'
		),
		'Permission' => array(
			'title' 	=> 'Permissions',
			'method'	=> 'getPermissionsByAddOn'
		),
		'PermissionGroup' => array(
			'title' 	=> 'Permission Groups',
			'model'		=> 'XenForo_Model_Permission',
			'method'	=> 'getPermissionGroupsByAddOn'
		),
		'Phrase' => array(
			'title' 	=> 'Phrases',
			'method'	=> 'getMasterPhrasesInAddOn'
		),
		'PublicRoute' => array(
			'title' 	=> 'Public Routes',
			'callback'	=> 'callbackPublicRoute'
		),
		'AdminRoute' => array(
			'title' 	=> 'Admin Routes',
			'callback'	=> 'callbackAdminRoute'
		),
		'StyleProperty' => array(
			'title' 	=> 'Style Properties',
			'callback'	=> 'callbackStyleProperty',
		),
		'Template'		=> array(
			'title'		=> 'Templates',
			'method'	=> 'getMasterTemplatesInAddOn'
		),
		'BbCode'		=> array(
			'title'		=> 'BBCode\'s',
			'method'	=> 'getBbCodeMediaSitesByAddOnId'
		)
	);
	
	/**
	 * Run the command
	 *
	 * @return	void							
	 */
	public function run($addonId = null)
	{
		// Use currently selected addon if no addon was provided
		if ($addonId == null)
		{
			$addonId = XfCli_Application::getConfig()->addon->id;
		}
		
		// Detect addon
		if ( ! $addon = $this->getParent()->getAddonByInput($addonId))
		{
			$this->bail('Could not find addon: ' . $addonId);
		}
		
		// Prepare info to be printed
		$haddon = array(
			array($this->colorText('Title:', self::BOLD), 	$addon['title']),
			array($this->colorText('ID:', self::BOLD),		$addon['addon_id']),
			array($this->colorText('Version:', self::BOLD), $addon['version_string']),
			array($this->colorText('Status:', self::BOLD),	$addon['active'] ? 'Enabled' : $this->colorText('Disabled', self::RED))
		);
		
		// Append config file to info, if it's defined
		if (isset($addon['config_file']))
		{
			$haddon[] = array($this->colorText('Config File:', self::BOLD), $addon['config_file']);
		}
		
		// Print info table
		$this->printTable($haddon, '', false);
		$this->printEmptyLine();
		
		// Print database entry stats
		$stats = array();
		foreach ($this->_dataEntries AS $entry => $prop)
		{
			$entry = $this->getDataEntry($entry, $addonId);
			
			if ($entry['stat'])
			{
				$stats[] = array($entry['title'], count($entry['stat']));
			}
		}
		
		$this->printTable($stats, '', false);
		
		// Check if we want to print database entry details
		if ($this->hasFlag('details'))
		{
			$details = array_keys($this->_dataEntries);
		}
		else if ($this->hasOption('details'))
		{
			$details = $this->getOption('details');
			$details = explode(',', $details);
			array_walk($details, create_function('&$val', '$val = trim($val);')); 
		}
		
		// Print db entry details
		if (isset($details))
		{
			foreach ($details AS $detail)
			{
				if ( ! $entry = $this->getDataEntry($detail, $addonId) OR ! $entry['data'])
				{
					continue;
				}
				
				$this->printEmptyLine(2);
				
				$string = 'Details for: ' . $entry['title'];
				$this->printInfo($this->colorText($string, self::BOLD));
				
				$print = '';
				for ($c=0;$c<strlen($string); $c++) {
					$print .= '=';
				}
				
				$this->printInfo($print);
				
				$this->printTable($entry['data']);
			}
		}
	}
	
	/**
	 * Get DB entry
	 * 
	 * @param		string		$name			
	 * @param		string		$addonId
	 * 
	 * @return		bool|array						
	 */
	protected function getDataEntry($name, $addonId)
	{
		if ( ! isset($this->_dataEntries[$name]))
		{
			return false;
		}
		
		$entry = $this->_dataEntries[$name];
		
		if (isset($entry['callback']))
		{
			$data = call_user_func(array($this, $entry['callback']), $addonId);
			$stat = call_user_func(array($this, $entry['callback']), $addonId, true);
		}
		else
		{
			$model 	= isset($entry['model']) ? $entry['model'] : 'XenForo_Model_' . $name;
			$model 	= call_user_func(array('XenForo_Model','create'), $model);
			$data 	= call_user_func(array($model, $entry['method']), $addonId);
			$stat 	= count($data);
		}
		
		return array(
			'title' => $entry['title'],
			'data'	=> $data,
			'stat'	=> $stat
		);
	}
	
	/**
	 * Callback method for StyleProperty DB entry
	 * 
	 * @param		string		$addonId		
	 * @param		bool		$stat
	 * 
	 * @return		bool|array|int						
	 */
	protected function callbackStyleProperty($addonId, $stat = false)
	{
		$entries = XenForo_Application::getDb()->fetchAll('SELECT * FROM xf_style_property_group WHERE addon_id = ?', $addonId);
		
		if ( ! $entries)
		{
			return false;
		}
		
		if ($stat)
		{
			return count($entries);
		}
		else
		{
			return $entries;
		}
	}

	/**
	 * Callback wrapper for callbackPublicRoute and callbackAdminRoute
	 * 
	 * @param		string		$addonId		
	 * @param		bool		$stat			
	 * @param		string		$type
	 * 
	 * @return		bool|array|int
	 */
	protected function callbackRoutePrefix($addonId, $stat = false, $type = 'public')
	{
		$model 		= XenForo_Model::create('XenForo_Model_RoutePrefix');
		$entries 	= $model->getPrefixesByAddOnGroupedByRouteType($addonId);
		
		if ( ! $entries OR ! isset($entries[$type]))
		{
			return false;
		}
		
		if ($stat)
		{
			return count($entries[$type]);
		}
		else
		{
			return $entries[$type];
		}
	}
	
	/**
	 * Callback method for PublicRoute DB entry
	 * 
	 * @param		string		$addonId		
	 * @param		bool		$stat
	 * 
	 * @return		$this->callbackRoutePrefix
	 */
	protected function callbackPublicRoute($addonId, $stat = false)
	{
		return $this->callbackRoutePrefix($addonId, $stat, 'public');
	}
	
	/**
	 * Callback method for AdminRoute DB entry
	 * 
	 * @param		string		$addonId		
	 * @param		bool		$stat
	 * 
	 * @return		$this->callbackRoutePrefix
	 */
	protected function callbackAdminRoute($addonId, $stat = false)
	{
		return $this->callbackRoutePrefix($addonId, $stat, 'admin');
	}
	
}
