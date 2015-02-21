<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2011 - 2014 SMS Taiwan, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Windwalker\Core\Seeder\Command\Seed;

use Windwalker\Console\Command\Command;
use Windwalker\Core\Ioc;

/**
 * Class Seed
 */
class CleanCommand extends Command
{
	/**
	 * An enabled flag.
	 *
	 * @var bool
	 */
	public static $isEnabled = true;

	/**
	 * Console(Argument) name.
	 *
	 * @var  string
	 */
	protected $name = 'clean';

	/**
	 * The command description.
	 *
	 * @var  string
	 */
	protected $description = 'Clean seed';

	/**
	 * The usage to tell user how to use this command.
	 *
	 * @var string
	 */
	protected $usage = 'clean <cmd><command></cmd> <option>[option]</option>';

	/**
	 * Initialise command information.
	 *
	 * @return void
	 */
	public function initialise()
	{
		parent::initialise();
	}

	/**
	 * Execute this command.
	 *
	 * @return int|void
	 */
	protected function doExecute()
	{
		$class = $this->app->get('seed.class');

		/** @var \Windwalker\Core\Seeder\AbstractSeeder $seeder */
		$seeder = new $class(Ioc::getDatabase(), $this);

		$seeder->doClean();
		
		$this->out('Database clean.');

		return true;
	}
}
