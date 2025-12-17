<?php

namespace AIEngine\Tests\Providers;

use PHPUnit\Framework\TestCase;
use AIEngine\Providers\Gemini;
use AIEngine\Providers\ProviderInterface;

class GeminiTest extends TestCase
{
    private string $dummyApiKey = 'test-api-key-12345678901234567890';

    /**
     * Test Gemini implements ProviderInterface
     */
    public function testImplementsProviderInterface(): void
    {
        $provider = new Gemini($this->dummyApiKey);
        
        $this->assertInstanceOf(ProviderInterface::class, $provider);
    }

    /**
     * Test getName returns correct name
     */
    public function testGetName(): void
    {
        $provider = new Gemini($this->dummyApiKey);
        
        $this->assertEquals('Gemini', $provider->getName());
    }

    /**
     * Test isConfigured with valid API key
     */
    public function testIsConfiguredWithValidKey(): void
    {
        $provider = new Gemini($this->dummyApiKey);
        
        $this->assertTrue($provider->isConfigured());
    }

    /**
     * Test isConfigured with empty API key
     */
    public function testIsConfiguredWithEmptyKey(): void
    {
        $provider = new Gemini('');
        
        $this->assertFalse($provider->isConfigured());
    }

    /**
     * Test default model
     */
    public function testDefaultModel(): void
    {
        $provider = new Gemini($this->dummyApiKey);
        
        $this->assertEquals('gemini-2.0-flash', $provider->getModel());
    }

    /**
     * Test custom model
     */
    public function testCustomModel(): void
    {
        $provider = new Gemini($this->dummyApiKey, 'gemini-2.5-pro');
        
        $this->assertEquals('gemini-2.5-pro', $provider->getModel());
    }

    /**
     * Test setModel
     */
    public function testSetModel(): void
    {
        $provider = new Gemini($this->dummyApiKey);
        $provider->setModel('gemini-2.5-flash');
        
        $this->assertEquals('gemini-2.5-flash', $provider->getModel());
    }

    /**
     * Test setTimeout
     */
    public function testSetTimeout(): void
    {
        $provider = new Gemini($this->dummyApiKey);
        $provider->setTimeout(120);
        
        // No direct getter, but should not throw
        $this->assertTrue(true);
    }

    /**
     * Test conversation history starts empty
     */
    public function testConversationHistoryStartsEmpty(): void
    {
        $provider = new Gemini($this->dummyApiKey);
        
        $this->assertIsArray($provider->getConversationHistory());
        $this->assertEmpty($provider->getConversationHistory());
    }

    /**
     * Test startNewConversation clears history
     */
    public function testStartNewConversationClearsHistory(): void
    {
        $provider = new Gemini($this->dummyApiKey);
        $provider->addToHistory('user', 'Hello');
        $provider->addToHistory('model', 'Hi there!');
        
        $this->assertCount(2, $provider->getConversationHistory());
        
        $provider->startNewConversation();
        
        $this->assertEmpty($provider->getConversationHistory());
    }

    /**
     * Test addToHistory
     */
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

    /**
     * Test setSystemInstruction
     */
    public function testSetSystemInstruction(): void
    {
        $provider = new Gemini($this->dummyApiKey);
        
        $provider->setSystemInstruction('Be helpful');
        
        $this->assertEquals('Be helpful', $provider->getSystemInstruction());
    }

    /**
     * Test getSystemInstruction returns null when not set
     */
    public function testGetSystemInstructionReturnsNullWhenNotSet(): void
    {
        $provider = new Gemini($this->dummyApiKey);
        
        $this->assertNull($provider->getSystemInstruction());
    }

    /**
     * Test generateContent returns error when not configured
     */
    public function testGenerateContentReturnsErrorWhenNotConfigured(): void
    {
        $provider = new Gemini('');
        
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
        $provider = new Gemini($this->dummyApiKey);
        
        $result = $provider->generateContent('');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * Test sendMessage returns error when not configured
     */
    public function testSendMessageReturnsErrorWhenNotConfigured(): void
    {
        $provider = new Gemini('');
        
        $result = $provider->sendMessage('Hello');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * Test sendMessage returns error with empty message
     */
    public function testSendMessageReturnsErrorWithEmptyMessage(): void
    {
        $provider = new Gemini($this->dummyApiKey);
        
        $result = $provider->sendMessage('');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }
}

