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
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \Spryker\Zed\PaymentGui\Communication\PaymentGuiCommunicationFactory getFactory()
 */
class UpdatePaymentMethodController extends AbstractController
{
    /**
     * @var string
     */
    protected const REDIRECT_URL = '/payment-gui/payment-method/index';

    /**
     * @var string
     */
    protected const MESSAGE_SUCCESS = 'Payment method has been successfully updated';

    /**
     * @var string
     */
    protected const PARAMETER_ID_PAYMENT_METHOD = 'id-payment-method';

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
            $request->query->getInt(static::PARAMETER_ID_PAYMENT_METHOD),
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

        $paymentMethodTabs = $this->getFactory()->createPaymentMethodTabs();
        $dataProvider = $this->getFactory()->createPaymentMethodFormDataProvider();
        /** @var \Generated\Shared\Transfer\PaymentMethodTransfer $paymentMethodTransfer */
        $paymentMethodTransfer = $paymentMethodCollectionTransfer->getPaymentMethods()->getIterator()->current();
        $paymentMethodForm = $this->getFactory()
            ->createPaymentMethodForm(
                $dataProvider->getData($paymentMethodTransfer),
                $dataProvider->getOptions(),
            );
        $paymentMethodForm->handleRequest($request);

        if ($paymentMethodForm->isSubmitted() && $paymentMethodForm->isValid()) {
            return $this->handlePaymentMethodForm($paymentMethodForm);
        }

        return $this->viewResponse([
            'paymentMethodForm' => $paymentMethodForm->createView(),
            'paymentMethodTabs' => $paymentMethodTabs->createView(),
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
            $messageText = $messageTransfer->getValue();

            if ($messageText === null) {
                continue;
            }

            $this->addErrorMessage($messageText);
        }
    }

    /**
     * @param \Symfony\Component\Form\FormInterface $paymentMethodForm
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function handlePaymentMethodForm(FormInterface $paymentMethodForm): RedirectResponse
    {
        $paymentMethodResponseTransfer = $this->getFactory()
            ->getPaymentFacade()
            ->updatePaymentMethod($paymentMethodForm->getData());

        if (!$paymentMethodResponseTransfer->getIsSuccessful()) {
            $this->setErrors($paymentMethodResponseTransfer);

            return $this->redirectResponse(static::REDIRECT_URL);
        }

        $this->addSuccessMessage(static::MESSAGE_SUCCESS);

        return $this->redirectResponse(static::REDIRECT_URL);
    }
}
