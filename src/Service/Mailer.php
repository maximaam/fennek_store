<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\EmailMessageDto;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

readonly class Mailer
{
    public function __construct(
        private MailerInterface $mailer,
        #[Autowire('%app_mailer_from%')]
        private string $defaultFrom,
        #[Autowire('%app_name%')]
        private string $appName,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function send(EmailMessageDto $messageDto): void
    {
        $email = new TemplatedEmail()
            ->from($messageDto->from ?? new Address($this->defaultFrom, $this->appName))
            ->to($messageDto->to)
            ->subject($messageDto->subject)
            ->htmlTemplate($messageDto->template)
            ->context($messageDto->context);

        if ([] !== $messageDto->cc) {
            $email->cc(...$messageDto->cc);
        }

        if ([] !== $messageDto->bcc) {
            $email->bcc(...$messageDto->bcc);
        }

        $this->mailer->send($email);
    }
}
