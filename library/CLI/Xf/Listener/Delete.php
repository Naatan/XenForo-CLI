<?php

class CLI_Xf_Listener_Delete extends CLI
{
	
	public function run()
	{
		
		// Requires at least 1 argument (ie. event to delete)
		$this->assertNumArguments(1);
		
	}
	
}