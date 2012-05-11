<?php

/**
 * XenForo CLI - Find Phrases command (ie. xf phrase find)
 */
class CLI_Xf_Phrase_Find extends CLI
{
	
	/**
	 * @var string	Help text
	 */
	protected $_help = '
		Example: xf phrase find <name> [--exact]
			--exact - must be an exact match
	';
	
	/**
	 * Default run method
	 *
	 * @param	string			$name
	 * 
	 * @return	void							
	 */
	public function run($name)
	{
		$addon = XfCli_Application::getConfig()->addon;
		
		if ( ! $addon->id)
		{
			$this->showHelp();
			$this->bail('No addon selected');
		}
		
		$phraseModel = XenForo_Model::create('XenForo_Model_Phrase');
		
		if ($this->hasFlag('exact'))
		{
			$phrases = $phraseModel->getPhraseInLanguageByTitle($name);
			
			if ($phrases)
			{
				$phrases = array($phrases['title'] => $phrases['phrase_text']);
			}
		}
		else
		{
			$phrases = $phraseModel->getPhrasesMatchingSearchTextWithConstrainedTitles('', $name, 20, 0);
		}
		
		if ($phrases)
		{
			$this->printMessage('Results found: ' . PHP_EOL);
			
			foreach ($phrases AS $phrase => $value)
			{
				if (strlen($value) > 50)
				{
					$value = substr($value, 0, 50) . '..';
				}
				
				$value = str_replace("\n" , '\n', $value);
				
				$this->printMessage(' - ' . $this->colorText($phrase, CLI::BOLD) . ': ' . $value);
			}
			
			if (count($phrases) == 20)
			{
				$this->printMessage(PHP_EOL . 'There may be more results..');
			}
		}
		else
		{
			$this->printMessage('No phrases found ');
		}
		
	}
	
}