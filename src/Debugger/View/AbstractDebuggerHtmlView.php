<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Debugger\View;

use Windwalker\Core\View\PhpHtmlView;

/**
 * The AbstractDebuggerHtmlView class.
 * 
 * @since  2.1.1
 */
class AbstractDebuggerPhpHtmlView extends PhpHtmlView
{
	/**
	 * initialise
	 *
	 * @return  void
	 */
	protected function initialise()
	{
		$this->renderer->config->set('local_variables', true);
	}
}
