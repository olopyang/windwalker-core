<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Authentication;

use Windwalker\Authentication\Authentication;
use Windwalker\Authentication\Credential;
use Windwalker\Authentication\Method\MethodInterface;
use Windwalker\Core\Event\DispatcherAwareStaticInterface;
use Windwalker\Core\Facade\Facade;
use Windwalker\Data\Data;
use Windwalker\Event\Dispatcher;
use Windwalker\Event\Event;
use Windwalker\Event\EventInterface;
use Windwalker\Registry\Registry;
use Windwalker\Utilities\ArrayHelper;

/**
 * The User class.
 *
 * @see \Windwalker\Authentication\Authentication
 *
 * @method static boolean          authenticate()   authenticate(Credential $credential)
 * @method static integer[]        getResults()     getResults()
 * @method static Credential       getCredential()  getCredential()
 * @method static MethodInterface  getMethod()      getMethod($name)
 * @method static Authentication   removeMethod()   removeMethod($name)
 * @method static Authentication   addMethod()      addMethod($name, MethodInterface $method)
 *
 * @since  2.0
 */
class User extends Facade implements DispatcherAwareStaticInterface
{
	/**
	 * Property key.
	 *
	 * @var  string
	 */
	protected static $key = 'system.authenticate';

	/**
	 * Property handler.
	 *
	 * @var UserHandlerInterface
	 */
	protected static $handler;

	/**
	 * Property dispatcher.
	 *
	 * @var Dispatcher
	 */
	protected static $dispatcher;

	/**
	 * login
	 *
	 * @param array|object $user
	 * @param bool         $remember
	 * @param array        $options
	 *
	 * @return  boolean
	 */
	public static function login($user, $remember = false, $options = array())
	{
		if (!is_array($user) && !is_object($user))
		{
			throw new \InvalidArgumentException('Credential should be array or object.');
		}

		if (($user instanceof Credential))
		{
			$user = new Credential($user);
		}

		$options = $options instanceof Registry ? $options : new Registry($options);

		$options['remember'] = $remember;

		$result = false;

		// Before login event
		$event = static::triggerEvent('onUserBeforeLogin', array('credential' => $user, 'options' => $options));

		// Do login
		if (static::authenticate($event['credential']))
		{
			$user = static::getCredential();

			$result = static::makeUserLogin($user);
		}

		// After login event
		$event['user'] = $user;
		$event['result'] = $result;

		$event = static::triggerEvent('onUserAfterLogin', $event->getArguments());

		// Fail event
		if (!$event['result'])
		{
			$event['results'] = static::getResults();

			static::triggerEvent('onUserLoginFailure', $event->getArguments());

			return false;
		}

		return true;
	}

	/**
	 * makeUserLogin
	 *
	 * @param mixed $user
	 *
	 * @return  bool
	 */
	public static function makeUserLogin($user)
	{
		if (!is_array($user) && !is_object($user))
		{
			$user = User::get($user);
		}
		elseif (!($user instanceof UserDataInterface))
		{
			$user = new UserData($user);
		}

		if ($user->isNull())
		{
			return false;
		}

		static::$handler->login($user);

		return true;
	}

	/**
	 * logout
	 *
	 * @param array|object $conditions
	 * @param array        $options
	 *
	 * @return  boolean
	 */
	public static function logout($conditions, $options = array())
	{
		$options = $options instanceof Registry ? $options : new Registry($options);

		$user = User::get($conditions);

		// Before logout event
		$event = static::triggerEvent('onUserBeforeLogout', array('user' => $user, 'conditions' => $conditions, 'options' => $options));

		// Do logout
		$result = static::$handler->logout($event['user']);

		// After logout event
		$event['result'] = $result;

		$event = static::triggerEvent('onUserAfterLogout', $event->getArguments());

		// Fail event
		if (!$event['result'])
		{
			static::triggerEvent('onUserLogoutFailure', $event->getArguments());

			return false;
		}

		return true;
	}

	/**
	 * getUser
	 *
	 * @param array $conditions
	 *
	 * @return  UserData|UserDataInterface
	 */
	public static function get($conditions = array())
	{
		$user = static::getHandler()->load($conditions);

		if (!($user instanceof UserDataInterface))
		{
			throw new \UnexpectedValueException('User data should implement from UserDataInterface');
		}

		return $user;
	}

	/**
	 * save
	 *
	 * @param array $user
	 * @param array $options
	 *
	 * @throws \Exception
	 * @return  Data
	 */
	public static function save($user = array(), $options = array())
	{
		if (!is_array($user) && !is_object($user))
		{
			throw new \InvalidArgumentException('User data should be array or object.');
		}

		if (!($user instanceof UserData))
		{
			$user = new UserData($user);
		}

		$options = ($options instanceof Registry) ? $options : new Registry($options);

		static::triggerEvent('onUserBeforeSave', array('user' => $user, 'options' => $options));

		try
		{
			static::$handler->save($user);
		}
		catch (\Exception $e)
		{
			static::triggerEvent('onUserSaveFailure', array('user' => $user, 'exception' => $e, 'options' => $options));

			throw $e;
		}

		static::triggerEvent('onUserAfterSave', array('user' => $user, 'options' => $options));

		return $user;
	}

	/**
	 * delete
	 *
	 * @param mixed $conditions
	 * @param array $options
	 *
	 * @return  boolean
	 */
	public function delete($conditions = array(), $options = array())
	{
		if (is_object($conditions))
		{
			$conditions = get_object_vars($conditions);
		}

		if (!is_array($conditions))
		{
			$conditions = array('id' => $conditions);
		}

		$options = ($options instanceof Registry) ? $options : new Registry($options);

		static::triggerEvent('onUserBeforeDelete', array('conditions' => $conditions, 'options' => $options));

		try
		{
			static::$handler->delete($conditions);
		}
		catch (\Exception $e)
		{
			static::triggerEvent('onUserDeleteFailure', array('conditions' => $conditions, 'exception' => $e, 'options' => $options));

			return false;
		}

		static::triggerEvent('onUseAfterDelete', array('conditions' => $conditions, 'options' => $options));

		return true;
	}

	/**
	 * Method to get property Handler
	 *
	 * @return  UserHandlerInterface
	 */
	public static function getHandler()
	{
		if (!static::$handler)
		{
			throw new \LogicException('No User handler.');
		}

		return static::$handler;
	}

	/**
	 * Method to set property handler
	 *
	 * @param   UserHandlerInterface $handler
	 *
	 * @return  void
	 */
	public static function setHandler(UserHandlerInterface $handler)
	{
		static::$handler = $handler;
	}

	/**
	 * triggerEvent
	 *
	 * @param string|\Windwalker\Event\Event $event
	 * @param array                          $args
	 *
	 * @return  EventInterface|Event
	 */
	public static function triggerEvent($event, $args = array())
	{
		if (!static::$dispatcher)
		{
			static::$dispatcher = static::getContainer()->get('system.dispatcher');
		}

		return static::$dispatcher->triggerEvent($event, $args);
	}
}