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
use CoreShop\Payum\Payone\Request\Api\Authorize;
use CoreShop\Payum\Payone\Request\Api\OnSitePayment;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\RenderTemplate;

class CreditCardOnSitePayment extends BaseApiAwareAction implements GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * @var string
     */
    private $templateName;

    /**
     * @var ArrayObject
     */
    private $options;

    /**
     * @param string $templateName
     */
    public function __construct($templateName, array $options)
    {
        $this->templateName = $templateName;
        $this->options = ArrayObject::ensureArrayObject($options);
    }

    /**
     * @param Authorize $request
     * @throws \Exception
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $model->validateNotEmpty(['language']);

        if (null !== $model->get('pseudocardpan', null)) {
            // we already have a pseudo card pan
            return;
        }

        // process form submission if present
        $this->gateway->execute($httpRequest = new GetHttpRequest());
        if ('POST' === $httpRequest->method) {
            $postParams = [];
            parse_str($httpRequest->content, $postParams);
            if (array_key_exists('pseudocardpan', $postParams) && array_key_exists('truncatedcardpan', $postParams)) {
                $model['pseudocardpan'] = $postParams['pseudocardpan'];
                $model['truncatedcardpan'] = $postParams['truncatedcardpan'];

                return;
            }
        }

        $language = strtolower($model['language']);
        if (1 !== preg_match('/^[a-z]{2}$/', $language)) {
            $language = 'en';
        }

        $params = [
            'aid' => $this->options['accountId'],
            'encoding' => 'UTF-8',
            'mid' => $this->options['merchantId'],
            'mode' => $this->options['mode'],
            'portalid' => $this->options['portalId'],
            'request' => 'creditcardcheck',
            'responsetype' => 'JSON',
            'storecarddata' => 'yes',
        ];
        ksort($params);
        $hash = hash('md5', implode('', $params) . $this->options['key']);

        $this->gateway->execute($renderTemplate = new RenderTemplate($this->templateName, [
            'params' => $params,
            'hash' => $hash,
            'language' => $language,
            'actionUrl' => $request->getToken() ? $request->getToken()->getTargetUrl() : null,
        ]));

        throw new HttpResponse($renderTemplate->getResult());
    }

    /**
     * @param mixed $request
     *
     * @return boolean
     */
    public function supports($request)
    {
        return
            $request instanceof OnSitePayment &&
            $request->getPaymentType() === PaymentTypes::PAYONE_CREDIT_CARD &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
