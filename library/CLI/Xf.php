<?php

/**
 * XenForo Command Line Interface class
 */
class CLI_Xf extends CLI
{
	
	/**
	 * Default run method
	 * 
	 * @return	void							
	 */
	public function run()
	{
		$class = 'CLI_' . ucfirst(strtolower($this->getArgumentAt(0)));
		
		// If a sub command is called, attempt to forward the call
		// this is to allow addon developers to create CLI command handlers
		// inside their XF install
		if ($class != __CLASS__ AND class_exists($class))
		{
			$arguments = $this->getArguments();
			array_shift($arguments);
			
			$callStructure 		= $this->_callStructure;
			$callStructure[] 	= $this;
			
			new $class($class, $arguments, $this->getFlags(), $this->getOptions(), $callStructure);
		}
		else
		{
			$this->showHelp();
		}
		
	}
	
}