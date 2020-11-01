<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\Queue\Command\Queue;

use Windwalker\Core\Console\CoreCommand;
use Windwalker\Core\Database\DatabaseAdapter;
use Windwalker\Core\Logger\Logger;
use Windwalker\Core\Mailer\Adapter\SwiftMailerAdapter;
use Windwalker\Core\Mailer\Mailer;
use Windwalker\Core\Queue\QueueManager;
use Windwalker\Database\Driver\AbstractDatabaseDriver;
use Windwalker\Event\Event;
use Windwalker\Queue\Driver\DatabaseQueueDriver;
use Windwalker\Queue\Job\JobInterface;
use Windwalker\Queue\QueueMessage;
use Windwalker\Queue\Worker;
use Windwalker\Structure\Structure;

/**
 * The WorkerCommand class.
 *
 * @since  3.2
 */
class WorkerCommand extends CoreCommand
{
    /**
     * Property name.
     *
     * @var  string
     */
    protected $name = 'worker';

    /**
     * Property description.
     *
     * @var  string
     */
    protected $description = 'Start a queue worker.';

    /**
     * Property usage.
     *
     * @var  string
     */
    protected $usage = '%s <cmd><queues...></cmd> <option>[option]</option>';

    /**
     * init
     *
     * @return  void
     */
    protected function init()
    {
        $this->addOption('c')
            ->alias('connection')
            ->defaultValue(null)
            ->description('The connection of queue.');

        $this->addOption('o')
            ->alias('once')
            ->defaultValue(false)
            ->description('Only run next job.');

        $this->addOption('d')
            ->alias('delay')
            ->defaultValue(0)
            ->description('Delay time for failed job to wait next run.');

        $this->addOption('f')
            ->alias('force')
            ->defaultValue(false)
            ->description('Force run worker if in pause mode.');

        $this->addOption('m')
            ->alias('memory')
            ->defaultValue(128)
            ->description('The memory limit in megabytes.');

        $this->addOption('s')
            ->alias('sleep')
            ->defaultValue(1)
            ->description('Number of seconds to sleep after job run complete.');

        $this->addOption('t')
            ->alias('tries')
            ->defaultValue(5)
            ->description('Number of times to attempt a job if it failed.');

        $this->addOption('timeout')
            ->defaultValue(60)
            ->description('Number of seconds that a job can run.');

        $this->addOption('file')
            ->description('The job file to run once.');
    }

    /**
     * doExecute
     *
     * @return  bool
     * @throws \Exception
     */
    protected function doExecute()
    {
        $queues     = $this->io->getArguments();
        $options    = $this->getWorkOptions();
        $connection = $this->getOption('connection', null) ?: $this->console->get('queue.connection');

        // Set connection as default
        if ($connection !== null) {
            $this->console->set('queue.connection', $connection);
        }

        /** @var Worker $worker */
        $worker = $this->console->container->get('queue.worker');

        // Default Queues
        if (!count($queues)) {
            $queues = $this->console->container->get('queue.manager')
                ->getConnectionConfig($connection)
                ->get('queue');
        }

        $this->listenToWorker($worker);

        if ($this->getOption('once')) {
            $file = $this->getOption('file');

            if ($file) {
                /** @var QueueMessage $message */
                $message = unserialize(file_get_contents($file));
                $worker->process($message, $options);
            } else {
                $worker->runNextJob($queues, $options);
            }
        } else {
            $worker->loop($queues, $options);
        }

        return true;
    }

    /**
     * listenToWorker
     *
     * @param Worker $worker
     *
     * @return  void
     */
    protected function listenToWorker(Worker $worker)
    {
        $worker->getDispatcher()
            ->listen('onWorkerBeforeJobRun', function (Event $event) {
                /**
                 * @var JobInterface $job
                 * @var QueueMessage $message
                 */
                $job     = $event['job'];
                $message = $event['message'];

                $this->console->addMessage(sprintf(
                    'Run Job: <info>%s</info> - Message ID: <info>%s</info>',
                    $job->getName(),
                    $message->getId()
                ));
            })
            ->listen('onWorkerJobFailure', function (Event $event) {
                /**
                 * @var JobInterface $job
                 * @var \Exception   $e
                 * @var QueueMessage $message
                 */
                $job     = $event['job'];
                $e       = $event['exception'];
                $message = $event['message'];

                $this->console->addMessage(sprintf(
                    'Job %s failed: %s (%s)',
                    $job->getName(),
                    $e->getMessage(),
                    $message->getId()
                ), 'error');

                // If be deleted, send to failed table
                if ($message->isDeleted()) {
                    $this->console->container->get('queue.failer')->add(
                        $this->console->get('queue.connection', 'sync'),
                        $message->getQueueName(),
                        json_encode($message),
                        (string) $e
                    );
                }

                if ($this->getOption('once')) {
                    throw $e;
                }

                $this->console->handleException($e);
            })
            ->listen('onWorkerLoopCycleStart', function (Event $event) {
                /** @var Worker $worker */
                $worker = $event['worker'];

                if ($this->console->database->isConnected() && !$this->console->database->ping()) {
                    $this->console->database->disconnect();
                }

                switch ($worker->getState()) {
                    case $worker::STATE_ACTIVE:
                        if ($this->console->isOffline()) {
                            $worker->setState($worker::STATE_PAUSE);
                        }
                        break;

                    case $worker::STATE_PAUSE:
                        if ($this->console->isOffline()) {
                            $worker->setState($worker::STATE_ACTIVE);
                        }
                        break;
                }
            })
            ->listen('onWorkerLoopCycleFailure', function (Event $event) {
                /** @var \Exception $e */
                $e = $event['exception'];

                $this->console->addMessage(
                    sprintf(
                        '%s File: %s (%s)',
                        $e->getMessage(),
                        $e->getFile(),
                        $e->getLine()
                    ),
                    'error'
                );

                $this->console->handleException($e);

                $this->stopConnections();
            })
            ->listen('onWorkerLoopCycleEnd', function (Event $event) {
                /** @var QueueManager $manager */
                $manager = $event['manager'];

                $driver = $manager->getDriver();

                if ($driver instanceof DatabaseQueueDriver) {
                    $driver->disconnect();
                }

                $this->stopConnections();
            });
    }

    /**
     * getWorkOptions
     *
     * @return  Structure
     */
    protected function getWorkOptions()
    {
        return new Structure(
            [
                'once' => $this->getOption('once'),
                'delay' => $this->getOption('delay'),
                'force' => $this->getOption('force'),
                'memory' => $this->getOption('memory'),
                'sleep' => $this->getOption('sleep'),
                'tries' => $this->getOption('tries'),
                'timeout' => $this->getOption('timeout'),
                'restart_signal' => $file = $this->console->get('path.temp') . '/queue/restart',
            ]
        );
    }

    /**
     * stopConnections
     *
     * @return  void
     *
     * @since  3.5.22.4
     */
    protected function stopConnections(): void
    {
        if ($this->console->database instanceof AbstractDatabaseDriver) {
            $this->console->database->disconnect();
        }

        $mailerAdapter = Mailer::getAdapter();

        if ($mailerAdapter instanceof SwiftMailerAdapter) {
            $mailerAdapter->getMailer()->getTransport()->stop();
        }

        foreach ((array) $this->console->get('queue.stop_connections') as $handler) {
            if (is_callable($handler)) {
                $this->console->getContainer()->call($handler, [static::class => $this]);
            }
        }
    }
}
