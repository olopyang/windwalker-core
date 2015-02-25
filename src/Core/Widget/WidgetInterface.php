<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Widget;

/**
 * Interface WidgetInterface
 *
 * @since  2.0
 */
interface WidgetInterface
{
	/**
	 * render
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	public function render($data = array());
}