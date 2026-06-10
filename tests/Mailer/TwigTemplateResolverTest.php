<?php

declare(strict_types=1);

namespace App\Tests\Mailer;

use App\Exception\UnknownTemplateException;
use App\Mailer\TwigTemplateResolver;
use PHPUnit\Framework\TestCase;

final class TwigTemplateResolverTest extends TestCase
{
    public function testResolvesKnownTemplate(): void
    {
        $resolver = new TwigTemplateResolver();
        $tpl = $resolver->resolve('welcome');
        self::assertSame(['html' => 'emails/welcome.html.twig', 'text' => 'emails/welcome.txt.twig'], $tpl);
    }

    public function testThrowsOnUnknownTemplate(): void
    {
        $this->expectException(UnknownTemplateException::class);
        (new TwigTemplateResolver())->resolve('does_not_exist');
    }
}
