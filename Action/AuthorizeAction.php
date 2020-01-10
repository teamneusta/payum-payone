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

use CoreShop\Payum\Payone\Request\Api\PreAuthorize;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\Authorize;
use Payum\Core\Request\Convert;
use Payum\Core\Request\GetHumanStatus;

class AuthorizeAction extends AbstractPurchaseAction
{
    /**
     * {@inheritDoc}
     */
    protected function preExecute($request)
    {
        $model = parent::preExecute($request);
        $payment = $request->getFirstModel();

        if ($payment instanceof PaymentInterface) {
            $this->gateway->execute($status = new GetHumanStatus($payment));
            if ($status->isNew()) {
                $this->gateway->execute($convert = new Convert($payment, 'array', $request->getToken()));

                $model->replace($convert->getResult());
            }
        }

        return $model;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Authorize &&
            $request->getModel() instanceof \ArrayAccess;
    }

    /**
     * {@inheritDoc}
     */
    protected function createApiRequest($model)
    {
        return new PreAuthorize($model);
    }

    /**
     * {@inheritDoc}
     */
    protected function getCompletedStatus()
    {
        return 'authorized';
    }
}
