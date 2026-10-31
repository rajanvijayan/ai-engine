<?php

declare(strict_types=1);

namespace AIEngine\Tests\Providers;

use AIEngine\Exceptions\ConfigurationException;
use AIEngine\Knowledge\KnowledgeBase;
use AIEngine\Providers\Anthropic;
use AIEngine\Providers\ProviderInterface;
use PHPUnit\Framework\TestCase;

class AnthropicTest extends TestCase
{
    private string $dummyApiKey = 'test-anthropic-api-key-1234567890';

    /**
     * Expose protected helpers for testing.
     */
    private function makeTestable(string $model = Anthropic::DEFAULT_MODEL): Anthropic
    {
        return new class ($this->dummyApiKey, $model) extends Anthropic {
            public function parse(string $json): ?string
            {
                return $this->getTextFromResponse($json);
            }

            public function payload(array $messages): array
            {
                return $this->buildRequestData($messages);
            }
        };
    }

    public function testImplementsProviderInterface(): void
    {
        $this->assertInstanceOf(ProviderInterface::class, new Anthropic($this->dummyApiKey));
    }

    public function testGetName(): void
    {
        $this->assertEquals('Anthropic', (new Anthropic($this->dummyApiKey))->getName());
    }

    public function testIsConfigured(): void
    {
        $this->assertTrue((new Anthropic($this->dummyApiKey))->isConfigured());
        $this->assertFalse((new Anthropic(''))->isConfigured());
    }

    public function testDefaultModel(): void
    {
        $provider = new Anthropic($this->dummyApiKey);

        $this->assertEquals('claude-opus-5-5', $provider->getModel());
    }

    public function testSetModel(): void
    {
        $provider = new Anthropic($this->dummyApiKey, 'claude-haiku-5-5');
        $this->assertEquals('claude-haiku-5-5', $provider->getModel());

        $provider->setModel('claude-sonnet-5-5');
        $this->assertEquals('claude-sonnet-5-5', $provider->getModel());
    }

    public function testGetAvailableModels(): void
    {
        $models = Anthropic::getAvailableModels();

        $this->assertContains('claude-opus-5-5', $models);
        $this->assertContains('claude-sonnet-5-5', $models);
        $this->assertContains('claude-haiku-5-5', $models);
    }

    public function testMaxTokens(): void
    {
        $provider = new Anthropic($this->dummyApiKey);
        $this->assertEquals(16000, $provider->getMaxTokens());

        $provider->setMaxTokens(1024);
        $this->assertEquals(1024, $provider->getMaxTokens());
    }

    public function testParsesTextBlocks(): void
    {
        $provider = $this->makeTestable();
        $json = json_encode([
            'content' => [
                ['type' => 'thinking', 'thinking' => ''],
                ['type' => 'text', 'text' => 'Hello '],
                ['type' => 'text', 'text' => 'world'],
            ],
            'stop_reason' => 'end_turn',
        ]);

        $this->assertEquals('Hello world', $provider->parse((string) $json));
    }

    public function testParseReturnsNullWithoutText(): void
    {
        $provider = $this->makeTestable();

        $this->assertNull($provider->parse('{"type":"error","error":{"message":"bad"}}'));
        $this->assertNull($provider->parse('{"content":[],"stop_reason":"refusal"}'));
        $this->assertNull($provider->parse('not json'));
    }

    public function testRequestPayloadUsesTopLevelSystem(): void
    {
        $provider = $this->makeTestable();
        $provider->setSystemInstruction('Be concise');

        $payload = $provider->payload([['role' => 'user', 'content' => 'Hi']]);

        $this->assertEquals('claude-opus-5-5', $payload['model']);
        $this->assertEquals(16000, $payload['max_tokens']);
        $this->assertEquals('Be concise', $payload['system']);
        $this->assertCount(1, $payload['messages']);
        $this->assertEquals('user', $payload['messages'][0]['role']);
    }

    public function testRequestPayloadOmitsSystemWhenUnset(): void
    {
        $payload = $this->makeTestable()->payload([['role' => 'user', 'content' => 'Hi']]);

        $this->assertArrayNotHasKey('system', $payload);
    }

    public function testRequestPayloadIncludesKnowledgeBase(): void
    {
        $provider = $this->makeTestable();
        $kb = new KnowledgeBase();
        $kb->addText('The secret word is banana.', 'test');
        $provider->setKnowledgeBase($kb);

        $payload = $provider->payload([['role' => 'user', 'content' => 'Hi']]);

        $this->assertStringContainsString('banana', $payload['system']);
    }

    public function testFallbacksEnabledForSupportedModels(): void
    {
        $payload = $this->makeTestable('claude-opus-5-5')->payload([]);
        $this->assertEquals('default', $payload['fallbacks']);

        $payload = $this->makeTestable('claude-haiku-5-5')->payload([]);
        $this->assertArrayNotHasKey('fallbacks', $payload);
    }

    public function testFallbacksCanBeDisabled(): void
    {
        $provider = $this->makeTestable('claude-opus-5-5');
        $provider->enableFallbacks(false);

        $this->assertArrayNotHasKey('fallbacks', $provider->payload([]));
    }

    public function testConversationHistory(): void
    {
        $provider = new Anthropic($this->dummyApiKey);
        $provider->addToHistory('user', 'Hello');
        $provider->addToHistory('assistant', 'Hi!');

        $this->assertCount(2, $provider->getConversationHistory());

        $provider->startNewConversation();

        $this->assertEmpty($provider->getConversationHistory());
    }

    public function testGenerateContentThrowsWhenNotConfigured(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Provider not properly configured');

        (new Anthropic(''))->generateContent('Hello');
    }

    public function testSendMessageThrowsOnEmptyMessage(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid message');

        (new Anthropic($this->dummyApiKey))->sendMessage('');
    }
}
