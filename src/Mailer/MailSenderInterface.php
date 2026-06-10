<?php

declare(strict_types=1);

namespace App\Mailer;

use App\Dto\SendEmailRequest;

interface MailSenderInterface
{
    public function send(SendEmailRequest $request, string $messageId): void;
}
