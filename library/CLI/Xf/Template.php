<?php

/**
 * XenForo CLI - Template command (ie. xf template)
 */
class CLI_Xf_Template extends CLI {
	
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