<?php
/**
 * Part of Windwalker project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Test\Mvc\View\Stub;

use Windwalker\Core\Model\Model;
use Windwalker\Core\View\HtmlView;
use Windwalker\Core\View\ViewModel;

/**
 * The StubHtmlView class.
 * 
 * @since  {DEPLOY_VERSION}
 */
class StubHtmlView extends HtmlView
{
	/**
	 * Property model.
	 *
	 * @var ViewModel
	 */
	public $model;

	/**
	 * getRegisteredPaths
	 *
	 * @return  \SplPriorityQueue
	 */
	public function getRegisteredPaths()
	{
		$this->registerPaths();

		return clone $this->renderer->getPaths();
	}
}
