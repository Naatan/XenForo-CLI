<?php

class CLI_Xf_Addon_Create extends CLI
{
	protected $_help = '
		usage: addon create <addon_name> [--addon-id=ID] [--type=bare|normal|full] [--include-examples] [--path=PATH]

		--addon-id
			The addon ID by default is the same as the <addon_name> except with the first letter lower case. You can overwrite it with this option.

		--type
			There are 3 types of directories we can make:
				bare: just the add-on folder in library,
				normal (default): add-on folder with most commonly used folders (ControllerPublic, ControllerAdmin, Model, DataWriter, ViewPublic, ViewAdmin, Route, Route/Prefix, Route/PrefixAdmin)
				full: this adds all the folders you could ever need. Handlers, Helpers, BbCode etc..
			Note: normal and full will 

		--path
			By default the add-on will be made in the library folder. You can overwrite this with --path

		--include-examples
			TODO!
	';

	public function run()
	{
		$this->assertNumArguments(1);
	}
}