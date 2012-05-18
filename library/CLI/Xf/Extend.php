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
		$this->manualRun('extend add');
	}
	
}