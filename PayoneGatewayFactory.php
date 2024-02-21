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

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

class PayoneGatewayFactory extends GatewayFactory
{
    /**
     * {@inheritDoc}
     */
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([
            'payum.factory_name' => 'Payone',
            'payum.factory_title' => 'Payone',
//            'payum.template.credit_card_payment' => '@PayumPayone/Action/credit_card_payment_form.html.twig',
            'payum.template.credit_card_payment' => '@PayumPayone/Action/credit_card_payment_json.html.twig',

            'payum.action.authorize' => new Action\AuthorizeAction(),
            'payum.action.capture' => new Action\CaptureAction(),
            'payum.action.convert_payment' => new Action\ConvertPaymentAction(),
            'payum.action.notify' => new Action\NotifyAction(),
            'payum.action.notify_null' => new Action\NotifyNullAction(),
            'payum.action.status' => new Action\StatusAction(),

            'payum.action.api.authorize' => new Action\Api\AuthorizeAction(),
            'payum.action.api.capture' => new Action\Api\CaptureAction(),
            'payum.action.api.pre_authorize' => new Action\Api\PreAuthorizeAction(),
            'payum.action.get_pseudo_card_pan' => function (ArrayObject $config) {
                return new Action\Api\CreditCardOnSitePayment(
                    $config['payum.template.credit_card_payment'],
                    (array)$config
                );
            },
        ]);

        if (false == $config['payum.api']) {
            $config['payum.required_options'] = [
                'merchantId',
                'portalId',
                'accountId',
                'key',
                'paymentType',
                'mode',
            ];

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                return new Api(
                    [
                        'merchantId' => $config['merchantId'],
                        'portalId' => $config['portalId'],
                        'accountId' => $config['accountId'],
                        'key' => $config['key'],
                        'paymentType' => $config['paymentType'],
                        'mode' => $config['mode'],
                    ],
                    $config['payum.http_client'],
                    $config['httplug.message_factory']
                );
            };

            $config['payum.paths'] = array_replace([
                'PayumPayone' => __DIR__.'/Resources/views',
            ], $config['payum.paths'] ?: []);
        }
    }
}
