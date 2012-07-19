<?php

require_once dirname(__FILE__) . '/Bootstrap.php';

class Commands extends PHPUnit_Framework_TestCase
{
	
	/**
	 * @runInSeparateProcess enabled
	 */
	public function test__AddonAdd()
	{
		self::runCommand(array('addon', 'add', self::getAddon(true)));
	}
	
	/**
	 * @runInSeparateProcess enabled
	 */
	public function test__AddonList()
	{
		self::runCommand(array('addon', 'list'));
	}
	
	/**
	 * @runInSeparateProcess enabled
	 */
	public function test__AddonSelect()
	{
		self::runCommand(array('addon', 'select', self::getAddon()));
	}
	
	/**
	 * @runInSeparateProcess enabled
	 */
	public function test__AddonShow()
	{
		self::runCommand(array('addon', 'show', self::getAddon()));
	}
	
	/**
	 * @runInSeparateProcess enabled
	 */
	public function test__ExtendAdd()
	{
		self::runCommand(array('extend', 'add', 'XenForo_ControllerPublic_Account'));
	}
	
	/**
	 * @runInSeparateProcess enabled
	 */
	public function test__ListenerAdd()
	{
		self::runCommand(array('listener', 'add', 'load_class_controller'));
	}
	
	/**
	 * @runInSeparateProcess enabled
	 */
	public function test__PhraseAdd()
	{
		self::runCommand(array('phrase', 'add', strtolower(self::getAddon()), strtolower(self::getAddon()) ));
	}
	
	/**
	 * @runInSeparateProcess enabled
	 */
	public function test__PhraseFind()
	{
		self::runCommand(array('phrase', 'find', strtolower(self::getAddon())));
	}
	
	/**
	 * @runInSeparateProcess enabled
	 */
	public function test__PhraseGet()
	{
		self::runCommand(array('phrase', 'get', strtolower(self::getAddon())));
	}
	
	/**
	 * @runInSeparateProcess enabled
	 */
	public function test__GenerateController()
	{
		self::runCommand(array('generate', 'controller', 'test'));
	}
	
	/**
	 * @runInSeparateProcess enabled
	 */
	public function test__TemplateAdd()
	{
		self::runCommand(array('template', 'add', strtolower(self::getAddon())));
	}
	
	/**
	 * @runInSeparateProcess enabled
	 */
	public function test__RouteAdd()
	{
		self::runCommand(array('route', 'add', strtolower(self::getAddon())));
	}
	
	public static function getAddon($new = false)
	{
		$file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'xfcli-addonId';
		
		if ($new)
		{
			file_put_contents($file, 'unitTest' . time());
		}
		
		return file_get_contents($file);
	}
	
	public static function runCommand(array $args, array $flags = array(), array $options = array())
	{
		$cli = new CLI_Xf(false);
		$cli->manualRun($args, false, $flags, $options);
	}
	
}