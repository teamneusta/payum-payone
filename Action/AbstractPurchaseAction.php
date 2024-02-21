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

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Request\Authorize;
use Payum\Core\Request\Capture;
use Payum\Core\Request\Generic;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;

abstract class AbstractPurchaseAction implements ActionInterface, GenericTokenFactoryAwareInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;
    use GenericTokenFactoryAwareTrait;

    /**
     * @param Authorize|Capture $request
     *
     * @return ArrayObject
     */
    protected function preExecute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        return ArrayObject::ensureArrayObject($request->getModel());
    }

    /**
     * {@inheritDoc}
     *
     * @param Authorize|Capture $request
     */
    public final function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if ($this->getCompletedStatus() === $model['status']) {
            return;
        }

        $model['completed_status'] = $this->getCompletedStatus();

        // there might be a more beautiful way
        $afterUrl = $request->getToken()->getAfterUrl();
        if(strpos($afterUrl,'?')>0){
            $seperatorChar = '&';
        } else {
            $seperatorChar = '?';
        }

        $model['redirect'] = [
            'success' => $request->getToken()->getTargetUrl(),
            'error' => $afterUrl. $seperatorChar .'failed=1',
            'back' => $afterUrl . $seperatorChar .'canceled=1',
        ];

        $this->gateway->execute($httpRequest = new GetHttpRequest());

        if (isset($httpRequest->query['canceled'])) {
            $model['status'] = 'canceled';

            return;
        }

        if (isset($httpRequest->query['failed'])) {
            $model['status'] = 'failed';

            return;
        }

        if (in_array($model['status'], ['canceled', 'failed', 'refunded'], true)) {
            return;
        }

        if (false === $model->get('param', false) && $request->getToken() && $this->tokenFactory) {
            $notifyToken = $this->tokenFactory->createNotifyToken(
                $request->getToken()->getGatewayName(),
                $request->getToken()->getDetails()
            );

            $model['param'] = $notifyToken->getHash();
        }

        if ('pending' !== $model['status']) {
            $this->gateway->execute($this->createApiRequest($model));
        }

        if (in_array($model['status'], ['authorized', 'captured', 'failed'], true)) {
            return;
        }

        if ($model['param']) {
            sleep(5);

            throw new HttpRedirect($request->getToken()->getAfterUrl());
        }

        // if notification is needed the payment will be completed in the NotifyAction
    }

    /**
     * @param $model
     *
     * @return Generic
     */
    abstract protected function createApiRequest($model);

    /**
     * @return string
     */
    abstract protected function getCompletedStatus();
}
