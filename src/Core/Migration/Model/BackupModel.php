<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2015 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Migration\Model;

use Windwalker\Console\Command\AbstractCommand;
use Windwalker\Core\Ioc;
use Windwalker\Core\Model\DatabaseModel;
use Windwalker\Filesystem\Folder;

/**
 * The BackupModel class.
 * 
 * @since  {DEPLOY_VERSION}
 */
class BackupModel extends DatabaseModel
{
	/**
	 * Property command.
	 *
	 * @var AbstractCommand
	 */
	protected $command;

	/**
	 * Property lastBackup.
	 *
	 * @var string
	 */
	public $lastBackup;

	/**
	 * Property instance.
	 *
	 * @var  static
	 */
	protected static $instance;

	/**
	 * getInstance
	 *
	 * @return static
	 */
	public static function getInstance()
	{
		if (!static::$instance)
		{
			static::$instance = new static;
		}

		return static::$instance;
	}

	/**
	 * backup
	 *
	 * @return  boolean
	 */
	public function backup()
	{
		$this->command->out()->out('Backing up SQL...');

		$this->lastBackup = $sql = $this->getSQLExport();

		Folder::create(WINDWALKER_TEMP . '/sql-backup');

		$file = WINDWALKER_TEMP . '/sql-backup/sql-backup-' . gmdate('Y-m-d-H-i-s-') . uniqid() . '.sql';

		file_put_contents($file, $sql);

		$this->command->out()->out('SQL backup to: <info>' . $file . '</info>')->out();

		return true;
	}

	/**
	 * getSQLExport
	 *
	 * @return  string
	 */
	public function getSQLExport()
	{
		$exporter = Ioc::get('sql.exporter');

		return $exporter->export();
	}

	/**
	 * Method to get property Command
	 *
	 * @return  mixed
	 */
	public function getCommand()
	{
		return $this->command;
	}

	/**
	 * Method to set property command
	 *
	 * @param   mixed $command
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setCommand(AbstractCommand $command)
	{
		$this->command = $command;

		return $this;
	}

	/**
	 * restoreLatest
	 *
	 * @return  void
	 */
	public function restoreLatest()
	{
		$sql = $this->lastBackup;

		$this->db->setQuery($sql)->execute();

		$this->command->out('<info>Restore to latest backup complete.</info>');
	}
}
