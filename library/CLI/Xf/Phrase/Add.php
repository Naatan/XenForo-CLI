<?php

/**
 * XenForo CLI - Add Phrase command (ie. xf phrase add)
 */
class CLI_Xf_Phrase_Add extends CLI
{
	
	/**
	 * @var string	Help text
	 */
	protected $_help = '
		Example: xf phrase add <name> <value> [--global] 
	';
	
	/**
	 * Default run method
	 *
	 * @param	string			$name
	 * @param 	string 			$value
	 * 
	 * @return	void							
	 */
	public function run($name, $value)
	{
		$addon = XfCli_Application::getConfig()->addon;
		
		if ( ! $addon->id)
		{
			$this->showHelp();
			$this->bail('No addon selected');
		}
		
		$this->addToDb($addon, $name, $value);
		
		echo 'Phrase Added';
	}
	
	/**
	 * Add phrase to database
	 * 
	 * @param	object			$addon
	 * @param	string			$name
	 * @param 	string 			$value
	 * 
	 * @return	void							
	 */
	protected function addToDb($addon, $name, $value)
	{
		$this->printInfo("Adding phrase to database.. ", false);
		
		// Validate if listener already exists
		$phraseModel 	= XenForo_Model::create('XenForo_Model_Phrase');
		$phrase 		= $phraseModel->getPhraseInLanguageByTitle($name);
		
		if ($phrase)
		{
			$this->printInfo("skipped (already exists)");
			return;
		}
		
		// Prepare data for insert
		$dwInput = array(
			'language_id' 	=> 0,
			'title'			=> $name,
			'phrase_text'	=> $value,
			'global_cache' 	=> $this->hasFlag('global'),
			'addon_id'		=> $addon->id
		);
		
		// Perform the actual insert
		try
		{
			$dw = XenForo_DataWriter::create('XenForo_DataWriter_Phrase');
			$dw->bulkSet($dwInput);
			$dw->save();
			
			$this->printInfo("ok");
		}
		catch (Exception $e)
		{
			$this->bail($e->getMessage());
		}
	}
	
}