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
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\Notify;

class NotifyAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * {@inheritDoc}
     *
     * @param $request Notify
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if (in_array($model['status'], ['canceled', 'failed', 'refunded'], true)) {
            throw new HttpResponse('TSOK', 200, ['Content-Type' => 'text/plain']);
        }

        $this->gateway->execute($httpRequest = new GetHttpRequest());

        $postParams = [];
        parse_str($httpRequest->content, $postParams);

        if (false === array_key_exists('txaction', $postParams)) {

            throw new HttpResponse('Parameter "txaction" is missing."', 400, ['Content-Type' => 'text/plain']);
        }

        if ('cancelation' === $postParams['txaction']) {
            $model['status'] = 'canceled';
            $model['txid'] = $postParams['txaction'];

            throw new HttpResponse('TSOK', 200, ['Content-Type' => 'text/plain']);
        }

        $transactionStatus = null;
        if (array_key_exists('transaction_status', $postParams)) {
            $transactionStatus = $postParams['transaction_status'];
        }
        if ((null === $transactionStatus || 'completed' === $transactionStatus) && 'appointed' === $postParams['txaction']) {
            $model['status'] = $model['completed_status'];
            $model['transaction_status'] = $transactionStatus;
            $model['txid'] = $postParams['txaction'];

            throw new HttpResponse('TSOK', 200, ['Content-Type' => 'text/plain']);
        }

        if (in_array($postParams['txaction'], ['capture', 'paid'], true)) {
            $model['status'] = $model['completed_status'];
            $model['txid'] = $postParams['txaction'];

            throw new HttpResponse('TSOK', 200, ['Content-Type' => 'text/plain']);
        }

        throw new LogicException('Unsupported txaction: '.$postParams['txaction']);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Notify &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
