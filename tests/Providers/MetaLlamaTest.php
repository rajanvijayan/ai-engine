<?php

declare(strict_types=1);

namespace AIEngine\Tests\Providers;

use AIEngine\Exceptions\ConfigurationException;
use AIEngine\Knowledge\KnowledgeBase;
use AIEngine\Providers\MetaLlama;
use AIEngine\Providers\ProviderInterface;
use PHPUnit\Framework\TestCase;

class MetaLlamaTest extends TestCase
{
    private string $dummyApiKey = 'meta-test-api-key-1234567890';

    public function testImplementsProviderInterface(): void
    {
        $provider = new MetaLlama($this->dummyApiKey);

        $this->assertInstanceOf(ProviderInterface::class, $provider);
    }

    public function testGetName(): void
    {
        $provider = new MetaLlama($this->dummyApiKey);

        $this->assertEquals('MetaLlama', $provider->getName());
    }

    public function testIsConfiguredWithValidKey(): void
    {
        $provider = new MetaLlama($this->dummyApiKey);

        $this->assertTrue($provider->isConfigured());
    }

    public function testIsConfiguredWithEmptyKey(): void
    {
        $provider = new MetaLlama('');

        $this->assertFalse($provider->isConfigured());
    }

    public function testDefaultModel(): void
    {
        $provider = new MetaLlama($this->dummyApiKey);

        $this->assertEquals('Llama-4-Maverick-17B-128E-Instruct-FP8', $provider->getModel());
    }

    public function testCustomModel(): void
    {
        $provider = new MetaLlama($this->dummyApiKey, 'Llama-3.3-70B-Instruct');

        $this->assertEquals('Llama-3.3-70B-Instruct', $provider->getModel());
    }

    public function testSetModel(): void
    {
        $provider = new MetaLlama($this->dummyApiKey);
        $provider->setModel('Llama-3.2-3B-Instruct');

        $this->assertEquals('Llama-3.2-3B-Instruct', $provider->getModel());
    }

    public function testGetAvailableModels(): void
    {
        $models = MetaLlama::getAvailableModels();

        $this->assertIsArray($models);
        $this->assertNotEmpty($models);
        $this->assertContains('Llama-4-Maverick-17B-128E-Instruct-FP8', $models);
        $this->assertContains('Llama-3.3-70B-Instruct', $models);
    }

    public function testConversationHistoryStartsEmpty(): void
    {
        $provider = new MetaLlama($this->dummyApiKey);

        $this->assertIsArray($provider->getConversationHistory());
        $this->assertEmpty($provider->getConversationHistory());
    }

    public function testStartNewConversationClearsHistory(): void
    {
        $provider = new MetaLlama($this->dummyApiKey);
        $provider->addToHistory('user', 'Hello');
        $provider->addToHistory('assistant', 'Hi there!');

        $this->assertCount(2, $provider->getConversationHistory());

        $provider->startNewConversation();

        $this->assertEmpty($provider->getConversationHistory());
    }

    public function testAddToHistory(): void
    {
        $provider = new MetaLlama($this->dummyApiKey);

        $provider->addToHistory('user', 'Hello');
        $provider->addToHistory('assistant', 'Hi there!');

        $history = $provider->getConversationHistory();

        $this->assertCount(2, $history);
        $this->assertEquals('user', $history[0]['role']);
        $this->assertEquals('Hello', $history[0]['content']);
        $this->assertEquals('assistant', $history[1]['role']);
        $this->assertEquals('Hi there!', $history[1]['content']);
    }

    public function testSetSystemInstruction(): void
    {
        $provider = new MetaLlama($this->dummyApiKey);

        $provider->setSystemInstruction('Be helpful');

        $this->assertEquals('Be helpful', $provider->getSystemInstruction());
    }

    public function testGenerateContentThrowsConfigurationExceptionWhenNotConfigured(): void
    {
        $provider = new MetaLlama('');

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Provider not properly configured');

        $provider->generateContent('Hello');
    }

    public function testGenerateContentThrowsOnEmptyPrompt(): void
    {
        $provider = new MetaLlama($this->dummyApiKey);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid prompt');

        $provider->generateContent('');
    }

    public function testSendMessageThrowsConfigurationExceptionWhenNotConfigured(): void
    {
        $provider = new MetaLlama('');

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Provider not properly configured');

        $provider->sendMessage('Hello');
    }

    public function testSendMessageThrowsOnEmptyMessage(): void
    {
        $provider = new MetaLlama($this->dummyApiKey);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid message');

        $provider->sendMessage('');
    }

    public function testKnowledgeBaseSupport(): void
    {
        $provider = new MetaLlama($this->dummyApiKey);

        $this->assertFalse($provider->hasKnowledgeBase());

        $kb = new KnowledgeBase();
        $kb->addText('Test knowledge', 'test');

        $provider->setKnowledgeBase($kb);

        $this->assertTrue($provider->hasKnowledgeBase());
    }

    public function testClearKnowledgeBase(): void
    {
        $provider = new MetaLlama($this->dummyApiKey);

        $kb = new KnowledgeBase();
        $kb->addText('Test knowledge', 'test');
        $provider->setKnowledgeBase($kb);

        $this->assertTrue($provider->hasKnowledgeBase());

        $provider->setKnowledgeBase(null);

        $this->assertFalse($provider->hasKnowledgeBase());
    }
}
