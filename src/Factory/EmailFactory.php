<?php

declare(strict_types=1);

namespace App\Factory;

use App\Dto\EmailMessageDto;
use App\Entity\Purchase;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class EmailFactory
{
    public function __construct(
        private UrlGeneratorInterface $router,
        private TranslatorInterface $translator,
    ) {
    }

    public function purchaseSuccess(Purchase $purchase): EmailMessageDto
    {
        $purchaseLink = $this->router->generate(
            'app_purchase_success',
            ['orderId' => $purchase->getOrderId()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        return new EmailMessageDto(
            to: new Address($purchase->getPayment()['payer']['email_address']),
            subject: $this->translator->trans('email.purchase.success.subject'),
            template: 'emails/purchase_success.html.twig',
            context: [
                'purchase' => $purchase,
                'purchase_link' => $purchaseLink,
            ],
            bcc: [new Address('m.rezouani@hotmail.com')],
        );
    }
}
