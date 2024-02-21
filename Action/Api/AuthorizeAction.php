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

namespace CoreShop\Payum\Payone\Action\Api;

use ArvPayoneApi\Request\PaymentTypes;
use ArvPayoneApi\Response\GenericResponse;
use ArvPayoneApi\Response\Status;
use CoreShop\Payum\Payone\Request\Api\Authorize;
use CoreShop\Payum\Payone\Request\Api\OnSitePayment;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpRedirect;

class AuthorizeAction extends BaseApiAwareAction implements GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * @param Authorize $request
     * @throws \Exception
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if ($this->api->isOnsite()) {
            $this->gateway->execute(new OnSitePayment($this->api->getPaymentType(), $model));
        }

        $paymentType = $this->api->getPaymentType();

        if ($paymentType === PaymentTypes::PAYONE_SOFORT) {
            $model['successurl'] = $model['redirect']['success'];
            $model['errorurl'] = $model['redirect']['error'];
            $model['backurl'] = $model['redirect']['back'];
        }

        $response = $this->api->authorize($model->toUnsafeArray());

        if ($response->getSuccess()) {
            $model['txid'] = $response->getTransactionID();

            $model['status'] = 'pending';
            $model['message'] = $response->getErrorMessage();

            if ($response->getStatus() === Status::REDIRECT) {
                if ($response instanceof GenericResponse) {
                    $model['responseData'] = $response->getResponseData();

                    throw new HttpRedirect($model['responseData']['redirecturl']);
                }
            }

            if ($response->getStatus() === Status::APPROVED) {
                $model['status'] = 'authorized';
            }

            return;
        }
        $onsite = $this->api->isOnsite() ? 'true' : 'false';
        $unsafeArray = $model->toUnsafeArray();
        throw new LogicException(
            'api onsite: ' . $onsite . PHP_EOL .
            ' model reference: ' . $unsafeArray['reference'] . PHP_EOL .
            ' Unknown status: '.$response->getStatus() . ' ' . $response->getErrorMessage()
        );
    }

    /**
     * @param mixed $request
     *
     * @return boolean
     */
    public function supports($request)
    {
        return
            $request instanceof Authorize &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
