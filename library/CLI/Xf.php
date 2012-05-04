<?php

class CLI_Xf extends CLI
{
	
	public function run()
	{
		$class = 'CLI_' . ucfirst(strtolower($this->getArgumentAt(0)));
		
		if ($class != __CLASS__ AND class_exists($class))
		{
			$arguments = $this->getArguments();
			array_shift($arguments);
			
			$callStructure 		= $this->_callStructure;
			$callStructure[] 	= $this;
			
			new $class($class, $arguments, $this->flags, $callStructure);
		}
		else
		{
			$this->showHelp();
		}
		
	}
	
}