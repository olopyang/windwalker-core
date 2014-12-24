<?php
/**
 * Part of Windwalker project Test files.
 *
 * @copyright  Copyright (C) 2011 - 2014 SMS Taiwan, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Windwalker\Core\Test\View;

use Joomla\Date\Date;
use Twig_SimpleFunction;
use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Test\View\Stub\StubExtension;
use Windwalker\Core\Test\View\Stub\StubTwigHtmlView;
use Windwalker\Core\View\Helper\Set\HelperSet;
use Windwalker\Core\View\Twig\WindwalkerExtension;
use Windwalker\Core\View\TwigHtmlView;
use Windwalker\DI\Container;
use Windwalker\Registry\Registry;
use Windwalker\Renderer\TwigRenderer;
use Windwalker\Test\TestHelper;

/**
 * Test class of TwigHtmlView
 *
 * @since {DEPLOY_VERSION}
 */
class TwigHtmlViewTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Test instance.
	 *
	 * @var TwigHtmlView
	 */
	protected $instance;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @return void
	 */
	protected function setUp()
	{
		if (!class_exists('Twig_Environment'))
		{
			$this->markTestSkipped('Twig not installed');

			return;
		}

		$this->instance = new StubTwigHtmlView;
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 *
	 * @return void
	 */
	protected function tearDown()
	{
	}

	public function testGetAndSetExtensions()
	{
		/** @var TwigRenderer $renderer */
		$renderer = $this->instance->getRenderer();

		TestHelper::invoke($this->instance, 'prepareGlobals', $this->instance->getData());

		$twig = $renderer->getEngine();

		$exts = $twig->getExtensions();

		$this->assertArrayHasKey('stub', $exts);
		$this->assertTrue($exts['stub'] instanceof StubExtension);
		$this->assertTrue($exts['windwalker'] instanceof WindwalkerExtension);

		/** @var WindwalkerExtension $wwExt */
		$wwExt = $exts['windwalker'];

		/** @var Twig_SimpleFunction[] $functions */
		$functions = $exts['windwalker']->getFunctions();

		$this->assertEquals('show', $functions[0]->getName());

		$globals = $wwExt->getGlobals();

		$this->assertTrue($globals['uri'] instanceof Registry);
		$this->assertTrue($globals['app'] instanceof WebApplication);
		$this->assertTrue($globals['container'] instanceof Container);
		$this->assertTrue($globals['helper'] instanceof HelperSet);
		$this->assertTrue(is_array($globals['flashes']));
		$this->assertTrue($globals['datetime'] instanceof Date);
	}
}
