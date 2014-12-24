<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core;

use Windwalker\Registry\Registry;
use Windwalker\SystemPackage\SystemPackage;

/**
 * The Windwalker class.
 * 
 * @since  {DEPLOY_VERSION}
 */
abstract class Windwalker
{
	/**
	 * getPackages
	 *
	 * @return  array
	 */
	public static function loadPackages()
	{
		return array(
			'system' => new SystemPackage
		);
	}

	/**
	 * loadConfiguration
	 *
	 * @param Registry $config
	 *
	 * @throws  \RuntimeException
	 * @return  void
	 */
	public static function loadConfiguration(Registry $config)
	{
		$config->loadFile($config['path.etc'] . '/config.yml', 'yaml');
	}

	/**
	 * loadRouting
	 *
	 * @return  array
	 */
	public static function loadRouting()
	{
		return array();
	}

	/**
	 * prepareSystemPath
	 *
	 * @param Registry $config
	 *
	 * @return  void
	 */
	public static function prepareSystemPath(Registry $config)
	{
		$config['path.root']       = WINDWALKER_ROOT;
		$config['path.bin']        = WINDWALKER_BIN;
		$config['path.cache']      = WINDWALKER_CACHE;
		$config['path.etc']        = WINDWALKER_ETC;
		$config['path.logs']       = WINDWALKER_LOGS;
		$config['path.resources']  = WINDWALKER_RESOURCES;
		$config['path.source']     = WINDWALKER_SOURCE;
		$config['path.temp']       = WINDWALKER_TEMP;
		$config['path.templates']  = WINDWALKER_TEMPLATES;
		$config['path.vendor']     = WINDWALKER_VENDOR;
		$config['path.public']     = WINDWALKER_PUBLIC;
		$config['path.migrations'] = WINDWALKER_MIGRATIONS;
		$config['path.seeders']    = WINDWALKER_SEEDERS;
		$config['path.languages']  = WINDWALKER_LANGUAGES;
	}
}