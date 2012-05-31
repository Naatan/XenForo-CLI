<?php

class CLI_Xf_Addon_Install extends CLI
{
	protected $_help = '
		usage: addon install <xml file / folder> [--paths=PATHS]
			
			--paths 
				list of paths this addon is associated with, will be added to the config to help with updating and removing
	';

	public function run($path)
	{
		$addonModel = XenForo_Model::create('XenForo_Model_AddOn');
		try 
		{
			$this->printInfo('Installing addon...');

			if (!file_exists($path) || !is_readable($path))
			{
				throw new XenForo_Exception(new XenForo_Phrase('please_enter_valid_file_name_requested_file_not_read'), true);
			}

			try
			{
				$document = new SimpleXMLElement($path, 0, true);
			}
			catch (Exception $e)
			{
				throw new XenForo_Exception(
					new XenForo_Phrase('provided_file_was_not_valid_xml_file'), true
				);
			}

			try 
			{
				$caches = $addonModel->installAddOnXml($document);
			}
			catch (Exception $e)
			{
				if ($this->hasFlag('upgrade-if-exists'))
				{
					$this->printInfo('Addon exists, updating...');
					$updated = true;
					$caches = $addonModel->installAddOnXml($document, (string)$document['addon_id']);
				}
				else
				{
					throw $e;
				}
			}

			$libraryPath = $this->_detectAddonLibrary((string)$document['addon_id'], (string)$document['title']);
			if ($libraryPath === false)
			{
				$this->printInfo('Note: could not detect addon paths. It will need to be manually configured to work with the CLI');
			}
			else
			{
				$addon = (object) array(
					'id' => (string)$document['addon_id'],
					'name' => (string)$document['title'],
					'namespace' => substr($libraryPath, 8, strlen($libraryPath) - strpos($libraryPath, '/', 8) - 8),
					'path' => $libraryPath,
				);
				$config = array(
					'addon' => $addon, 
					'paths' => $this->getOption('paths'), 
				);
				$extraConfig = $this->getOption('extra-config');
				if ($extraConfig AND is_array($extraConfig))
				{
					$config = array_merge($config, $extraConfig);
				}

				XfCli_Application::writeConfig($config, XfCli_Application::xfBaseDir() . $addon->path . DIRECTORY_SEPARATOR . '.xfcli-config');
				$this->getParent()->selectAddon($addon->namespace);
			}

			$this->manualRun('rebuild ' . implode(' ', $caches), false, false, false);

			$this->printInfo('Addon ' . (isset($updated) ? 'updated' : 'installed'));
		} 
		catch (Exception $e)
		{
			if (isset($caches))
			{
				$dw = XenForo_DataWriter::create('XenForo_DataWriter_AddOn');
				$dw->setExistingData((string)$document['addon_id']);
				$dw->delete();
				$addonModel->rebuildAddOnCaches();
			}

			$this->bail($e->getMessage());
		}
	}

	protected function _detectAddonLibrary($addonId, $addonName)
	{
		// We are simply going to get a list of the folders in library. Look for addonId and addonName exact matches
		// then try with in all directories for exact matches
		// then try the root with close matches.
		$base = XfCli_Application::xfBaseDir();
		$possibleNames = array(
			strtolower($addonId), 
			strtolower(str_replace(' ', '_', $addonName)),
			strtolower(str_replace(' ', '', $addonName))
		);
		// Plurals
		if (strlen($addonName) - 1 == strrpos($addonName, 's'))
		{
			$possibleNames[] = substr(strtolower($addonName), 0, strlen($addonName) - 1);
			$possibleNames[] = substr(strtolower(str_replace(' ', '', $addonName)), 0, strlen(str_replace(' ', '', $addonName)) - 1);
		}

		$libraryDirectory = new DirectoryIterator($base . 'library');
		$skipFolders = array(
			'XenForo',
			'Lgpl',
			'Minify',
			'Sabre',
			'XFCliImporter',
			'Zend'
		);
		$libraryFolders = array();
		foreach ($libraryDirectory AS $obj)
		{
			if ( ! $obj->isDot() AND $obj->isDir() AND ! in_array($obj->getFilename(), $skipFolders))
			{
				$libraryFolders[strtolower($obj->getFilename())] = clone $obj;
			}
		}

		// Exact matches (remember everything has been changed to case insensitive)
		foreach ($possibleNames AS $name)
		{
			if (isset($libraryFolders[$name]))
			{
				return 'library/' . $libraryFolders[$name]->getFileName();
			}
		}

		$nextLevelFolders = array();
		foreach ($libraryFolders AS $folder)
		{
			$dir = new DirectoryIterator($folder->getPathname());
			foreach ($dir AS $obj)
			{
				if ( ! $obj->isDot() AND $obj->isDir())
				{
					$nextLevelFolders[strtolower($obj->getFilename())] = clone $obj;
				}
			}
		}

		// Exact matches in the next lot of folders
		foreach ($possibleNames AS $name)
		{
			if (isset($nextLevelFolders[$name]))
			{
				return 'library/' . dirname($nextLevelFolders[$name]->getPathname) . '/' . $nextLevelFolders[$name]->getFileName;
			}
		}

		// Closest match, only check this one with the library folder. Don't want something like {AddonId}Handler giving a false positive
		$closest = false;
		$match = false;
		foreach ($possibleNames AS $name)
		{
			foreach ($libraryFolders AS $folder => $obj)
			{echo $folder . ' =? ' . $name . "\n";
				if (strpos($folder, $name) !== false)
				{
					$howClose = strlen($folder) - strlen($name);
					if ($closest === false OR $howClose < $closest)
					{
						$closest = $howClose;
						$match = $name;
					}
				}
			}
		} 
		if ($match)
		{
			return 'library/' . $match;
		}

		return false;
	}
}