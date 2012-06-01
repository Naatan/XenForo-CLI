<?php

/**
 * XenForo CLI - Extend command (ie. xf extend)
 */
class CLI_Xf_Extend extends CLI
{
	
	protected $_help = '
		Possible commands:
		
		(you can excute these commands with --help to view their instructions)
		
		xf extend ..
			- add
			- delete
	';
	
	/**
	 * Default run method
	 * 
	 * @return	void							
	 */
	public function run()
	{
		$this->manualRun('extend add');
	}
	
}