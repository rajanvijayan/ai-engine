<?php

namespace AIEngine\Tests\Providers;

use PHPUnit\Framework\TestCase;
use AIEngine\Providers\Groq;
use AIEngine\Providers\ProviderInterface;

class GroqTest extends TestCase
{
    private string $dummyApiKey = 'gsk_test12345678901234567890123456789012345678';

    /**
     * Test Groq implements ProviderInterface
     */
    public function testImplementsProviderInterface(): void
    {
        $provider = new Groq($this->dummyApiKey);
        
        $this->assertInstanceOf(ProviderInterface::class, $provider);
    }

    /**
     * Test getName returns correct name
     */
    public function testGetName(): void
    {
        $provider = new Groq($this->dummyApiKey);
        
        $this->assertEquals('Groq', $provider->getName());
    }

    /**
     * Test isConfigured with valid API key
     */
    public function testIsConfiguredWithValidKey(): void
    {
        $provider = new Groq($this->dummyApiKey);
        
        $this->assertTrue($provider->isConfigured());
    }

    /**
     * Test isConfigured with empty API key
     */
    public function testIsConfiguredWithEmptyKey(): void
    {
        $provider = new Groq('');
        
        $this->assertFalse($provider->isConfigured());
    }

    /**
     * Test default model
     */
    public function testDefaultModel(): void
    {
        $provider = new Groq($this->dummyApiKey);
        
        $this->assertEquals('llama-3.3-70b-versatile', $provider->getModel());
    }

    /**
     * Test custom model
     */
    public function testCustomModel(): void
    {
        $provider = new Groq($this->dummyApiKey, 'mixtral-8x7b-32768');
        
        $this->assertEquals('mixtral-8x7b-32768', $provider->getModel());
    }

    /**
     * Test setModel
     */
    public function testSetModel(): void
    {
        $provider = new Groq($this->dummyApiKey);
        $provider->setModel('llama-3.1-8b-instant');
        
        $this->assertEquals('llama-3.1-8b-instant', $provider->getModel());
    }

    /**
     * Test getAvailableModels returns array with models
     */
    public function testGetAvailableModels(): void
    {
        $models = Groq::getAvailableModels();
        
        $this->assertIsArray($models);
        $this->assertNotEmpty($models);
        $this->assertContains('llama-3.3-70b-versatile', $models);
        $this->assertContains('mixtral-8x7b-32768', $models);
    }

    /**
     * Test conversation history starts empty
     */
    public function testConversationHistoryStartsEmpty(): void
    {
        $provider = new Groq($this->dummyApiKey);
        
        $this->assertIsArray($provider->getConversationHistory());
        $this->assertEmpty($provider->getConversationHistory());
    }

    /**
     * Test startNewConversation clears history
     */
    public function testStartNewConversationClearsHistory(): void
    {
        $provider = new Groq($this->dummyApiKey);
        $provider->addToHistory('user', 'Hello');
        $provider->addToHistory('assistant', 'Hi there!');
        
        $this->assertCount(2, $provider->getConversationHistory());
        
        $provider->startNewConversation();
        
        $this->assertEmpty($provider->getConversationHistory());
    }

    /**
     * Test addToHistory with OpenAI-compatible format
     */
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

    /**
     * Test setSystemInstruction
     */
    public function testSetSystemInstruction(): void
    {
        $provider = new Groq($this->dummyApiKey);
        
        $provider->setSystemInstruction('Be concise');
        
        $this->assertEquals('Be concise', $provider->getSystemInstruction());
    }

    /**
     * Test generateContent returns error when not configured
     */
    public function testGenerateContentReturnsErrorWhenNotConfigured(): void
    {
        $provider = new Groq('');
        
        $result = $provider->generateContent('Hello');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Provider not properly configured', $result['error']);
    }

    /**
     * Test generateContent returns error with empty prompt
     */
    public function testGenerateContentReturnsErrorWithEmptyPrompt(): void
    {
        $provider = new Groq($this->dummyApiKey);
        
        $result = $provider->generateContent('');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * Test sendMessage returns error when not configured
     */
    public function testSendMessageReturnsErrorWhenNotConfigured(): void
    {
        $provider = new Groq('');
        
        $result = $provider->sendMessage('Hello');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }
}

