<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\SendEmailRequest;
use App\Exception\UnknownTemplateException;
use App\Mailer\MailSenderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Ulid;

final class SendEmailController extends AbstractController
{
    public function __construct(private readonly MailSenderInterface $mailSender)
    {
    }

    #[Route('/api/send-email', name: 'send_email', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] SendEmailRequest $request): JsonResponse
    {
        $messageId = (new Ulid())->toRfc4122();
        try {
            $this->mailSender->send($request, $messageId);
        } catch (UnknownTemplateException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['status' => 'accepted', 'messageId' => $messageId], Response::HTTP_ACCEPTED);
    }
}
