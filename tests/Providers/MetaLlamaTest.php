<?php

namespace AIEngine\Tests\Providers;

use PHPUnit\Framework\TestCase;
use AIEngine\Providers\MetaLlama;
use AIEngine\Providers\ProviderInterface;

class MetaLlamaTest extends TestCase
{
    private string $dummyApiKey = 'meta-test-api-key-1234567890';

    /**
     * Test MetaLlama implements ProviderInterface
     */
    public function testImplementsProviderInterface(): void
    {
        $provider = new MetaLlama($this->dummyApiKey);
        
        $this->assertInstanceOf(ProviderInterface::class, $provider);
    }

    /**
     * Test getName returns correct name
     */
    public function testGetName(): void
    {
        $provider = new MetaLlama($this->dummyApiKey);
        
        $this->assertEquals('MetaLlama', $provider->getName());
    }

    /**
     * Test isConfigured with valid API key
     */
    public function testIsConfiguredWithValidKey(): void
    {
        $provider = new MetaLlama($this->dummyApiKey);
        
        $this->assertTrue($provider->isConfigured());
    }

    /**
     * Test isConfigured with empty API key
     */
    public function testIsConfiguredWithEmptyKey(): void
    {
        $provider = new MetaLlama('');
        
        $this->assertFalse($provider->isConfigured());
    }

    /**
     * Test default model
     */
    public function testDefaultModel(): void
    {
        $provider = new MetaLlama($this->dummyApiKey);
        
        $this->assertEquals('Llama-4-Maverick-17B-128E-Instruct-FP8', $provider->getModel());
    }

    /**
     * Test custom model
     */
    public function testCustomModel(): void
    {
        $provider = new MetaLlama($this->dummyApiKey, 'Llama-3.3-70B-Instruct');
        
        $this->assertEquals('Llama-3.3-70B-Instruct', $provider->getModel());
    }

    /**
     * Test setModel
     */
    public function testSetModel(): void
    {
        $provider = new MetaLlama($this->dummyApiKey);
        $provider->setModel('Llama-3.2-3B-Instruct');
        
        $this->assertEquals('Llama-3.2-3B-Instruct', $provider->getModel());
    }

    /**
     * Test getAvailableModels returns array with models
     */
    public function testGetAvailableModels(): void
    {
        $models = MetaLlama::getAvailableModels();
        
        $this->assertIsArray($models);
        $this->assertNotEmpty($models);
        $this->assertContains('Llama-4-Maverick-17B-128E-Instruct-FP8', $models);
        $this->assertContains('Llama-3.3-70B-Instruct', $models);
    }

    /**
     * Test conversation history starts empty
     */
    public function testConversationHistoryStartsEmpty(): void
    {
        $provider = new MetaLlama($this->dummyApiKey);
        
        $this->assertIsArray($provider->getConversationHistory());
        $this->assertEmpty($provider->getConversationHistory());
    }

    /**
     * Test startNewConversation clears history
     */
    public function testStartNewConversationClearsHistory(): void
    {
        $provider = new MetaLlama($this->dummyApiKey);
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

    /**
     * Test setSystemInstruction
     */
    public function testSetSystemInstruction(): void
    {
        $provider = new MetaLlama($this->dummyApiKey);
        
        $provider->setSystemInstruction('Be helpful');
        
        $this->assertEquals('Be helpful', $provider->getSystemInstruction());
    }

    /**
     * Test generateContent returns error when not configured
     */
    public function testGenerateContentReturnsErrorWhenNotConfigured(): void
    {
        $provider = new MetaLlama('');
        
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
        $provider = new MetaLlama($this->dummyApiKey);
        
        $result = $provider->generateContent('');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * Test sendMessage returns error when not configured
     */
    public function testSendMessageReturnsErrorWhenNotConfigured(): void
    {
        $provider = new MetaLlama('');
        
        $result = $provider->sendMessage('Hello');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }
}

