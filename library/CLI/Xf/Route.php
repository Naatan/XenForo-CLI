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
			add
			delete
	';
	
	public function run()
	{
		$this->showHelp();
	}
	
}