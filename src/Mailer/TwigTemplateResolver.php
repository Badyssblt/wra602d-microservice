<?php

declare(strict_types=1);

namespace App\Mailer;

use App\Exception\UnknownTemplateException;

final class TwigTemplateResolver implements TemplateResolverInterface
{
    private const MAP = [
        'welcome' => [
            'html' => 'emails/welcome.html.twig',
            'text' => 'emails/welcome.txt.twig',
        ],
        'new_high_score' => [
            'html' => 'emails/new_high_score.html.twig',
            'text' => 'emails/new_high_score.txt.twig',
        ],
    ];

    public function resolve(string $logicalName): array
    {
        if (!\array_key_exists($logicalName, self::MAP)) {
            throw new UnknownTemplateException(sprintf('Unknown email template "%s".', $logicalName));
        }

        return self::MAP[$logicalName];
    }
}
