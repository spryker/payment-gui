<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\PaymentGui\Communication\Controller;

use Generated\Shared\Transfer\PaymentMethodConditionsTransfer;
use Generated\Shared\Transfer\PaymentMethodCriteriaTransfer;
use Generated\Shared\Transfer\PaymentMethodResponseTransfer;
use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \Spryker\Zed\PaymentGui\Communication\PaymentGuiCommunicationFactory getFactory()
 */
class ViewPaymentMethodController extends AbstractController
{
    /**
     * @var string
     */
    protected const PARAM_ID_PAYMENT_METHOD = 'id-payment-method';

    /**
     * @var string
     */
    protected const REDIRECT_URL = '/payment-gui/payment-method';

    /**
     * @var string
     */
    protected const MESSAGE_PAYMENT_METHOD_NOT_FOUND = 'Payment method not found';

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|array
     */
    public function indexAction(Request $request)
    {
        $idPaymentMethod = $this->castId(
            $request->query->getInt(static::PARAM_ID_PAYMENT_METHOD),
        );

        $paymentMethodCriteriaTransfer = (new PaymentMethodCriteriaTransfer())
            ->setPaymentMethodConditions(
                (new PaymentMethodConditionsTransfer())->addIdPaymentMethod($idPaymentMethod),
            );
        $paymentMethodCollectionTransfer = $this->getFactory()->getPaymentFacade()->getPaymentMethodCollection($paymentMethodCriteriaTransfer);
        if ($paymentMethodCollectionTransfer->getPaymentMethods()->count() === 0) {
            $this->addErrorMessage(static::MESSAGE_PAYMENT_METHOD_NOT_FOUND);

            return $this->redirectResponse(static::REDIRECT_URL);
        }

        /** @var \Generated\Shared\Transfer\PaymentMethodTransfer $paymentMethodTransfer */
        $paymentMethodTransfer = $paymentMethodCollectionTransfer->getPaymentMethods()->getIterator()->current();
        $dataProvider = $this->getFactory()->createViewPaymentMethodFormDataProvider();
        $form = $this->getFactory()->createViewPaymentMethodForm(
            $dataProvider->getData($paymentMethodTransfer),
            $dataProvider->getOptions(),
        );

        return $this->viewResponse([
            'form' => $form->createView(),
            'paymentMethod' => $paymentMethodTransfer,
        ]);
    }

    /**
     * @param \Generated\Shared\Transfer\PaymentMethodResponseTransfer $paymentMethodResponseTransfer
     *
     * @return void
     */
    protected function setErrors(PaymentMethodResponseTransfer $paymentMethodResponseTransfer): void
    {
        foreach ($paymentMethodResponseTransfer->getMessages() as $messageTransfer) {
            $messageValue = $messageTransfer->getValue();

            if ($messageValue === null) {
                continue;
            }

            $this->addErrorMessage($messageValue);
        }
    }
}
