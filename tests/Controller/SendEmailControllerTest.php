<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Security\SharedTokenAuthenticator;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SendEmailControllerTest extends WebTestCase
{
    public function testRejectsRequestWithoutToken(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/send-email',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode(['to' => 'a@b.c', 'subject' => 's', 'template' => 'welcome', 'context' => []]),
        );
        self::assertResponseStatusCodeSame(401);
    }

    public function testRejectsInvalidPayload(): void
    {
        $client = $this->authenticatedClient();
        $client->request(
            'POST',
            '/api/send-email',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode(['to' => 'not-an-email', 'subject' => '', 'template' => 'welcome']),
        );
        self::assertResponseStatusCodeSame(422);
    }

    public function testReturns404OnUnknownTemplate(): void
    {
        $client = $this->authenticatedClient();
        $client->request(
            'POST',
            '/api/send-email',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode([
                'to' => 'alice@example.com',
                'subject' => 'Hi',
                'template' => 'nonexistent_template',
                'context' => [],
            ]),
        );
        self::assertResponseStatusCodeSame(404);
    }

    public function testAcceptsValidPayloadAndDispatchesEmail(): void
    {
        $client = $this->authenticatedClient();
        $client->enableProfiler();
        $client->request(
            'POST',
            '/api/send-email',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode([
                'to' => 'alice@example.com',
                'subject' => 'Bienvenue',
                'template' => 'welcome',
                'context' => ['username' => 'alice'],
            ]),
        );
        self::assertResponseStatusCodeSame(202);
        self::assertEmailCount(1);
    }

    private function authenticatedClient(): KernelBrowser
    {
        $token = $_ENV['MICROSERVICE_SHARED_SECRET'] ?? $_SERVER['MICROSERVICE_SHARED_SECRET'] ?? 'test_token_for_phpunit';

        return static::createClient(server: [
            'HTTP_'.str_replace('-', '_', SharedTokenAuthenticator::HEADER) => $token,
        ]);
    }
}
