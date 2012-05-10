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
		$structure 		= $this->_callStructure;
		$structure[] 	= $this;
		
		$flags = $this->getFlags();
		
		if ( ! in_array('exact', $flags))
		{
			$flags[] = 'exact';
		}
		
		$args = $this->getArguments();
		
		if ($unshift)
		{
			array_shift($args);
		}
		
		new CLI_Xf_Phrase_Find('CLI_Xf_Phrase_Find', $args, $flags, $this->getOptions(), $structure);
	}
	
}