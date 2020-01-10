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

namespace CoreShop\Payum\Payone;

use ArvPayoneApi\Api\Client;
use ArvPayoneApi\Api\PostApi;
use ArvPayoneApi\Request\Authorization\RequestFactory as AuthFactory;
use ArvPayoneApi\Request\Capture\RequestFactory as CaptureFactory;
use ArvPayoneApi\Request\PaymentTypes;
use ArvPayoneApi\Request\PreAuthorization\RequestFactory as PreAuthFactory;
use ArvPayoneApi\Request\SerializerFactory;
use Http\Message\MessageFactory;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\HttpClientInterface;

class Api
{
    /**
     * @var mixed
     */
    protected $api;

    /**
     * @var HttpClientInterface
     */
    protected $client;

    /**
     * @var MessageFactory
     */
    protected $messageFactory;

    const TYPES = [
        'PayPal' => PaymentTypes::PAYONE_PAY_PAL,
        'Sofort' => PaymentTypes::PAYONE_SOFORT,
        'CreditCard' => PaymentTypes::PAYONE_CREDIT_CARD,
    ];

    /**
     * @var array|ArrayObject
     */
    protected $options = [];

    public function __construct(array $options, HttpClientInterface $client, MessageFactory $messageFactory)
    {
        $this->options = ArrayObject::ensureArrayObject($options);
        $this->client = $client;
        $this->messageFactory = $messageFactory;
    }

    /**
     * @return bool
     */
    public function isOnsite()
    {
        return in_array($this->getPaymentType(), [PaymentTypes::PAYONE_CREDIT_CARD, PaymentTypes::PAYONE_DIRECT_DEBIT], true);
    }

    /**
     * @return bool
     */
    public function getPaymentType()
    {
        return $this->getOption('paymentType');
    }

    /**
     * @param $model
     * @return \ArvPayoneApi\Response\ResponseContract
     * @throws \Exception
     */
    public function preAuthorize($model)
    {
        $model = $this->prepareModel($model);

        return $this->getPostClient()->doRequest(PreAuthFactory::create($this->getOption('paymentType'), $model));
    }

    /**
     * @param $model
     * @return \ArvPayoneApi\Response\ResponseContract
     * @throws \Exception
     */
    public function authorize($model)
    {
        $model = $this->prepareModel($model);

        return $this->getPostClient()->doRequest(AuthFactory::create($this->getOption('paymentType'), $model));
    }

    /**
     * @param $model
     * @return \ArvPayoneApi\Response\ResponseContract
     * @throws \Exception
     */
    public function capture($model)
    {
        $model = $this->prepareModel($model);

        return $this->getPostClient()->doRequest(CaptureFactory::create($this->getOption('paymentType'), $model));
    }

    /**
     * @param array $model
     * @return array
     */
    protected function prepareModel($model)
    {
        $model['context'] = [
            'aid' => $this->getOption('accountId'),
            'mid' => $this->getOption('merchantId'),
            'portalid' => $this->getOption('portalId'),
            'key' => $this->getOption('key'),
            'mode' => $this->getOption('mode'),
        ];

        return $model;
    }

    /**
     * @param $option
     * @return mixed
     */
    protected function getOption($option)
    {
        return $this->options[$option];
    }

    /**
     * @return PostApi
     */
    protected function getPostClient()
    {
        if (null === $this->api) {
            $this->api = new PostApi(new Client(), SerializerFactory::createArraySerializer());
        }

        return $this->api;
    }
}
