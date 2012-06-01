<?php

/**
 * XenForo CLI - Listener command (ie. xf listener)
 */
class CLI_Xf_Listener extends CLI {
	
	/**
	 * @var string	Help text
	 */
	protected $_help = '
		Possible commands:
		
		(you can excute these commands with --help to view their instructions)
		
		xf listener ..
			- add
			- delete
	';
	
	public function run()
	{
		$this->showHelp();
	}
	
}