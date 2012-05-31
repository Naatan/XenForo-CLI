<?php
/**
 * Import an addon from a folder path or repo
 */
class CLI_Xf_Addon_Import extends CLI
{
	protected $_help = '
		Use this to import an add-on quickly from a repo or folder. It has to have the right structure, upload folder and addon-*.xml file. It will be either copied over or symlinked depending on the --no-symlinks option.
		
		usage: 	addon import <path / repo url (git, hg)>  [--path=path]  [--addon=path] [--no-symlinks]
		
			--path
				The folder for the repo if importing for one. Defaults to /repos/reponame
				
			--addon
				For upgrading an addon, mainly used by "addon update" when an addon is selected, currently only supports the addon\'s config path as a value
		
			--no-symlinks
				This will do a hard copy instead of symlinking the repo
	';

	/**
	 * Run the command
	 * 
	 * @param	string 	$addonId
	 * 
	 * @return	void							
	 */
	public function run($path)
	{
		$extraConfig = array();

		// TODO: make this test looks for protocols instead of just ://
		if (strpos($path, '://') !== false)
		{
			$extraConfig['importUrl'] = $path;

			// The following might not be correct for all test cases, but we will give it a go
			$this->printInfo('Checking for git repository...');
			if (strpos(trim(exec('git ls-remote ' . $path . ' 2>&1')), 'fatal:') !== 0)
			{
				$path = $this->_cloneGit($path, $this->getOption('addon-config'));
			}
			else
			{
				$this->printInfo('Checking for hg repository...');
				if (strpos(trim(exec('hg identify ' . $path . ' 2>&1')), 'abort:') !== 0)
				{
					$path = $this->_cloneHg($path, $this->getOption('addon-config'));
				}
				else
				{
					$this->bail('No valid folder path, git repo URL or hg repo URL was provided: ' . $path);
				}
			}
		}

		$this->_import($path, $extraConfig);
	}

	protected function _import($path, $extraConfig = array())
	{
		$this->_assertCanImport($path);

		$this->printInfo('Importing addon source from ' . $path . '...');

		list ($xml) = glob($path . DIRECTORY_SEPARATOR . '*.xml');
		$extraConfig['importPath'] = $path;
		if (is_dir($path . DIRECTORY_SEPARATOR . 'upload'))
		{
			$path = $path . DIRECTORY_SEPARATOR . 'upload';
		}

		$addonConfig = $this->getOption('addon-config');
		if ($addonConfig)
		{
			$addonConfig = XfCli_Application::loadConfigJson($addonConfig);
		}

		$logPaths = array();
		if ($this->_importFolder($path, null, $logPaths, $addonConfig))
		{
			try 
			{
				$flags = array();
				if ($addonConfig)
				{
					$flags[] = 'upgrade-if-exists';
				}
				$this->manualRun('addon install ' . $xml, false, $flags, array('paths' => $logPaths, 'extra-config' => $extraConfig));
			}
			catch (Exception $e)
			{
				$this->printInfo($this->colorText('Error: ', self::RED) . $e->getMessage());
				$this->_rollback($path, $logPaths);
			}
		}
	}

	protected function _importFolder($path, $rootPath = null, array &$logPaths = array(), $config = false)
	{
		if ($rootPath === null)
		{
			$rootPath = $path;
		}

		$dir = new DirectoryIterator($path);
		$base = XfCli_Application::xfBaseDir();
		foreach ($dir as $obj)
		{
			if ($obj->isDot() OR 
				$obj->getFilename() == '.git' OR 
				$obj->getFilename() == '.hg' OR
				strpos(strtolower($obj->getFilename()), 'readme') !== false OR
				strpos(strtolower($obj->getFilename()), 'license') !== false
			)
			{
				continue;
			}

			$xfEquivalent = str_replace($rootPath . DIRECTORY_SEPARATOR, $base, $obj->getPathname());

			if ($obj->isDir() AND is_dir($xfEquivalent))
			{
				if ($config)
				{
					$paths = (array) $config->paths;
					if (isset($paths[$obj->getPathname()]))
					{
						continue;
					}
				}

				if ( ! $this->_importFolder($obj->getPathname(), $rootPath, $logPaths, $config))
				{
					return false;
				}
				continue;
			}
			else if (is_file($xfEquivalent))
			{
				if ($config)
				{
					$paths = (array) $config->paths;
					if (isset($paths[$obj->getPathname()]))
					{
						continue;
					}
				}

				$this->printInfo($this->colorText('Error: ', self::RED) . 'File already exists in your XenForo install: ' . $xfEquivalent);
				$this->_rollback($rootPath, $logPaths);
				return false;
			}

			if ( ! $this->hasFlag('no-symlinks'))
			{
				shell_exec('ln -s ' . $obj->getPathname() . ' ' . $xfEquivalent);
			}

			// TODO: hard copy

			$logPaths[$obj->getPathname()] = $xfEquivalent;
		}

		// log everything for later updates
		 
		return true;
	}

	protected function _rollback($repoPath, $paths)
	{
		$this->printInfo('Rolling back changes...');

		// TODO: use PHP and not CMD to remove?
		foreach ($paths AS $path)
		{
			if (is_dir($path[1]))
			{
				shell_exec('rm -Rf ' . $path[1]);
			}
			else if (is_file($path[1]))
			{
				shell_exec('rm -f ' . $path[1]);
			}
		}

		if ( ! $this->getOption('addon-config'))
		{
			shell_exec('rm -Rf ' . $repoPath);
		}
	}

	protected function _assertCanImport($path)
	{
		// Need one xml file only
		if (count(glob($path . DIRECTORY_SEPARATOR . '*.xml')) !== 1)
		{
			// TODO: add option to sepecify xml install file
			$this->bail('Didn\'t detect a single XML file.. addon not compatible with this command');
		}

		// If we want to be more strict and force an upload folder then do it here
	}

	protected function _cloneGit($url, $pull = false)
	{
		$path = $this->_getRepoPath($url);

		if ($pull)
		{
			$this->printInfo('Updating git repository at ' . $path . '...');
			shell_exec('cd ' . $path . ' && git pull');
			return $path;
		}

		$this->printInfo('Cloning git repository ' . $url . ' into ' . $path . '...');

		// TODO: error checking
		shell_exec('git clone ' . $url . ' ' . $path);

		if ( ! is_dir($path . DIRECTORY_SEPARATOR . '.git'))
		{
			$this->bail('Failed to clone git repository: ' . $url);
		}

		return $path;
	}

	protected function _cloneHg($url, $pull = false)
	{
		$path = $this->_getRepoPath($url);

		if ($pull)
		{
			$this->printInfo('Updating hg repository at ' . $path . '...');
			shell_exec('cd ' . $path . ' && hg pull');
			return $path;
		}

		$this->printInfo('Cloning hg repository ' . $url . ' into ' . $path . '...');

		// TODO: error checking
		shell_exec('hg clone ' . $url . ' ' . $path);

		if ( ! is_dir($path . DIRECTORY_SEPARATOR . '.hg'))
		{
			$this->bail('Failed to clone hg repository: ' . $url);
		}

		return $path;
	}

	protected function _getRepoPath($url)
	{
		$path = $this->getOption('path');
		if ( ! $path)
		{
			$folder = ltrim(str_replace('.git', '', strrchr(rtrim($url, '/'), '/')), '/');
			$path = XfCli_Application::xfBaseDir() . 'repos' . DIRECTORY_SEPARATOR . $folder;
			/*if (is_dir($path))
			{
				$path = $path . '-' . count(glob($path . '*'));
			}*/
			
			if ( ! is_dir(dirname($path)))
			{
				if ( ! mkdir(dirname($path), 0755, true))
				{
					$this->bail('Could not create repos directory: ' . dirname($path));
				}
			}

			if ( ! is_dir($path))
			{
				if ( ! mkdir($path, 0755, true))
				{
					$this->bail('Could not create repo directory: ' . $path);
				}
			}
		}

		return $path;
	}
}