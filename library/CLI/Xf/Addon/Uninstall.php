<?php

/**
 * Delete an addon
 */
class CLI_Xf_Addon_Uninstall extends CLI
{
	protected $_help = '
		usage: addon uninstall <addon_name|addon_id|path> [--delete-files] [-y]
		
		--delete-files
			Delete the related addon files as well (currently only works if executed with addon path)
			
		-y
			Confirm deletion (don\'t prompt) - USE WITH CARE!
	';
	
	/**
	 * Run the command
	 *
	 * @param	string			$addonId
	 * 
	 * @return	void							
	 */
	public function run($addonId)
	{
		// Detect addon
		if (
			! $addon = $this->getParent()->getAddonById($addonId) AND
			! $addon = $this->getParent()->getAddonByName($addonId) AND
			! $addon = $this->getParent()->getAddonByPath($addonId)
		)
		{
			$this->bail('Could not find addon: ' . $addonId);
		}
		
		// Define full file path
		$base = XfCli_Application::xfBaseDir();
		$file = isset($addon['config_file']) ? dirname($addon['config_file']) : '';
		
		// Validate file path
		if ($this->hasFlag('delete-files') AND (empty($file) OR ! is_dir($base . $file)))
		{
			$this->bail('Could not locate files');
		}
		
		// Confirmation
		if ( ! $this->hasFlag('y'))
		{
			$this->printMessage('You are about to delete..');
			
			// Print summary of data that is to be deleted
			if ($this->hasFlag('delete-files'))
			{
				$files 		= trim(shell_exec('find ' . $base . $file . ' -type f | wc -l'));
				$folders 	= trim(shell_exec('find ' . $base . $file . ' -type d | wc -l'));
				
				$this->printTable(array(
					array('Addon:', 		$addon['title'], 	'id: '.$addon['addon_id']),
					array('Directory:',		$file, 				"$files files, $folders folders")
				), '   ', false);
				$this->printEmptyLine();
			}
			else
			{
				$this->printEmptyLine();
				$this->printMessage('  Addon: ' . $addon['title'] . ' ('.$addon['addon_id'].')');
			}
			
			// Ask for confirmation
			$this->printEmptyLine();
			$really = $this->confirm('Are you sure you want to do this?');
			$this->printEmptyLine();
			
			// Fail if not confirmed
			if ( ! $really)
			{
				return $this->printMessage('Addon delete canceled');
			}
		}
		
		// Delete DB data
		$this->printMessage('Deleting DB data.. ', false);
		$addonModel = XenForo_Model::create('XenForo_Model_AddOn');
		$addonModel->deleteAddOnMasterData($addonId);
		$this->printMessage('ok');
		
		// Delete files
		if ($this->hasFlag('delete-files'))
		{
			$this->printMessage('Deleting files.. ', false);
			
			if ($deleted = shell_exec('rm -Rv ' . $base . $file))
			{
				$this->printMessage('ok');
			}
			else
			{
				$this->bail('failed: ' . $deleted);
			}
		}
		
		// Done
		$this->printEmptyLine();
		$this->printMessage('Addon delete');
		
	}
	
}
