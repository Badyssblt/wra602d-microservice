<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Security\SharedTokenAuthenticator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

final class SharedTokenAuthenticatorTest extends TestCase
{
    public function testSupportsRequiresHeader(): void
    {
        $auth = new SharedTokenAuthenticator('secret');
        self::assertFalse($auth->supports(new Request()));

        $req = new Request();
        $req->headers->set(SharedTokenAuthenticator::HEADER, 'anything');
        self::assertTrue($auth->supports($req));
    }

    public function testRejectsEmptyToken(): void
    {
        $auth = new SharedTokenAuthenticator('secret');
        $req = new Request();
        $req->headers->set(SharedTokenAuthenticator::HEADER, '');

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $auth->authenticate($req);
    }

    public function testRejectsBadToken(): void
    {
        $auth = new SharedTokenAuthenticator('secret');
        $req = new Request();
        $req->headers->set(SharedTokenAuthenticator::HEADER, 'wrong');

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $auth->authenticate($req);
    }

    public function testAcceptsCorrectToken(): void
    {
        $auth = new SharedTokenAuthenticator('secret');
        $req = new Request();
        $req->headers->set(SharedTokenAuthenticator::HEADER, 'secret');
        $passport = $auth->authenticate($req);
        self::assertNotNull($passport);
    }
}
