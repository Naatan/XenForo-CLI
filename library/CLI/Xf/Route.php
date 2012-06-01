<?php

/**
 * XenForo CLI - Route command (ie. xf route)
 */
class CLI_Xf_Route extends CLI {
	
	/**
	 * @var string	Help text
	 */
	protected $_help = '
		Possible commands:
		
		(you can excute these commands with --help to view their instructions)
		
		xf route ..
			- add
			- delete
	';
	
	public function run()
	{
		$this->showHelp();
	}
	
}