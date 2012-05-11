<?php

/**
 * Select an addon that is to be used for all addon-related commands
 */
class CLI_Xf_Addon_Select extends CLI
{
	
	/**
	 * @var string	Help text
	 */
	protected $_help = '
		usage: addon select <addon_id> --auto-create
		
		--auto-create
			Create the addon if it doesn\'t exist yet, forwards the command to "addon add", you can
			use flags and options related to that command
	';
	
	/**
	 * Run the command
	 * 
	 * @param	string			$addonId
	 * 
	 * @return	void							
	 */
	public function run($addonId)
	{
		$this->getParent()->selectAddon($addonId);
		$this->printMessage("Addon selected");
	}

}