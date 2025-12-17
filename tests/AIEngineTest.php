<?php

namespace AIEngine\Tests;

use PHPUnit\Framework\TestCase;
use AIEngine\AIEngine;
use AIEngine\Providers\Gemini;
use AIEngine\Providers\MetaLlama;
use AIEngine\Providers\Groq;

class AIEngineTest extends TestCase
{
    private string $dummyApiKey = 'test-api-key-12345678901234567890';

    /**
     * Test AIEngine instantiation with default provider (Gemini)
     */
    public function testDefaultProviderIsGemini(): void
    {
        $engine = new AIEngine($this->dummyApiKey);
        
        $this->assertInstanceOf(AIEngine::class, $engine);
        $this->assertEquals('Gemini', $engine->getProviderName());
    }

    /**
     * Test AIEngine instantiation with Groq provider
     */
    public function testCreateWithGroqProvider(): void
    {
        $engine = new AIEngine($this->dummyApiKey, ['provider' => 'groq']);
        
        $this->assertEquals('Groq', $engine->getProviderName());
    }

    /**
     * Test AIEngine instantiation with Meta Llama provider
     */
    public function testCreateWithMetaProvider(): void
    {
        $engine = new AIEngine($this->dummyApiKey, ['provider' => 'meta']);
        
        $this->assertEquals('MetaLlama', $engine->getProviderName());
    }

    /**
     * Test static factory method
     */
    public function testStaticFactoryMethod(): void
    {
        $engine = AIEngine::create('groq', $this->dummyApiKey);
        
        $this->assertInstanceOf(AIEngine::class, $engine);
        $this->assertEquals('Groq', $engine->getProviderName());
    }

    /**
     * Test provider switching
     */
    public function testSwitchProvider(): void
    {
        $engine = new AIEngine($this->dummyApiKey, ['provider' => 'gemini']);
        $this->assertEquals('Gemini', $engine->getProviderName());
        
        $engine->switchProvider('groq', $this->dummyApiKey);
        $this->assertEquals('Groq', $engine->getProviderName());
    }

    /**
     * Test isConfigured returns true with valid API key
     */
    public function testIsConfiguredWithValidKey(): void
    {
        $engine = new AIEngine($this->dummyApiKey);
        
        $this->assertTrue($engine->isConfigured());
    }

    /**
     * Test isConfigured returns false with empty API key
     */
    public function testIsConfiguredWithEmptyKey(): void
    {
        $engine = new AIEngine('');
        
        $this->assertFalse($engine->isConfigured());
    }

    /**
     * Test getAvailableProviders returns all providers
     */
    public function testGetAvailableProviders(): void
    {
        $engine = new AIEngine($this->dummyApiKey);
        $providers = $engine->getAvailableProviders();
        
        $this->assertContains('gemini', $providers);
        $this->assertContains('meta', $providers);
        $this->assertContains('groq', $providers);
    }

    /**
     * Test getDefaultModels returns models for all providers
     */
    public function testGetDefaultModels(): void
    {
        $models = AIEngine::getDefaultModels();
        
        $this->assertArrayHasKey('gemini', $models);
        $this->assertArrayHasKey('meta', $models);
        $this->assertArrayHasKey('groq', $models);
    }

    /**
     * Test getModelsForProvider returns array
     */
    public function testGetModelsForProvider(): void
    {
        $geminiModels = AIEngine::getModelsForProvider('gemini');
        $groqModels = AIEngine::getModelsForProvider('groq');
        $metaModels = AIEngine::getModelsForProvider('meta');
        
        $this->assertIsArray($geminiModels);
        $this->assertIsArray($groqModels);
        $this->assertIsArray($metaModels);
        $this->assertNotEmpty($geminiModels);
        $this->assertNotEmpty($groqModels);
        $this->assertNotEmpty($metaModels);
    }

    /**
     * Test configuration options
     */
    public function testConfigurationOptions(): void
    {
        $config = [
            'provider' => 'gemini',
            'model' => 'gemini-2.5-pro',
            'timeout' => 120,
            'enable_logging' => true
        ];
        
        $engine = new AIEngine($this->dummyApiKey, $config);
        
        $this->assertEquals(120, $engine->getConfig('timeout'));
        $this->assertTrue($engine->getConfig('enable_logging'));
    }

    /**
     * Test setConfig and getConfig
     */
    public function testSetAndGetConfig(): void
    {
        $engine = new AIEngine($this->dummyApiKey);
        
        $engine->setConfig('custom_option', 'custom_value');
        
        $this->assertEquals('custom_value', $engine->getConfig('custom_option'));
        $this->assertNull($engine->getConfig('nonexistent'));
        $this->assertEquals('default', $engine->getConfig('nonexistent', 'default'));
    }

    /**
     * Test validatePrompt with valid prompt
     */
    public function testValidatePromptWithValidInput(): void
    {
        $engine = new AIEngine($this->dummyApiKey);
        
        $this->assertTrue($engine->validatePrompt('Hello, AI!'));
        $this->assertTrue($engine->validatePrompt('What is 2+2?'));
    }

    /**
     * Test validatePrompt with invalid prompt
     */
    public function testValidatePromptWithInvalidInput(): void
    {
        $engine = new AIEngine($this->dummyApiKey);
        
        $this->assertFalse($engine->validatePrompt(''));
        $this->assertFalse($engine->validatePrompt('   '));
        $this->assertFalse($engine->validatePrompt(null));
    }

    /**
     * Test getProvider returns ProviderInterface instance
     */
    public function testGetProviderReturnsCorrectInstance(): void
    {
        $geminiEngine = new AIEngine($this->dummyApiKey, ['provider' => 'gemini']);
        $groqEngine = AIEngine::create('groq', $this->dummyApiKey);
        $metaEngine = AIEngine::create('meta', $this->dummyApiKey);
        
        $this->assertInstanceOf(Gemini::class, $geminiEngine->getProvider());
        $this->assertInstanceOf(Groq::class, $groqEngine->getProvider());
        $this->assertInstanceOf(MetaLlama::class, $metaEngine->getProvider());
    }

    /**
     * Test conversation methods exist and work
     */
    public function testConversationMethods(): void
    {
        $engine = new AIEngine($this->dummyApiKey);
        
        // Test newConversation doesn't throw
        $engine->newConversation();
        
        // Test getHistory returns array
        $history = $engine->getHistory();
        $this->assertIsArray($history);
        $this->assertEmpty($history);
        
        // Test setSystemInstruction doesn't throw
        $engine->setSystemInstruction('You are a helpful assistant.');
    }

    /**
     * Test logging can be enabled/disabled
     */
    public function testLoggingConfiguration(): void
    {
        $engine = new AIEngine($this->dummyApiKey);
        
        $engine->enableLogging(true);
        $this->assertTrue($engine->getConfig('enable_logging'));
        
        $engine->enableLogging(false);
        $this->assertFalse($engine->getConfig('enable_logging'));
    }

    /**
     * Test custom logger can be set
     */
    public function testCustomLogger(): void
    {
        $engine = new AIEngine($this->dummyApiKey);
        $logMessages = [];
        
        $engine->setLogger(function($message, $level) use (&$logMessages) {
            $logMessages[] = ['message' => $message, 'level' => $level];
        });
        
        $engine->enableLogging(true);
        
        // The logger is set, but we can't easily test it without making API calls
        // Just verify it doesn't throw
        $this->assertIsArray($logMessages);
    }
}
