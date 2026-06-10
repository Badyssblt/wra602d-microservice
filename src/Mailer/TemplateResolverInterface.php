<?php

declare(strict_types=1);

namespace App\Mailer;

interface TemplateResolverInterface
{
    /**
     * @return array{html: string, text: string}
     *
     * @throws \App\Exception\UnknownTemplateException
     */
    public function resolve(string $logicalName): array;
}
