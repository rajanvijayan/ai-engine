<?php

declare(strict_types=1);

namespace AIEngine\Tests\Providers;

use PHPUnit\Framework\TestCase;
use AIEngine\Providers\Gemini;
use AIEngine\Providers\ProviderInterface;
use AIEngine\Response;
use AIEngine\Knowledge\KnowledgeBase;
use AIEngine\Exceptions\ConfigurationException;
use AIEngine\Exceptions\ApiException;

class GeminiTest extends TestCase
{
    private string $dummyApiKey = 'test-api-key-12345678901234567890';

    public function testImplementsProviderInterface(): void
    {
        $provider = new Gemini($this->dummyApiKey);

        $this->assertInstanceOf(ProviderInterface::class, $provider);
    }

    public function testGetName(): void
    {
        $provider = new Gemini($this->dummyApiKey);

        $this->assertEquals('Gemini', $provider->getName());
    }

    public function testIsConfiguredWithValidKey(): void
    {
        $provider = new Gemini($this->dummyApiKey);

        $this->assertTrue($provider->isConfigured());
    }

    public function testIsConfiguredWithEmptyKey(): void
    {
        $provider = new Gemini('');

        $this->assertFalse($provider->isConfigured());
    }

    public function testDefaultModel(): void
    {
        $provider = new Gemini($this->dummyApiKey);

        $this->assertEquals('gemini-2.0-flash', $provider->getModel());
    }

    public function testCustomModel(): void
    {
        $provider = new Gemini($this->dummyApiKey, 'gemini-2.5-pro');

        $this->assertEquals('gemini-2.5-pro', $provider->getModel());
    }

    public function testSetModel(): void
    {
        $provider = new Gemini($this->dummyApiKey);
        $provider->setModel('gemini-2.5-flash');

        $this->assertEquals('gemini-2.5-flash', $provider->getModel());
    }

    public function testSetTimeout(): void
    {
        $provider = new Gemini($this->dummyApiKey);
        $provider->setTimeout(120);

        $this->assertTrue(true);
    }

    public function testConversationHistoryStartsEmpty(): void
    {
        $provider = new Gemini($this->dummyApiKey);

        $this->assertIsArray($provider->getConversationHistory());
        $this->assertEmpty($provider->getConversationHistory());
    }

    public function testStartNewConversationClearsHistory(): void
    {
        $provider = new Gemini($this->dummyApiKey);
        $provider->addToHistory('user', 'Hello');
        $provider->addToHistory('model', 'Hi there!');

        $this->assertCount(2, $provider->getConversationHistory());

        $provider->startNewConversation();

        $this->assertEmpty($provider->getConversationHistory());
    }

    public function testAddToHistory(): void
    {
        $provider = new Gemini($this->dummyApiKey);

        $provider->addToHistory('user', 'Hello');
        $provider->addToHistory('model', 'Hi there!');

        $history = $provider->getConversationHistory();

        $this->assertCount(2, $history);
        $this->assertEquals('user', $history[0]['role']);
        $this->assertEquals('Hello', $history[0]['parts'][0]['text']);
        $this->assertEquals('model', $history[1]['role']);
    }

    public function testSetSystemInstruction(): void
    {
        $provider = new Gemini($this->dummyApiKey);

        $provider->setSystemInstruction('Be helpful');

        $this->assertEquals('Be helpful', $provider->getSystemInstruction());
    }

    public function testGetSystemInstructionReturnsNullWhenNotSet(): void
    {
        $provider = new Gemini($this->dummyApiKey);

        $this->assertNull($provider->getSystemInstruction());
    }

    public function testGenerateContentThrowsConfigurationExceptionWhenNotConfigured(): void
    {
        $provider = new Gemini('');

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Provider not properly configured');

        $provider->generateContent('Hello');
    }

    public function testGenerateContentThrowsOnEmptyPrompt(): void
    {
        $provider = new Gemini($this->dummyApiKey);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid prompt');

        $provider->generateContent('');
    }

    public function testSendMessageThrowsConfigurationExceptionWhenNotConfigured(): void
    {
        $provider = new Gemini('');

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Provider not properly configured');

        $provider->sendMessage('Hello');
    }

    public function testSendMessageThrowsOnEmptyMessage(): void
    {
        $provider = new Gemini($this->dummyApiKey);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid message');

        $provider->sendMessage('');
    }

    public function testGetTextFromPartsWithValidResponse(): void
    {
        $provider = new Gemini($this->dummyApiKey);

        $json = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Hello from Gemini!']
                        ]
                    ]
                ]
            ]
        ]);

        $this->assertEquals('Hello from Gemini!', $provider->getTextFromParts($json));
    }

    public function testGetTextFromPartsWithInvalidResponse(): void
    {
        $provider = new Gemini($this->dummyApiKey);

        $this->assertNull($provider->getTextFromParts('{}'));
        $this->assertNull($provider->getTextFromParts('{"candidates": []}'));
    }

    public function testGetAvailableModels(): void
    {
        $models = Gemini::getAvailableModels();

        $this->assertIsArray($models);
        $this->assertNotEmpty($models);
        $this->assertContains('gemini-2.0-flash', $models);
        $this->assertContains('gemini-2.5-pro', $models);
    }

    public function testKnowledgeBaseSupport(): void
    {
        $provider = new Gemini($this->dummyApiKey);

        $this->assertFalse($provider->hasKnowledgeBase());

        $kb = new KnowledgeBase();
        $kb->addText('Test knowledge', 'test');

        $provider->setKnowledgeBase($kb);

        $this->assertTrue($provider->hasKnowledgeBase());
    }

    public function testClearKnowledgeBase(): void
    {
        $provider = new Gemini($this->dummyApiKey);

        $kb = new KnowledgeBase();
        $kb->addText('Test knowledge', 'test');
        $provider->setKnowledgeBase($kb);

        $this->assertTrue($provider->hasKnowledgeBase());

        $provider->setKnowledgeBase(null);

        $this->assertFalse($provider->hasKnowledgeBase());
    }
}
