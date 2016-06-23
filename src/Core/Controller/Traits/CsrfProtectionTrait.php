<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Controller\Traits;

use Windwalker\Core\Controller\Middleware\CsrfProtectionMiddleware;

/**
 * The CsrfProtectionTrait class.
 *
 * @since  {DEPLOY_VERSION}
 */
trait CsrfProtectionTrait
{
	/**
	 * bootCsrfProtectionTrait
	 *
	 * @return  void
	 */
	public function bootCsrfProtectionTrait()
	{
		$this->addMiddleware(CsrfProtectionMiddleware::class);
	}
}
