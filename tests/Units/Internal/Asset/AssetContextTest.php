<?php

declare(strict_types=1);

namespace Bavix\Wallet\Test\Units\Internal\Asset;

use Bavix\Wallet\Internal\Asset\AssetContext;
use Bavix\Wallet\Test\Infra\TestCase;

/**
 * @internal
 */
final class AssetContextTest extends TestCase
{
    private AssetContext $context;

    protected function setUp(): void
    {
        parent::setUp();
        $this->context = new AssetContext();
    }

    public function testSetAndGetContext(): void
    {
        $this->context->setContext('bonds');

        self::assertSame('bonds', $this->context->getContext());
        self::assertTrue($this->context->hasContext());
    }

    public function testClearContext(): void
    {
        $this->context->setContext('shares');
        self::assertTrue($this->context->hasContext());

        $this->context->clearContext();

        self::assertNull($this->context->getContext());
        self::assertFalse($this->context->hasContext());
    }

    public function testWithContext(): void
    {
        $result = $this->context->withContext('inventory', function () {
            self::assertSame('inventory', $this->context->getContext());
            return 'test_result';
        });

        self::assertSame('test_result', $result);
        self::assertNull($this->context->getContext());
    }

    public function testWithContextRestoresPreviousContext(): void
    {
        $this->context->setContext('bonds');

        $result = $this->context->withContext('shares', function () {
            self::assertSame('shares', $this->context->getContext());
            return 'nested_result';
        });

        self::assertSame('nested_result', $result);
        self::assertSame('bonds', $this->context->getContext());
    }

    public function testWithContextHandlesExceptions(): void
    {
        $this->context->setContext('bonds');

        try {
            $this->context->withContext('shares', function () {
                throw new \Exception('Test exception');
            });
        } catch (\Exception) {
            // Expected exception
        }

        // Context should still be restored
        self::assertSame('bonds', $this->context->getContext());
    }

    public function testInitialState(): void
    {
        self::assertNull($this->context->getContext());
        self::assertFalse($this->context->hasContext());
    }
}