<?php

declare(strict_types=1);

namespace AIEngine\Tests\Exceptions;

use PHPUnit\Framework\TestCase;
use AIEngine\Exceptions\AIEngineException;
use AIEngine\Exceptions\ConfigurationException;
use AIEngine\Exceptions\ApiException;
use RuntimeException;

class ExceptionTest extends TestCase
{
    public function testAIEngineExceptionExtendsRuntimeException(): void
    {
        $exception = new AIEngineException('test');

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testConfigurationExceptionExtendsAIEngineException(): void
    {
        $exception = new ConfigurationException('config error');

        $this->assertInstanceOf(AIEngineException::class, $exception);
        $this->assertEquals('config error', $exception->getMessage());
    }

    public function testApiExceptionContainsProviderInfo(): void
    {
        $exception = new ApiException('API failed', 'Gemini', 'Rate limit exceeded');

        $this->assertInstanceOf(AIEngineException::class, $exception);
        $this->assertEquals('API failed', $exception->getMessage());
        $this->assertEquals('Gemini', $exception->getProvider());
        $this->assertEquals('Rate limit exceeded', $exception->getApiErrorMessage());
    }

    public function testApiExceptionWithNullApiError(): void
    {
        $exception = new ApiException('Connection error', 'Groq');

        $this->assertEquals('Connection error', $exception->getMessage());
        $this->assertEquals('Groq', $exception->getProvider());
        $this->assertNull($exception->getApiErrorMessage());
    }

    public function testApiExceptionWithPreviousException(): void
    {
        $previous = new \Exception('Original error');
        $exception = new ApiException('Wrapped error', 'MetaLlama', null, 500, $previous);

        $this->assertEquals(500, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testExceptionHierarchyCatchable(): void
    {
        $caught = false;

        try {
            throw new ConfigurationException('test');
        } catch (AIEngineException $e) {
            $caught = true;
        }

        $this->assertTrue($caught);
    }

    public function testApiExceptionCatchableAsAIEngineException(): void
    {
        $caught = false;

        try {
            throw new ApiException('test', 'Gemini');
        } catch (AIEngineException $e) {
            $caught = true;
        }

        $this->assertTrue($caught);
    }
}
