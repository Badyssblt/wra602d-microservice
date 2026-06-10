<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class SharedTokenAuthenticator extends AbstractAuthenticator
{
    public const HEADER = 'X-Microservice-Token';

    public function __construct(
        #[\SensitiveParameter]
        #[Autowire('%env(MICROSERVICE_SHARED_SECRET)%')]
        private readonly string $sharedSecret,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has(self::HEADER);
    }

    public function authenticate(Request $request): Passport
    {
        $token = (string) $request->headers->get(self::HEADER, '');
        if ('' === $token || '' === $this->sharedSecret || !hash_equals($this->sharedSecret, $token)) {
            throw new CustomUserMessageAuthenticationException('Invalid microservice token.');
        }

        return new SelfValidatingPassport(new UserBadge('backoffice', static fn (): MicroserviceUser => new MicroserviceUser()));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
    }
}
