<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class SendEmailRequest
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $to = '',

        #[Assert\Length(max: 120)]
        public ?string $toName = null,

        #[Assert\NotBlank]
        #[Assert\Length(max: 200)]
        public string $subject = '',

        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^[a-z0-9_]+$/', message: 'Le nom de template doit être en snake_case minuscule.')]
        public string $template = '',

        #[Assert\Type('array')]
        public array $context = [],
    ) {
    }
}
