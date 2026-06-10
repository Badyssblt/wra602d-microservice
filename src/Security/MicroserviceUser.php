<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;

final class MicroserviceUser implements UserInterface
{
    public function __construct(private readonly string $identifier = 'backoffice')
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        return ['ROLE_MICROSERVICE_CLIENT'];
    }

    public function eraseCredentials(): void
    {
    }
}
