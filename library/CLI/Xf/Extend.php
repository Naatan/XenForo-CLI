<?php

/**
 * XenForo CLI - Extend command (ie. xf extend)
 */
class CLI_Xf_Extend extends CLI
{
	
	/**
	 * Default run method
	 * 
	 * @return	void							
	 */
	public function run()
	{
		$structure 		= $this->_callStructure;
		$structure[] 	= $this;
		
		new CLI_Xf_Extend_Add('CLI_Xf_Extend_Add', $this->getArguments(), $this->getFlags(), $this->getOptions(), $structure);
	}
	
}