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
			add
			delete
	';
	
	public function run()
	{
		$structure 		= $this->_callStructure;
		$structure[] 	= $this;
		
		new CLI_Xf_Listener_Add('CLI_Xf_Listener_Add', $this->getArguments(), $this->getFlags(), $this->getOptions(), $structure);
	}
	
}