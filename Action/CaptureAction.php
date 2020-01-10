<?php
/**
 * CoreShop.
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2020 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace CoreShop\Payum\Payone\Action;

use CoreShop\Payum\Payone\Request\Api\Authorize;
use Payum\Core\Request\Capture;
use Payum\Core\Request\Generic;

class CaptureAction extends AbstractPurchaseAction
{
    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess;
    }

    /**
     * @param $model
     *
     * @return Generic
     */
    protected function createApiRequest($model)
    {
        if ('authorized' === $model->get('status')) {
            return new \CoreShop\Payum\Payone\Request\Api\Capture($model);
        }

        return new Authorize($model);
    }

    /**
     * @return string
     */
    protected function getCompletedStatus()
    {
        return 'captured';
    }
}
