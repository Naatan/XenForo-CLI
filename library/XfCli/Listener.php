<?php

class XfCli_Listener extends CLI {
	
	protected $_help = '
		Possible commands:
			add
			delete
	';
	
	public function run()
	{
		$this->showHelp(true);
	}
	
}