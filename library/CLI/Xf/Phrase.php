<?php

/**
 * XenForo CLI - Phrase command (ie. xf phrase)
 */
class CLI_Xf_Phrase extends CLI {
	
	/**
	 * @var string	Help text
	 */
	protected $_help = '
		Possible commands:
			add
			find
			get
	';
	
	public function run()
	{
		$this->runGet(false);
	}
	
	public function runGet($unshift=true)
	{
		$flags = $this->getFlags();
		
		if ( ! in_array('exact', $flags))
		{
			$flags[] = 'exact';
		}
		
		$this->manualRun('phrase find', true, $flags);
	}
	
}