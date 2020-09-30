<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\Queue;

use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Queue\Failer\QueueFailerInterface;
use Windwalker\Queue\Job\JobInterface;
use Windwalker\Queue\Queue;
use Windwalker\Queue\Worker;

// B/C
class_alias(Queue::class, \Windwalker\Core\Queue\Queue::class);
class_alias(JobInterface::class, \Windwalker\Core\Queue\Job\JobInterface::class);

/**
 * The QueueProvider class.
 *
 * @since  3.2
 */
class QueueProvider implements ServiceProviderInterface
{
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container $container The DI container.
     *
     * @return  void
     * @throws \UnexpectedValueException
     */
    public function register(Container $container)
    {
        $container->prepareSharedObject(QueueManager::class);

        $container->share(Queue::class, function (Container $container) {
            /** @var QueueManager $manager */
            $manager = $container->get('queue.manager');
            $queue   = $manager->create(false);

            // Worker also uses Queue object, share it once to prevent infinity loop
            $container->share(Queue::class, $queue);

            return $queue->setDriver($manager->createDriverByConnection());
        });

        // Worker
        $container->share(Worker::class, function (Container $container) {
            return $container->newInstance(
                Worker::class,
                [
                    'logger' => $container->get('logger')->createRotatingLogger('queue')
                ]
            );
        })->alias('queue.worker', Worker::class);

        // Failer
        $container->share(QueueFailerInterface::class, function (Container $container) {
            return $container->get('queue.manager')->createFailer();
        })->alias('queue.failer', QueueFailerInterface::class);

        // B/C
        $container->alias(\Windwalker\Core\Queue\Queue::class, Queue::class);
    }
}
