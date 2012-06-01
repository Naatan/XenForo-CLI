<?php

/**
 * XenForo CLI - Phrase command (ie. xf phrase)
 */
class CLI_Xf_Phrase_Get extends CLI {
	
	public function run($unshift=true)
	{
		$flags = $this->getFlags();
		
		if ( ! in_array('exact', $flags))
		{
			$flags[] = 'exact';
		}
		
		$this->manualRun('phrase find', true, $flags);
	}
	
}