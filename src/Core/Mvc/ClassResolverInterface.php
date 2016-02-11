<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Mvc;

use Windwalker\Core\Package\AbstractPackage;

/**
 * Interface ClassResolverInterface
 *
 * @since  {DEPLOY_VERSION}
 */
interface ClassResolverInterface
{
	/**
	 * Get container key prefix.
	 *
	 * @return  string
	 */
	public static function getPrefix();

	/**
	 * Resolve class path.
	 *
	 * @param   string|AbstractPackage $package
	 * @param   string                 $name
	 *
	 * @return  string|false
	 */
	public function resolve($package, $name);
}
