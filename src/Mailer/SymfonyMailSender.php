<?php

declare(strict_types=1);

namespace App\Mailer;

use App\Dto\SendEmailRequest;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

final readonly class SymfonyMailSender implements MailSenderInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private TemplateResolverInterface $resolver,
        #[Autowire('%env(MAILER_FROM)%')]
        private string $fromAddress,
        private LoggerInterface $mailerLogger,
    ) {
    }

    public function send(SendEmailRequest $request, string $messageId): void
    {
        $tpl = $this->resolver->resolve($request->template);

        $email = (new TemplatedEmail())
            ->from(Address::create($this->fromAddress))
            ->to(null !== $request->toName ? new Address($request->to, $request->toName) : new Address($request->to))
            ->subject($request->subject)
            ->htmlTemplate($tpl['html'])
            ->textTemplate($tpl['text'])
            ->context(array_merge($request->context, ['_messageId' => $messageId]));

        $email->getHeaders()->addTextHeader('X-Message-Id', $messageId);

        $this->mailer->send($email);

        $this->mailerLogger->info('mail.sent', [
            'messageId' => $messageId,
            'template' => $request->template,
            'to' => $request->to,
        ]);
    }
}
