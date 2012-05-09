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
		$structure 		= $this->_callStructure;
		$structure[] 	= $this;
		
		new CLI_Xf_Route_Add('CLI_Xf_Route_Add', $this->getArguments(), $this->getFlags(), $this->getOptions(), $structure);
	}
	
}