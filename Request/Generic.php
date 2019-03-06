<?php
/**
 * CoreShop.
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2017 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace CoreShop\Payum\Payone\Request;

use ArvPayoneApi\Response\Status;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Request\Generic as BaseGeneric;

abstract class Generic extends BaseGeneric
{
    /**
     * @return bool
     */
    public function isApproved()
    {
        $model = ArrayObject::ensureArrayObject($this->getModel());

        return Status::APPROVED === $model->get('mandate_status');
    }

    /**
     * @return bool
     */
    public function isError()
    {
        $model = ArrayObject::ensureArrayObject($this->getModel());

        return Status::ERROR === $model->get('mandate_status');
    }

    /**
     * @return bool
     */
    public function isRedirect()
    {
        $model = ArrayObject::ensureArrayObject($this->getModel());

        return Status::REDIRECT === $model->get('mandate_status');
    }
}
