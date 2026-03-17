<?php

declare(strict_types=1);

namespace AIEngine\Tests\Providers;

use AIEngine\Exceptions\ConfigurationException;
use AIEngine\Knowledge\KnowledgeBase;
use AIEngine\Providers\Groq;
use AIEngine\Providers\ProviderInterface;
use PHPUnit\Framework\TestCase;

class GroqTest extends TestCase
{
    private string $dummyApiKey = 'gsk_test12345678901234567890123456789012345678';

    public function testImplementsProviderInterface(): void
    {
        $provider = new Groq($this->dummyApiKey);

        $this->assertInstanceOf(ProviderInterface::class, $provider);
    }

    public function testGetName(): void
    {
        $provider = new Groq($this->dummyApiKey);

        $this->assertEquals('Groq', $provider->getName());
    }

    public function testIsConfiguredWithValidKey(): void
    {
        $provider = new Groq($this->dummyApiKey);

        $this->assertTrue($provider->isConfigured());
    }

    public function testIsConfiguredWithEmptyKey(): void
    {
        $provider = new Groq('');

        $this->assertFalse($provider->isConfigured());
    }

    public function testDefaultModel(): void
    {
        $provider = new Groq($this->dummyApiKey);

        $this->assertEquals('llama-3.3-70b-versatile', $provider->getModel());
    }

    public function testCustomModel(): void
    {
        $provider = new Groq($this->dummyApiKey, 'mixtral-8x7b-32768');

        $this->assertEquals('mixtral-8x7b-32768', $provider->getModel());
    }

    public function testSetModel(): void
    {
        $provider = new Groq($this->dummyApiKey);
        $provider->setModel('llama-3.1-8b-instant');

        $this->assertEquals('llama-3.1-8b-instant', $provider->getModel());
    }

    public function testGetAvailableModels(): void
    {
        $models = Groq::getAvailableModels();

        $this->assertIsArray($models);
        $this->assertNotEmpty($models);
        $this->assertContains('llama-3.3-70b-versatile', $models);
        $this->assertContains('mixtral-8x7b-32768', $models);
    }

    public function testConversationHistoryStartsEmpty(): void
    {
        $provider = new Groq($this->dummyApiKey);

        $this->assertIsArray($provider->getConversationHistory());
        $this->assertEmpty($provider->getConversationHistory());
    }

    public function testStartNewConversationClearsHistory(): void
    {
        $provider = new Groq($this->dummyApiKey);
        $provider->addToHistory('user', 'Hello');
        $provider->addToHistory('assistant', 'Hi there!');

        $this->assertCount(2, $provider->getConversationHistory());

        $provider->startNewConversation();

        $this->assertEmpty($provider->getConversationHistory());
    }

    public function testAddToHistory(): void
    {
        $provider = new Groq($this->dummyApiKey);

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
        $provider = new Groq($this->dummyApiKey);

        $provider->setSystemInstruction('Be concise');

        $this->assertEquals('Be concise', $provider->getSystemInstruction());
    }

    public function testGenerateContentThrowsConfigurationExceptionWhenNotConfigured(): void
    {
        $provider = new Groq('');

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Provider not properly configured');

        $provider->generateContent('Hello');
    }

    public function testGenerateContentThrowsOnEmptyPrompt(): void
    {
        $provider = new Groq($this->dummyApiKey);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid prompt');

        $provider->generateContent('');
    }

    public function testSendMessageThrowsConfigurationExceptionWhenNotConfigured(): void
    {
        $provider = new Groq('');

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Provider not properly configured');

        $provider->sendMessage('Hello');
    }

    public function testSendMessageThrowsOnEmptyMessage(): void
    {
        $provider = new Groq($this->dummyApiKey);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid message');

        $provider->sendMessage('');
    }

    public function testKnowledgeBaseSupport(): void
    {
        $provider = new Groq($this->dummyApiKey);

        $this->assertFalse($provider->hasKnowledgeBase());

        $kb = new KnowledgeBase();
        $kb->addText('Test knowledge', 'test');

        $provider->setKnowledgeBase($kb);

        $this->assertTrue($provider->hasKnowledgeBase());
    }

    public function testSetMaxKnowledgeChars(): void
    {
        $provider = new Groq($this->dummyApiKey);

        $provider->setMaxKnowledgeChars(4000);

        // No exception means success
        $this->assertTrue(true);
    }

    public function testClearKnowledgeBase(): void
    {
        $provider = new Groq($this->dummyApiKey);

        $kb = new KnowledgeBase();
        $kb->addText('Test knowledge', 'test');
        $provider->setKnowledgeBase($kb);

        $this->assertTrue($provider->hasKnowledgeBase());

        $provider->clearKnowledgeBase();

        $this->assertFalse($provider->hasKnowledgeBase());
    }

    public function testGetKnowledgeBase(): void
    {
        $provider = new Groq($this->dummyApiKey);

        $this->assertNull($provider->getKnowledgeBase());

        $kb = new KnowledgeBase();
        $provider->setKnowledgeBase($kb);

        $this->assertSame($kb, $provider->getKnowledgeBase());
    }
}
