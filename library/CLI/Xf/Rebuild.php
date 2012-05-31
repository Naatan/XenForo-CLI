<?php

class CLI_Xf_Rebuild extends CLI
{
	protected $_help = '
		usage: rebuild [cache1, cache2, cache3] [--caches=all|master|addon]
		
		Passing no arguments will rebuild all caches
		
		--caches
		
			Using this option allows you to rebuild a predefined set of caches
			
			all: 	rebuilds all caches
			master:	rebuilds most important caches
			addon: 	rebuilds caches that hold addon related data
	';

	public function run()
	{
		$caches = func_get_args();
		$caches = explode(',', $caches);
		array_walk($caches, create_function('&$val', '$val = trim($val);')); 
		
		if ( ! empty($caches) AND $this->hasOption('caches'))
		{
			switch ($this->getOption('caches'))
			{
				default:
				case 'all':
					$caches = array();
					foreach (XenForo_CacheRebuilder_Abstract::$builders as $cache => $meh)
					{
						if (strpos($cache, 'Import') === 0)
						{
							// We don't want import caches to be run unless it is a master rebuild
							continue;
						}
		
						$caches[] = $cache;
					}
					break;
				
				case 'master':
					$caches = array(
						'ImportMasterData', 'Permission',
						'ImportPhrase', 'Phrase',
						'ImportTemplate', 'Template',
						'ImportAdminTemplate', 'AdminTemplate',
						'ImportEmailTemplate', 'EmailTemplate'
					);
					break;
				
				case 'addon':
					$caches = array(
						'Permission', 
						'Phrase', 
						'Template', 
						'AdminTemplate', 
						'EmailTemplate'
					);
					break;
			}
		}
		
		if (empty($caches))
		{
			$this->bail('No caches were specified to rebuild');
		}

		$this->printInfo('Rebuilding caches...');

		$validCaches = array();
		foreach (XenForo_CacheRebuilder_Abstract::$builders AS $cache => $meh)
		{
			$validCaches[strtolower($cache)] = $cache;
		}

		foreach ($caches AS $cache)
		{
			$cache = strtolower($cache);

			// Special case and todo: search index is a bit different
			if ($cache == 'searchindex')
			{
				continue;
			}

			if (isset($validCaches[$cache]))
			{
				$this->printInfo("\t" . (string) XenForo_CacheRebuilder_Abstract::getCacheRebuilder($validCaches[$cache])->getRebuildMessage(), false);
				$this->_rebuild($validCaches[$cache], 0, array(), '');
				$this->printInfo('');
			}
		}
	}

	protected function _rebuild($cache, $position, $options, $message)
	{
		$rebuilt = XenForo_CacheRebuilder_Abstract::getCacheRebuilder($cache)->rebuild($position, $options, $message);

		// Special case
		/*if ($cache == 'DailyStats')
		{
			$message = "\n\t\t" . $message;
			$this->printInfo($message, false);
		}
		else*/ if ( ! empty($message))
		{
			$this->printInfo('.', false);
		}

		if (is_int($rebuilt))
		{
			$this->_rebuild($cache, $rebuilt, $options, $message);
		}
	}
}